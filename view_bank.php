<?php
session_start();
include "db.php"; 

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

  $res=$conn->query("select * from bank order by created_at desc");

$username = $_SESSION['username'];

$stmt = $conn->prepare( "SELECT lastlogin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$resultUser = $stmt->get_result();
$userData = $resultUser->fetch_assoc();
$stmt->close();

    
    
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
    body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .container {
            width: 80%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        a {
            text-decoration: none;
            color: #007bff;
            
        }

        .top-links {
            text-align: right;
            margin-bottom: 10px;
        }
</style>

</head>
<body>
    <?php include "dashboard.php"; ?>


  <h1>Bank Accounts</h1>
  
  <div class="top-links">
      <a href="bank.php">ADD BANK ACCOUNT</a>
  </div>
      <table>
        <tr>
            <th>Bank Name</th>
            <th>Account Name</th>
            <th>Account Number</th>
            <th>Branch Name</th>
            <th>Opening amt</th>
            <th>Created at</th>           
            <th>Actions</th>
        </tr>


       <?php
           if($res->num_rows > 0) :?>
           
           <?php while($row =$res->fetch_assoc()) :?>
            <tr>
                    <td><?php echo htmlspecialchars($row['bank_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['accname']); ?></td>
                    <td><?php echo htmlspecialchars($row['accno']); ?></td>
                    <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['opening_cash']); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="edit_bank.php?id=<?php echo $row['id']; ?>">Edit</a>
                        |
                        <a href="delete_bank.php?id=<?php echo $row['id']; ?>" 
                        onclick="return confirm('Are you sure?');">
                        Delete
                        </a>
                    </td>
                </tr>
             <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No Bank Account  found</td>
            </tr>
        <?php endif; ?>
      </table>
</div>   


</body>
</html>
