<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE pid = ?"); 
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
header("Location: view_products.php");
$conn->close();
?>