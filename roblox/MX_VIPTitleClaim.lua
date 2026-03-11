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
	local claimSlot = config.ClaimSlot or 10
	local vipGamepassId = config.VIPGamepassId or 0
	local pollInterval = config.PollInterval or 30
	local allowNonVipInStudio = config.AllowNonVipInStudio == true

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

	local function ownsVip(player)
		if vipGamepassId == 0 then
			return true
		end
		if allowNonVipInStudio and RunService:IsStudio() then
			return true
		end
		local ok, result = pcall(function()
			return MarketplaceService:UserOwnsGamePassAsync(player.UserId, vipGamepassId)
		end)
		return ok and result == true
	end

	local function applyClaim(player, claim)
		if not ownsVip(player) then
			return false
		end

		local profile = data:Load(player.UserId)
		profile.customTitles = profile.customTitles or {}
		profile.customTitleMeta = profile.customTitleMeta or {}
		profile.customTitles[claimSlot] = tostring(claim.title or "")
		profile.customTitleMeta[claimSlot] = {
			mode = "SOLID",
			preset = "VIP",
			color = { r = 255, g = 255, b = 255 },
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
		local payload = requestJson("/roblox/claim/pull", {
			userId = player.UserId,
			username = player.Name,
		})
		if payload and payload.claim then
			applyClaim(player, payload.claim)
			requestJson("/roblox/claim/consume", {
				claimId = payload.claim.claimId,
				status = "applied",
				reason = "Applied in game",
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
