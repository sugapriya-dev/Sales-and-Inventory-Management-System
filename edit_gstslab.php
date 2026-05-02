<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

/* GET ID FROM URL */
if (!isset($_GET['id'])) {
    echo "Invalid GST ID";
    exit();
}

$id = intval($_GET['id']);

/* FETCH GSTSLAB DATA */
$stmt = $conn->prepare("SELECT * FROM gstslab WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();



/* UPDATE GST */
if (isset($_POST['submit'])) {

    $name       = $_POST['name'];
    $gstpercent = $_POST['percent'];
    $unit_type  = $_POST['unit_type'];

$update = $conn->prepare("UPDATE gstslab SET name = ?, gst_percent = ?, pricetype = ? WHERE id = ?");    $update->bind_param("sdsi", $name, $gstpercent, $unit_type,$id);

    if ($update->execute()) {
        echo "<script>
            alert('GST updated successfully');
            window.location.href='view_gstslab.php';
        </script>";
        exit();
    } else {
        echo "<script>alert('Update failed: " . $update->error . "');</script>";
    }

    $update->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Product</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
}

.container {
    width: 480px;
    margin: 60px auto;
    background: #fff;
    padding: 25px;
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
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
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

button {
    width: 100%;
    margin-top: 20px;
    padding: 10px;
    font-size: 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-update {
    background: #28a745;
    color: #fff;
}

.btn-update:hover {
    background: #218838;
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

.required::after {
    content: " *";
    color: red;
}
</style>

</head>
<body>
<?php include "dashboard.php"; ?>

<div class="container">
<h1>Edit GST Slab</h1>

<form method="post">

    <label>Slab Name *</label>
    <input type="text" name="name" value="<?php echo $row['name']; ?>" required>

    <label>GST % *</label>
    <input type="text" name="percent" value="<?php echo $row['gst_percent']; ?>" required>

    <!--<label>Price Type </label>
    <select name="unit_type">
     <option value="">-- Select Type --</option>
    <option value="Inclusive" 
        <?php if($row['pricetype'] == "Inclusive") echo "selected"; ?>>
        Inclusive
    </option>
    <option value="Exclusive" 
        <?php if($row['pricetype'] == "Exclusive") echo "selected"; ?>>
        Exclusive
    </option> -->
    </select>


    

    <button type="submit" name="submit">Update GST</button>

</form>

<a href="view_gstslab.php">⬅ Back to GST Slab</a>
</div>

</body>
</html>