<?php
session_start();
include "db.php"; 


// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}


if (isset($_POST['submit'])) {
    $bname      = $_POST['bankname'];
    $accname    = $_POST['accname'];
    $accno      = $_POST['accno'];
    $branchname = $_POST['branchname'];
    $amt        = $_POST['amt'];

    $message = "";
    $messageClass = "";

    // Prepare insert query for bank table
    $stmt = $conn->prepare("INSERT INTO bank(bank_name, accname, accno, branch_name, opening_cash) VALUES (?, ?, ?, ?, ?)");

    // Bind parameters: s = string, i = integer
    $stmt->bind_param("ssssi", $bname, $accname, $accno, $branchname, $amt);

    if ($stmt->execute()) {
        $message = "Bank account added successfully!";
        $messageClass = "success";
    } else {
        $message = "Failed to add bank account!";
        $messageClass = "error";
    }

    $stmt->close();
}

    
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f6f8;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 400px;
        margin: 80px auto;
        background: #fff;
        padding: 25px;
        border-radius: 6px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        color: #555;
    }

    input {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button {
        width: 100%;
        margin-top: 20px;
        padding: 10px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 15px;
        cursor: pointer;
    }

    button:hover {
        background: #0056b3;
    }

    a {
        display: block;
        text-align: center;
        margin-top: 15px;
        text-decoration: none;
        color: #007bff;
    }

    a:hover {
        text-decoration: underline;
    }
</style>

</head>
<body>
<?php include "dashboard.php"; ?>
<div class="container">
   <!--<h1>Welcome to Dashboard</h1>
   <p>Hello, <strong><?php echo $_SESSION['username']; ?></strong></p>-->
   <form action="" method="post">
      <h1>Bank Details</h1>
      <label>Bank Name </label><input type="text" name="bankname" placeholder="Enter a Bank Name"  >
       <label>Account Name</label><input type="text" name="accname" placeholder="Enter a Account Name" >
       <label>Account No</label><input type="text" name="accno" placeholder="Enter a Account No" >
      <label>Branch Name</label><input type="text" name="branchname" placeholder="Enter a Branch Name">
      <label>Opening amt</label><input type="number" name="amt" placeholder="Opening amt">

   <button type="submit" name="submit" onclick="return confirm('Bank Account Created Successfully');">Submit</button>


   </form>

   <a href="view_bank.php" >View Bank Details</a>

</div>

</body>
</html>
