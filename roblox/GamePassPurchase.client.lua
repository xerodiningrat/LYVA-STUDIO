local MarketplaceService = game:GetService("MarketplaceService")
local ReplicatedStorage = game:GetService("ReplicatedStorage")
local Players = game:GetService("Players")

local player = Players.LocalPlayer
local PurchaseSignal = ReplicatedStorage:WaitForChild("GamePassPurchaseSignal")

MarketplaceService.PromptGamePassPurchaseFinished:Connect(function(finishedPlayer, gamePassId, wasPurchased)
    if finishedPlayer ~= player then
        return
    end

    if not wasPurchased then
        return
    end

    PurchaseSignal:FireServer(gamePassId)
end)
