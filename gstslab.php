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

    $name       = $_POST['name'];
    $gstpercent = $_POST['percent'];
    //$unit_type  = $_POST['unit_type'];

    $stmt = $conn->prepare("INSERT INTO gstslab (name, gst_percent) VALUES (?, ?)");
    $stmt->bind_param("sd", $name, $gstpercent);

    if ($stmt->execute()) {
        $message = "GST Slab added successfully";
        $messageClass = "success";
    } else {
        $message = "An error occurred: " . $stmt->error;
        $messageClass = "error";
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Add Product</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
}

.container {
    width: 450px;
    margin: 60px auto;
    background: #fff;
    padding: 25px;
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h1 {
    text-align: center;
    margin-bottom: 20px;
}

label {
    font-weight: bold;
    margin-top: 10px;
    display: block;
}

input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
}

button {
    width: 100%;
    margin-top: 20px;
    padding: 10px;
    background: #007bff;
    color: #fff;
    border: none;
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
}
</style>
</head>

<body>
<?php include "dashboard.php"; ?>

<div class="container">
    
<h1>Add GST Slabs</h1>

<form method="post">
    <label>Slab Name *</label>
    <input type="text" name="name" required>

    <label>GST % *</label>
    <input type="text" name="percent" required>

    <!-- <label>Price Type </label>
    <select name="unit_type" >
        <option value="">-- Select Type --</option>
        <option value="Inclusive">Inclusive</option>
        <option value="Exclusive">Excluive</option>
    </select> -->

    <button type="submit" name="submit">Submit</button>
</form>

<a href="view_gstslab.php">View GST Slabs</a>

</div>

<?php if (!empty($message)) { ?>
<script>
    alert("<?php echo $message; ?>");
</script>
<?php } ?>

</body>
</html>
