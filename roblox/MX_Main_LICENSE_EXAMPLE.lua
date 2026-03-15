-- Contoh integrasi di baris paling atas MX_Main.

local okLicense, licenseResult = pcall(function()
    return require(script:WaitForChild("MX_License"))
end)

if not okLicense then
    print("[MX] License error:", licenseResult)
    return
end

-- Lanjutkan load module lain di bawah ini.
