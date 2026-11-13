<?php
// AI Market Analyst: sends a qualifying setup + indicator snapshot to Google Gemini and stores a structured review.
// It only EXPLAINS the signal. It never changes entry/SL/TP and never blocks a signal.

function ai_enabled(): bool { return (string)cfg('ai_key') !== '' && cfg('ai_enabled') !== false; }

/** Build the data snapshot sent to the model (all numbers come from the scanner, nothing is invented). */
function ai_context(array $s, array $P, int $i, array $HP, int $hi): array {
  $e21=$P['e21'][$i]; $e50=$P['e50'][$i];
  $hc=$HP['c'][$hi]; $h21=$HP['e21'][$hi]; $h50=$HP['e50'][$hi];
  $ht=($hc>$h50&&$h21>$h50)?'Bullish':(($hc<$h50&&$h21<$h50)?'Bearish':'Neutral');
  return [
    'symbol'=>cfg('symbol'), 'timeframe'=>cfg('interval'), 'direction'=>$s['side'],
    'entry'=>round($s['entry'],2), 'stop_loss'=>round($s['sl'],2),
    'tp1'=>round($s['tp1'],2), 'tp2'=>round($s['tp2'],2),
    'risk_reward_tp1'=>round((float)$s['rr'],2), 'strategy_score'=>$s['score'], 'trigger_and_reasons'=>$s['why'],
    'adx'=>round($P['adx'][$i]), 'rsi'=>round($P['rsi'][$i]), 'atr'=>round($P['atr'][$i],2),
    'ema21_vs_ema50'=>$e21>$e50?'EMA21 above EMA50':'EMA21 below EMA50',
    'price_vs_ema50'=>$P['c'][$i]>$e50?'price above EMA50':'price below EMA50',
    'macd'=>$P['mh'][$i]>0?'Bullish':'Bearish', 'macd_histogram'=>round($P['mh'][$i],3),
    '1h_trend'=>$ht, 'session'=>sess_label((int)gmdate('G')), 'next_news'=>next_news(),
  ];
}

/** Calls the Google Gemini API. Returns the parsed review or null (reason in $GLOBALS['ai_error']). */
function ai_model(): string { return (string)cfg('ai_model') ?: 'gemini-3.8-flash'; }
function ai_analyze(array $ctx): ?array {
  $sys = "You are a cautious intraday gold (XAU/USD) market analyst reviewing a signal produced by a rules-based scanner. "
       . "Use ONLY the data provided. Do not invent prices, news or indicators. Be concise and specific, cite the given numbers. "
       . "This is a review of the setup, not financial advice. Reply with a single JSON object and nothing else (no markdown), with keys: "
       . "\"summary\" (string, max 2 sentences on the market condition), "
       . "\"why_valid\" (array of 2-4 short strings), "
       . "\"weaknesses\" (array of 1-4 short strings), "
       . "\"conflicts\" (array of short strings naming indicators that disagree with the direction; empty array if none), "
       . "\"invalidation\" (array of 2-3 concrete conditions that would invalidate the setup, using the given price levels).";
  $body = json_encode([
    'systemInstruction'=>['parts'=>[['text'=>$sys]]],
    'contents'=>[['role'=>'user','parts'=>[['text'=>"Review this setup:\n".json_encode($ctx, JSON_PRETTY_PRINT)]]]],
    'generationConfig'=>['responseMimeType'=>'application/json','maxOutputTokens'=>2048,'temperature'=>0.3],
  ]);
  $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode(ai_model()).':generateContent');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>1, CURLOPT_TIMEOUT=>40, CURLOPT_POST=>1, CURLOPT_POSTFIELDS=>$body,
    CURLOPT_HTTPHEADER=>['content-type: application/json','x-goog-api-key: '.cfg('ai_key')]]);
  $raw = curl_exec($ch);
  if ($raw === false) { $GLOBALS['ai_error'] = 'Network error: '.curl_error($ch); return null; }
  $d = json_decode($raw, true);
  if (!empty($d['error'])) { $GLOBALS['ai_error'] = $d['error']['message'] ?? 'AI API error'; return null; }
  $txt = '';
  foreach (($d['candidates'][0]['content']['parts'] ?? []) as $b) if (empty($b['thought']) && isset($b['text'])) $txt .= $b['text'];
  if ($txt === '') { $GLOBALS['ai_error'] = 'AI returned no text'.(!empty($d['promptFeedback']['blockReason']) ? ' (blocked: '.$d['promptFeedback']['blockReason'].')' : ''); return null; }
  $txt = trim(preg_replace('/^```(?:json)?|```$/m', '', $txt));
  $j = json_decode($txt, true);
  if (!is_array($j) || empty($j['summary'])) { $GLOBALS['ai_error'] = 'AI returned an unreadable answer'; return null; }
  $list = fn($k) => array_values(array_filter(array_map(fn($v)=>mb_substr(trim((string)$v),0,300), (array)($j[$k] ?? []))));
  return ['summary'=>mb_substr((string)$j['summary'],0,500), 'why_valid'=>$list('why_valid'), 'weaknesses'=>$list('weaknesses'),
          'conflicts'=>$list('conflicts'), 'invalidation'=>$list('invalidation')];
}

/** Analyze + save for one signal. Never throws: a failed review must not break the scanner. */
function ai_run(int $signalId, array $s, array $P, int $i, array $HP, int $hi): ?array {
  if (!ai_enabled()) return null;
  try {
    $a = ai_analyze(ai_context($s, $P, $i, $HP, $hi));
    if (!$a) return null;
    db()->prepare('REPLACE INTO signal_ai(signal_id,analysis,model) VALUES(?,?,?)')
       ->execute([$signalId, json_encode($a), ai_model()]);
    return $a;
  } catch (Throwable $e) { $GLOBALS['ai_error'] = $e->getMessage(); return null; }
}

/** Short text version for the Telegram / email alert. */
function ai_alert_text(?array $a): string {
  if (!$a) return '';
  $t = "\n\nAI review: ".$a['summary'];
  if ($a['weaknesses']) $t .= "\nRisk: ".$a['weaknesses'][0];
  if ($a['invalidation']) $t .= "\nInvalid if: ".$a['invalidation'][0];
  return $t;
}