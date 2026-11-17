<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/mailer.php';
$to = $argv[1] ?? exit("usage: php bin/mail_test.php you@example.com\n");
echo send_mail($to, 'SMTP test - XAU Terminal', "If you can read this, SMTP works.\n") ? "sent OK\n" : "FAILED (see error above)\n";