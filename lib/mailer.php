<?php
// Mail helper. Priority: mail_debug (log only) > SMTP (if smtp_host is set) > PHP mail().
function send_mail(string $to, string $subject, string $body): bool {
  $to = str_replace(["\r", "\n"], '', $to);
  if (cfg('mail_debug')) { error_log("[mail] to=$to | $subject\n$body"); return true; }
  if (cfg('smtp_host')) return smtp_send($to, $subject, $body);
  $headers = 'From: '.cfg('mail_from')."\r\nContent-Type: text/plain; charset=utf-8";
  return @mail($to, $subject, $body, $headers);
}

function smtp_send(string $to, string $subject, string $body): bool {
  $host = (string)cfg('smtp_host'); $port = (int)(cfg('smtp_port') ?: 587); $from = (string)cfg('mail_from');
  $fp = @stream_socket_client("tcp://$host:$port", $en, $es, 15);
  if (!$fp) { error_log("[smtp] connect failed: $es"); return false; }
  stream_set_timeout($fp, 15);
  $rd = function () use ($fp) { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $r; };
  $cmd = function (string $c, string $ok) use ($fp, $rd) {
    fwrite($fp, $c."\r\n"); $r = $rd();
    if (strpos($r, $ok) !== 0) throw new RuntimeException(trim($r));
  };
  try {
    $r = $rd(); if (strpos($r, '220') !== 0) throw new RuntimeException(trim($r));
    $me = gethostname() ?: 'localhost';
    $cmd("EHLO $me", '250'); $cmd('STARTTLS', '220');
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS failed');
    $cmd("EHLO $me", '250');
    $cmd('AUTH LOGIN', '334'); $cmd(base64_encode((string)cfg('smtp_user')), '334'); $cmd(base64_encode((string)cfg('smtp_pass')), '235');
    $cmd("MAIL FROM:<$from>", '250'); $cmd("RCPT TO:<$to>", '250'); $cmd('DATA', '354');
    $body = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
    $msg = "From: XAU Terminal <$from>\r\nTo: <$to>\r\nSubject: $subject\r\nDate: ".date('r')."\r\nMessage-ID: <".bin2hex(random_bytes(8))."@".$me.">\r\n"
         ."MIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".str_replace("\n", "\r\n", $body);
    $cmd($msg."\r\n.", '250');
    fwrite($fp, "QUIT\r\n"); return true;
  } catch (Throwable $e) { error_log('[smtp] '.$e->getMessage()); return false; }
  finally { fclose($fp); }
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