module.exports = {
  apps: [
    {
      name: 'lyva-bot',
      cwd: __dirname,
      script: './bot/index.js',
      interpreter: 'node',
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 3000,
      env: {
        NODE_ENV: 'production',
      },
    },
  ],
};
