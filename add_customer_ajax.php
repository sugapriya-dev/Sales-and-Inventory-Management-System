<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$city = trim($data['city'] ?? '');
$state = trim($data['state'] ?? '');
$aadhar = trim($data['aadhar'] ?? '');

if(empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Name and Phone are required']);
    exit();
}

// Check if customer already exists with same phone number
$check = $conn->prepare("SELECT id, customername, phoneno FROM customers WHERE phoneno = ?");
$check->bind_param("s", $phone);
$check->execute();
$result = $check->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'id' => $row['id'],
        'customername' => $row['customername'],
        'phone' => $row['phoneno'],
        'message' => 'Customer already exists'
    ]);
    $check->close();
    exit();
}
$check->close();

// Insert new customer
$stmt = $conn->prepare("INSERT INTO customers (customername, phoneno, city, state, aadhaarno) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $phone, $city, $state, $aadhar);

if($stmt->execute()) {
    $new_id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'id' => $new_id,
        'customername' => $name,
        'phone' => $phone,
        'message' => 'Customer added successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>