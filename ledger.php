<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Get all suppliers with their purchase summary
$query = "SELECT 
            s.id,
            s.suppliers_name,
            s.phoneno,
            COALESCE(SUM(p.totalamt), 0) as total_purchase,
            COALESCE(SUM(p.paidamt), 0) as total_paid
          FROM suppliers s
          LEFT JOIN purchase p ON s.id = p.supplier_id
          GROUP BY s.id, s.suppliers_name, s.phoneno
          ORDER BY s.suppliers_name ASC";

$result = $conn->query($query);
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

        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Supplier Name</th>
                    <th>Phone</th>
                    <th>Total Purchase (₹)</th>
                    <th>Total Paid (₹)</th>
                    <th>Balance Due (₹)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                $grandTotalPurchase = 0;
                $grandTotalPaid = 0;
                $grandBalance = 0;
                
                if ($result->num_rows > 0): 
                    while($row = $result->fetch_assoc()): 
                        $balance = $row['total_purchase'] - $row['total_paid'];
                        $grandTotalPurchase += $row['total_purchase'];
                        $grandTotalPaid += $row['total_paid'];
                        $grandBalance += $balance;
                ?>
                    <tr>
                        <td><?php echo $sno++; ?></td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($row['suppliers_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phoneno'] ?? 'N/A'); ?></td>
                        <td>₹<?php echo number_format($row['total_purchase'], 2); ?></td>
                        <td>₹<?php echo number_format($row['total_paid'], 2); ?></td>
                        <td class="<?php echo ($balance >= 0) ? 'positive' : 'negative'; ?>">
                            ₹<?php echo number_format($balance, 2); ?>
                        </td>
                        <td>
                            <a href="supplier_ledger.php?supplier_id=<?php echo $row['id']; ?>" 
                               class="view-btn">View Ledger</a>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">
                            No suppliers found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #e9ecef; font-weight: bold;">
                    <td colspan="3" style="text-align: right;">Grand Total:</td>
                    <td>₹<?php echo number_format($grandTotalPurchase, 2); ?></td>
                    <td>₹<?php echo number_format($grandTotalPaid, 2); ?></td>
                    <td class="<?php echo ($grandBalance >= 0) ? 'positive' : 'negative'; ?>">
                        ₹<?php echo number_format($grandBalance, 2); ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        
    </div>
</body>
</html>