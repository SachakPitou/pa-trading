<?php
// Any PHP crash is returned as readable JSON (to a logged-in user) instead of a blank/HTML error page.
ini_set('display_errors', '0'); header('Content-Type: application/json');
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    error_log('news_api fatal: '.$e['message'].' in '.$e['file'].':'.$e['line']);
    if (!headers_sent()) { http_response_code(500); header('Content-Type: application/json'); }
    echo json_encode(['error'=>'PHP error'.(!empty($_SESSION['uid']) ? ': '.$e['message'].' ('.basename($e['file']).':'.$e['line'].')' : '')]);
  }
});
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/newsai.php';
$u = user(); if (!$u) out(['error'=>'auth'], 401);
$a = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') check_csrf();
function news_state(): array {
  $t = cache_get('newsai_try', 10**9);
  return ['enabled'=>ai_enabled(), 'now'=>time(), 'events'=>newsai_events(), 'saved'=>newsai_saved(), 'cooldown'=>newsai_cooldown(),
    'queued'=>newsai_forced(), 'last_error'=>($t && empty($t['ok']) && !empty($t['err'])) ? $t['err'] : null];
}
try {
  switch ($a) {
    case 'state':   out(news_state());
    case 'refresh':
      if (!ai_enabled()) out(['error'=>'AI is not enabled. Set ai_key in config.php.'], 422);
      if (newsai_forced()) out(['error'=>'A refresh is already queued. The scanner picks it up within about 5 minutes.'], 429);
      if ($w = newsai_cooldown()) out(['error'=>'Please wait '.ceil($w/60).' more minute(s) before refreshing again (this protects your AI credits).'], 429);
      newsai_queue(); out(news_state());
    default: out(['error'=>'unknown action'], 404);
  }
} catch (Throwable $e) { out(['error'=>'Could not complete the request.'], 500); }