# XAU/USD Scalp Terminal (PHP + MySQL)

Login · Twelve Data price proxy · signal scanner · journal · Telegram/email alerts · TradingView chart.

## Requirements
PHP 8.0+ (with PDO MySQL and cURL), MySQL/MariaDB, a way to run cron every 5 minutes.

## Setup
1. Create an empty database, then run `php bin/migrate.php` (builds/updates all tables from `migrations/`).
2. Copy `config.example.php` to `config.php` and edit it (DB login, Twelve Data key, Telegram token, mail_from, cron_key). Trading hours and strategy rules are in `strategy.php`.
3. Point your web server's document root at the `public/` folder. Keep `config.php` and `lib/` outside it.
   Quick local test: `php -S localhost:8000 -t public`
4. Open `/login.php?register=1`, create your account, then set `allow_register` to `false`.
5. Schedule the scanner (this generates signals and sends alerts):
   `*/5 * * * * php /full/path/public/cron.php`
   No shell cron? Use your host's URL cron: `https://yourdomain.com/cron.php?key=YOUR_CRON_KEY`
6. Telegram: create a bot with @BotFather, put its token in `config.php`, message the bot once, then enter your chat ID in the dashboard Alerts card and press "Send test".

## How it works
- `lib/market.php` calls Twelve Data server-side and caches responses in MySQL (price 10 s, candles 45 s) to stay inside the free-plan limit.
- `lib/signals.php`: EMA 9/21 cross on the last closed candle, RSI-filtered; SL = 1.5 x ATR, TP1 = 1.5R, TP2 = 3R. Open signals are resolved against later candles (same-candle SL and TP counts as SL).
- Signals are shared by all users; the journal and alert settings are per user.

## Security notes
- Your API key was shared in a chat. Regenerate it in the Twelve Data dashboard and update `config.php`.
- Use HTTPS in production. Passwords are hashed, queries are prepared, POSTs need a CSRF token, logins are throttled (5 failures / 15 min per IP).
- Signals are not financial advice. Backtest and paper-trade first.

## Development and deployment
See `DEVOPS.md`: local Docker test environment, automated tests, one-command deploy and GitHub Actions auto-deploy.
