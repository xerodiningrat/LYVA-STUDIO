-- MX_License
-- ModuleScript validasi license untuk Roblox LuaU.

local HttpService = game:GetService("HttpService")

local CONFIG = {
    LICENSE_KEY = "\076\089\086\065\045\068\069\077\079\045\075\069\089\049\045\048\048\048\049\045\065\066\067\068",
    MAP_NAME = "\077\111\117\110\116\088\121\114\097",
    SERVER_URL = "\104\116\116\112\058\047\047\049\050\055\046\048\046\048\046\049\058\051\048\048\048",
    CHECK_PATH = "\047\099\104\101\099\107\045\107\101\121",
    GRACE_SECONDS = 30,
    RETRY_INTERVAL = 5,
}

local License = {}

local function failHard(message)
    local fullMessage = "[LYVA LICENSE] " .. tostring(message)
    warn(fullMessage)
    error(fullMessage, 0)
end

local function buildCheckUrl()
    return CONFIG.SERVER_URL .. CONFIG.CHECK_PATH
end

local function buildPayload()
    return {
        key = CONFIG.LICENSE_KEY,
        gameId = tostring(game.GameId),
        placeId = tostring(game.PlaceId),
    }
end

local function requestCheck()
    return HttpService:RequestAsync({
        Url = buildCheckUrl(),
        Method = "POST",
        Headers = {
            ["Content-Type"] = "application/json",
        },
        Body = HttpService:JSONEncode(buildPayload()),
    })
end

local function decodeResponse(response)
    local ok, body = pcall(function()
        return HttpService:JSONDecode(response.Body or "{}")
    end)

    if not ok or type(body) ~= "table" then
        return nil
    end

    return body
end

function License.Validate()
    local startedAt = os.clock()

    while true do
        local ok, response = pcall(requestCheck)

        if ok and response and response.Success then
            local body = decodeResponse(response)

            if body and body.valid == true then
                return true
            end

            failHard(body and body.reason or "License tidak valid.")
        end

        if (os.clock() - startedAt) >= CONFIG.GRACE_SECONDS then
            failHard("Server license tidak bisa diakses selama 30 detik. Script dihentikan.")
        end

        task.wait(CONFIG.RETRY_INTERVAL)
    end
end

License.Validate()

return License
