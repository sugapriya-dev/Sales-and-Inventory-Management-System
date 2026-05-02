<?php
session_start();
include "db.php"; 

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

  $res=$conn->query("select * from gstslab order by created_at desc");

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
            background: #f4f6f8;display: flex;
            justify-content: center;  
            align-items: center;     
            min-height: 100vh;        
            margin: 0;                 
            background: #f0f0f0; 
        }

        .container {
             width: 80%;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow-x: auto;
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

<div class="container">
  <h1>GST Slabs</h1>
  
  <div class="top-links">
      <a href="gstslab.php">ADD GST SLAB</a>
  </div>
      <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>GST %</th>
            <th>Actions</th>
        </tr>


       <?php
           if($res->num_rows > 0) :?>
           
           <?php while($row =$res->fetch_assoc()) :?>
            <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['gst_percent']); ?></td>
                   <!-- <td><?php echo htmlspecialchars($row['pricetype']); ?></td> -->
                    <td> <a href="edit_gstslab.php ?id=<?php echo $row['id'];?>">Edit</a>
                         <a href="delete_gstslab.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                    
            </tr>
             <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No Gst Slab found</td>
            </tr>
        <?php endif; ?>
      </table>
</div>   


</body>
</html>
