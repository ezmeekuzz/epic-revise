<?php
// Isolated MySQL verification: never run database test migrations against the app database.
require_once dirname(__DIR__, 2) . '/vendor/codeigniter4/framework/system/Test/bootstrap.php';
$databaseConfig = config('Database');
$connection = $databaseConfig->default;
$connection['database'] = '';
$server = \Config\Database::connect($connection, false);
$testName = 'epic_review_test_' . getmypid();
$server->query('CREATE DATABASE ' . $server->protectIdentifiers($testName));
foreach (['hostname', 'username', 'password', 'port'] as $key) {
    $value = (string) $connection[$key];
    putenv('database.tests.' . $key . '=' . $value);
    $_ENV['database.tests.' . $key] = $_SERVER['database.tests.' . $key] = $value;
}
foreach (['database' => $testName, 'DBDriver' => 'MySQLi', 'DBPrefix' => 'test_'] as $key => $value) {
    putenv('database.tests.' . $key . '=' . $value);
    $_ENV['database.tests.' . $key] = $_SERVER['database.tests.' . $key] = $value;
}
$databaseConfig->tests = [...$databaseConfig->tests, ...$connection, 'database' => $testName, 'DBDriver' => 'MySQLi', 'DBPrefix' => 'test_'];
