<?php
// Run every 5 minutes:  */5 * * * * php /path/to/public/cron.php
// or via URL:           https://yourdomain.com/cron.php?key=YOUR_CRON_KEY
require __DIR__.'/../lib/bootstrap.php';
if (PHP_SAPI !== 'cli' && !hash_equals((string)cfg('cron_key'), (string)($_GET['key'] ?? ''))) { http_response_code(403); exit('forbidden'); }
$t = microtime(true);
try { $res = scan(); } catch (Throwable $e) { $res = ['error'=>'Scanner crashed: '.$e->getMessage()]; }
scan_log_write($res, (int)round((microtime(true)-$t)*1000));   // execution history for the health card
echo json_encode($res), "\n";