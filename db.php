<?php
declare(strict_types=1);

// XAMPP defaults:
// host: localhost, user: root, pass: (empty), port: 3306
// If your setup differs, update below.

$DB_HOST = 'localhost';
$DB_NAME = 'food_delivery';
$DB_USER = 'root';
$DB_PASS = '';
$DB_PORT = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli {
  static $conn = null;
  if ($conn instanceof mysqli) return $conn;

  global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_PORT;
  $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
  $conn->set_charset('utf8mb4');
  return $conn;
}

function json_response(array $payload, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || trim($raw) === '') return [];
  $data = json_decode($raw, true);
  if (!is_array($data)) json_response(['ok' => false, 'error' => 'Invalid JSON body'], 400);
  return $data;
}

