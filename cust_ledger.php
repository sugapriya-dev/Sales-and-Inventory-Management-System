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

// Get customer ID from URL
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Fetch all customer for dropdown
$customers = $conn->query("SELECT id, customername FROM customers ORDER BY customername");

// Fetch Customer details if selected
$customer_name = "";
if ($customer_id > 0) {
    $stmt = $conn->prepare("SELECT customername FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $customer_name = $row['customername'];
    }
    $stmt->close();
}

// Fetch ledger entries
$ledger_entries = [];

if ($customer_id > 0) {

 
    // Get purchase entries
    $purchase_query = "SELECT s.date as date,concat('Sale-',s.invoiceno) as particulars,s.grand_total as cr ,null as dr from sales as s where customer_id=$customer_id and  s.date BETWEEN '$from_date' and '$to_date'
UNION ALL
SELECT c.transaction_date as date,'By Cash' as particulars,null as cr, ABS(c.amt) as dr from cash_in_hand as c  where payee=$customer_id and  c.transaction_date BETWEEN '$from_date' and '$to_date'
UNION ALL
SELECT b.transaction_date as date,concat('By Bank - ',bk.bank_name)as particulars,null as cr, ABS(b.amt) as dr   from bank_ledger as b join bank as bk on bk.id=b.bank_id where payee=$customer_id and  b.transaction_date BETWEEN '$from_date' and '$to_date' order by date desc;";
    

    $stmt = $conn->prepare($purchase_query);
    $stmt->execute();
    $purchases = $stmt->get_result();
    // Get the count
    $rowCount = $purchases->num_rows; 


    $customer_total=cust_total($customer_id);
    $customer_payment=cust_payment($customer_id);
    $customer_outstanding=cust_outstanding($customer_id);

    
    
    
}

// Calculate totals
$total_credit = 0;
$total_debit = 0;


// foreach($ledger_entries as $entry) {
//     $total_credit += $entry['credit'];
//     $total_debit += $entry['debit'];
// }
$balance_due = $total_credit - $total_debit;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Ledger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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

        .filter-group input, .filter-group select {
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

        .supplier-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
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

        .summary-box.total-bill {
            background: #fff3cd;
            color: #856404;
        }

        .summary-box.total-payment {
            background: #d4edda;
            color: #155724;
        }

        .summary-box.balance-due {
            background: #f8d7da;
            color: #721c24;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        th, td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            text-align: left;
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
        
        .balance {
            font-weight: 600;
            color: #333;
        }
        
        .advance {
            color: #17a2b8;
            font-weight: 600;
        }

        .action-links {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .action-links a {
            padding: 8px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .action-links a:hover {
            background: #218838;
        }

        .text-center {
            text-align: center;
        }
        
        .dash {
            color: #999;
            text-align: center;
        }

        .credit-row {
    background-color: #e7f1ff;  /* light blue */
         }


    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1>Customer Ledger</h1>

        <form method="GET" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>Select Customer *</label>
                    <select name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php while($sup = $customers->fetch_assoc()): ?>
                            <option value="<?php echo $sup['id']; ?>" <?php echo ($customer_id == $sup['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sup['customername']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>" required>
                </div>

                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>" required>
                </div>

                <div class="filter-group">
                    <button type="submit">Show Ledger</button>
                </div>
            </div>
        </form>

        <?php if ($customer_id > 0 && !empty($customer_name)): ?>
            <div class="supplier-info">
                <span>Customer: <?php echo htmlspecialchars($customer_name); ?></span>
                <span>Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?></span>
            </div>

            <!-- Summary Boxes -->
            <div class="summary-boxes">
                <div class="summary-box total-bill">
                    Total Bill Amount: ₹<?php echo number_format(( !empty($customer_total['total'] )? $customer_total['total'] : 0), 2); ?>
                </div>
                <div class="summary-box total-payment">
                    Total Payment: ₹<?php echo number_format(( !empty($customer_payment) ? $customer_payment : 0), 2); ?>
 
                </div>
                <div class="summary-box balance-due">
                    Total Balance Due: ₹<?php echo number_format((!empty ($customer_outstanding) ? $customer_outstanding :0 ), 2); ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Particulars</th>
                        <th>Debit (₹)</th>
                        <th>Credit (₹)</th>
        
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    
                        $running_balance = 0;
                        $running_advance = 0;
                        
                        if($rowCount>0){
                        
                            while ($entry = $purchases->fetch_assoc()) {
                                                       
                                // Generate particulars description
                                $particulars = '';
                                
                                
                        ?>
                            <tr>
                            <td><?php echo $entry['date']; ?></td>
                                <td><?php echo $entry['particulars']; ?></td>
                                <td class="<?php echo ($entry['dr'] > 0) ? 'debit' : ''; ?>"><?php echo $entry['dr']; ?></td>
                                <td class="<?php echo ($entry['cr'] > 0) ? 'credit' : ''; ?>"><?php echo $entry['cr']; ?></td>
                                
                            
                            </tr>
                        <?php }
                    } else{ ?>
                        <td colspan=4 style="text-align: center;"> No Records Found </td>
                    <?php } ?>
                </tbody>
            </table>

            <div class="action-links">
                <a href="sales_payment.php?customer_id=<?php echo $customer_id; ?>">Make Payment</a>
            </div> 
        <?php endif; ?>
    </div>
</body>
</html>