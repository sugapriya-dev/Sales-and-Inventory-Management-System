<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

/* GET BANK ID */
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: view_bank.php");
    exit();
}

/* FETCH BANK DATA */
$stmt = $conn->prepare("SELECT * FROM bank WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: view_bank.php");
    exit();
}

/* UPDATE BANK DATA */
if (isset($_POST['submit'])) {

    $bankname   = $_POST['bankname'];
    $accname    = $_POST['accname'];
    $accno      = $_POST['accno'];
    $branchname = $_POST['branchname'];
    $amt        = $_POST['amt'];

    $update = $conn->prepare(
        "UPDATE bank SET
            bank_name = ?,
            accname = ?,
            accno = ?,
            branch_name = ?,
            opening_cash = ?
         WHERE id = ?"
    );

    $update->bind_param(
        "ssssii",
        $bankname,
        $accname,
        $accno,
        $branchname,
        $amt,
        $id
    );

    if ($update->execute()) {
        echo "<script>
            alert('Bank details updated successfully');
            window.location.href='view_bank.php';
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
<title>Edit Bank</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
}

.container {
    width: 420px;
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
<h1>Edit Bank</h1>

<form method="post">

    <label >Bank Name</label>
    <input type="text" name="bankname"
           value="<?= htmlspecialchars($row['bank_name']); ?>" >

    <label >Account Name</label>
    <input type="text" name="accname"
           value="<?= htmlspecialchars($row['accname']); ?>" >

    <label >Account Number</label>
    <input type="text" name="accno"
           value="<?= htmlspecialchars($row['accno']); ?>" >

    <label >Branch Name</label>
    <input type="text" name="branchname"
           value="<?= htmlspecialchars($row['branch_name']); ?>" >

    <label>Opening Amount</label>
    <input type="number" name="amt"
           value="<?= htmlspecialchars($row['opening_cash']); ?>">

    <button type="submit" name="submit" class="btn-update">
        Update Bank
    </button>

</form>

<a href="view_bank.php">⬅ Back to Bank List</a>
</div>

</body>
</html>
