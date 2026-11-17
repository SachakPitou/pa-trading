<?php
/** Central access rules. To change who can see what, edit ACCESS_FEATURES. Levels: guest < user < admin. */
const ACCESS_FEATURES = [
  'chart'=>'guest', 'price'=>'guest', 'news'=>'guest',
  'signals'=>'user', 'ai'=>'user', 'performance'=>'user', 'journal'=>'user',
  'risk'=>'user', 'calendar'=>'user', 'alerts'=>'user', 'health'=>'user',
];
/** Features that will need a paid plan once config 'billing_enabled' is true (ignored while it is false). */
const ACCESS_PAID = ['signals','ai','performance','journal','risk','calendar','alerts','health'];

function level(): string { $u = user(); return !$u ? 'guest' : (($u['role'] ?? 'user') === 'admin' ? 'admin' : 'user'); }

function has_active_plan(): bool {
  if (!cfg('billing_enabled')) return true;            // billing off = everyone signed in is fine
  $u = user(); if (!$u) return false; if (is_admin()) return true;
  $s = db()->prepare("SELECT 1 FROM subscriptions WHERE user_id=? AND status='active' AND (current_period_end IS NULL OR current_period_end>NOW()) LIMIT 1");
  $s->execute([$u['id']]); return (bool)$s->fetchColumn();
}

function can(string $feature): bool {
  $rank = ['guest'=>0,'user'=>1,'admin'=>2];
  $need = ACCESS_FEATURES[$feature] ?? 'admin';         // unknown feature = admin only (safe default)
  if ($rank[level()] < $rank[$need]) return false;
  if (in_array($feature, ACCESS_PAID, true) && !has_active_plan()) return false;
  return true;
}

/** Where to send a user right after a successful sign in, before the app. null = straight in.
 *  FUTURE (billing): return 'billing.php' when cfg('billing_enabled') && !has_active_plan()
 *  so new users must add a payment method (card / ABA) first. */
function gate_redirect(array $u): ?string { return null; }

function require_feature(string $f): void {            // for pages
  if (can($f)) return;
  if (!user()) { header('Location: login.php'); exit; }
  if (!has_active_plan()) { header('Location: billing.php'); exit; }
  http_response_code(403); exit('Forbidden');
}
function api_require(string $f): void {                // for JSON endpoints
  if (can($f)) return;
  if (!user()) out(['error'=>'auth','locked'=>$f], 401);
  if (!has_active_plan()) out(['error'=>'payment_required','locked'=>$f], 402);
  out(['error'=>'forbidden'], 403);
}