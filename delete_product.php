<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    //echo $id;

    // Check if product exists in sales_items table
    $check_sales = $conn->prepare("SELECT id FROM sales_items WHERE product_id = ?");
    $check_sales->bind_param("i", $id);
    $check_sales->execute();
    $check_sales->store_result();

    // Check if product exists in purchase_items table (assuming you have a purchase_items table)
    $check_purchase = $conn->prepare("SELECT id FROM purchase_items WHERE product_id = ?");
    $check_purchase->bind_param("i", $id);
    $check_purchase->execute();
    $check_purchase->store_result();

    $sales_count = $check_sales->num_rows;
    $purchase_count = $check_purchase->num_rows;

    if ($sales_count > 0 || $purchase_count > 0) {
        $message = "This Product cannot be deleted because it is linked to:";
        if ($sales_count > 0) {
            $message .= "\n- $sales_count Sales entry/entries";
             echo $id;
             echo "<script>alert('This Product is linked to sales entries. Cannot delete.'); window.location.href='view_products.php';</script>";
        }
        if ($purchase_count > 0) {
            $message .= "\n- $purchase_count Purchase entry/entries";
             echo $id;
             echo "<script>alert('This Product is linked to Purchase entries. Cannot delete.'); window.location.href='view_products.php';</script>";
        }
        echo "<script>
                alert('$message');
               
              </script>";
        exit();
    }

    $check_sales->close();
    $check_purchase->close();

    // If not linked, show confirm and delete
    echo "<script>
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = 'delete_product_confirm.php?id=$id';
            } else {
                window.location.href = 'view_products.php';
            }
          </script>";
} else {
    header("Location: view_products.php");
}
$conn->close();
?>