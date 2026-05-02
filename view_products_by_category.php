<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$category_name = isset($_GET['category']) ? $_GET['category'] : '';

if (empty($category_name)) {
    echo "<script>alert('No category specified!'); window.location.href='view_categories.php';</script>";
    exit();
}

// Fetch products in this category
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY pro_name ASC");
$stmt->bind_param("s", $category_name);
$stmt->execute();
$products = $stmt->get_result();

$username = $_SESSION['username'];
$userStmt = $conn->prepare("SELECT lastlogin FROM users WHERE username = ?");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$resultUser = $userStmt->get_result();
$userData = $resultUser->fetch_assoc();
$userStmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products in <?php echo htmlspecialchars($category_name); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            font-size: 28px;
        }

        h2 {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #007bff;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: #545b62;
            text-decoration: none;
        }

        .stock-instock {
            color: #28a745;
            font-weight: bold;
        }

        .stock-outstock {
            color: #dc3545;
            font-weight: bold;
        }

        .category-header {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .category-header strong {
            color: #007bff;
            font-size: 24px;
        }

        .no-products {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>
    
    <div class="container">
       

        <div class="category-header">
            <h1> Products in <strong>"<?php echo htmlspecialchars($category_name); ?>"</strong> Category</h1>
        </div>
        
        <?php if($products->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Purchase Price</th>
                        <th>Price</th>
                        <th>GST %</th>
                        <th>Stock Status</th>
                        <th>Stock Qty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sno = 1; ?>
                    <?php while($row = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $sno++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['pro_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>₹<?php echo number_format($row['purchaseprice'], 2); ?></td>
                            <td>₹<?php echo number_format($row['regularprice'], 2); ?></td>
                            <td><?php echo $row['taxtype']; ?>%</td>
                            <td>
                                <?php if($row['initialqty'] > 0): ?>
                                    <span class="stock-instock"> In Stock</span>
                                <?php else: ?>
                                    <span class="stock-outstock"> Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if($row['initialqty'] > 0) {
                                    echo $row['initialqty']; 
                                } else {
                                    echo "Unlimited";
                                }
                                ?>
                            </td>
                            <td class="action-links">
                                <a href="edit_product.php?id=<?php echo $row['pid']; ?>" style="color:#ffc107;"> Edit</a> | 
                                <a href="delete_product.php?id=<?php echo $row['pid']; ?>" style="color:#dc3545;" onclick="return confirm('Delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-products">
                 No products found in this category<br><br>
                <a href="products.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Add New Product</a>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="view_categories.php" class="back-link">← Back to Categories</a>
        </div>
    </div>
</body>
</html>