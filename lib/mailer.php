<?php
// Mail helper. With 'mail_debug' => true the email is written to the PHP error log instead of being sent
// (see it with: docker compose logs web). Swap the body of send_mail() for SMTP later without touching callers.
function send_mail(string $to, string $subject, string $body): bool {
  if (cfg('mail_debug')) { error_log("[mail] to=$to | $subject\n$body"); return true; }
  $headers = 'From: '.cfg('mail_from')."\r\nContent-Type: text/plain; charset=utf-8";
  return @mail($to, $subject, $body, $headers);
}

function app_url(): string {
  if (cfg('app_url')) return rtrim((string)cfg('app_url'), '/');
  return (!empty($_SERVER['HTTPS']) ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function send_verification(int $uid, string $email): void {
  $token = bin2hex(random_bytes(32));
  db()->prepare('DELETE FROM email_tokens WHERE user_id=?')->execute([$uid]);
  db()->prepare('INSERT INTO email_tokens(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))')
     ->execute([$uid, hash('sha256', $token)]);
  send_mail($email, 'Confirm your email - XAU Terminal',
    "Confirm your email to activate your account (link valid 24 hours):\n\n".app_url().'/verify.php?token='.$token."\n\nIf you didn't sign up, ignore this email.");
}