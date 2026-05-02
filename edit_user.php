<?php
session_start();
include "db.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$message = "";
$error = "";

// Get user data
$result = $conn->query("SELECT * FROM users WHERE id=$id");
$row = $result->fetch_assoc();

if(isset($_POST['update'])){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    
    // Check if password field is empty
    if(!empty($new_password)) {
        // Update with new password
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $role, $new_password, $id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $email, $role, $id);
    }
    
    if($stmt->execute()) {
        // Success - now show alert and redirect
        echo "<script>
            alert('User Updated Successfully! New password is saved in database.');
            window.location='users.php';
        </script>";
        exit();
    } else {
        $error = "Error updating user: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
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
        input[type="password"],
        select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
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
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
        }

        button:hover {
            background: #0069d9;
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

        .password-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: -10px;
            margin-bottom: 15px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
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

        .current-password-indicator {
            background: #e9ecef;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #495057;
            border-left: 3px solid #007bff;
        }

        .password-section {
            border-top: 1px solid #dee2e6;
            margin-top: 10px;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h2>Edit User</h2>
        
        <?php if($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($row['username']); ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>

            <label>Role</label>
            <select name="role">
                <option value="admin" <?php if($row['role']=="admin") echo "selected"; ?>>Admin</option>
                <option value="staff" <?php if($row['role']=="staff") echo "selected"; ?>>Staff</option>
            </select>

           

            <div class="password-section">
                <label>New Password (Leave empty to keep current)</label>
                <input type="password" name="new_password" placeholder="Enter new password">
               
            </div>

            <div class="button-group">
                <button type="submit" name="update">Update User</button>
                <a href="users.php" class="back-btn">Back to Users</a>
            </div>
        </form>
    </div>
</body>
</html>