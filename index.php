<?php
session_start();

include "db.php"; 
$error = "";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query to check if user exists in database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role']; // Store user role if needed
        
        // Update last login time
        $updateStmt = $conn->prepare("UPDATE users SET lastlogin = NOW() WHERE username = ?");
        $updateStmt->bind_param("s", $username);
        $updateStmt->execute();
        $updateStmt->close();
        
        header("Location: nav.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
    
    $stmt->close();

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <style>
        body{
            font-family: Arial;
            background: #f2f2f2;
        }
        .loginbox {
            width: 350px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            box-sizing: border-box;
        }
        h1 {
            text-align: center;
            color: brown;
        }
        input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76,175,80,0.3);
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffe6e6;
            border-radius: 4px;
        }
        label {
            color: black;
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        .demo-credentials {
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
            text-align: center;
            border: 1px dashed #ccc;
        }
        .demo-credentials span {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="loginbox">
    <h1>LOGIN PAGE</h1>

    <?php if ($error) { ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <form method="post" action="">
        <label>USERNAME</label>
        <input type="text" name="username" placeholder="Enter username" required>

        <label>PASSWORD</label>
        <input type="password" name="password" placeholder="Enter password" required>

        <button type="submit" name="login">Login</button>
    </form>
    
    <!-- Optional: Show demo credentials (you can remove this in production) 
    <div class="demo-credentials">
        <strong>Demo Credentials:</strong><br>
        Use any user from database<br>
        <span>Admin:</span> admin / 12345<br>
        <span>Staff:</span> staff / staff123
    </div>-->
</div>

</body>
</html>