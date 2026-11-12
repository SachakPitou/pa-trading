# DevOps guide: test on your PC first, then deploy to the VPS

```
 your PC (Docker + tests)  --git push-->  GitHub (CI: lint + tests)  --auto-->  VPS (rsync + DB migrate)
```

## What lives where
| Thing | Where | In git? |
|---|---|---|
| Code, `strategy.php` (trading hours/rules), `migrations/*.sql` | repo | yes |
| `config.php` (DB password, API keys, Telegram token) | one copy on your PC, one on the VPS | **never** |
| `data/` (backtest history) | your PC only | no |

Trading hours are in `strategy.php`, in **Phnom Penh time** (UTC+7). Current value: 07:00 AM to 04:00 AM (Asia + London + New York).

## A. One-time setup on your PC
1. Install Docker Desktop and Git.
2. In the project folder:
   ```bash
   git init && git add . && git commit -m "first commit"
   cp config.example.php config.php        # then put a DEV Twelve Data key in it (not your live key)
   docker compose up -d --build
   ```
3. Open http://localhost:8000/login.php?register=1 and create a local account. This database is separate from the VPS, so alerts stay off unless you turn them on locally.

## B. Daily loop (change -> test -> push)
```bash
docker compose exec web php tests/run.php            # fast offline tests (no API credits)
docker compose exec web php fetch_history.php        # once: downloads ~100 days of candles into data/ (uses API credits)
docker compose exec web php backtest.php sessions    # compares Asia / London / overlap / New York separately
docker compose exec web php diag.php                 # live check of feed + current filters
docker compose exec web php public/cron.php          # run one scan by hand
```
Change `strategy.php`, re-run the backtest, and only push when you like the result. Then:
```bash
git add -A && git commit -m "Add Asian session" && git push
```
GitHub runs the tests on PHP 8.3 and 8.5. If they pass and the branch is `main`, it deploys by itself. Better habit: work on a branch, open a pull request, let CI check it, then merge.

No GitHub? Deploy from your PC instead: `./deploy.sh --dry-run` (shows what would change), then `./deploy.sh`.

## C. One-time setup on the VPS
```bash
# 1) a deploy user (do not deploy as root)
adduser --disabled-password --gecos "" deploy
# 2) give it your deploy public key (made on your PC with: ssh-keygen -t ed25519 -f deploy_key -N "")
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
echo "PASTE deploy_key.pub CONTENT" >> /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys && chmod 600 /home/deploy/.ssh/authorized_keys
# 3) ownership: deploy writes, www-data (web + cron) reads
chown -R deploy:www-data /var/www/pa-trading
find /var/www/pa-trading -type d -exec chmod 2755 {} +
find /var/www/pa-trading -type f -exec chmod 644 {} +
chmod 640 /var/www/pa-trading/config.php
apt install -y rsync
```
Your existing `config.php` on the VPS stays as it is. Deploys never touch it. If it has a `'strategy' => [...]` block, you can delete that block later: `strategy.php` now wins anyway.

The scanner cron stays the same (as `www-data`):
`*/5 * * * * php /var/www/pa-trading/public/cron.php >> /var/log/pa-cron.log 2>&1`

## D. GitHub secrets (Settings -> Secrets and variables -> Actions)
| Secret | Value |
|---|---|
| `VPS_HOST` | VPS IP or hostname |
| `VPS_USER` | `deploy` |
| `VPS_PATH` | `/var/www/pa-trading` |
| `VPS_PORT` | `22` (optional) |
| `VPS_SSH_KEY` | content of the **private** file `deploy_key` |
| `VPS_KNOWN_HOSTS` | output of `ssh-keyscan -H YOUR_VPS_IP` |
| `SITE_URL` | optional, e.g. `https://yourdomain.com` (post-deploy check) |

## E. First deploy: be careful
`rsync --delete` removes files on the VPS that are not in git (except `config.php` and `data/`). So the first time, run `./deploy.sh --dry-run` and read the list: lines starting with `*deleting` are files that would be removed.

## F. Rules that keep it safe
- **Database changes** = a new file `migrations/002_name.sql` (never edit an old one). Deploy applies it automatically. Migrations only go forward, so check your nightly backup exists before risky ones.
- **Never set `PA_TESTS_DB=1` on the VPS.** The DB tests insert and delete rows. Docker and CI use throw-away databases.
- **Rollback:** `git revert <commit>` and push. CI redeploys the old code.
- Use a separate Twelve Data key and a separate Telegram bot for local testing, so tests never use your live credits or alert your live chat.
- The Twelve Data key was shared in chat earlier: regenerate it and update `config.php` on the VPS.
