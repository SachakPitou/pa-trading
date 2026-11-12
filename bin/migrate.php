<?php
// php bin/migrate.php            -> apply every migrations/*.sql that has not been applied yet (in filename order)
// php bin/migrate.php --status   -> only list applied / pending
// Add a change by creating the next file, e.g. migrations/002_add_something.sql. Never edit an applied file.
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';

$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations(name VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$done  = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/../migrations/*.sql') ?: [];
sort($files);
$statusOnly = in_array('--status', $argv, true);
$pending = 0;

foreach ($files as $f) {
  $name = basename($f);
  if (in_array($name, $done, true)) { echo "  applied  $name\n"; continue; }
  $pending++;
  if ($statusOnly) { echo "  PENDING  $name\n"; continue; }
  $sql = preg_replace('/^\s*--.*$/m', '', (string)file_get_contents($f));
  foreach (preg_split('/;\s*(?:\r?\n|$)/', (string)$sql) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt !== '') $pdo->exec($stmt);          // PDO is in exception mode: a bad statement stops the run
  }
  $pdo->prepare('INSERT INTO schema_migrations(name) VALUES(?)')->execute([$name]);
  echo "  APPLIED  $name\n";
}
echo $pending ? ($statusOnly ? "$pending pending\n" : "Done: $pending migration(s) applied\n") : "Database is up to date\n";
