<?php
session_start();
include "db.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Optional: Add confirmation message or check if user exists before deleting
$check = $conn->query("SELECT username FROM users WHERE id=$id");
if($check->num_rows > 0) {
    $user = $check->fetch_assoc();
    $conn->query("DELETE FROM users WHERE id=$id");
    $_SESSION['message'] = "User '" . $user['username'] . "' deleted successfully";
} else {
    $_SESSION['error'] = "User not found";
}

header("Location: users.php");
exit();
?>