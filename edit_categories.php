<?php
session_start();
include "db.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$messageClass = "";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid category ID!'); window.location.href='view_categories.php';</script>";
    exit();
}

$id = $_GET['id'];

// Fetch category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Category not found!'); window.location.href='view_categories.php';</script>";
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

// Update category
if (isset($_POST['update'])) {
    $category_name = $_POST['category_name'];
    $category_code = $_POST['category_code'];
  

    $update_stmt = $conn->prepare("UPDATE categories SET category_name=?, category_code=? WHERE id=?");
    $update_stmt->bind_param("ssi", $category_name, $category_code, $id);

    if ($update_stmt->execute()) {
        echo "<script>alert('Category updated successfully!'); window.location.href='view_categories.php';</script>";
    } else {
        echo "<script>alert(' Failed to update category!');</script>";
    }
    $update_stmt->close();
}

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
    <title>Edit Category</title>
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
            padding: 30px;
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
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .button-group button {
            flex: 1;
        }

        .btn-cancel {
            background: #6c757d;
        }

        .btn-cancel:hover {
            background: #545b62;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .user-info {
            text-align: right;
            font-weight: bold;
            color: #333;
            font-size: 30px;
            margin-bottom: 20px;
        }

        .user-info span {
            font-size: 20px;
            font-weight: normal;
            color: #666;
        }

        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
    
        <h1>Edit Category</h1>

        <form action="" method="post">
            <label class="required-field">Category Name</label>
            <input type="text" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>" required>

            <label>Category Code</label>
            <input type="text" name="category_code" value="<?php echo htmlspecialchars($category['category_code']); ?>">


            <div class="button-group">
                <button type="submit" name="update">Update Category</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='view_categories.php'">Back</button>
            </div>
        </form>

      
    </div>
</body>
</html>