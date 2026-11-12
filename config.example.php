<?php
// Copy this file to config.php (config.php is NOT committed - it holds secrets).
// Env vars let the same file work in Docker and CI; on the VPS you can simply type real values.
$e = fn(string $k, string $d = '') => (getenv($k) !== false && getenv($k) !== '') ? getenv($k) : $d;

return [
  'db_host'  => $e('DB_HOST', '127.0.0.1'),
  'db_name'  => $e('DB_NAME', 'pa_trading'),
  'db_user'  => $e('DB_USER', 'root'),
  'db_pass'  => $e('DB_PASS', ''),

  'td_key'   => $e('TD_KEY', 'YOUR_TWELVE_DATA_KEY'),   // use a separate key for local testing
  'symbol'   => 'XAU/USD',
  'interval' => '5min',
  'daily_budget' => 600,                                 // stop calling Twelve Data after this many requests per UTC day

  'tg_token' => $e('TG_TOKEN', ''),                      // Telegram bot token (use a separate dev bot locally)
  'mail_from' => 'alerts@example.com',
  'cron_key'  => $e('CRON_KEY', 'change-me'),            // for cron.php?key=... URL cron
  'allow_register' => true,                              // set false after creating your account

  // Trading window and rules live in strategy.php (tracked in git), not here.
];
