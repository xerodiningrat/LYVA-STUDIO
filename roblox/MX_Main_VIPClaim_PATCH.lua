local VIPClaimModule = require(ModFolder:WaitForChild("MX_VIPTitleClaim"))

CONFIG.VIP_TITLE_BACKEND_URL = "https://YOUR-DOMAIN.com"
CONFIG.VIP_TITLE_API_KEY = "CHANGE_THIS_RANDOM_SECRET"
CONFIG.VIP_TITLE_MAP_KEY = "mountxyra"
CONFIG.VIP_TITLE_SLOT = 10
CONFIG.VIP_TITLE_POLL_INTERVAL = 30
CONFIG.VIP_TITLE_ALLOW_NONVIP_IN_STUDIO = true
CONFIG.VIP_TITLE_ALLOWED_PLACE_IDS = {
	-- [1234567890] = true,
}

local VIPClaim = VIPClaimModule.Init({
	Data = Data,
	safeRefreshTitle = safeRefreshTitle,
	applyCustomTitlesFromData = applyCustomTitlesFromData,
	notifyPlayer = notifyPlayer,
	MapKey = CONFIG.VIP_TITLE_MAP_KEY,
	AllowedPlaceIds = CONFIG.VIP_TITLE_ALLOWED_PLACE_IDS,
	VIPGamepassId = CONFIG.VIP_GAMEPASS_ID,
	BackendUrl = CONFIG.VIP_TITLE_BACKEND_URL,
	ApiKey = CONFIG.VIP_TITLE_API_KEY,
	ClaimSlot = CONFIG.VIP_TITLE_SLOT,
	PollInterval = CONFIG.VIP_TITLE_POLL_INTERVAL,
	AllowNonVipInStudio = CONFIG.VIP_TITLE_ALLOW_NONVIP_IN_STUDIO,
})

task.defer(function()
	VIPClaim.CheckPlayer(player)
end)
