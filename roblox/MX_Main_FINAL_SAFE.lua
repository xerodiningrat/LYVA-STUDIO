--====================================================
-- MX_Main FINAL (SAFE) - ANTI REJOIN HILANG
-- - Instant CP/Summit/Popup
-- - Title auto update instant (CP/Summit/Admin changes)
-- - NO OrderedDataStore traffic
-- - Data Save THROTTLED (batch) + flush on leave + BindToClose
-- - Prevent "save default 0" when Load fails
--
-- REQUEST FIX:
--   - Leaderboard tetap ada kolom "Checkpoint" + "Summits"
--   - Saat sentuh SummitPart: kolom "Checkpoint" JANGAN 0, tapi tampil NILAI Summits (angka)
--   - Sistem anti-skip checkpoint TETAP aman (pakai internal RunCheckpoint)
--   - Rejoin/mati setelah finish spawn di SummitPart (SpawnAtSummit)
--====================================================

local Players = game:GetService("Players")
local ReplicatedStorage = game:GetService("ReplicatedStorage")
local MarketplaceService = game:GetService("MarketplaceService")
local RunService = game:GetService("RunService")
local ServerStorage = game:GetService("ServerStorage")
local ServerScriptService = game:GetService("ServerScriptService")
local HttpService = game:GetService("HttpService")

--========================
-- CONFIG
--========================
local CONFIG = {
	MAP_NAME = "MountXyra",

	CHECKPOINT_PREFIX = "CheckpointPart",
	SUMMIT_PART_NAME  = "SummitPart",

	CP_TOUCH_COOLDOWN = 0.20,
	FINISH_COOLDOWN   = 1.20,

	BIND_ATTR = "MX_Bound",

	NOTIF_DELAY = 0,
	HRP_ONLY_TOUCH = false,

	VIP_GAMEPASS_ID = 1700114697,
	VIP_TITLE_MAP_KEY = "mountxyra",
	VIP_TITLE_BACKEND_URL = "https://lyvaindonesia.my.id",
	VIP_TITLE_API_KEY = "lyva_9X2kPq71AbC_secure_token_2026",
	VIP_TITLE_SLOT = 1,
	VIP_TITLE_POLL_INTERVAL = 30,
	VIP_TITLE_ALLOW_NONVIP_IN_STUDIO = true,
	VIP_TITLE_ALLOWED_PLACE_IDS = {
		-- [1234567890] = true,
	},

	SPAWN_TO_CP_ON_RESPAWN = true,
	SPAWN_TO_CP_DELAY = 0.25,
	SPAWN_TO_CP_HEIGHT = 6,
	SPAWN_RAYCAST_UP = 40,
	SPAWN_RAYCAST_DOWN = 120,
	SPAWN_FREEZE_TIME = 0.18,

	SAVE_THROTTLE_SEC = 25,
}

local SPAWN_SUMMIT_ATTR = "SpawnAtSummit"
local LAST_CHECKPOINT_ATTR = "MX_LastCheckpoint"
local RESPAWN_CHECKPOINT_ATTR = "RespawnCheckpoint"

local ROLES = {
	DEVELOPER      = { 9006398922, 7301115202, 8500880086 },
	MODERATOR      = { 0 },
	HANDSOME_OWNER = { 9271419670 },
	OWNER          = { 0 },
	SUPER_ADMIN    = { 9455466126 },
	ADMIN          = { 8467134798, 7679695008, 8925005930, 8850577882, 8251495621, 2777667222 },
	STREAMER       = { 7301115202, 7679695008 },
	HELPER         = { 0 },
}

CONFIG.LYVA_COMMUNITY_USERIDS = {
	[9006398922]=true,[9271419670]=true,[7301115202]=true,[8500880086]=true,[9455466126]=true,
	[8467134798]=true,[7679695008]=true,[8925005930]=true,[8850577882]=true,[8251495621]=true,[2777667222]=true,
}

--========================
-- Remotes
--========================
local function ensureRemote(name: string)
	local r = ReplicatedStorage:FindFirstChild(name)
	if r and not r:IsA("RemoteEvent") then r:Destroy(); r=nil end
	if not r then
		r = Instance.new("RemoteEvent")
		r.Name = name
		r.Parent = ReplicatedStorage
	end
	return r
end

local PopupEvent = ensureRemote("PopupEvent")
local AdminTitleEvent = ensureRemote("MX_AdminTitle")

local function notifyPlayer(player: Player, msg: string)
	if player and player:IsDescendantOf(Players) then
		PopupEvent:FireClient(player, msg)
	end
end

--========================
-- Detect total checkpoints
--========================
local function detectTotalCheckpoints()
	local count=0
	for i=1,500 do
		local p=workspace:FindFirstChild(CONFIG.CHECKPOINT_PREFIX..tostring(i))
		if p and p:IsA("BasePart") then count=i else break end
	end
	return math.max(1, count)
end
local TOTAL_CHECKPOINTS = detectTotalCheckpoints()

--========================
-- Touch helpers
--========================
local function getPlayerFromHit(hit)
	if not hit then return nil end
	local model = hit:FindFirstAncestorOfClass("Model")
	if not model then return nil end
	return Players:GetPlayerFromCharacter(model)
end

local function isValidTouch(hit:Instance)
	if not hit or not hit:IsA("BasePart") then return false end
	if CONFIG.HRP_ONLY_TOUCH then
		return hit.Name == "HumanoidRootPart"
	end
	local model = hit:FindFirstAncestorOfClass("Model")
	return model and model:FindFirstChildOfClass("Humanoid") ~= nil
end

local function forceCanTouch(part:BasePart)
	pcall(function() part.CanTouch=true end)
end

--========================
-- VIP
--========================
local function updateVIPStatus(player:Player)
	local hasVip=false
	if CONFIG.VIP_GAMEPASS_ID and CONFIG.VIP_GAMEPASS_ID~=0 then
		local ok,own = pcall(function()
			return MarketplaceService:UserOwnsGamePassAsync(player.UserId, CONFIG.VIP_GAMEPASS_ID)
		end)
		if ok and own then hasVip=true end
	end
	player:SetAttribute("IsVIP", hasVip)
end

--========================
-- Leaderstats + internal run checkpoint
--========================
local function ensureLeaderstats(player:Player)
	local ls = player:FindFirstChild("leaderstats")
	if not ls then
		ls = Instance.new("Folder")
		ls.Name = "leaderstats"
		ls.Parent = player
	end

	local function getOrCreateInt(folder: Instance, name: string)
		local v = folder:FindFirstChild(name)
		if v and v:IsA("IntValue") then return v end
		if v then v:Destroy() end
		v = Instance.new("IntValue")
		v.Name = name
		v.Parent = folder
		return v
	end

	local displayCp = getOrCreateInt(ls, "Checkpoint")
	local sum = getOrCreateInt(ls, "Summits")

	local internal = player:FindFirstChild("MX_Internal")
	if not internal then
		internal = Instance.new("Folder")
		internal.Name = "MX_Internal"
		internal.Parent = player
	end
	local runCp = getOrCreateInt(internal, "RunCheckpoint")

	return displayCp, sum, runCp
end

--========================
-- Teleport helpers
--========================
local function getCheckpointPart(cpIndex:number):BasePart?
	if cpIndex<=0 then return nil end
	local part=workspace:FindFirstChild(CONFIG.CHECKPOINT_PREFIX..tostring(cpIndex))
	if part and part:IsA("BasePart") then return part end
	return nil
end

local function computeSafeSpawnCFrame(cpPart:BasePart, character:Model):CFrame
	local origin = cpPart.Position + Vector3.new(0, CONFIG.SPAWN_RAYCAST_UP, 0)
	local dir = Vector3.new(0, -(CONFIG.SPAWN_RAYCAST_UP + CONFIG.SPAWN_RAYCAST_DOWN), 0)

	local params = RaycastParams.new()
	params.FilterType = Enum.RaycastFilterType.Exclude
	params.IgnoreWater=true
	params.FilterDescendantsInstances={character}

	local result = workspace:Raycast(origin, dir, params)
	local hitPos = result and result.Position or cpPart.Position
	local spawnPos = hitPos + Vector3.new(0, CONFIG.SPAWN_TO_CP_HEIGHT, 0)
	return CFrame.new(spawnPos)
end

local function getSummitPart(): BasePart?
	local p = workspace:FindFirstChild(CONFIG.SUMMIT_PART_NAME)
	if p and p:IsA("BasePart") then return p end
	return nil
end

local function teleportToSummit(character: Model)
	local part = getSummitPart()
	if not part then return end

	local hrp = character:FindFirstChild("HumanoidRootPart")
	local hum = character:FindFirstChildOfClass("Humanoid")
	if not hrp or not hum then return end

	local wasAnchored = hrp.Anchored
	hrp.Anchored = true
	hrp.AssemblyLinearVelocity = Vector3.zero
	hrp.AssemblyAngularVelocity = Vector3.zero

	character:PivotTo(CFrame.new(part.Position + Vector3.new(0, CONFIG.SPAWN_TO_CP_HEIGHT, 0)))

	RunService.Heartbeat:Wait()
	task.delay(CONFIG.SPAWN_FREEZE_TIME, function()
		if character.Parent and hrp.Parent==character then
			hrp.Anchored = wasAnchored
		end
	end)
end

local function teleportToSavedCheckpointOrSummit(player:Player, character:Model)
	if player:GetAttribute(SPAWN_SUMMIT_ATTR) == true then
		teleportToSummit(character)
		return
	end

	local displayCp, _, runCp = ensureLeaderstats(player)
	local respawnCp = tonumber(player:GetAttribute(RESPAWN_CHECKPOINT_ATTR)) or 0
	local attrCp = tonumber(player:GetAttribute(LAST_CHECKPOINT_ATTR)) or 0
	local cpIndex = math.max(respawnCp, attrCp, tonumber(runCp.Value) or 0)
	if cpIndex <= 0 then
		cpIndex = displayCp.Value
	end
	if cpIndex<=0 then return end

	local part = getCheckpointPart(cpIndex)
	if not part then return end

	local hrp=character:FindFirstChild("HumanoidRootPart")
	local hum=character:FindFirstChildOfClass("Humanoid")
	if not hrp or not hum then return end

	local wasAnchored=hrp.Anchored
	hrp.Anchored=true
	hrp.AssemblyLinearVelocity=Vector3.zero
	hrp.AssemblyAngularVelocity=Vector3.zero

	character:PivotTo(computeSafeSpawnCFrame(part, character))

	RunService.Heartbeat:Wait()
	task.delay(CONFIG.SPAWN_FREEZE_TIME, function()
		if character.Parent and hrp.Parent==character then
			hrp.Anchored=wasAnchored
		end
	end)
end

--========================
-- Modules
--========================
local ModFolder =
	(ServerStorage:FindFirstChild("MX_Modules"))
	or (ServerScriptService:FindFirstChild("MX_Modules"))

assert(ModFolder, "MX_Modules not found. Put MX_Modules in ServerStorage or ServerScriptService.")

local DataModule = require(ModFolder:WaitForChild("MX_Data"))
local SpeedrunModule = require(ModFolder:WaitForChild("MX_Speedrun"))
local TitleModule = require(ModFolder:WaitForChild("MX_Title"))

local Data = DataModule.Init(CONFIG)

--========================
-- Disable ordered DS traffic
--========================
function Data:SyncOrdered(uid, summits, checkpoint) end
Data.SummitsTopDS = { GetSortedAsync = function() return nil end, SetAsync = function() end }
Data.CheckpointODS= { GetSortedAsync = function() return nil end, SetAsync = function() end }
Data.BestTimeODS  = { GetSortedAsync = function() return nil end, SetAsync = function() end }
if Data.SaveBestTime then
	function Data:SaveBestTime(uid, ms) end
end

--========================
-- Save throttling
--========================
local rawSave = Data.Save
local pending, nextAt, saving = {}, {}, {}
local loadedOk = {}

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

local function queueSave(uid:number, patch:table)
	if typeof(patch) ~= "table" then return end
	if loadedOk[uid] ~= true then
		return
	end

	pending[uid] = pending[uid] or {}
	for k,v in pairs(patch) do
		pending[uid][k] = v
	end

	nextAt[uid] = os.clock() + (CONFIG.SAVE_THROTTLE_SEC or 25)

	if saving[uid] then return end
	saving[uid] = true

	task.spawn(function()
		while pending[uid] do
			local t = nextAt[uid] or os.clock()
			while os.clock() < t do task.wait(0.1) end

			local payload = pending[uid]
			pending[uid] = nil

			local ok = rawSave(Data, uid, payload)
			if not ok then
				pending[uid] = payload
				nextAt[uid] = os.clock() + 3 + math.random() * 3
				task.wait(0.5)
			end
		end
		saving[uid] = nil
	end)
end

function Data:Save(uid:number, patch:table)
	queueSave(uid, patch)
end

local function flushSave(uid:number)
	local payload = pending[uid]
	pending[uid] = nil
	if payload then
		local ok = rawSave(Data, uid, payload)
		if not ok then
			rawSave(Data, uid, payload)
		end
	end
	saving[uid] = nil
end

--========================
-- Init other modules
--========================
local Speedrun = SpeedrunModule.Init(CONFIG, Data, TOTAL_CHECKPOINTS, isValidTouch, forceCanTouch, getPlayerFromHit, notifyPlayer)
local Title = TitleModule.Init(CONFIG, Data, Speedrun, TOTAL_CHECKPOINTS)

if Title.StartGlobalRankLoop then
	function Title:StartGlobalRankLoop() end
end

--========================
-- Title refresh bridge
--========================
local refresh = ServerScriptService:FindFirstChild("MX_TitleRefresh")
if not refresh then
	refresh = Instance.new("BindableEvent")
	refresh.Name = "MX_TitleRefresh"
	refresh.Parent = ServerScriptService
end

local titleDebounce = {}

local function safeRefreshTitle(plr: Player)
	if not plr or not plr:IsDescendantOf(Players) then return end
	local char = plr.Character
	if not char then return end
	if not char:FindFirstChild("Head") then return end
	local uid = plr.UserId
	local now = os.clock()
	if titleDebounce[uid] and (now - titleDebounce[uid]) < 0.15 then return end
	titleDebounce[uid] = now
	Title:ApplyRetry(plr, char, ROLES, CONFIG.LYVA_COMMUNITY_USERIDS)
end

refresh.Event:Connect(function(targetPlayer: Player)
	safeRefreshTitle(targetPlayer)
end)

local function hookLeaderstatsRefresh(player: Player)
	local displayCp, sum, _ = ensureLeaderstats(player)

	displayCp.Changed:Connect(function()
		safeRefreshTitle(player)
	end)
	sum.Changed:Connect(function()
		safeRefreshTitle(player)
	end)

	player:GetAttributeChangedSignal("BestTimeMS"):Connect(function()
		safeRefreshTitle(player)
	end)
end

--========================
-- Custom title helpers
--========================
local function hasRole(uid:number, groupName:string):boolean
	local t = ROLES[groupName]
	if not t then return false end
	for _,v in ipairs(t) do if v==uid then return true end end
	return false
end

local function isAdminUser(player:Player):boolean
	local uid = player.UserId
	return hasRole(uid,"DEVELOPER")
		or hasRole(uid,"ADMIN")
		or hasRole(uid,"MODERATOR")
		or hasRole(uid,"SUPER_ADMIN")
		or hasRole(uid,"HANDSOME_OWNER")
		or hasRole(uid,"OWNER")
end

local function sanitizeText(s:any):string
	s = tostring(s or "")
	s = s:gsub("\n"," "):gsub("\r"," ")
	s = s:gsub("^%s+",""):gsub("%s+$","")
	if #s > 28 then s = s:sub(1,28) end
	return s
end

local VALID_MODES = { NONE=true, SOLID=true, RGB=true }
local VALID_PRESETS = {
	VIP=true, DEV=true, ADMIN=true, MOD=true, ROLE=true, LYVA=true,
	SUMMITS_BADGE=true, BESTTIME_BADGE=true, NAME=true, STATS=true
}

local function applyCustomTitlesFromProfile(player: Player, profile: table)
	Title:EnsureCustomTitles(player)

	local folder = player:FindFirstChild("CustomTitles")
	if folder then
		local t = profile.customTitles or {}
		for i=1,10 do
			local sv = folder:FindFirstChild(string.format("Title%02d", i))
			if sv and sv:IsA("StringValue") then
				sv.Value = tostring(t[i] or "")
			end
		end
	end

	local metaFolder = player:FindFirstChild("CustomTitleMeta")
	if metaFolder then
		local mt = profile.customTitleMeta or {}
		for i=1,10 do
			local slot = metaFolder:FindFirstChild(string.format("Slot%02d", i))
			if slot then
				local info = mt[i]
				if typeof(info)=="table" then
					local mode = slot:FindFirstChild("Mode")
					local col  = slot:FindFirstChild("Color")
					local pre  = slot:FindFirstChild("Preset")
					if mode and mode:IsA("StringValue") then mode.Value = tostring(info.mode or "NONE") end
					if pre and pre:IsA("StringValue") then pre.Value = tostring(info.preset or "VIP") end
					if col and col:IsA("Color3Value") and typeof(info.color)=="table" then
						col.Value = Color3.fromRGB(
							math.clamp(tonumber(info.color.r) or 255, 0, 255),
							math.clamp(tonumber(info.color.g) or 255, 0, 255),
							math.clamp(tonumber(info.color.b) or 255, 0, 255)
						)
					end
				end
			end
		end
	end
end

local function applyCustomTitlesFromData(player: Player)
	local cached = Data:GetCached(player.UserId)
	if cached then
		applyCustomTitlesFromProfile(player, cached)
	end
end

local function patchCachedProfile(userId: number, patcher: (table) -> ())
	local cached = Data:GetCached(userId)
	if typeof(cached) ~= "table" then
		return
	end

	patcher(cached)
end

local rgbRefreshCooldown = {}
local applyRgbMetaToPlayer

applyRgbMetaToPlayer = function(player: Player): boolean
	local metaFolder = player:FindFirstChild("CustomTitleMeta")
	if not metaFolder then
		return false
	end

	local hasRgb = false
	for slotIndex = 1,10 do
		local slot = metaFolder:FindFirstChild(string.format("Slot%02d", slotIndex))
		if slot and slot:IsA("Folder") then
			local mode = slot:FindFirstChild("Mode")
			local col  = slot:FindFirstChild("Color")
			if mode and mode:IsA("StringValue") and string.upper(tostring(mode.Value)) == "RGB" and col and col:IsA("Color3Value") then
				hasRgb = true
				local hue = (os.clock() * 0.18 + (slotIndex * 0.07)) % 1
				col.Value = Color3.fromHSV(hue, 1, 1)
			end
		end
	end

	if hasRgb then
		local nowClock = os.clock()
		local prev = rgbRefreshCooldown[player.UserId] or 0
		if (nowClock - prev) >= 0.75 then
			rgbRefreshCooldown[player.UserId] = nowClock
			safeRefreshTitle(player)
		end
	end

	return hasRgb
end

local function hydratePlayerTitle(player: Player, profile: table?)
	if not player or not player:IsDescendantOf(Players) then
		return
	end

	local activeProfile = profile
	if typeof(activeProfile) ~= "table" then
		activeProfile = Data:GetCached(player.UserId)
	end

	if typeof(activeProfile) == "table" then
		applyCustomTitlesFromProfile(player, activeProfile)
	end

	applyRgbMetaToPlayer(player)
	safeRefreshTitle(player)
end

local function scheduleTitleHydration(player: Player, profile: table?)
	for _, delaySeconds in ipairs({ 0.1, 0.5, 1.5, 3 }) do
		task.delay(delaySeconds, function()
			hydratePlayerTitle(player, profile)
		end)
	end
end

local function scheduleCharacterTitleRefresh(player: Player, character: Model, profile: table?)
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

			hydratePlayerTitle(player, profile)
		end)
	end
end

local function scheduleRespawnTitleRecovery(player: Player, character: Model, profile: table?)
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

			if typeof(profile) == "table" then
				applyCustomTitlesFromProfile(player, profile)
			else
				applyCustomTitlesFromData(player)
			end

			applyRgbMetaToPlayer(player)
			safeRefreshTitle(player)
		end)
	end
end

--========================
-- VIP title claim polling
--========================
local function normalizeVipBackendUrl(rawUrl: string): string
	local normalized = tostring(rawUrl or "")
	normalized = normalized:gsub("%s+", "")
	normalized = normalized:gsub("/+$", "")
	normalized = normalized:gsub("/api$", "")
	return normalized
end

local function vipClaimRequest(pathSuffix: string, body: table)
	local requestUrl = normalizeVipBackendUrl(CONFIG.VIP_TITLE_BACKEND_URL) .. pathSuffix
	local ok, response = pcall(function()
		return HttpService:RequestAsync({
			Url = requestUrl,
			Method = "POST",
			Headers = {
				["Content-Type"] = "application/json",
				["x-api-key"] = CONFIG.VIP_TITLE_API_KEY,
			},
			Body = HttpService:JSONEncode(body or {}),
		})
	end)

	if not ok or not response then
		warn("[VIP CLAIM] RequestAsync gagal:", requestUrl)
		return nil
	end

	if not response.Success then
		warn("[VIP CLAIM] HTTP request gagal:", requestUrl, response.StatusCode, response.StatusMessage, response.Body)
		return nil
	end

	local decodedOk, decoded = pcall(function()
		return HttpService:JSONDecode(response.Body)
	end)

	if not decodedOk then
		warn("[VIP CLAIM] JSON decode gagal:", requestUrl, response.Body)
	end

	return decodedOk and decoded or nil
end

local function playerOwnsVipForClaim(player: Player, claimGamepassId: number?): boolean
	local activeGamepassId = tonumber(claimGamepassId) or CONFIG.VIP_GAMEPASS_ID
	if activeGamepassId == 0 then
		return true
	end

	if CONFIG.VIP_TITLE_ALLOW_NONVIP_IN_STUDIO and RunService:IsStudio() then
		return true
	end

	local ok, owns = pcall(function()
		return MarketplaceService:UserOwnsGamePassAsync(player.UserId, activeGamepassId)
	end)

	if not ok then
		warn("[VIP CLAIM] UserOwnsGamePassAsync gagal:", player.Name, player.UserId, activeGamepassId)
	end

	return ok and owns == true
end

local function resolveVipClaimSlot(claim: table?): number
	local slot = tonumber(claim and claim.titleSlot) or tonumber(CONFIG.VIP_TITLE_SLOT) or 10
	return math.clamp(slot, 1, 10)
end

local function isVipClaimPlaceAllowed(): boolean
	local allowed = CONFIG.VIP_TITLE_ALLOWED_PLACE_IDS
	if typeof(allowed) ~= "table" or next(allowed) == nil then
		return true
	end
	return allowed[game.PlaceId] == true
end

local function applyVipClaimToPlayer(player: Player, claim: table): boolean
	local targetSlot = resolveVipClaimSlot(claim)
	print("[VIP CLAIM] APPLY START", player.Name, claim and claim.title or "NO_TITLE", "slot", targetSlot)

	if not playerOwnsVipForClaim(player, claim and claim.gamepassId) then
		warn("[VIP CLAIM] APPLY FAIL: player tidak punya VIP", player.Name, player.UserId, claim and claim.gamepassId or CONFIG.VIP_GAMEPASS_ID)
		return false
	end

	local profile, ok = loadProfileCompat(Data:Load(player.UserId))
	print("[VIP CLAIM] DATA LOAD", player.Name, ok, profile ~= nil)

	if not ok or not profile then
		warn("[VIP CLAIM] APPLY FAIL: Data load gagal", player.Name, player.UserId)
		return false
	end

	loadedOk[player.UserId] = true
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

	print("[VIP CLAIM] TITLE WRITE", player.Name, targetSlot, profile.customTitles[targetSlot])

	Data:Save(player.UserId, {
		customTitles = profile.customTitles,
		customTitleMeta = profile.customTitleMeta,
	})

	applyCustomTitlesFromProfile(player, profile)
	applyRgbMetaToPlayer(player)
	safeRefreshTitle(player)
	notifyPlayer(player, ("VIP TITLE BERHASIL SLOT %d: %s"):format(targetSlot, profile.customTitles[targetSlot]))
	print("[VIP CLAIM] APPLY DONE", player.Name, profile.customTitles[targetSlot])
	return true
end

local function checkVipTitleClaim(player: Player)
	print("[VIP CLAIM] CHECK START", player and player.Name or "NIL_PLAYER", CONFIG.VIP_TITLE_MAP_KEY, game.PlaceId)

	if not player or not player:IsDescendantOf(Players) then
		warn("[VIP CLAIM] CHECK STOP: player invalid")
		return
	end

	if CONFIG.VIP_TITLE_BACKEND_URL == "" or CONFIG.VIP_TITLE_API_KEY == "" then
		warn("[VIP CLAIM] CHECK STOP: backend/api key kosong")
		return
	end

	if CONFIG.VIP_TITLE_MAP_KEY == "" then
		warn("[VIP CLAIM] CHECK STOP: map key kosong")
		return
	end

	if not isVipClaimPlaceAllowed() then
		warn("[VIP CLAIM] CHECK STOP: place tidak diizinkan", game.PlaceId)
		return
	end

	local payload = vipClaimRequest("/api/roblox/vip-title-claims/pull", {
		userId = player.UserId,
		username = player.Name,
		mapKey = CONFIG.VIP_TITLE_MAP_KEY,
		placeId = tostring(game.PlaceId),
		universeId = tostring(game.GameId),
	})

	if payload then
		print("[VIP CLAIM] CHECK RESPONSE OK", player.Name)
	else
		warn("[VIP CLAIM] CHECK RESPONSE NIL", player.Name)
	end

	if not payload or not payload.claim then
		print("[VIP CLAIM] CHECK NO CLAIM", player.Name)
		return
	end

	print("[VIP CLAIM] CLAIM FOUND", player.Name, payload.claim.claimId, payload.claim.title, payload.claim.mapKey)
	local applied = applyVipClaimToPlayer(player, payload.claim)
	print("[VIP CLAIM] APPLY RESULT", player.Name, applied)
	vipClaimRequest("/api/roblox/vip-title-claims/consume", {
		claimId = payload.claim.claimId,
		status = applied and "applied" or "rejected",
		reason = applied and "Applied in game" or "VIP ownership / data load failed",
		mapKey = CONFIG.VIP_TITLE_MAP_KEY,
		placeId = tostring(game.PlaceId),
		universeId = tostring(game.GameId),
	})
	print("[VIP CLAIM] CONSUME SENT", player.Name, payload.claim.claimId, applied and "applied" or "rejected")
end

local function snapshotCustomTitles(player: Player)
	local titles, meta = {}, {}
	local folder = player:FindFirstChild("CustomTitles")
	if folder then
		for i=1,10 do
			local sv = folder:FindFirstChild(string.format("Title%02d", i))
			titles[i] = (sv and sv:IsA("StringValue")) and tostring(sv.Value) or ""
		end
	end
	local metaFolder = player:FindFirstChild("CustomTitleMeta")
	if metaFolder then
		for i=1,10 do
			local slot = metaFolder:FindFirstChild(string.format("Slot%02d", i))
			if slot and slot:IsA("Folder") then
				local mode = slot:FindFirstChild("Mode")
				local col  = slot:FindFirstChild("Color")
				local pre  = slot:FindFirstChild("Preset")
				local m = (mode and mode:IsA("StringValue")) and tostring(mode.Value) or "NONE"
				local p = (pre and pre:IsA("StringValue")) and tostring(pre.Value) or "VIP"
				local c = (col and col:IsA("Color3Value")) and col.Value or Color3.fromRGB(255,255,255)
				meta[i] = { mode=m, preset=p, color={ r=math.floor(c.R*255+0.5), g=math.floor(c.G*255+0.5), b=math.floor(c.B*255+0.5) } }
			end
		end
	end
	return titles, meta
end

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

--========================
-- Admin give title
--========================
AdminTitleEvent.OnServerEvent:Connect(function(sender:Player, targetUsername:any, slot:any, text:any, mode:any, r:any, g:any, b:any, preset:any)
	if not sender or not sender:IsDescendantOf(Players) then return end
	if not isAdminUser(sender) then
		notifyPlayer(sender, "KAMU BUKAN ADMIN!")
		return
	end

	local username = tostring(targetUsername or ""):gsub("^%s+",""):gsub("%s+$","")
	if username == "" then notifyPlayer(sender, "USERNAME KOSONG!"); return end

	local okId, targetUserId = pcall(function()
		return Players:GetUserIdFromNameAsync(username)
	end)
	if not okId or not targetUserId then notifyPlayer(sender, "USERNAME TIDAK DITEMUKAN!"); return end

	slot = tonumber(slot) or 0
	if slot < 1 or slot > 10 then notifyPlayer(sender, "SLOT HARUS 1-10!"); return end

	local cleanText = sanitizeText(text)
	mode = tostring(mode or "NONE"):upper()
	if not VALID_MODES[mode] then mode = "NONE" end

	local rr = math.clamp(tonumber(r) or 255, 0, 255)
	local gg = math.clamp(tonumber(g) or 255, 0, 255)
	local bb = math.clamp(tonumber(b) or 255, 0, 255)

	preset = tostring(preset or "VIP")
	if not VALID_PRESETS[preset] then preset = "VIP" end

	local d, ok = loadProfileCompat(Data:Load(targetUserId, true))
	if not ok or not d then
		notifyPlayer(sender, "DS LAG/ERROR. COBA LAGI.")
		return
	end
	loadedOk[targetUserId] = true

	d.customTitles = d.customTitles or {}
	d.customTitleMeta = d.customTitleMeta or {}
	d.customTitles[slot] = cleanText
	d.customTitleMeta[slot] = { mode=mode, preset=preset, color={r=rr,g=gg,b=bb} }

	Data:Save(targetUserId, { customTitles=d.customTitles, customTitleMeta=d.customTitleMeta })

	local targetPlayer = Players:GetPlayerByUserId(targetUserId)
	if targetPlayer then
		applyCustomTitlesFromProfile(targetPlayer, d)
		safeRefreshTitle(targetPlayer)
	end

	notifyPlayer(sender, ("GIVE TITLE KE @%s ✅"):format(username))
end)

--========================
-- Runtime state
--========================
local touchDebounce, lastPopupForCp = {}, {}
local mustStartFromOne, runLocked, validRun = {}, {}, {}
local lastFinishAt = {}

local function canTouch(uid:number)
	local now=os.clock()
	local prev=touchDebounce[uid]
	if prev and (now-prev)<CONFIG.CP_TOUCH_COOLDOWN then return false end
	touchDebounce[uid]=now
	return true
end

local function canFinishTouch(uid:number)
	local now=os.clock()
	local prev=lastFinishAt[uid]
	if prev and (now-prev)<CONFIG.FINISH_COOLDOWN then return false end
	lastFinishAt[uid]=now
	return true
end

local function tryPopupCP(player:Player, cpIndex:number)
	if lastPopupForCp[player.UserId]==cpIndex then return end
	lastPopupForCp[player.UserId]=cpIndex
	notifyPlayer(player, ("CHECKPOINT %d"):format(cpIndex))
end

--========================
-- Gameplay checkpoints
--========================
local function onCheckpointTouched(player:Player, cpIndex:number)
	local uid=player.UserId
	if not canTouch(uid) then return end

	local displayCp, _, runCp = ensureLeaderstats(player)

	if mustStartFromOne[player] then
		if cpIndex~=1 then
			notifyPlayer(player, "CHECKPOINT 1 DULU!")
			return
		end
		mustStartFromOne[player]=nil
		runLocked[player]=nil
		validRun[player]=true

		runCp.Value = 1
		displayCp.Value = 1
		player:SetAttribute(LAST_CHECKPOINT_ATTR, 1)
		player:SetAttribute(RESPAWN_CHECKPOINT_ATTR, 1)

		player:SetAttribute(SPAWN_SUMMIT_ATTR, false)
		patchCachedProfile(uid, function(cached)
			cached.checkpoint = 1
			cached.spawnAtSummit = false
		end)
		Data:Save(uid, { checkpoint=1, spawnAtSummit=false })

		lastPopupForCp[uid]=0
		tryPopupCP(player,1)
		return
	end

	local expected = runCp.Value + 1

	if cpIndex < expected then
		return
	end

	if cpIndex > expected then
		validRun[player]=false
		notifyPlayer(player, "CHECKPOINT SEBELUMNYA BELUM DIINJAK!")
		return
	end

	runCp.Value = cpIndex
	displayCp.Value = cpIndex
	player:SetAttribute(LAST_CHECKPOINT_ATTR, cpIndex)
	player:SetAttribute(RESPAWN_CHECKPOINT_ATTR, cpIndex)

	patchCachedProfile(uid, function(cached)
		cached.checkpoint = cpIndex
		cached.spawnAtSummit = false
	end)
	Data:Save(uid, { checkpoint=cpIndex })
	tryPopupCP(player, cpIndex)
end

local function canFinish(player:Player)
	local _, _, runCp = ensureLeaderstats(player)
	if runCp.Value ~= TOTAL_CHECKPOINTS then return false,"BELUM SEMUA CHECKPOINT!" end
	if validRun[player]==false then return false,"KAMU PERNAH SKIP! ULANGI CP1!" end
	if runLocked[player] then return false,"SUDAH FINISH! ULANGI CP1!" end
	return true,nil
end

local function finishRun(player:Player)
	local displayCp, sum, runCp = ensureLeaderstats(player)
	local uid = player.UserId

	runLocked[player]=true
	sum.Value += 250
	runCp.Value = 0
	displayCp.Value = sum.Value
	player:SetAttribute(LAST_CHECKPOINT_ATTR, 0)
	player:SetAttribute(RESPAWN_CHECKPOINT_ATTR, 0)

	player:SetAttribute(SPAWN_SUMMIT_ATTR, true)

	patchCachedProfile(uid, function(cached)
		cached.summits = sum.Value
		cached.checkpoint = 0
		cached.spawnAtSummit = true
	end)
	Data:Save(uid, {
		summits = sum.Value,
		checkpoint = 0,
		spawnAtSummit = true,
	})

	mustStartFromOne[player]=true
	validRun[player]=nil

	notifyPlayer(player, "SUMMIT!")
end

--========================
-- Bind parts
--========================
local function bindCheckpoints()
	for i=1,TOTAL_CHECKPOINTS do
		local part=workspace:FindFirstChild(CONFIG.CHECKPOINT_PREFIX..tostring(i))
		if part and part:IsA("BasePart") then
			forceCanTouch(part)
			if part:GetAttribute(CONFIG.BIND_ATTR) then continue end
			part:SetAttribute(CONFIG.BIND_ATTR, true)

			part.Touched:Connect(function(hit)
				if not isValidTouch(hit) then return end
				local plr=getPlayerFromHit(hit)
				if plr then onCheckpointTouched(plr, i) end
			end)
		end
	end
end

local function bindSummitPart()
	local part=workspace:FindFirstChild(CONFIG.SUMMIT_PART_NAME)
	if not part or not part:IsA("BasePart") then return end

	forceCanTouch(part)
	if part:GetAttribute(CONFIG.BIND_ATTR) then return end
	part:SetAttribute(CONFIG.BIND_ATTR, true)

	part.Touched:Connect(function(hit)
		if not isValidTouch(hit) then return end
		local plr=getPlayerFromHit(hit)
		if not plr then return end
		if not canFinishTouch(plr.UserId) then return end

		local ok,reason=canFinish(plr)
		if not ok then
			if reason then notifyPlayer(plr, reason) end
			return
		end
		finishRun(plr)
	end)
end

--========================
-- Player setup
--========================
local function setupPlayer(player:Player)
	local displayCp, sum, runCp = ensureLeaderstats(player)

	local d, ok = loadProfileCompat(Data:Load(player.UserId))
	if ok and d then
		loadedOk[player.UserId] = true

		local savedCp = math.clamp(tonumber(d.checkpoint) or 0, 0, TOTAL_CHECKPOINTS)
		local savedSum = math.max(tonumber(d.summits) or 0, 0)

		sum.Value = savedSum
		runCp.Value = savedCp
		player:SetAttribute(LAST_CHECKPOINT_ATTR, savedCp)
		player:SetAttribute(RESPAWN_CHECKPOINT_ATTR, savedCp)

		local spawnSummit = (d.spawnAtSummit == true)
		player:SetAttribute(SPAWN_SUMMIT_ATTR, spawnSummit)

		if spawnSummit then
			displayCp.Value = sum.Value
		else
			displayCp.Value = runCp.Value
		end

		player:SetAttribute("BestTimeMS", tonumber(d.bestTime) or 0)
		applyCustomTitlesFromProfile(player, d)
		scheduleTitleHydration(player, d)
	else
		player:SetAttribute(LAST_CHECKPOINT_ATTR, 0)
		player:SetAttribute(RESPAWN_CHECKPOINT_ATTR, 0)
		loadedOk[player.UserId] = nil
		warn("[MX] DS LOAD FAILED:", player.Name, player.UserId)
		notifyPlayer(player, "DATA STORE LAG/ERROR. COBA REJOIN.")
	end

	lastPopupForCp[player.UserId]=nil
	validRun[player]=true
	runLocked[player]=nil
	mustStartFromOne[player]=nil

	updateVIPStatus(player)
	hookLeaderstatsRefresh(player)

	if CONFIG.SPAWN_TO_CP_ON_RESPAWN and player.Character then
		teleportToSavedCheckpointOrSummit(player, player.Character)
	end

	task.defer(function()
		if player.Character then
			scheduleTitleHydration(player, d)
			scheduleCharacterTitleRefresh(player, player.Character, d)
			scheduleRespawnTitleRecovery(player, player.Character, d)
		end
	end)

	task.defer(function()
		task.wait(5)
		checkVipTitleClaim(player)
	end)

	player.CharacterAdded:Connect(function(char)
		char:WaitForChild("Head")
		char:WaitForChild("HumanoidRootPart")

		task.wait(CONFIG.SPAWN_TO_CP_DELAY)
		RunService.Heartbeat:Wait()

		if CONFIG.SPAWN_TO_CP_ON_RESPAWN then
			teleportToSavedCheckpointOrSummit(player, char)
		end

		local cached = Data:GetCached(player.UserId)
		if cached then
			applyCustomTitlesFromProfile(player, cached)
			local cachedCheckpoint = math.clamp(tonumber(cached.checkpoint) or 0, 0, TOTAL_CHECKPOINTS)
			if cachedCheckpoint > 0 then
				local checkpointToUse = math.max(
					tonumber(runCp.Value) or 0,
					tonumber(player:GetAttribute(RESPAWN_CHECKPOINT_ATTR)) or 0,
					cachedCheckpoint
				)
				player:SetAttribute(LAST_CHECKPOINT_ATTR, checkpointToUse)
				player:SetAttribute(RESPAWN_CHECKPOINT_ATTR, checkpointToUse)
			end
			if cached.spawnAtSummit ~= nil then
				player:SetAttribute(SPAWN_SUMMIT_ATTR, cached.spawnAtSummit == true)
			end
		end

		scheduleTitleHydration(player, cached)
		scheduleCharacterTitleRefresh(player, char, cached)
		scheduleRespawnTitleRecovery(player, char, cached)

		if player:GetAttribute(SPAWN_SUMMIT_ATTR) ~= true and runCp.Value > 0 then
			task.delay(0.25, function()
				if player.Character==char and runCp.Value>0 then
					tryPopupCP(player, runCp.Value)
				end
			end)
		end

		task.delay(0.2, function()
			if player.Character == char then
				scheduleTitleHydration(player, cached)
			end
		end)
	end)

	player.CharacterAppearanceLoaded:Connect(function(char)
		local cached = Data:GetCached(player.UserId)
		scheduleTitleHydration(player, cached)
		scheduleCharacterTitleRefresh(player, char, cached)
		scheduleRespawnTitleRecovery(player, char, cached)
	end)
end

--========================
-- Init
--========================
bindCheckpoints()
bindSummitPart()

Players.PlayerAdded:Connect(setupPlayer)

task.spawn(function()
	while true do
		task.wait(CONFIG.VIP_TITLE_POLL_INTERVAL)
		for _, player in ipairs(Players:GetPlayers()) do
			task.spawn(function()
				checkVipTitleClaim(player)
			end)
		end
	end
end)

Players.PlayerRemoving:Connect(function(player)
	local uid = player.UserId

	if loadedOk[uid] == true then
		local titles, meta = snapshotCustomTitles(player)
		Data:Save(uid, { customTitles=titles, customTitleMeta=meta })
		flushSave(uid)
	end

	touchDebounce[uid]=nil
	lastPopupForCp[uid]=nil
	mustStartFromOne[player]=nil
	runLocked[player]=nil
	validRun[player]=nil
	lastFinishAt[uid]=nil
	loadedOk[uid]=nil

	if Speedrun and Speedrun.Cleanup then Speedrun:Cleanup(player) end
end)

game:BindToClose(function()
	for _, plr in ipairs(Players:GetPlayers()) do
		local uid = plr.UserId
		if loadedOk[uid] == true then
			local titles, meta = snapshotCustomTitles(plr)
			Data:Save(uid, { customTitles=titles, customTitleMeta=meta })
			flushSave(uid)
		end
	end
	task.wait(1.5)
end)
