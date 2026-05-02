<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if supplier exists in purchase table
    $check = $conn->prepare("SELECT id FROM purchase WHERE supplier_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('This supplier is linked to purchase entries. Cannot delete.'); window.location.href='supplier_display.php';</script>";
        exit();
    }
    $check->close();

    // If not linked, show confirm and delete
    echo "<script>
            if (confirm('Are you sure?')) {
                window.location.href = 'delete_supplier_confirm.php?id=$id';
            } else {
                window.location.href = 'supplier_display.php';
            }
          </script>";
} else {
    header("Location: supplier_display.php");
}
$conn->close();
?>