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

// For product details view
$selected_product = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Fetch all products for dropdown
$products_list = [];
$products_query = "SELECT pid, pro_name FROM products ORDER BY pro_name ASC";
$products_result = $conn->query($products_query);
if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $products_list[] = $row;
    }
}

// Current Stock Data - Using your query
$stock_data = [];
$total_initial = 0;
$total_purchased = 0;
$total_sold = 0;
$total_balance = 0;

if (isset($_GET['filter']) && $selected_product == 0) {
    // Your exact query for current stock
    $stock_query = "select stock.pid, stock.productname,stock.opening,stock.purchase,stock.sales,(stock.opening+ stock.purchase- stock.sales)as balance from  (SELECT p.pid, p.pro_name as productname, COALESCE( p.initialqty ,0)as opening ,  COALESCE((select sum(quantity)from purchase_items where product_id=p.pid),0) as purchase , COALESCE((select sum(quantity)from sales_items where product_id=p.pid),0) as sales  from products as p)as stock;";
    
    $result = $conn->query($stock_query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stock_data[] = $row;
            $total_initial += $row['opening'];
            $total_purchased += $row['purchase'];
            $total_sold += $row['sales'];
            $total_balance += $row['balance'];
        }
    }
}

// Product Details View - When a specific product is selected
$product_details = [];
$transaction_data = [];
$product_total_initial = 0;
$product_total_purchased = 0;
$product_total_sold = 0;
$product_total_balance = 0;

if ($selected_product > 0 && isset($_GET['filter'])) {
    // Get product name
    $product_info = $conn->query("SELECT pro_name, initialqty FROM products WHERE pid = $selected_product");
    if ($product_info && $product_info->num_rows > 0) {
        $product_details = $product_info->fetch_assoc();
    }
    
    // Your exact transaction query
  
$transaction_query = "
    SELECT
        DATE(COALESCE(pr.updated_at, pr.created_at)) AS date,
        COALESCE(pr.updated_at, pr.created_at) AS transaction_datetime,
        'OPENING' AS type,
        pr.initialqty AS qty_change,
        'Opening Stock' AS description,
        pr.initialqty AS in_qty,
        0 AS out_qty
    FROM products pr
    WHERE pr.pid = $selected_product

    UNION ALL

    SELECT 
        p.date AS date,
        p.date AS transaction_datetime,
        'PURCHASE' AS type,
        pi.quantity AS qty_change,
        CONCAT('Purchased via Bill #', COALESCE(p.invoiceno, 'N/A')) AS description,
        pi.quantity AS in_qty,
        0 AS out_qty
    FROM purchase_items pi
    JOIN purchase p ON p.id = pi.purchase_id
    WHERE pi.product_id = $selected_product
    AND p.date BETWEEN '$from_date' AND '$to_date'

    UNION ALL

    SELECT 
        s.date AS date,
        s.date AS transaction_datetime,
        'SALE' AS type,
        -si.quantity AS qty_change,
        CONCAT('Sold via Bill #', s.invoiceno) AS description,
        0 AS in_qty,
        si.quantity AS out_qty
    FROM sales_items si
    JOIN sales s ON s.id = si.sales_id
    WHERE si.product_id = $selected_product
    AND s.date BETWEEN '$from_date' AND '$to_date'

    ORDER BY transaction_datetime ASC";
    
    $result = $conn->query($transaction_query);
    
    if ($result && $result->num_rows > 0) {
        $running_balance = 0;
        $product_total_purchased = 0; // This will store only purchase quantities
        $product_total_sold = 0;       // This will store only sale quantities
        while ($row = $result->fetch_assoc()) {
            //  Calculate running balance and Simply add qty_change for ALL transaction types
            //  Positive for OPENING and PURCHASE
            //  Negative for SALE
             $running_balance += $row['qty_change'];
        
            $row['balance'] = $running_balance;
            $transaction_data[] = $row;
            
            // Calculate totals for purchase(in Qty) and sales(out qty)
            if ($row['type'] == 'PURCHASE' || $row['type'] == 'OPENING') {
                $product_total_purchased += $row['in_qty'];
            }
            if ($row['type'] == 'SALE') {
                $product_total_sold += $row['out_qty'];
            }
        }
        
        $product_total_initial = $product_details['initialqty'];
        $product_total_balance = $running_balance;
       // $product_total_balance = $product_total_purchased-$product_total_sold;
    }
}

// Helper to determine stock status class
function getStockStatusClass($balance) {
    if ($balance <= 0) return 'status-out';
    if ($balance < 10) return 'status-low';
    return 'status-ok';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stock Ledger</title>
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
            flex-wrap: wrap;
        }

        .summary-box {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .summary-box.initial {
            background: #cce5ff;
            color: #004085;
        }

        .summary-box.purchased {
            background: #d4edda;
            color: #155724;
        }

        .summary-box.sold {
            background: #f8d7da;
            color: #721c24;
        }

        .summary-box.balance {
            background: #fff3cd;
            color: #856404;
        }

        .product-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
            min-width: 1000px;
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

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        .status-low {
            background: #fff3cd;
            color: #856404;
        }
        .status-out {
            background: #f8d7da;
            color: #721c24;
        }

        .type-purchase {
            background: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }

        .type-sale {
            background: #f8d7da;
            color: #721c24;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }

        .type-opening {
            background: #cce5ff;
            color: #004085;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }

        .in-qty {
            color: #28a745;
            font-weight: 500;
        }

        .out-qty {
            color: #dc3545;
            font-weight: 500;
        }

        .product-link {
            color: #007bff;
            text-decoration: none;
        }
        .product-link:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            padding: 5px 10px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1>Stock Ledger</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $messageClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>Select Product</label>
                    <select name="product_id">
                        <option value="0">-- All Products (Current Stock) --</option>
                        <?php foreach ($products_list as $product): ?>
                            <option value="<?php echo $product['pid']; ?>" <?php echo ($selected_product == $product['pid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['pro_name']); ?>
                            </option>
                        <?php endforeach; ?>
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
                    <button type="submit" name="filter" value="1">Show Stock</button>
                </div>
            </div>
        </form>

        <?php if (isset($_GET['filter'])): ?>
            <div class="period-info">
                Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>
                <?php if ($selected_product > 0 && !empty($product_details)): ?>
                    - Product: <?php echo htmlspecialchars($product_details['pro_name']); ?>
                <?php endif; ?>
            </div>

            <?php if ($selected_product == 0): ?>
                <!-- CURRENT STOCK VIEW - All Products using your query -->
                
                <!-- Summary Boxes -->
                <div class="summary-boxes">
                    <div class="summary-box initial">
                        Total Initial Qty: <?php echo number_format($total_initial, 2); ?>
                    </div>
                    <div class="summary-box purchased">
                        Total Purchased: <?php echo number_format($total_purchased, 2); ?>
                    </div>
                    <div class="summary-box sold">
                        Total Sold: <?php echo number_format($total_sold, 2); ?>
                    </div>
                    <div class="summary-box balance">
                        Total Balance: <?php echo number_format($total_balance, 2); ?>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Initial Qty</th>
                            <th>Purchased Qty</th>
                            <th>Sold Qty</th>
                            <th>Balance Qty</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stock_data)): 
                            foreach ($stock_data as $item): 
                                $status_class = getStockStatusClass($item['balance']);
                                $status_text = ($item['balance'] <= 0) ? 'Out of Stock' : (($item['balance'] < 10) ? 'Low Stock' : 'In Stock');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['productname']); ?></td>
                                <td class="text-right"><?php echo number_format($item['opening'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($item['purchase'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($item['sales'], 2); ?></td>
                                <td class="text-right"><strong><?php echo number_format($item['balance'], 2); ?></strong></td>
                                <td class="text-center">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="?product_id=<?php echo $item['pid']; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&filter=1" class="product-link">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            
                            <!-- Total Row -->
                            <tr class="total-row">
                                <td class="text-right"><strong>Total</strong></td>
                                <td class="text-right"><strong><?php echo number_format($total_initial, 2); ?></strong></td>
                                <td class="text-right"><strong><?php echo number_format($total_purchased, 2); ?></strong></td>
                                <td class="text-right"><strong><?php echo number_format($total_sold, 2); ?></strong></td>
                                <td class="text-right"><strong><?php echo number_format($total_balance, 2); ?></strong></td>
                                <td></td>
                                <td></td>
                            </tr>
                            
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No products found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($selected_product > 0 && !empty($transaction_data)): ?>
                <!-- PRODUCT DETAILS VIEW - Individual product transactions -->
                
                <a href="?filter=1&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="back-link">← Back to All Products</a>

                <!-- Product Info -->
                <div class="product-info">
                    <strong><?php echo htmlspecialchars($product_details['pro_name']); ?></strong> - Transaction Details
                </div>

                <!-- Summary Boxes for selected product -->
                <div class="summary-boxes">
                    <div class="summary-box initial">
                        Opening Stock: <?php echo number_format($product_total_initial, 2); ?>
                    </div>
                    <div class="summary-box purchased">
                        Purchased (Period): <?php echo number_format($product_total_purchased - $product_total_initial, 2); ?>
                    </div>
                    <div class="summary-box sold">
                        Sold (Period): <?php echo number_format($product_total_sold, 2); ?>
                    </div>
                    <div class="summary-box balance">
                        Current Stock: <?php echo number_format($product_total_balance, 2); ?>
                    </div>
                </div>

                <!-- Detailed Transaction Table -->
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>In Qty</th>
                            <th>Out Qty</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($transaction_data as $trans): 
                            $type_class = '';
                            if ($trans['type'] == 'PURCHASE') {
                                $type_class = 'type-purchase';
                            } elseif ($trans['type'] == 'SALE') {
                                $type_class = 'type-sale';
                            } else {
                                $type_class = 'type-opening';
                            }
                        ?>
                            <tr>
                                <td>
                                    <?php 
                                    if (!empty($trans['transaction_datetime']) && $trans['transaction_datetime'] != '0000-00-00 00:00:00') {
                                        echo date("d-m-Y H:i:s", strtotime($trans['transaction_datetime']));
                                    } else {
                                        echo date("d-m-Y", strtotime($trans['date']));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="<?php echo $type_class; ?>">
                                        <?php echo $trans['type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($trans['description']); ?></td>
                                <td class="text-right in-qty">
                                    <?php echo ($trans['in_qty'] > 0) ? number_format($trans['in_qty'], 2) : '-'; ?>
                                </td>
                                <td class="text-right out-qty">
                                    <?php echo ($trans['out_qty'] > 0) ? number_format($trans['out_qty'], 2) : '-'; ?>
                                </td>
                               <td class="text-right out-qty">
                                    <?php echo ($trans['balance'] > 0) ? number_format($trans['balance'], 2) : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <p class="text-center">No transaction data found for the selected product in this period</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>