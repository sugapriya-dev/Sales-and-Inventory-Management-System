<?php 
session_start();
include "db.php";
include "functions.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$messageClass = "";

// Get filter dates from URL
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Fetch cash in hand entries with debit/credit using the provided query
$cash_entries = [];
$total_dr = 0;
$total_cr = 0;

if (isset($_GET['filter'])) {
    // Use the exact query provided
    $cash_query = "SELECT ch.*,IF(ch.amt < 0, ABS(ch.amt), 0) AS dr,IF(ch.amt > 0, ch.amt, 0) AS cr,
       COALESCE(s.suppliers_name, c.customername, ch.payee) AS party_name FROM cash_in_hand ch LEFT JOIN suppliers s ON ch.payee = s.id LEFT JOIN customers c ON ch.payee = c.id WHERE ch.transaction_date BETWEEN '$from_date' AND '$to_date' ORDER BY ch.transaction_date DESC;";
    
    $result = $conn->query($cash_query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Convert to lowercase for case-insensitive comparison
            $trans_type_lower = strtolower($row['transaction_type']);
            
            // For sales transactions (sale, Sale, SALE all work)
            if ($trans_type_lower == 'sale' || $trans_type_lower == 'receipt') {
                // Get customer name based on payee ID
                $customer_query = "SELECT customername FROM customers WHERE id = '{$row['payee']}'";
                $customer_result = $conn->query($customer_query);
                if ($customer_result && $customer_result->num_rows > 0) {
                    $customer = $customer_result->fetch_assoc();
                    $row['party_name'] = $customer['customername'];
                }
            }
            // For purchase transactions, get supplier name
            elseif ($trans_type_lower == 'purchase' || $trans_type_lower == 'payment') {
                // Get supplier name based on payee ID
                $supplier_query = "SELECT suppliers_name FROM suppliers WHERE id = '{$row['payee']}'";
                $supplier_result = $conn->query($supplier_query);
                if ($supplier_result && $supplier_result->num_rows > 0) {
                    $supplier = $supplier_result->fetch_assoc();
                    $row['party_name'] = $supplier['suppliers_name'];
                }
            }

            $cash_entries[] = $row;
            $total_dr += $row['dr'];
            $total_cr += $row['cr'];
        }
    }
}

$net_balance = $total_cr - $total_dr;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cash in Hand Ledger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 98%;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .filter-group button {
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .filter-group button:hover {
            background: #0056b3;
        }

        .period-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .summary-boxes {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-box {
            flex: 1;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .summary-box.total-dr {
            background: #f8d7da;
            color: #721c24;
        }

        .summary-box.total-cr {
            background: #d4edda;
            color: #155724;
        }

        .summary-box.net-balance {
            background: #fff3cd;
            color: #856404;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
            min-width: 1200px;
        }

        th, td {
            padding: 10px 5px;
            border: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #007bff;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .credit {
            color: #28a745;
            font-weight: 500;
        }

        .debit {
            color: #dc3545;
            font-weight: 500;
        }

        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-row {
            background: #e9ecef;
            font-weight: bold;
        }
        
        .transaction-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .type-purchase {
            background: #f8d7da;
            color: #721c24;
        }
        
        .type-sale {
            background: #d4edda;
            color: #155724;
        }
        
        .id-badge {
            background: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }

        .type-sale {
                background: #d4edda;  /* Green  for sale */
                color: #155724;
                padding: 3px 8px;
                border-radius: 3px;
                font-weight: bold;
            }

            .type-purchase {
                background: #f8d7da;  /* Red for purchase */
                color: #721c24;
                padding: 3px 8px;
                border-radius: 3px;
                font-weight: bold;
            }

            .type-payment {
                background: #fff3cd;  /* Yellow for payment */
                color: #856404;
                padding: 3px 8px;
                border-radius: 3px;
                font-weight: bold;
            }
            /* Amount colors */
            .amount-sale {
                color: #28a745;  /* Green */
                font-weight: bold;
            }

            .amount-purchase {
                color: #dc3545;  /* Red */
                font-weight: bold;
            }

            .amount-payment {
                color: #ffc107;  /*  1d9ff0 Yellow */
                font-weight: bold;
            }
            .amount-receipt {
                color: #17a2b8;  /* Teal */
                font-weight: bold;
            }

            .text-right {
                text-align: right;
            }
            .type-receipt {
                background: #cce5ff;  /* Light blue */
                color: #004085;
                padding: 3px 8px;
                border-radius: 3px;
                font-weight: bold;
            }

            .amount-receipt {
                color: #17a2b8;  /* Teal */
                font-weight: bold;
            }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1>Cash in Hand Ledger</h1>

        <form method="GET" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>" required>
                </div>

                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>" required>
                </div>

                <div class="filter-group">
                    <button type="submit" name="filter" value="1">Show Entries</button>
                </div>
            </div>
        </form>

        <?php if (isset($_GET['filter'])): ?>
            <div class="period-info">
                Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>
            </div>

            <!-- Summary Boxes -->
            <div class="summary-boxes">
                <div class="summary-box total-dr">
                    Total Debit (Purchase): ₹<?php echo number_format($total_dr, 2); ?>
                </div>
                <div class="summary-box total-cr">
                    Total Credit (Sales): ₹<?php echo number_format($total_cr, 2); ?>
                </div>
                <div class="summary-box net-balance">
                    Net Balance: ₹<?php echo number_format($net_balance, 2); ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Transaction Date</th>
                        <th>Party Name</th>
                        <th>Transaction Type</th>
                        <th>Particulars</th>
                        <th>Debit (Dr) ₹</th>
                        <th>Credit (Cr) ₹</th>
                
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cash_entries)): 
                        $sno = 1;
                        foreach ($cash_entries as $entry): 
                            // Determine transaction type class
                         // Determine transaction type class
                                $transaction_type = $entry['transaction_type'];
                                $trans_type_lower = strtolower($transaction_type);  // Convert to lowercase
                                $payee = $entry['payee'];
                                $party_name = $entry['party_name'];

                                $type_class = '';
                                $amount_class = '';
                                $display_type = ucfirst($transaction_type); // Keep original case for display

                                // Check using lowercase for comparison
                                if ($trans_type_lower == 'payment') {
                                    $type_class = 'type-payment';
                                    $amount_class = 'amount-payment';
                                } elseif ($trans_type_lower == 'receipt') {    
                                    $type_class = 'type-receipt';
                                    $amount_class = 'amount-receipt';
                                } elseif ($trans_type_lower == 'sale') {
                                    $type_class = 'type-sale'; 
                                    $amount_class = 'amount-sale';
                                } elseif ($trans_type_lower == 'purchase') {
                                    $type_class = 'type-purchase'; 
                                    $amount_class = 'amount-purchase';
                                }
                             ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($entry['transaction_date'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['party_name']); ?></td>
                            <td>
                                <span class="transaction-type <?php echo $type_class; ?>">
                                    <?php echo ucfirst($display_type); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($entry['particulars'] ?? '-'); ?></td>
                            <td class="<?php 
                            if ($entry['dr'] > 0) {
                                if ($display_type == 'receipt') {
                                    echo 'amount-receipt';
                                } elseif ($transaction_type == 'payment') {
                                    echo 'amount-payment';
                                } else {
                                    echo 'debit';
                                }
                            }
                        ?> text-right">
                            <?php echo ($entry['dr'] > 0) ? number_format($entry['dr'], 2) : '-'; ?>
                        </td>

                        <td class="<?php 
                            if ($entry['cr'] > 0) {
                                if ($display_type == 'receipt') {
                                    echo 'amount-receipt';
                                } elseif ($transaction_type == 'payment') {
                                    echo 'amount-payment';
                                } elseif ($transaction_type == 'sale') {
                                    echo 'amount-sale';
                                } else {
                                    echo 'credit';
                                }
                            }
                        ?> text-right">
                            <?php echo ($entry['cr'] > 0) ? number_format($entry['cr'], 2) : '-'; ?>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                        
                        <!-- Total Row -->
                        <tr class="total-row">
                            <td colspan="4" class="text-right"><strong>Total</strong></td>
                            <td class="text-right"><strong><?php echo number_format($total_dr, 2); ?></strong></td>
                            <td class="text-right"><strong><?php echo number_format($total_cr, 2); ?></strong></td>
                            <td></td>
                        </tr>
                        
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center">No records found for the selected period</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>