/**
 * PM2: cd /var/www/mybeeCompany && pm2 start socket-server/ecosystem.config.cjs
 */
module.exports = {
  apps: [
    {
      name: "mybee-socket",
      script: "index.js",
      cwd: __dirname,
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      max_restarts: 10,
      env: {
        NODE_ENV: "production",
      },
    },
  ],
};
