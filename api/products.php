<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function to_int(mixed $value): ?int {
  if ($value === null) return null;
  if (is_int($value)) return $value;
  if (is_string($value) && preg_match('/^\d+$/', $value)) return (int)$value;
  return null;
}

function to_bool_int(mixed $value, int $default = 1): int {
  if ($value === null) return $default;
  if (is_bool($value)) return $value ? 1 : 0;
  if (is_int($value)) return $value ? 1 : 0;
  if (is_string($value)) {
    $v = strtolower(trim($value));
    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) return 1;
    if (in_array($v, ['0', 'false', 'no', 'off'], true)) return 0;
  }
  return $default;
}

function validate_product(array $data, bool $is_update = false): array {
  $name = trim((string)($data['name'] ?? ''));
  $category = trim((string)($data['category'] ?? ''));
  $description = trim((string)($data['description'] ?? ''));
  $price_raw = $data['price'] ?? null;
  $is_available = to_bool_int($data['is_available'] ?? null, 1);

  $errors = [];
  if (!$is_update || array_key_exists('name', $data)) {
    if ($name === '' || mb_strlen($name) > 120) $errors['name'] = 'Name is required (max 120 chars).';
  }
  if (!$is_update || array_key_exists('category', $data)) {
    if ($category === '' || mb_strlen($category) > 80) $errors['category'] = 'Category is required (max 80 chars).';
  }
  if (!$is_update || array_key_exists('price', $data)) {
    if ($price_raw === null || $price_raw === '') $errors['price'] = 'Price is required.';
    if (!isset($errors['price'])) {
      if (!is_numeric($price_raw)) $errors['price'] = 'Price must be a number.';
      else if ((float)$price_raw < 0) $errors['price'] = 'Price must be >= 0.';
    }
  }

  if ($errors) json_response(['ok' => false, 'errors' => $errors], 422);

  return [
    'name' => $name,
    'category' => $category,
    'description' => ($description === '' ? null : $description),
    'price' => ($price_raw === null || $price_raw === '' ? null : (float)$price_raw),
    'is_available' => $is_available,
  ];
}

try {
  $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
  $action = strtolower((string)($_GET['action'] ?? ''));
  $id = to_int($_GET['id'] ?? null);

  $conn = db();

  if ($method === 'GET' && ($action === '' || $action === 'list')) {
    $q = trim((string)($_GET['q'] ?? ''));
    $category = trim((string)($_GET['category'] ?? ''));
    $available = ($_GET['available'] ?? '') === '' ? null : to_bool_int($_GET['available'], 1);

    $sql = "SELECT id, name, category, description, price, is_available, created_at, updated_at
            FROM products WHERE 1=1";
    $types = '';
    $params = [];
    if ($q !== '') {
      $sql .= " AND (name LIKE CONCAT('%', ?, '%') OR category LIKE CONCAT('%', ?, '%'))";
      $types .= 'ss';
      $params[] = $q;
      $params[] = $q;
    }
    if ($category !== '') {
      $sql .= " AND category = ?";
      $types .= 's';
      $params[] = $category;
    }
    if ($available !== null) {
      $sql .= " AND is_available = ?";
      $types .= 'i';
      $params[] = $available;
    }
    $sql .= " ORDER BY id ASC";


    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    json_response(['ok' => true, 'data' => $rows]);
  }

  if ($method === 'GET' && $action === 'categories') {
    $stmt = $conn->prepare("SELECT DISTINCT category FROM products ORDER BY category ASC");
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cats = array_values(array_filter(array_map(static fn($r) => (string)($r['category'] ?? ''), $rows), static fn($c) => $c !== ''));
    json_response(['ok' => true, 'data' => $cats]);
  }

  if ($method === 'GET' && $action === 'get') {
    if ($id === null) json_response(['ok' => false, 'error' => 'Missing id'], 400);
    $stmt = $conn->prepare("SELECT id, name, category, description, price, is_available, created_at, updated_at
                            FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) json_response(['ok' => false, 'error' => 'Not found'], 404);
    json_response(['ok' => true, 'data' => $row]);
  }

  if ($method === 'POST' && ($action === '' || $action === 'create')) {
    $data = read_json_body();
    $p = validate_product($data, false);
    $stmt = $conn->prepare("INSERT INTO products (name, category, description, price, is_available)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param(
      'sssdi',
      $p['name'],
      $p['category'],
      $p['description'],
      $p['price'],
      $p['is_available']
    );
    $stmt->execute();
    json_response(['ok' => true, 'id' => $conn->insert_id], 201);
  }

  if ($method === 'PUT' && ($action === '' || $action === 'update')) {
    if ($id === null) json_response(['ok' => false, 'error' => 'Missing id'], 400);
    $data = read_json_body();
    $p = validate_product($data, true);

    // Build dynamic update for provided fields only
    $set = [];
    $types = '';
    $params = [];

    if (array_key_exists('name', $data)) { $set[] = "name=?"; $types .= 's'; $params[] = $p['name']; }
    if (array_key_exists('category', $data)) { $set[] = "category=?"; $types .= 's'; $params[] = $p['category']; }
    if (array_key_exists('description', $data)) { $set[] = "description=?"; $types .= 's'; $params[] = $p['description']; }
    if (array_key_exists('price', $data)) { $set[] = "price=?"; $types .= 'd'; $params[] = $p['price']; }
    if (array_key_exists('is_available', $data)) { $set[] = "is_available=?"; $types .= 'i'; $params[] = $p['is_available']; }

    if (!$set) json_response(['ok' => false, 'error' => 'No fields to update'], 400);

    $sql = "UPDATE products SET " . implode(', ', $set) . " WHERE id=?";
    $types .= 'i';
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
      // Could be: not found OR data unchanged
      $check = $conn->prepare("SELECT id FROM products WHERE id=?");
      $check->bind_param('i', $id);
      $check->execute();
      $exists = $check->get_result()->fetch_assoc();
      if (!$exists) json_response(['ok' => false, 'error' => 'Not found'], 404);
    }
    json_response(['ok' => true]);
  }

  if ($method === 'DELETE' && ($action === '' || $action === 'delete')) {
    if ($id === null) json_response(['ok' => false, 'error' => 'Missing id'], 400);
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) json_response(['ok' => false, 'error' => 'Not found'], 404);
    json_response(['ok' => true]);
  }

  json_response(['ok' => false, 'error' => 'Unsupported route'], 405);
} catch (Throwable $e) {
  json_response(['ok' => false, 'error' => 'Server error', 'details' => $e->getMessage()], 500);
}
