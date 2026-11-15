<?php
// Finds out why requests to Gemini hang. Run:  docker compose exec web php bin/news_test.php
// Sends the same tiny question padded to different sizes (default network settings) and prints what comes back for each.
// If small sizes answer and big ones hang, the network path drops larger uploads (usually Docker/VPN packet size, "MTU").
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/newsai.php';
if (!ai_enabled()) exit("AI is not enabled (ai_key missing in config.php)\n");
$models = array_values(array_unique([newsai_model(), (string)(cfg('news_ai_fallback') ?? 'gemini-flash-latest')]));

function probe(string $model, int $pad): void {
  $text = 'Reply with the single word OK.'.($pad ? ' Ignore the filler that follows: '.str_repeat('lorem ipsum ', intdiv($pad, 12)) : '');
  $body = json_encode(['contents'=>[['role'=>'user', 'parts'=>[['text'=>$text]]]], 'generationConfig'=>['maxOutputTokens'=>20]]);
  $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>20, CURLOPT_POST=>1, CURLOPT_POSTFIELDS=>$body,
    CURLOPT_HTTPHEADER=>['content-type: application/json', 'Expect:', 'x-goog-api-key: '.cfg('ai_key')]]);
  $t = microtime(true); $raw = curl_exec($ch); $sec = microtime(true)-$t;
  if ($raw === false) { printf("  %5d bytes sent: NO ANSWER after %.0fs (%s)\n", strlen($body), $sec, curl_error($ch)); return; }
  $d = json_decode($raw, true);
  printf("  %5d bytes sent: %.1fs  http %s  %s\n", strlen($body), $sec, curl_getinfo($ch, CURLINFO_HTTP_CODE), !empty($d['error']) ? mb_substr($d['error']['message'] ?? '', 0, 70) : 'answered');
}
foreach ($models as $m) { echo "model $m\n"; foreach ([0, 800, 1300, 2000, 4000] as $pad) probe($m, $pad); }
echo "\nHow to read it:\n";
echo " - 'answered' or 'high demand' (503) on a line = Google received the request, so the network is fine for that size.\n";
echo " - Small sizes answer but big ones say 'NO ANSWER' = the network drops bigger uploads (packet-size / MTU problem), not Google overload.\n";
echo " - Every line says NO ANSWER = Google is not answering at all right now, or something blocks this server.\n";