<?php
// Strategy settings tracked in git (no secrets here). They override any 'strategy' block inside config.php.
// Times below are PHNOM PENH time (UTC+7); the code converts them to UTC itself.
//
//   local_from 7, local_to 4  ->  07:00 AM to 04:00 AM next day  = Asia + London + New York (current default)
//   local_from 14, local_to 4 ->  the old window (London + New York only)
//   local_from 7, local_to 7  ->  full 24 hours, Monday-Friday (by UTC day)
//
// Test any change locally first:  php backtest.php sessions   (compares each session separately)
return [
  'tz_offset'  => 7,
  'local_from' => 7,
  'local_to'   => 4,
];
