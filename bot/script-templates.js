import { readFile } from 'node:fs/promises';
import path from 'node:path';

const templates = {
  devproduct: 'DevProductSalesReporter.server.lua',
  gamepass_server: 'GamePassPurchaseReporter.server.lua',
  gamepass_client: 'GamePassPurchase.client.lua',
  catalog: 'ProductCatalog.lua',
  remote: 'GamePassPurchaseSignal.remote.lua',
  readme: 'README.md',
};

export async function getScriptTemplate(config, slug) {
  const filename = templates[slug];

  if (!filename) {
    throw new Error('Script template tidak ditemukan.');
  }

  const filePath = path.resolve(process.cwd(), 'roblox', filename);
  const raw = await readFile(filePath, 'utf8');
  const endpoint = `${config.appUrl}/api/roblox/sales-events`;

  const content = raw
    .replaceAll('https://domainkamu.com/api/roblox/sales-events', endpoint)
    .replaceAll(
      'ISI_DENGAN_ROBLOX_INGEST_TOKEN',
      process.env.ROBLOX_INGEST_TOKEN || 'ISI_DENGAN_ROBLOX_INGEST_TOKEN',
    );

  return { filename, content };
}
