local ProductCatalog = {}

ProductCatalog.DeveloperProducts = {
    [1234567890] = {
        name = "Premium Crate",
        price = 120,
        type = "dev_product",
    },
    [1234567891] = {
        name = "VIP Spin",
        price = 50,
        type = "dev_product",
    },
}

ProductCatalog.GamePasses = {
    [2234567890] = {
        name = "VIP Access",
        price = 299,
        type = "game_pass",
    },
    [2234567891] = {
        name = "2x Coins",
        price = 199,
        type = "game_pass",
    },
}

function ProductCatalog.getDeveloperProduct(productId)
    return ProductCatalog.DeveloperProducts[productId]
end

function ProductCatalog.getGamePass(gamePassId)
    return ProductCatalog.GamePasses[gamePassId]
end

return ProductCatalog
