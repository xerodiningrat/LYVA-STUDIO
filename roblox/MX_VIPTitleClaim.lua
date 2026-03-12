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
		local response = HttpService:RequestAsync({
			Url = backendUrl .. path,
			Method = "POST",
			Headers = {
				["Content-Type"] = "application/json",
				["x-api-key"] = apiKey,
			},
			Body = HttpService:JSONEncode(body or {}),
		})
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

	local function applyClaim(player, claim)
		if not ownsVip(player, claim and claim.gamepassId) then
			return false
		end

		local profile, ok = loadProfileCompat(data:Load(player.UserId))
		if not ok or not profile then
			return false
		end
		profile.customTitles = profile.customTitles or {}
		profile.customTitleMeta = profile.customTitleMeta or {}
		profile.customTitles[claimSlot] = tostring(claim.title or "")
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
		profile.customTitleMeta[claimSlot] = {
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
		safeRefreshTitle(player)
		if notifyPlayer then
			notifyPlayer(player, ("VIP TITLE BERHASIL: %s"):format(profile.customTitles[claimSlot]))
		end
		return true
	end

	local function checkPlayer(player)
		if mapKey == "" or not isPlaceAllowed() then
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
			applyClaim(player, payload.claim)
			requestJson("/api/roblox/vip-title-claims/consume", {
				claimId = payload.claim.claimId,
				status = "applied",
				reason = "Applied in game",
				mapKey = mapKey,
				placeId = tostring(game.PlaceId),
				universeId = tostring(game.GameId),
			})
		end
	end

	Players.PlayerAdded:Connect(function(player)
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

	return {
		CheckPlayer = checkPlayer,
	}
end

return Module
