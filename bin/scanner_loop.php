<?php
// Dev helper: runs the scanner every 5 minutes. Stop it with Ctrl+C (or by restarting the container).
// On the VPS use a real cron job instead: */5 * * * * php /path/to/public/cron.php
while (true) {
  passthru('php '.escapeshellarg(__DIR__.'/../public/cron.php'));
  echo '['.date('H:i:s')."] scan done\n";
  sleep(300);
}