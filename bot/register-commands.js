import { REST, Routes } from 'discord.js';

export async function registerCommands(config, commandDefinitions) {
  const rest = new REST({ version: '10' }).setToken(config.botToken);
  const guildIds = Array.isArray(config.guildIds) ? config.guildIds : [];
  const useGuildScope = config.commandScope === 'guild' && guildIds.length > 0;

  if (useGuildScope) {
    console.log(`Registering ${commandDefinitions.length} commands in guild scope...`);
    for (const guildId of guildIds) {
      await rest.put(Routes.applicationGuildCommands(config.applicationId, guildId), {
        body: commandDefinitions,
      });
      console.log(`Discord commands synced in guild ${guildId}.`);
    }
    return;
  }

  console.log(`Registering ${commandDefinitions.length} commands in global scope...`);
  await rest.put(Routes.applicationCommands(config.applicationId), { body: commandDefinitions });
  console.log('Discord commands synced in global scope.');
}
