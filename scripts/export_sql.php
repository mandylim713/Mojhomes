<?php
$dbPath = dirname(__DIR__) . '/storage/mojhometouch.sqlite';
$outPath = dirname(__DIR__) . '/docker/seed.sql';

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$sql = "-- Mojhometouch seed data (auto-generated " . date('Y-m-d H:i:s') . ")\n";
$sql .= "PRAGMA journal_mode=WAL;\n\n";

foreach ($tables as $t) {
    $create = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$t'")->fetchColumn();
    $sql .= "-- Table: $t\n";
    $sql .= $create . ";\n";
    $rows = $db->query("SELECT * FROM $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $cols = implode(', ', array_map(fn($c) => "\"$c\"", array_keys($row)));
        $vals = implode(', ', array_map(function($v) {
            if (is_null($v)) return 'NULL';
            return "'" . str_replace("'", "''", $v) . "'";
        }, array_values($row)));
        $sql .= "INSERT OR IGNORE INTO \"$t\" ($cols) VALUES ($vals);\n";
    }
    $sql .= "\n";
}

file_put_contents($outPath, $sql);
echo "Exported " . count($tables) . " tables, " . strlen($sql) . " bytes to docker/seed.sql\n";

