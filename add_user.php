<?php
session_start();
include "db.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if(isset($_POST['save'])){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $username, $email, $password, $role);
    $stmt->execute();

    header("Location: users.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f0f0f0;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 50%;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin: 10px 0 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
        }

        button:hover {
            background: #218838;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .required-field {
            color: #dc3545;
            font-size: 12px;
            margin-top: -10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h2>Add User</h2>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required placeholder="Enter username">

            <label>Email</label>
            <input type="email" name="email" required placeholder="Enter email">

            <label>Password</label>
            <input type="text" name="password" required placeholder="Enter password">
            <div class="required-field">Password will be stored as plain text</div>

            <label>Role</label>
            <select name="required">
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
            </select>

            <div class="button-group">
                <button type="submit" name="save">Save</button>
                <a href="users.php" class="back-btn">Back</a>
            </div>
        </form>
    </div>
</body>
</html>