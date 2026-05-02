<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$suppliers = $result->fetch_assoc();
$stmt->close();

// Update logic
if (isset($_POST['update'])) {
    $name = $_POST['supname'];
    $phone = $_POST['phoneno'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $gst = $_POST['gst'];
    $city     = $_POST['city'];
   
     $state=$_POST['state'];

    $stmt = $conn->prepare(
        "UPDATE suppliers SET suppliers_name=?, phoneno=?, email=?, sup_address=?, gst_no=?,city=?,sup_state=? WHERE id=?"
    );
    // Added "i" at the end for the id parameter and included $id
    $stmt->bind_param("sssssssi", $name, $phone, $email, $address, $gst,$city,$state ,$id);
    $stmt->execute();
    $stmt->close();

    header("Location: supplier_display.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Supplier</title>
</head>
<style>
    body {
        display: flex;
    justify-content: center;   /* horizontal center */
    align-items: center;       /* vertical center */
    min-height: 100vh;         /* full viewport height */
    margin: 0;                 /* remove default body margin */
    background: #f0f0f0;       /* optional background */
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
<body>
<?php include "dashboard.php"; ?>


<div class="container">
    <h1>Edit Supplier</h1>
<form method="post">
    <label>Name</label>
    <input type="text" name="supname"
           value="<?php echo htmlspecialchars($suppliers['suppliers_name']); ?>">

    <label>Phone</label>
    <input type="text" name="phoneno"
           value="<?php echo htmlspecialchars($suppliers['phoneno']); ?>">

    <label>Email</label>
    <input type="text" name="email"
           value="<?php echo htmlspecialchars($suppliers['email']); ?>">

    <label>Address</label>
    <input type="text" name="address"
           value="<?php echo htmlspecialchars($suppliers['sup_address']); ?>"> 
    
    <label>Gst no</label>
    <input type="text" name="gst"
           value="<?php echo htmlspecialchars($suppliers['gst_no']); ?>">            

    <label>City</label>
    <input type="text" name="city"
           value="<?php echo htmlspecialchars($suppliers['city']); ?>">  

   <label>State</label>
        <select name="state" required>

        <option value="">Select State</option>

        <option value="Tamilnadu" <?php if($suppliers['sup_state']=="Tamilnadu") echo "selected"; ?>>Tamilnadu</option>

        <option value="Kerala" <?php if($suppliers['sup_state']=="Kerala") echo "selected"; ?>>Kerala</option>

        <option value="Karnataka" <?php if($suppliers['sup_state']=="Karnataka") echo "selected"; ?>>Karnataka</option>

        <option value="Andhra Pradesh" <?php if($suppliers['sup_state']=="Andhra Pradesh") echo "selected"; ?>>Andhra Pradesh</option>

        <option value="Telangana" <?php if($suppliers['sup_state']=="Telangana") echo "selected"; ?>>Telangana</option>

        <option value="Maharashtra" <?php if($suppliers['sup_state']=="Maharashtra") echo "selected"; ?>>Maharashtra</option>

    </select>               

   
    <br><br>
    <button type="submit" name="update">Update</button>
</form>

<a href="supplier_display.php">Back</a>
</div>
</body>
</html>