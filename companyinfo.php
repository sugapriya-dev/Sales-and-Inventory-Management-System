<?php
session_start();
include "db.php"; 

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Fetch only one company record
$res = $conn->query("SELECT * FROM company LIMIT 1");
$row = $res->fetch_assoc();

$username = $_SESSION['username'];

// Get last login info
$stmt = $conn->prepare("SELECT lastlogin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$resultUser = $stmt->get_result();
$userData = $resultUser->fetch_assoc();
$stmt->close();

if (isset($_POST['submit'])) {
    $logoName = $_FILES['logo']['name'];
    $logoTmp  = $_FILES['logo']['tmp_name'];

    // Upload folder path
    $target = "photos/" . basename($logoName);

    // Move file to uploads folder
    if (move_uploaded_file($logoTmp, $target)) {
        // Update DB logo column
        $sql = "UPDATE company 
                SET logo = '$logoName' 
                WHERE id = 1";

        mysqli_query($conn, $sql);
       // echo "Logo updated successfully!";
    } else {
        alert("Logo upload failed!");
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Companyinfo</title>
    <style>
        body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding-top: 70px; /* space for fixed navbar */
}

/* Main container */
.container {
    width: 90%;
    max-width: 1200px;
    margin: 30px auto;
    background: #ffffff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    
}

/* Page heading */
h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

/* Top action links */
.top-links {
    text-align: right;
    margin-bottom: 15px;
}

.top-links a {
    background: #007bff;
    color: #fff;
    padding: 8px 14px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
}

.top-links a:hover {
    background: #0056b3;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 18px;
    margin-top: 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

th {
    width: 30%;
    background: #f1f5ff;
    text-align: left;
    padding: 14px 16px;
    color: #333;
    font-weight: 600;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}

td {
    width: 70%;
    padding: 14px 16px;
    color: #555;
    font-weight: 400;
    border-bottom: 1px solid #dee2e6;
    background-color: white;
}

tr {
    border-bottom: 1px solid #dee2e6;
}

tr:last-child th,
tr:last-child td {
    border-bottom: none;
}

tr:nth-child(even) td {
    background: #f8f9fa;
}

tr:hover td {
    background: #eef4ff;
}

/* Links inside table */
td a {
    color: #0d6efd;
    font-weight: 500;
    text-decoration: none;
    padding: 2px 0;
    display: inline-block;
}

td a:hover {
    text-decoration: underline;
}

/* Action buttons spacing */
td a + a {
    margin-left: 8px;
}

/* Responsive table (small screens) */
@media (max-width: 900px) {
    table {
        font-size: 14px;
        border: 1px solid #dee2e6;
    }

    th, td {
        padding: 10px 8px;
    }
    
    th {
        width: 35%;
    }
    
    td {
        width: 65%;
    }
}

@media (max-width: 600px) {
    table {
        font-size: 13px;
    }

    th, td {
        padding: 8px 6px;
        display: block;
        width: 100%;
        box-sizing: border-box;
    }
    
    th {
        background: #e9ecef;
        border-bottom: none;
        padding-bottom: 4px;
        font-size: 12px;
        color: #6c757d;
    }
    
    td {
        padding-top: 4px;
        padding-bottom: 12px;
        border-bottom: 1px solid #dee2e6;
    }
    
    tr:last-child td {
        border-bottom: none;
    }
}

img {
    display: block;
    margin: 0 auto;
}

.top-links {
            text-align: right;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php include "dashboard.php"; ?>
<form method="post" enctype="multipart/form-data">

<div class="container">
    <h1>Company Details </h1>
    
    <img src="photos/weblogo.png" alt="Company Logo" width="500" height="100">

    <div class="top-links">
    <a href="edit_company.php?id=<?= $row['id']; ?>">EDIT COMPANY</a>
</div>

  <table>
<?php if ($row): ?>

    <tr>
        <th>Company Name</th>
        <td><?= htmlspecialchars($row['comp_name']); ?></td>
    </tr>

    <tr>
        <th>Address</th>
        <td><?= htmlspecialchars($row['comp_address']); ?></td>
    </tr>

    <tr>
        <th>Phone</th>
        <td><?= htmlspecialchars($row['phone']); ?></td>
    </tr>

    <tr>
        <th>Email</th>
        <td><?= htmlspecialchars($row['email']); ?></td>
    </tr>

    <tr>
        <th>GST No</th>
        <td><?= htmlspecialchars($row['gstno']); ?></td>
    </tr>

    <tr>
        <th>State</th>
        <td><?= htmlspecialchars($row['comp_state']); ?></td>
    </tr>

    <tr>
        <th>Website</th>
        <td>
            <a href="<?= htmlspecialchars($row['website']); ?>" target="_blank">
                <?= htmlspecialchars($row['website']); ?>
            </a>
        </td>
    </tr>

    <tr> <th>Logo</th> 
         <td> <input type="file" name="logo" accept="image/*">  <input type="submit" name="submit" value="Save"></td> 
    </tr>
   

    <tr>
        <th>Opening Cash</th>
        <td><?= htmlspecialchars($row['opening_cash']); ?></td>
    </tr>

<?php else: ?>
    <tr>
        <td colspan="2">No company found</td>
    </tr>
<?php endif; ?>
</table>


</div>
</form>
</body>

</html>
