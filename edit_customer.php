<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// Update logic
if (isset($_POST['update'])) {
    $name  = $_POST['cusname'];
    $phone = $_POST['phone'];
    $city  = $_POST['city'];
    $state =$_POST['state'];
     $aadhaar=$_POST['aadhaar'];

    $stmt = $conn->prepare(
        "UPDATE customers SET customername=?, phoneno=?, city=? , aadhaarno=?, state=? WHERE id=?"
    );
    $stmt->bind_param("sisssi", $name, $phone, $city,$aadhaar,$state, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: customer_display.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Customer</title>
</head>
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
<body>
<?php include "dashboard.php"; ?>


<div class="container">
    <h1>Edit Customer</h1>
<form method="post">
    <label>Name</label>
    <input type="text" name="cusname"
           value="<?php echo htmlspecialchars($customer['customername']); ?>">

    <label>Phone</label>
    <input type="text" name="phone"
           value="<?php echo htmlspecialchars($customer['phoneno']); ?>">

    <label>City</label>
    <input type="text" name="city"
           value="<?php echo htmlspecialchars($customer['city']); ?>">

    <label>State</label>
        <select name="state" required>

        <option value="">Select State</option>

        <option value="Tamilnadu" <?php if($customer['state']=="Tamilnadu") echo "selected"; ?>>Tamilnadu</option>

        <option value="Kerala" <?php if($customer['state']=="Kerala") echo "selected"; ?>>Kerala</option>

        <option value="Karnataka" <?php if($customer['state']=="Karnataka") echo "selected"; ?>>Karnataka</option>

        <option value="Andhra Pradesh" <?php if($customer['state']=="Andhra Pradesh") echo "selected"; ?>>Andhra Pradesh</option>

        <option value="Telangana" <?php if($customer['state']=="Telangana") echo "selected"; ?>>Telangana</option>

        <option value="Maharashtra" <?php if($customer['state']=="Maharashtra") echo "selected"; ?>>Maharashtra</option>

    </select>   

    <label>Aadhaar No</label>
    <input type="text" name="aadhaar"
           value="<?php echo htmlspecialchars($customer['aadhaarno']); ?>">       

          

    <br><br>
    <button type="submit" name="update">Update</button>
</form>

<a href="customer_display.php">Back</a>
</div>
</body>
</html>

