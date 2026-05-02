<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$supplier = null;
$data = [];
$totalPurchase = 0;
$totalPayment = 0;
$balanceAmount = 0;

if (isset($_GET['search_value']) && $_GET['search_value'] != "") {

    $search = $conn->real_escape_string($_GET['search_value']);

    // Extract phone or name
    $querySupplier = "SELECT id, suppliers_name 
                      FROM suppliers
                      WHERE CONCAT(suppliers_name, ' - ', phoneno) = '$search'
                      LIMIT 1";

    $supplierResult = $conn->query($querySupplier);
    $supplier = $supplierResult->fetch_assoc();

    if ($supplier) {

        $supplier_id = $supplier['id'];

        $query = "SELECT id, date, invoiceno, totalamt, paidamt
                  FROM purchase
                  WHERE supplier_id = $supplier_id
                  ORDER BY date ASC, id ASC";

        $result = $conn->query($query);

        while($row = $result->fetch_assoc()){
            $totalPurchase += $row['totalamt'];
            $totalPayment  += $row['paidamt'];
            $data[] = $row;
        }

        $balanceAmount = $totalPurchase - $totalPayment;
    }
}

?>


<!DOCTYPE html>
<html>
<head>
    <title>Supplier List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .view-btn {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .view-btn:hover {
            background: #218838;
        }

        .positive {
            color: #dc3545;
            font-weight: bold;
        }

        .negative {
            color: #28a745;
            font-weight: bold;
        }

        .summary {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1>Supplier Ledger</h1>

        <form method="GET" style="margin-bottom:20px; text-align:center;">
    
    <input list="supplierList" name="search_value"
       placeholder="Search by Name or Phone..."
       required
       style="padding:8px; width:250px;">

<datalist id="supplierList">
<?php
$supQuery = $conn->query("SELECT id, suppliers_name, phoneno FROM suppliers ORDER BY suppliers_name ASC");
while ($sup = $supQuery->fetch_assoc()) {
    $display = $sup['suppliers_name'] . " - " . $sup['phoneno'];
    echo "<option value='{$display}'></option>";
}
?>
</datalist>


    <button type="submit"
        style="padding:8px 15px; background:#007bff; color:white; border:none; border-radius:4px;">
        Find
    </button>

    <a href="supplier_list.php"
       style="padding:8px 15px; background:#6c757d; color:white; text-decoration:none; border-radius:4px;">
        Reset
    </a>
</form>
 <?php if (!isset($_GET['supplier_id'])): ?>
    <p style="text-align:center; color:red; font-weight:bold;">
        Please search and select a supplier to view ledger.
    </p>
<?php endif; ?>


      <?php if ($supplier): ?>

<h2>Supplier Ledger - <?php echo $supplier['suppliers_name']; ?></h2>

<div class="summary" style="display:flex; gap:20px; margin-bottom:20px;">
    <div><strong>Total Purchase:</strong> ₹<?php echo number_format($totalPurchase,2); ?></div>
    <div><strong>Total Payment:</strong> ₹<?php echo number_format($totalPayment,2); ?></div>
    <div><strong>Balance:</strong> ₹<?php echo number_format($balanceAmount,2); ?></div>
</div>

<table border="1" width="100%" cellpadding="10" cellspacing="0">
<tr style="background:#007bff; color:white;">
    <th>Bill No</th>
    <th>Date</th>
    <th>Bill Type</th>
    <th>Credit (+)</th>
    <th>Debit (-)</th>
    <th>Running Balance</th>
</tr>

<?php
$runningBalance = 0;

foreach($data as $row):

    $runningBalance += $row['totalamt'];
?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['date']; ?></td>
    <td>Purchase</td>
    <td style="color:red;">₹<?php echo number_format($row['totalamt'],2); ?></td>
    <td>-</td>
    <td>₹<?php echo number_format($runningBalance,2); ?></td>
</tr>

<?php
    if($row['paidamt'] > 0):
        $runningBalance -= $row['paidamt'];
?>
<tr>
    <td>#<?php echo $row['id']; ?></td>
    <td><?php echo $row['date']; ?></td>
    <td>Payment</td>
    <td>-</td>
    <td style="color:green;">₹<?php echo number_format($row['paidamt'],2); ?></td>
    <td>₹<?php echo number_format($runningBalance,2); ?></td>
</tr>
<?php endif; ?>

<?php endforeach; ?>

</table>

<?php endif; ?>
    </div>
</body>
</html>