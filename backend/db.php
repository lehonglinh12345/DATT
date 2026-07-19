<?php

// Database connection helper for XAMPP / local MySQL
// Copy this file into your project and include it where you need database access.

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'ngoc_anh_duong';
$DB_CHARSET = 'utf8mb4';

$database = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($database->connect_errno) {
    die('Database connection failed: ' . $database->connect_error);
}

$database->set_charset($DB_CHARSET);

/**
 * Execute a prepared statement and return result set.
 * @param string $sql
 * @param string|null $types
 * @param array|null $params
 * @return mysqli_result|bool
 */
function db_query(string $sql, ?string $types = null, ?array $params = null)
{
    global $database;

    $stmt = $database->prepare($sql);
    if (!$stmt) {
        trigger_error('Database prepare failed: ' . $database->error, E_USER_ERROR);
        return false;
    }

    if ($types !== null && $params !== null) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        trigger_error('Database execute failed: ' . $stmt->error, E_USER_ERROR);
        return false;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows >= 0;
    }

    $stmt->close();
    return $result;
}

/**
 * Escape a string for safe HTML output.
 * @param string $text
 * @return string
 */
function h(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Example usage:
// require_once __DIR__ . '/db.php';
// $result = db_query('SELECT * FROM products WHERE product_key = ?', 's', ['tang-luc-x3']);
// $product = $result ? $result->fetch_assoc() : null;

?>