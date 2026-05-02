<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: product_display.php");
    exit();
}

/* FETCH PRODUCT DATA */
$stmt = $conn->prepare("SELECT * FROM products WHERE pid = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: product_display.php");
    exit();
}

/* UPDATE PRODUCT */
if (isset($_POST['submit'])) {

    $name          = $_POST['pro_name'];
    $category      = $_POST['category'];
    $unit_type     = $_POST['unit_type'] ?? '';
    $purchaseprice = $_POST['purchaseprice'] ?? 0;
    $regularprice  = $_POST['regularprice'];
    $tax_type      = $_POST['tax_type'] ?? '';
    $hsncode       = $_POST['hsncode'] ?? '';
    $initialqty    = $_POST['initialqty'] ?? 0;
    $expirydate    = !empty($_POST['expirydate']) ? $_POST['expirydate'] : NULL;

    // unit price = regular price (as per your logic)
    $unitprice = $regularprice;

 
    $expirydate = !empty($_POST['expirydate']) ? $_POST['expirydate'] : NULL;

// $update = $conn->prepare("UPDATE products SET pro_name = ?,category = ?,unittype = ?,unitprice = ?,purchaseprice= ?,regularprice = ?,taxtype = ?,hsncode = ?,expirydate = ?,initialqty = ?WHERE pid = ?");


$update = $conn->prepare("UPDATE products SET
        pro_name = ?,
        category = ?,
        unittype = ?,
        unitprice = ?,
        purchaseprice = ?,
        regularprice = ?,
        taxtype = ?,
        hsncode = ?,
        expirydate = ?,
        initialqty = ?,
       updated_at = IF(? <> ?, NOW(), updated_at)
    WHERE pid = ?
");

            //works even expiry date is null
        $update->bind_param(
        "sssdddsssiiii",
        $name,
        $category,
        $unit_type,
        $unitprice,
        $purchaseprice,
        $regularprice,
        $tax_type,
        $hsncode,
        $expirydate,
        $initialqty,
        $row['initialqty'],   // old qty
        $initialqty,          // new qty
        $id
        );


    if ($update->execute()) {
        echo "<script>
            alert('Product updated successfully');
            window.location.href='view_products.php';
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
<h1>Edit Product</h1>

<form method="post">

    <label class="required">Product Name</label>
    <input type="text" name="pro_name" value="<?php echo htmlspecialchars($row['pro_name']); ?>" required>

    <label class="required">Category</label>
    <input type="text" name="category" value="<?php echo htmlspecialchars($row['category']); ?>" required>

    <label>Unit Type</label>
    <select name="unit_type">
        <option value="">-- Select Unit --</option>
        <option value="per_piece" <?php echo ($row['unittype']=="per_piece") ? "selected" : ""; ?>>Per Piece</option>
        <option value="per_pack" <?php echo ($row['unittype']=="per_pack") ? "selected" : ""; ?>>Per Pack</option>
        <option value="per_kg" <?php echo ($row['unittype']=="per_kg") ? "selected" : ""; ?>>Per Kg</option>
    </select>

    <label>Purchase Price</label>
    <input type="number" step="0.01" name="purchaseprice" min="0"
           value="<?php echo $row['purchaseprice']; ?>">

    <label class="required">Regular Price</label>
    <input type="number" step="0.01" name="regularprice" min="0"
           value="<?php echo $row['regularprice']; ?>" required>

    <label>Tax Type</label>
    <select name="tax_type">
    <option value="">-- Select Tax --</option>
    <?php 
    // Fetch GST slabs
    $gstSlabs = $conn->query("SELECT id, gst_percent FROM gstslab ORDER BY gst_percent ASC");
    while ($slab = $gstSlabs->fetch_assoc()) {
        $selected = ($row['taxtype'] == $slab['gst_percent']) ? "selected" : "";
        echo '<option value="' . $slab['gst_percent'] . '" ' . $selected . '>' . $slab['gst_percent'] . ' %</option>';
    }
    ?>
</select>


    <!-- <label>Tax Rate (%)</label>
    <input type="number" step="0.01" name="taxrate" min="0" max="100"
           value="<?php echo $row['taxrate']; ?>"> -->

    <label>HSN Code</label>
    <input type="text" name="hsncode"
           value="<?php echo htmlspecialchars($row['hsncode']); ?>">

    <label>Initial Quantity</label>
    <input type="number" name="initialqty" min="0"
           value="<?php echo $row['initialqty']; ?>">

    <label>Expiry Date</label>
    <input type="date" name="expirydate"
           value="<?php echo $row['expirydate']; ?>">

    <button type="submit" name="submit" class="btn-update">Update Product</button>

</form>

<a href="view_products.php">⬅ Back to Products</a>
</div>

</body>
</html>