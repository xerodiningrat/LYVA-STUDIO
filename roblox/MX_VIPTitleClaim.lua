local HttpService = game:GetService("HttpService")
local MarketplaceService = game:GetService("MarketplaceService")
local Players = game:GetService("Players")
local RunService = game:GetService("RunService")

local Module = {}

function Module.Init(config)
	local data = config.Data
	local safeRefreshTitle = config.safeRefreshTitle
	local applyCustomTitlesFromData = config.applyCustomTitlesFromData
	local notifyPlayer = config.notifyPlayer
	local backendUrl = config.BackendUrl
	local apiKey = config.ApiKey
	local mapKey = config.MapKey or ""
	local allowedPlaceIds = config.AllowedPlaceIds or {}
	local claimSlot = config.ClaimSlot or 10
	local vipGamepassId = config.VIPGamepassId or 0
	local pollInterval = config.PollInterval or 30
	local allowNonVipInStudio = config.AllowNonVipInStudio == true
	local rgbRefreshCooldown = {}

	local function loadProfileCompat(...)
		local profile, ok = ...

		if typeof(profile) == "table" then
			if ok == false then
				return profile, false
			end
			return profile, true
		end

		if typeof(ok) == "table" then
			return ok, true
		end

		return nil, false
	end

	local function requestJson(path, body)
		local normalizedBackendUrl = tostring(backendUrl or "")
			:gsub("%s+", "")
			:gsub("/+$", "")
			:gsub("/api$", "")

		local ok, response = pcall(function()
			return HttpService:RequestAsync({
				Url = normalizedBackendUrl .. path,
				Method = "POST",
				Headers = {
					["Content-Type"] = "application/json",
					["x-api-key"] = apiKey,
				},
				Body = HttpService:JSONEncode(body or {}),
			})
		end)
		if not ok or not response then
			return nil
		end
		if not response.Success then
			return nil
		end
		local ok, decoded = pcall(function()
			return HttpService:JSONDecode(response.Body)
		end)
		return ok and decoded or nil
	end

	local function isPlaceAllowed()
		if typeof(allowedPlaceIds) ~= "table" or next(allowedPlaceIds) == nil then
			return true
		end
		return allowedPlaceIds[game.PlaceId] == true
	end

	local function ownsVip(player, requiredGamepassId)
		local activeGamepassId = tonumber(requiredGamepassId) or vipGamepassId
		if activeGamepassId == 0 then
			return true
		end
		if allowNonVipInStudio and RunService:IsStudio() then
			return true
		end
		local ok, result = pcall(function()
			return MarketplaceService:UserOwnsGamePassAsync(player.UserId, activeGamepassId)
		end)
		return ok and result == true
	end

	local function resolveClaimSlot(claim)
		local slot = tonumber(claim and claim.titleSlot) or tonumber(claimSlot) or 10
		return math.clamp(slot, 1, 10)
	end

	local function applyRgbMetaToPlayer(player)
		local metaFolder = player:FindFirstChild("CustomTitleMeta")
		if not metaFolder then
			return false
		end

		local hasRgb = false
		for slotIndex = 1, 10 do
			local slotFolder = metaFolder:FindFirstChild(string.format("Slot%02d", slotIndex))
			if slotFolder and slotFolder:IsA("Folder") then
				local modeValue = slotFolder:FindFirstChild("Mode")
				local colorValue = slotFolder:FindFirstChild("Color")
				if modeValue and modeValue:IsA("StringValue") and string.upper(tostring(modeValue.Value)) == "RGB" and colorValue and colorValue:IsA("Color3Value") then
					hasRgb = true
					local hue = (os.clock() * 0.18 + (slotIndex * 0.07)) % 1
					colorValue.Value = Color3.fromHSV(hue, 1, 1)
				end
			end
		end

		if hasRgb and safeRefreshTitle then
			local nowClock = os.clock()
			local previousRefresh = rgbRefreshCooldown[player.UserId] or 0
			if (nowClock - previousRefresh) >= 0.75 then
				rgbRefreshCooldown[player.UserId] = nowClock
				safeRefreshTitle(player)
			end
		end

		return hasRgb
	end

	local function hydratePlayerTitle(player)
		if not player or not player:IsDescendantOf(Players) then
			return
		end

		applyCustomTitlesFromData(player)
		applyRgbMetaToPlayer(player)
		safeRefreshTitle(player)
	end

	local function scheduleTitleHydration(player)
		for _, delaySeconds in ipairs({ 0.1, 0.5, 1.5, 3 }) do
			task.delay(delaySeconds, function()
				hydratePlayerTitle(player)
			end)
		end
	end

	local function scheduleCharacterTitleRefresh(player, character)
		for _, delaySeconds in ipairs({ 0, 0.2, 0.6, 1.25, 2.5, 4 }) do
			task.delay(delaySeconds, function()
				if not player or not player:IsDescendantOf(Players) then
					return
				end

				if player.Character ~= character then
					return
				end

				if not character.Parent then
					return
				end

				if not character:FindFirstChild("Head") or not character:FindFirstChild("HumanoidRootPart") then
					return
				end

				hydratePlayerTitle(player)
			end)
		end
	end

	local function scheduleRespawnTitleRecovery(player, character)
		for _, delaySeconds in ipairs({ 0.4, 1, 2, 3 }) do
			task.delay(delaySeconds, function()
				if not player or not player:IsDescendantOf(Players) then
					return
				end

				if player.Character ~= character or not character.Parent then
					return
				end

				local head = character:FindFirstChild("Head")
				local hrp = character:FindFirstChild("HumanoidRootPart")
				local humanoid = character:FindFirstChildOfClass("Humanoid")
				if not head or not hrp or not humanoid or humanoid.Health <= 0 then
					return
				end

				hydratePlayerTitle(player)
			end)
		end
	end

	local function applyClaim(player, claim)
		if not ownsVip(player, claim and claim.gamepassId) then
			return false
		end

		local profile, ok = loadProfileCompat(data:Load(player.UserId))
		if not ok or not profile then
			return false
		end
		local targetSlot = resolveClaimSlot(claim)
		profile.customTitles = profile.customTitles or {}
		profile.customTitleMeta = profile.customTitleMeta or {}
		profile.customTitles[targetSlot] = tostring(claim.title or "")
		local titleMeta = (claim and typeof(claim.titleMeta) == "table") and claim.titleMeta or nil
		local titleMetaColor = (titleMeta and typeof(titleMeta.color) == "table") and titleMeta.color or {}
		local titleMode = titleMeta and tostring(titleMeta.mode or "SOLID"):upper() or "SOLID"
		if titleMode ~= "RGB" then
			titleMode = "SOLID"
		end
		local titlePreset = titleMeta and tostring(titleMeta.preset or "VIP") or "VIP"
		if titlePreset == "" then
			titlePreset = "VIP"
		end
		profile.customTitleMeta[targetSlot] = {
			mode = titleMode,
			preset = titlePreset,
			color = {
				r = math.clamp(tonumber(titleMetaColor.r) or 255, 0, 255),
				g = math.clamp(tonumber(titleMetaColor.g) or 255, 0, 255),
				b = math.clamp(tonumber(titleMetaColor.b) or 255, 0, 255),
			},
		}

		data:Save(player.UserId, {
			customTitles = profile.customTitles,
			customTitleMeta = profile.customTitleMeta,
		})

		applyCustomTitlesFromData(player)
		applyRgbMetaToPlayer(player)
		safeRefreshTitle(player)
		if notifyPlayer then
			notifyPlayer(player, ("VIP TITLE BERHASIL SLOT %d: %s"):format(targetSlot, profile.customTitles[targetSlot]))
		end
		return true
	end

	local function checkPlayer(player)
		if mapKey == "" or apiKey == "" or backendUrl == "" or not isPlaceAllowed() then
			return
		end

		local payload = requestJson("/api/roblox/vip-title-claims/pull", {
			userId = player.UserId,
			username = player.Name,
			mapKey = mapKey,
			placeId = tostring(game.PlaceId),
			universeId = tostring(game.GameId),
		})
		if payload and payload.claim then
			local applied = applyClaim(player, payload.claim)
			requestJson("/api/roblox/vip-title-claims/consume", {
				claimId = payload.claim.claimId,
				status = applied and "applied" or "rejected",
				reason = applied and "Applied in game" or "VIP ownership / data load failed",
				mapKey = mapKey,
				placeId = tostring(game.PlaceId),
				universeId = tostring(game.GameId),
			})
		end
	end

	Players.PlayerAdded:Connect(function(player)
		scheduleTitleHydration(player)
		if player.Character then
			scheduleCharacterTitleRefresh(player, player.Character)
			scheduleRespawnTitleRecovery(player, player.Character)
		end

		player.CharacterAdded:Connect(function(char)
			char:WaitForChild("Head")
			char:WaitForChild("HumanoidRootPart")
			scheduleTitleHydration(player)
			scheduleCharacterTitleRefresh(player, char)
			scheduleRespawnTitleRecovery(player, char)
		end)

		player.CharacterAppearanceLoaded:Connect(function(char)
			scheduleTitleHydration(player)
			scheduleCharacterTitleRefresh(player, char)
			scheduleRespawnTitleRecovery(player, char)
		end)

		task.delay(5, function()
			checkPlayer(player)
		end)
	end)

	task.spawn(function()
		while true do
			task.wait(pollInterval)
			for _, player in ipairs(Players:GetPlayers()) do
				task.spawn(function()
					checkPlayer(player)
				end)
			end
		end
	end)

	task.spawn(function()
		while true do
			task.wait(0.2)
			for _, player in ipairs(Players:GetPlayers()) do
				task.spawn(function()
					applyRgbMetaToPlayer(player)
				end)
			end
		end
	end)

	return {
		CheckPlayer = checkPlayer,
	}
end

return Module
