local HttpService = game:GetService("HttpService")
local MarketplaceService = game:GetService("MarketplaceService")
local Players = game:GetService("Players")
local ReplicatedStorage = game:GetService("ReplicatedStorage")

local Modules = ReplicatedStorage:WaitForChild("Modules")
local ProductCatalog = require(Modules:WaitForChild("ProductCatalog"))

local ENDPOINT = "https://domainkamu.com/api/roblox/sales-events"
local INGEST_TOKEN = "ISI_DENGAN_ROBLOX_INGEST_TOKEN"

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
        warn("Sales event request failed:", response)
        return false
    end

    if not response.Success then
        warn("Sales event rejected:", response.StatusCode, response.Body)
        return false
    end

    return true
end

MarketplaceService.ProcessReceipt = function(receiptInfo)
    local productId = receiptInfo.ProductId
    local player = Players:GetPlayerByUserId(receiptInfo.PlayerId)
    local product = ProductCatalog.getDeveloperProduct(productId)

    if not product then
        warn("Unknown developer product ID:", productId)
        return Enum.ProductPurchaseDecision.NotProcessedYet
    end

    local payload = {
        universe_id = tostring(game.GameId),
        product_name = product.name,
        product_type = product.type,
        product_id = tostring(productId),
        buyer_name = player and player.Name or ("User_" .. tostring(receiptInfo.PlayerId)),
        amount_robux = product.price,
        quantity = 1,
        purchased_at = DateTime.now():ToIsoDate(),
        payload = {
            source = "roblox_dev_product",
            player_id = receiptInfo.PlayerId,
            purchase_id = receiptInfo.PurchaseId,
            place_id = game.PlaceId,
            job_id = game.JobId,
        },
    }

    local success = postSalesEvent(payload)

    if success then
        return Enum.ProductPurchaseDecision.PurchaseGranted
    end

    return Enum.ProductPurchaseDecision.NotProcessedYet
end
