<?php
session_start();
include "db.php"; 

// If not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Fetch categories from database for dropdown
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");

$message = "";
$messageClass = "";

if (isset($_POST['submit'])) {

    $name          = $_POST['pro_name'];
    $category      = $_POST['category']; 
    $purchaseprice = $_POST['purchaseprice'];
    $regularprice  = $_POST['regularprice'];
    $hsncode       = $_POST['hsncode'];
    $qty           = $_POST['qty'];
    $expirydate    = !empty($_POST['expirydate']) ? $_POST['expirydate'] : null;
    $unitprice     = $_POST['regularprice'];
    $initialqty    = $_POST['qty'];
    $unit_type     = $_POST['unit_type'];
    $tax_type      = $_POST['tax_type'];

    // Check if product already exists
    $check = $conn->prepare("SELECT pid FROM products WHERE pro_name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Product already exists!');</script>";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO products 
            (pro_name, category, unittype, unitprice, purchaseprice, regularprice, taxtype, hsncode, expirydate, initialqty) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssdddsssi",
            $name,
            $category,
            $unit_type,
            $unitprice,
            $purchaseprice,
            $regularprice,
            $tax_type,
            $hsncode,
            $expirydate,
            $initialqty
        );

        if ($stmt->execute()) {
            echo "<script>alert('Product added successfully!'); window.location.href='view_products.php';</script>";
        } else {
            echo "<script>alert(' An error occurred!');</script>";
        }
        $stmt->close();
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 28px;
        }

        label {
            font-weight: bold;
            margin-top: 15px;
            display: block;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .button-group a {
            flex: 1;
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-view {
            background: #28a745;
            color: white;
        }

        .btn-view:hover {
            background: #218838;
        }

        .btn-logout {
            background: #dc3545;
            color: white;
        }

        .btn-logout:hover {
            background: #c82333;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .note {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1>Add Product</h1>

        <form method="post">
            <label class="required-field">Product Name</label>
            <input type="text" name="pro_name" placeholder="Enter product name" required>

            <label class="required-field">Category</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <?php 
                if ($categories->num_rows > 0) {
                    while($cat = $categories->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($cat['category_name']) . '">' . 
                             htmlspecialchars($cat['category_name']) . '</option>';
                    }
                } else {
                    echo '<option value="">No categories found</option>';
                }
                ?>
            </select>
            <div class="note">Categories are fetched from categories table</div>

            <label>Unit Type</label>
            <select name="unit_type">
                <option value="">-- Select Unit --</option>
                <option value="per_piece">Per Piece</option>
                <option value="per_pack">Per Pack</option>
                <option value="per_kg">Per Kg</option>
                <option value="per_dozen">Per Dozen</option>
                <option value="per_liter">Per Liter</option>
            </select>

            <label>Purchase Price</label>
            <input type="number" step="0.01" name="purchaseprice" placeholder="Enter purchase price">

            <label class="required-field">Regular Price</label>
            <input type="number" step="0.01" name="regularprice" placeholder="Enter regular price" required>

            <label>Tax Type</label>
            <select name="tax_type">
                <option value="">-- Select Tax --</option>
                <?php 
                $gstSlabs = $conn->query("SELECT id, gst_percent FROM gstslab ORDER BY gst_percent ASC"); 
                while ($row = $gstSlabs->fetch_assoc()) { 
                    echo '<option value="' . $row['gst_percent'] . '">' . $row['gst_percent'] . '% GST</option>'; 
                } 
                ?>
            </select>

            <label>HSN Code</label>
            <input type="text" name="hsncode" placeholder="Enter HSN code">

            <label>Initial Quantity</label>
            <input type="number" name="qty" placeholder="Enter quantity">

            <label>Expiry Date</label>
            <input type="date" name="expirydate">

            <button type="submit" name="submit"> Submit Product</button>
        </form>

        <div class="button-group">
            <a href="view_products.php" class="btn-view">BACK</a>
        </div>
    </div>
</body>
</html>