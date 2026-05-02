<?php
session_start();
include "db.php"; 


// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}


if (isset($_POST['submit']))
    {
        $name=$_POST['cusname'];
        $phone=$_POST['phone'];
        $city=$_POST['city'];
        $state=$_POST['state'];
        $aadhaar=$_POST['aadhaar'];

        $message = "";
        $messageClass = "";


       // $sql="insert into customers(customername,phoneno,city) values ('$name','$phone','$city')";
       /// mysqli_query($conn,$sql);

        $stmt = $conn->prepare("INSERT INTO customers (customername, phoneno, city,state,aadhaarno) VALUES (?, ?, ?,?,?)");
        $stmt->bind_param("sisss", $name, $phone, $city,$state,$aadhaar); 
       if ($stmt->execute()) {
            echo "<script>alert(' Customer added successfully!'); window.location.href='customer_display.php';</script>";
        } else {
            echo "<script>alert(' Failed to add Supplier!'); window.history.back();</script>";
        }
        $stmt->close();

    }
    
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customers</title>
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
    
    label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
    color: #555;
    }

    input, select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

</style>

</head>
<body>
<?php include "dashboard.php"; ?>
<div class="container">
   <!--<h1>Welcome to Dashboard</h1>
   <p>Hello, <strong><?php echo $_SESSION['username']; ?></strong></p>-->
   <form action="" method="post">
      <h1>Customer Details</h1>
      <label>Customer Name *</label><input type="text" name="cusname" placeholder="Enter a Customer Name"  required>
      <label>Customer Phone No *</label><input type="text" name="phone" placeholder="Enter a Customer Phoneno" required>
      <label>City *</label><input type="text" name="city" placeholder="Enter a City" required>
      
      <label>State</label>
    <select name="state" required>
        <option value="">Select State</option>
        <option value="1">Tamilnadu</option>
        <option value="2">Kerala</option>
        <option value="3">Karnataka</option>
        <option value="4">Andhra Pradesh</option>
        <option value="5">Telangana</option>
        <option value="6">Maharashtra</option>
        <!-- Add more states as needed -->
    </select>
      
      <label>Aadhaar No</label><input type="text" name="aadhaar" placeholder="Enter a aadharno">
     

   <button type="submit" name="submit">Submit</button>


   </form>

   <a href="customer_display.php" >View Customers</a>

  
</div>

</body>
</html>
