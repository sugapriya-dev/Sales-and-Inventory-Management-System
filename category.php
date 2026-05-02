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

if (isset($_POST['submit'])) {
    $category_name = $_POST['category_name'];
    $category_code = $_POST['category_code'];
   

    $stmt = $conn->prepare("INSERT INTO categories (category_name, category_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $category_name, $category_code);

     if ($stmt->execute()) {
        echo "<script>alert(' Category added successfully!'); window.location.href='view_categories.php';</script>";
    } else {
        echo "<script>alert(' Failed to add category!'); window.history.back();</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 450px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
            color: #444;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #aaa;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }

        a:hover {
            text-decoration: underline;
        }

        .nav-links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<?php include "dashboard.php"; ?>

<div class="container">
    <h1>Categories</h1>

    <?php if ($message != ""): ?>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <label>Category Name *</label>
        <input type="text" name="category_name" placeholder="e.g. Electronics" required>

        <label>Category Code</label>
        <input type="text" name="category_code" placeholder="e.g. CAT-001">

        

        <button type="submit" name="submit">Submit Category</button>
    </form>

    
        <a href="view_categories.php"> BACK </a>
        
   
</div>

</body>
</html>