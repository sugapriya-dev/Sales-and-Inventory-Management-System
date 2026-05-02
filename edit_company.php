<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

/* GET COMPANY ID */
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: companyinfo.php");
    exit();
}

/* FETCH COMPANY DATA */
$stmt = $conn->prepare("SELECT * FROM company WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: companyinfo.php");
    exit();
}
if (isset($_POST['submit'])) {
    $comp_name    = $_POST['comp_name'];
    $comp_address = $_POST['comp_address'];
    $phone        = $_POST['phone'];
    $email        = $_POST['email'];
    $gstno        = $_POST['gstno'];
    $comp_state   = $_POST['comp_state'];
    $website      = $_POST['website'];
    $opening_amt  = $_POST['opening_amt'];

    // Handle logo upload
    $logoName = $row['logo']; // keep old logo by default
    if (!empty($_FILES['logo']['name'])) {
        $logoName = basename($_FILES['logo']['name']);
        $logoTmp  = $_FILES['logo']['tmp_name'];
        $target   = "photos/" . $logoName;

        if (move_uploaded_file($logoTmp, $target)) {
            // file moved successfully
        } else {
            echo "<script>alert('Logo upload failed');</script>";
        }
    }

    $update = $conn->prepare(
        "UPDATE company SET
            comp_name = ?,
            comp_address = ?,
            phone = ?,
            email = ?,
            gstno = ?,
            comp_state = ?,
            website = ?,
            opening_cash = ?,
            logo = ?
         WHERE id = ?"
    );

    $update->bind_param(
        "sssssssisi",
        $comp_name,
        $comp_address,
        $phone,
        $email,
        $gstno,
        $comp_state,
        $website,
        $opening_amt,
        $logoName,
        $id
    );

    if ($update->execute()) {
        echo "<script>
            alert('Company updated successfully');
            window.location.href='companyinfo.php';
        </script>";
        exit();
    } else {
        echo "<script>alert('Update failed: ".$update->error."');</script>";
    }

    $update->close();
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Company</title>

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
}

label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
}

input, textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

textarea {
    resize: vertical;
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
    color: white;
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

.required::after {
    content: " *";
    color: red;
}
</style>
</head>

<body>
<?php include "dashboard.php"; ?>

<div class="container">
<h1>Edit Company</h1>

<form method="post" enctype="multipart/form-data">

    <label class="required">Company Name</label>
    <input type="text" name="comp_name"
           value="<?= htmlspecialchars($row['comp_name']); ?>" required>

    <label>Address</label>
    <textarea name="comp_address"><?= htmlspecialchars($row['comp_address']); ?></textarea>

    <label class="required">Phone</label>
    <input type="text" name="phone"
           value="<?= htmlspecialchars($row['phone']); ?>" required>

    <label class="required">Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($row['email']); ?>" required>

    <label class="required">GST No</label>
    <input type="text" name="gstno"
           value="<?= htmlspecialchars($row['gstno']); ?>" required>

    <label class="required">State</label>
    <input type="text" name="comp_state"
           value="<?= htmlspecialchars($row['comp_state']); ?>" required>

    <label>Website</label>
    <input type="text" name="website"
           value="<?= htmlspecialchars($row['website']); ?>">

    <label>Logo</label> 
    <input type="file" name="logo" accept="image/*">   

    <label>Opening Amount</label>
    <input type="number" name="opening_amt"
           value="<?= $row['opening_cash']; ?>">

    <button type="submit" name="submit" class="btn-update">
        Update Company
    </button>

</form>

<a href="companyinfo.php">⬅ Back to Company</a>
</div>

</body>
</html>
