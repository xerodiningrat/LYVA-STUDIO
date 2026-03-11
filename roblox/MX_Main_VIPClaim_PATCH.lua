local VIPClaimModule = require(ModFolder:WaitForChild("MX_VIPTitleClaim"))

CONFIG.VIP_TITLE_BACKEND_URL = "https://YOUR-DOMAIN.com"
CONFIG.VIP_TITLE_API_KEY = "CHANGE_THIS_RANDOM_SECRET"
CONFIG.VIP_TITLE_SLOT = 10

local VIPClaim = VIPClaimModule.Init({
	Data = Data,
	safeRefreshTitle = safeRefreshTitle,
	applyCustomTitlesFromData = applyCustomTitlesFromData,
	notifyPlayer = notifyPlayer,
	VIPGamepassId = CONFIG.VIP_GAMEPASS_ID,
	BackendUrl = CONFIG.VIP_TITLE_BACKEND_URL,
	ApiKey = CONFIG.VIP_TITLE_API_KEY,
	ClaimSlot = CONFIG.VIP_TITLE_SLOT,
	PollInterval = 30,
	AllowNonVipInStudio = true,
})

task.defer(function()
	VIPClaim.CheckPlayer(player)
end)
