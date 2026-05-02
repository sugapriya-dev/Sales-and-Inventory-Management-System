<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize input
$category_name = trim($_POST['category_name'] ?? '');
$category_code = trim($_POST['category_code'] ?? '');

// Validate category name
if (empty($category_name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit();
}

// Check if category already exists
$check_stmt = $conn->prepare("SELECT id FROM categories WHERE category_name = ?");
$check_stmt->bind_param("s", $category_name);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Category already exists']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Insert new category
$stmt = $conn->prepare("INSERT INTO categories (category_name, category_code) VALUES (?, ?)");
$stmt->bind_param("ss", $category_name, $category_code);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>