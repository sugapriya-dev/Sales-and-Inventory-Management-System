<?php
session_start();
include "db.php"; 

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Fetch categories with product count using LEFT JOIN
$res = $conn->query("
    SELECT c.*, COUNT(p.pid) as product_count 
    FROM categories c
    LEFT JOIN products p ON c.category_name = p.category
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

$username = $_SESSION['username'];

$stmt = $conn->prepare("SELECT lastlogin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$resultUser = $stmt->get_result();
$userData = $resultUser->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Category List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .container {
            width: 90%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
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

        a {
            text-decoration: none;
            color: #007bff;
            margin: 0 5px;
        }

        a:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        .top-links {
            text-align: right;
            margin-bottom: 20px;
        }

        .top-links a {
            display: inline-block;
            padding: 8px 15px;
            margin-left: 10px;
            background: #28a745;
            color: white;
            border-radius: 4px;
        }

        .top-links a:hover {
            background: #218838;
            text-decoration: none;
        }

        .top-links a.logout {
            background: #dc3545;
        }

        .top-links a.logout:hover {
            background: #c82333;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .action-links {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .action-links a {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 13px;
        }

        .action-links a.edit {
            background: #ffc107;
            color: #333;
        }

        .action-links a.delete {
            background: #dc3545;
            color: white;
        }

        .product-count-link {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .product-count-link:hover {
            transform: scale(1.05);
            text-decoration: none;
        }

        .count-positive {
            background: #007bff;
            color: white;
        }

        .count-positive:hover {
            background: #0056b3;
            color: white;
        }

        .count-zero {
            background: #6c757d;
            color: white;
            cursor: default;
            pointer-events: none;
            opacity: 0.6;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        /* Add Category Button Styles */
        .add-category-btn {
            display: inline-block;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 10px 24px;
            margin-bottom: 20px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: none;
            cursor: pointer;
        }

        .add-category-btn:hover {
            background: linear-gradient(135deg, #218838, #1ba87e);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            text-decoration: none;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 25px;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 22px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            transition: 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 5px rgba(40,167,69,0.3);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            display: none;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
    
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <h1 style="margin: 0;">Category List</h1>
            <button class="add-category-btn" onclick="openModal()">+ Add New Category</button>
        </div>

        <div id="message" class="message"></div>

        <div class="top-links">
            <!-- Keeping your existing top-links structure -->
        </div>

        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Category Name</th>
                    <th>Category Code</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($res->num_rows > 0): ?>
                    <?php $sno = 1; ?>
                    <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $sno++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['category_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['category_code']); ?></td>
                            <td>
                                <?php if($row['product_count'] > 0): ?>
                                    <a href="view_products_by_category.php?category=<?php echo urlencode($row['category_name']); ?>" 
                                       class="product-count-link count-positive">
                                        <?php echo $row['product_count']; ?> 
                                        <?php echo $row['product_count'] == 1 ? 'Product' : 'Products'; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="product-count-link count-zero">
                                        0 Products
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="action-links">
                                <a href="edit_categories.php?id=<?php echo $row['id']; ?>" class="edit"> Edit</a>
                                <a href="delete_categories.php?id=<?php echo $row['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this category?\n\nThis category has <?php echo $row['product_count']; ?> product(s) associated with it.');"> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">No categories found. Click "Add New Category" to create one.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="addCategoryForm" method="POST" action="">
                <div class="form-group">
                    <label for="category_name">Category Name *</label>
                    <input type="text" id="category_name" name="category_name" required placeholder="Enter category name">
                </div>
                <div class="form-group">
                    <label for="category_code">Category Code</label>
                    <input type="text" id="category_code" name="category_code" placeholder="Enter category code (optional)">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Get modal element
        var modal = document.getElementById("addCategoryModal");
        
        // Function to open modal
        function openModal() {
            modal.style.display = "block";
            document.getElementById("category_name").focus();
        }
        
        // Function to close modal
        function closeModal() {
            modal.style.display = "none";
            document.getElementById("addCategoryForm").reset();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Handle form submission via AJAX
        document.getElementById("addCategoryForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            var category_name = document.getElementById("category_name").value.trim();
            var category_code = document.getElementById("category_code").value.trim();
            
            if(category_name === "") {
                showMessage("Please enter category name", "error");
                return;
            }
            
            // Send AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "add_category_ajax.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onreadystatechange = function() {
                if(xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if(response.success) {
                        showMessage(response.message, "success");
                        closeModal();
                        // Reload the page after a short delay to show the new category
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage(response.message, "error");
                    }
                }
            };
            
            xhr.send("category_name=" + encodeURIComponent(category_name) + "&category_code=" + encodeURIComponent(category_code));
        });
        
        function showMessage(msg, type) {
            var messageDiv = document.getElementById("message");
            messageDiv.innerHTML = msg;
            messageDiv.className = "message " + type;
            messageDiv.style.display = "block";
            
            setTimeout(function() {
                messageDiv.style.display = "none";
            }, 3000);
        }
    </script>
</body>
</html>