import { commandDefinitions } from './commands.js';
import { loadBotConfig } from './config.js';
import { registerCommands } from './register-commands.js';

const config = loadBotConfig();
await registerCommands(config, commandDefinitions);
