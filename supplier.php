<?php
session_start();
include "db.php"; 

// If not logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$messageClass = "";

if (isset($_POST['submit'])) {

    $name    = $_POST['supname'];
    $phone   = $_POST['phoneno'];
    $email   = $_POST['email'];
    $address = $_POST['address'];
    $gst     = $_POST['gst'];
    $city    = $_POST['city'];
    
    
    
    
    $state = $_POST['state'];
   // $account_id = $_POST['account_id'];

    // 🔍 CHECK IF PHONE NUMBER ALREADY EXISTS
    $check = $conn->prepare("SELECT id FROM suppliers WHERE phoneno = ?");
    $check->bind_param("s", $phone);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Phone exists
        $message = "Phone number already exists!";
        $messageClass = "error";
    } else {
        // Insert new supplier
        $stmt = $conn->prepare(
            "INSERT INTO suppliers 
            (suppliers_name, phoneno, email, sup_address, gst_no, city, sup_state) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssssss",
            $name,         
            $phone,
            $email,
            $address,
            $gst,
            $city,
            $state
        );
        
        if ($stmt->execute()) {
            echo "<script>alert(' Supplier added successfully!'); window.location.href='supplier_display.php';</script>";
        } else {
            echo "<script>alert(' Failed to add Supplier!'); window.history.back();</script>";
        }
        $stmt->close();
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Supplier Details</title>
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

input, select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
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

.action-links {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    text-align:center;
}

.action-links a {
    text-decoration: none;
    color: #007bff;
    padding: 5px 10px;
}

.action-links a:hover {
    text-decoration: underline;
}

.message {
    padding: 10px;
    margin-bottom: 15px;
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
</style>
</head>
<body>
<?php include "dashboard.php"; ?>

<div class="container">
<h1>Supplier Details</h1>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $messageClass; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<form method="post">
    <label>Supplier Name *</label>
    <input type="text" name="supname" placeholder="Enter an Name" required>

    <label>Phone No *</label>
    <input type="text" name="phoneno" placeholder="Enter an Phoneno" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number">

    <label>Email</label>
    <input type="email" name="email" placeholder="Enter an Email">

    <label>Address</label>
    <input type="text" name="address" placeholder="Enter an Address">

    <label>GST No</label>
    <input type="text" name="gst" placeholder="Enter an GST NO">

    <label>City</label>
    <input type="text" name="city" placeholder="Enter an City">
    
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

    
    
    <!-- <label>Account ID *</label>
    <select name="account_id" required>
        <option value="">Select Account</option>
        <?php
        // Fetch accounts from database
        $accounts = $conn->query("SELECT id, account_name FROM accounts ORDER BY account_name");
        while($acc = $accounts->fetch_assoc()) {
            echo '<option value="'.$acc['id'].'">'.$acc['account_name'].'</option>';
        }
        ?>
    </select> -->

    <button type="submit" name="submit">Submit</button>
</form>

<div class="action-links">
    <a href="supplier_display.php" >View Suppliers</a>
   
</div>
</div>

</body>
</html>