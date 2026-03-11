local HttpService = game:GetService("HttpService")
local MarketplaceService = game:GetService("MarketplaceService")
local ReplicatedStorage = game:GetService("ReplicatedStorage")

local Modules = ReplicatedStorage:WaitForChild("Modules")
local ProductCatalog = require(Modules:WaitForChild("ProductCatalog"))
local PurchaseSignal = ReplicatedStorage:WaitForChild("GamePassPurchaseSignal")

local ENDPOINT = "https://domainkamu.com/api/roblox/sales-events"
local INGEST_TOKEN = "ISI_DENGAN_ROBLOX_INGEST_TOKEN"

local purchaseCache = {}

local function postSalesEvent(payload)
    local ok, response = pcall(function()
        return HttpService:RequestAsync({
            Url = ENDPOINT,
            Method = "POST",
            Headers = {
                ["Content-Type"] = "application/json",
                ["X-Roblox-Token"] = INGEST_TOKEN,
            },
            Body = HttpService:JSONEncode(payload),
        })
    end)

    if not ok then
        warn("Game pass sales event request failed:", response)
        return false
    end

    if not response.Success then
        warn("Game pass sales event rejected:", response.StatusCode, response.Body)
        return false
    end

    return true
end

PurchaseSignal.OnServerEvent:Connect(function(player, gamePassId)
    if typeof(gamePassId) ~= "number" then
        return
    end

    local cacheKey = tostring(player.UserId) .. ":" .. tostring(gamePassId)
    if purchaseCache[cacheKey] then
        return
    end

    local passConfig = ProductCatalog.getGamePass(gamePassId)
    if not passConfig then
        warn("Unknown game pass ID:", gamePassId)
        return
    end

    local ownsPass = false
    local ok, result = pcall(function()
        return MarketplaceService:UserOwnsGamePassAsync(player.UserId, gamePassId)
    end)

    if ok and result == true then
        ownsPass = true
    end

    if not ownsPass then
        return
    end

    purchaseCache[cacheKey] = true

    local payload = {
        universe_id = tostring(game.GameId),
        product_name = passConfig.name,
        product_type = passConfig.type,
        product_id = tostring(gamePassId),
        buyer_name = player.Name,
        amount_robux = passConfig.price,
        quantity = 1,
        purchased_at = DateTime.now():ToIsoDate(),
        payload = {
            source = "roblox_game_pass",
            player_id = player.UserId,
            place_id = game.PlaceId,
            job_id = game.JobId,
        },
    }

    local success = postSalesEvent(payload)

    if not success then
        purchaseCache[cacheKey] = nil
        return
    end

    task.delay(10, function()
        purchaseCache[cacheKey] = nil
    end)
end)
