export async function fetchLaravelStatus(config) {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/status`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'menghubungi status Laravel');

  if (!response.ok) {
    throw new Error(`Laravel status request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function fetchLaravelSales(config, mode = 'live') {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/sales?mode=${encodeURIComponent(mode)}`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'menghubungi sales Laravel');

  if (!response.ok) {
    throw new Error(`Laravel sales request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function createLaravelReport(config, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/reports`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'mengirim report ke Laravel');

  if (!response.ok) {
    const body = await response.text();

    throw new Error(`Laravel report request gagal: ${response.status} ${body}`);
  }

  return response.json();
}

export async function acknowledgeLaravelRules(config, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/rules/acknowledgements`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'menyimpan acknowledge rules');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel rules acknowledgement gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function fetchLaravelVerification(config, discordUserId) {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/verifications/${discordUserId}`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'mengambil status verifikasi');

  if (!response.ok) {
    throw new Error(`Laravel verification request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function fetchLaravelGuildSettings(config, guildId) {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/guild-settings/${guildId}`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'mengambil guild settings');

  if (!response.ok) {
    throw new Error(`Laravel guild settings request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function updateLaravelGuildSettings(config, guildId, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/guild-settings/${guildId}`, {
    method: 'PUT',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'menyimpan guild settings');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel guild settings gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function createLaravelVerification(config, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/verifications`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'mengirim verifikasi ke Laravel');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel verification gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function deleteLaravelVerification(config, discordUserId) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/verifications/${discordUserId}`, {
    method: 'DELETE',
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'melepas verifikasi');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel unlink verification gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function fetchLaravelRaces(config) {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/races`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'mengambil data race');

  if (!response.ok) {
    throw new Error(`Laravel races request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function createLaravelRace(config, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/races`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'membuat race');

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`Laravel create race request gagal: ${response.status} ${body}`);
  }

  return response.json();
}

export async function fetchLaravelRace(config, eventId) {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/races/${eventId}`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'mengambil detail race');

  if (!response.ok) {
    throw new Error(`Laravel race request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function joinLaravelRace(config, eventId, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/races/${eventId}/join`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'join race');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel join race request gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function updateLaravelRace(config, eventId, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/races/${eventId}`, {
    method: 'PATCH',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'update race');

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`Laravel update race request gagal: ${response.status} ${body}`);
  }

  return response.json();
}

export async function createLaravelVipTitleClaim(config, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/vip-title-claims`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'menyimpan VIP title claim');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel VIP title claim gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function createLaravelVipTitleCheckout(config, payload) {
  if (!config.internalToken) {
    throw new Error('DISCORD_INTERNAL_TOKEN belum diisi.');
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/vip-title-checkouts`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
    body: JSON.stringify(payload),
  }, 'membuat checkout VIP title');

  if (!response.ok) {
    const message = await readLaravelErrorMessage(response, 'Laravel checkout VIP title gagal.');
    throw new Error(message);
  }

  return response.json();
}

export async function fetchLaravelVipTitleMaps(config) {
  if (!config.internalToken) {
    return null;
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/vip-title-maps`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'mengambil daftar map VIP title');

  if (!response.ok) {
    throw new Error(`Laravel VIP title maps request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

export async function fetchLaravelVipTitleClaims(config, params = {}) {
  if (!config.internalToken) {
    return null;
  }

  const query = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value));
    }
  }

  const response = await requestLaravel(`${config.botApiUrl}/api/bot/vip-title-claims?${query.toString()}`, {
    headers: {
      'Accept': 'application/json',
      'X-Bot-Token': config.internalToken,
    },
  }, 'mengambil daftar VIP title claim');

  if (!response.ok) {
    throw new Error(`Laravel VIP title claim request gagal dengan HTTP ${response.status}`);
  }

  return response.json();
}

async function readLaravelErrorMessage(response, fallback) {
  const raw = await response.text();

  try {
    const parsed = JSON.parse(raw);

    if (typeof parsed.message === 'string' && parsed.message !== '') {
      return parsed.message;
    }
  } catch {
    // Ignore invalid JSON and fall back to the raw body.
  }

  const combined = raw ? `${fallback} ${response.status} ${raw}` : `${fallback} ${response.status}`;
  return combined.length > 1000 ? `${combined.slice(0, 997)}...` : combined;
}

async function requestLaravel(url, options, action) {
  try {
    return await fetch(url, {
      ...options,
      signal: options?.signal ?? AbortSignal.timeout(5000),
    });
  } catch (error) {
    const baseUrl = url.replace(/\/api\/.*/, '');
    const actionPrefix = error?.name === 'TimeoutError'
      ? `Laravel timeout saat ${action}.`
      : `Gagal ${action}.`;
    const serveHint = baseUrl === 'http://127.0.0.1:8000'
      ? ' Jalankan `php artisan serve --host=127.0.0.1 --port=8000` atau arahkan `BOT_API_URL` ke URL Laravel yang benar.'
      : ' Pastikan `BOT_API_URL` mengarah ke Laravel yang aktif.';

    throw new Error(`${actionPrefix} Laravel tidak bisa dijangkau di ${baseUrl}.${serveHint}`);
  }
}
