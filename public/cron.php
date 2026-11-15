<?php
// Run every 5 minutes:  */5 * * * * php /path/to/public/cron.php
// or via URL:           https://yourdomain.com/cron.php?key=YOUR_CRON_KEY
require __DIR__.'/../lib/bootstrap.php'; require_once __DIR__.'/../lib/newsai.php';
if (PHP_SAPI !== 'cli' && !hash_equals((string)cfg('cron_key'), (string)($_GET['key'] ?? ''))) { http_response_code(403); exit('forbidden'); }
@set_time_limit(120);   // the hourly AI news step can take up to a minute
$t = microtime(true);
try { $res = scan(); } catch (Throwable $e) { $res = ['error'=>'Scanner crashed: '.$e->getMessage()]; }
scan_log_write($res, (int)round((microtime(true)-$t)*1000));   // execution history for the health card
// AI market news: only when due (about hourly) or when someone pressed Refresh on the News page. Runs after the scan is logged and can never break it.
try { if (newsai_due() || newsai_forced()) { $n = newsai_run(); $res['news_ai'] = isset($n['error']) ? 'failed: '.$n['error'] : 'updated'; } } catch (Throwable $e) { $res['news_ai'] = 'failed'; }
echo json_encode($res), "\n";