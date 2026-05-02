<?php
session_start();
include "db.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Only admin can access users.php
if ($_SESSION['role'] != 'admin') {
    header("Location: nav.php?error=Access Denied");
    exit();
}

$result = $conn->query("SELECT * FROM users ORDER BY id DESC");

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
    <title>User Management</title>
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
            width: 90%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
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
            border: 1px solid #ccc;
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
            background: #f5f5f5;
        }

        a {
            text-decoration: none;
            color: #007bff;
            padding: 5px 10px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        a:hover {
            background: #007bff;
            color: white;
        }

        .top-links {
            text-align: right;
            margin-bottom: 20px;
        }

        .add-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            display: inline-block;
        }

        .add-btn:hover {
            background: #218838;
            color: white;
        }

        .action-link {
            margin: 0 5px;
            padding: 5px 10px;
            border-radius: 3px;
        }

        .edit-link {
            color: #ffc107;
        }

        .edit-link:hover {
            background: #ffc107;
            color: white;
        }

        .delete-link {
            color: #dc3545;
        }

        .delete-link:hover {
            background: #dc3545;
            color: white;
        }

        .separator {
            color: #ccc;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h2>User Management</h2>
        
        <div class="top-links">
            <a href="add_user.php" class="add-btn">Add User</a>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Last Login</th>
                <th>Action</th>
            </tr>

            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['lastlogin']); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="action-link edit-link">Edit</a>
                            <span class="separator">|</span>
                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" 
                               class="action-link delete-link" 
                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">No users found</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>