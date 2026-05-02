<?php
session_start();
include "db.php";
include "functions.php"; // Include functions to use cust_outstanding() and sup_outstanding()

// Fetch Cash in Hand total (positive balance - sales/receipts)
$cash_query = "SELECT SUM(IF(amt > 0, amt, 0)) as total_cash_in FROM cash_in_hand";
$cash_result = $conn->query($cash_query);
$cash_data = $cash_result->fetch_assoc();
$cash_total = ($cash_data['total_cash_in'] ?? 0);

// Fetch Bank totals separately for each bank (individual balances)
$bank_query = "SELECT b.id, b.bank_name, 
                      COALESCE((SELECT SUM(IF(amt > 0, amt, 0)) 
                               FROM bank_ledger WHERE bank_id = b.id), 0) as balance 
               FROM bank b 
               ORDER BY b.bank_name";
$bank_result = $conn->query($bank_query);
$bank_total = 0;
$bank_details = [];

if ($bank_result && $bank_result->num_rows > 0) {
    while ($bank = $bank_result->fetch_assoc()) {
        $balance = $bank['balance'];
        $bank_total += $balance;
        $bank_details[] = [
            'name' => $bank['bank_name'] ?? 'Unnamed Bank',
            'balance' => $balance
        ];
    }
}

// Fetch Today's Sales total
$today = date('Y-m-d');
$sales_query = "SELECT SUM(grand_total) as today_sales_total 
                FROM sales 
                WHERE date = '$today'";
$sales_result = $conn->query($sales_query);
$sales_data = $sales_result->fetch_assoc();
$today_sales = $sales_data['today_sales_total'] ?? 0;

// USING FUNCTIONS TO CALCULATE PENDING PAYMENTS
// Get all customers with their details
$customers = $conn->query("SELECT id, customername, phoneno FROM customers ORDER BY customername");
$customers_with_outstanding = [];
$total_customer_pending = 0;

if ($customers && $customers->num_rows > 0) {
    while($customer = $customers->fetch_assoc()) {
        // Use the cust_outstanding function from functions.php
        $outstanding = cust_outstanding($customer['id']);
        
        // Only include customers with outstanding balance > 0
        if ($outstanding > 0) {
            $customers_with_outstanding[] = [
                'id' => $customer['id'],
                'customername' => $customer['customername'],
                'phone' => $customer['phoneno'] ?? 'No phone',
                'outstanding' => $outstanding
            ];
            $total_customer_pending += $outstanding;
        }
    }
}

// Sort customers by outstanding amount (highest first)
usort($customers_with_outstanding, function($a, $b) {
    return $b['outstanding'] <=> $a['outstanding'];
});

// Get ALL customers for display 
$all_customers = $customers_with_outstanding; // This shows ALL customers with pending payments

// Get suppliers with outstanding using sup_outstanding function
$suppliers = $conn->query("SELECT id, suppliers_name, phoneno FROM suppliers ORDER BY suppliers_name");
$suppliers_with_outstanding = [];
$total_supplier_pending = 0;

if ($suppliers && $suppliers->num_rows > 0) {
    while($supplier = $suppliers->fetch_assoc()) {
        // Use the sup_outstanding function from functions.php
        $outstanding = sup_outstanding($supplier['id']);
        
        // Only include suppliers with outstanding balance > 0
        if ($outstanding > 0) {
            $suppliers_with_outstanding[] = [
                'id' => $supplier['id'],
                'suppliers_name' => $supplier['suppliers_name'],
                'phone' => $supplier['phoneno'] ?? 'No phone',
                'outstanding' => $outstanding
            ];
            $total_supplier_pending += $outstanding;
        }
    }
}

// Sort suppliers by outstanding amount (highest first)
usort($suppliers_with_outstanding, function($a, $b) {
    return $b['outstanding'] <=> $a['outstanding'];
});

// Get ALL suppliers for display
$all_suppliers = $suppliers_with_outstanding; // This shows ALL suppliers with pending payments

// Low Stock Alert
$low_stock_limit = 50;
$stock_query = "SELECT pro_name, initialqty FROM products WHERE initialqty <= $low_stock_limit ORDER BY initialqty ASC";
$stock_result = $conn->query($stock_query);



// ========== Fetch Last 30 Days Sales Data for Graph ==========
$last_30_days_sales = [];
$labels = [];
$values = [];

// Get date range for last 30 days
$end_date = date('Y-m-d'); // Today
$start_date = date('Y-m-d', strtotime('-29 days')); // 29 days ago (to get 30 days including today)

// Display date range
$date_range_text = date('d-m-Y', strtotime($start_date)) . ' to ' . date('d-m-Y', strtotime($end_date));

// Query to get sales for last 30 days
$sales_query = "SELECT 
                    DATE(date) as sale_date,
                    COALESCE(SUM(grand_total), 0) as daily_total 
                FROM sales 
                WHERE date BETWEEN '$start_date' AND '$end_date'
                GROUP BY DATE(date)
                ORDER BY sale_date ASC";

$sales_result = $conn->query($sales_query);

if (!$sales_result) {
    die("Query failed: " . $conn->error);
}

// Create an array with all dates of last 30 days initialized to 0
$current_date = $start_date;
$all_dates = [];
while (strtotime($current_date) <= strtotime($end_date)) {
    $date_key = $current_date;
    $day_name = date('D', strtotime($current_date));
    $formatted_date = date('d-m', strtotime($current_date));
    
    $all_dates[$date_key] = [
        'day' => $day_name,
        'formatted' => $formatted_date,
        'total' => 0
    ];
    
    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
}

// Fill in the actual sales data
if ($sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $sale_date = $row['sale_date'];
        if (isset($all_dates[$sale_date])) {
            $all_dates[$sale_date]['total'] = floatval($row['daily_total']);
        }
    }
}

// Prepare data for chart
foreach ($all_dates as $date_key => $data) {
    $labels[] = $data['day'] . ' ' . $data['formatted'];
    $values[] = $data['total'];
    $last_30_days_sales[] = [
        'date' => $date_key,
        'day' => $data['day'],
        'total' => $data['total']
    ];
}

// Convert to JSON for JavaScript
$chart_labels_json = json_encode($labels);
$chart_values_json = json_encode($values);

// Get total sales for last 30 days
$total_query = "SELECT COALESCE(SUM(grand_total), 0) as total_sales 
                FROM sales 
                WHERE date BETWEEN '$start_date' AND '$end_date'";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc();
$last_30_days_total = $total_data['total_sales'];




?>

<html>
<head>
    <title>Dashboard</title>
    <!-- Add Chart.js library for the graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #007bff;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-sizing: border-box;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: white;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a,
        .dropdown-toggle {
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }

        .nav-links a:hover,
        .dropdown-toggle:hover {
            background: rgba(255,255,255,0.2);
        }

        .dropdown-menu {
            display: block;
            position: absolute;
            top: 120%;
            left: 0;
            background: white;
            min-width: 180px;
            border-radius: 6px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            padding: 5px 0;
            margin-top: 6px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }

        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu li a {
            color: #333;
            display: block;
            padding: 10px 15px;
            text-decoration: none;
        }

        .dropdown-menu li a:hover {
            background: #f1f1f1;
        }

        .logout {
            background: #dc3545;
        }

        .logout:hover {
            background: #c82333;
        }

        .arrow {
            font-size: 12px;
            margin-left: 5px;
        }

        .user-info {
            text-align: right;
            color: white;
            font-weight: bold;
            line-height: 1.2;
        }

        .user-info span {
            font-size: 13px;
            font-weight: normal;
            color: #e0e0e0;
        }

        .content {
            margin-top: 90px;
            padding: 20px;
        }

        .submenu {
            position: relative;
        }

        .submenu-toggle {
            display: block;
            padding: 10px 15px;
            color: #333;
            cursor: pointer;
        }

        .submenu-toggle:hover {
            background: #f1f1f1;
        }

        .submenu-menu {
            position: absolute;
            top: 0;
            left: 100%;
            background: white;
            min-width: 160px;
            border-radius: 6px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            padding: 5px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateX(10px);
            transition: all 0.3s ease;
            z-index: 1002;
        }

        .submenu:hover .submenu-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        .dropdown:hover .arrow {
            transform: rotate(180deg);
            transition: 0.3s;
        }

        .submenu-menu li a {
            color: #333;
            display: block;
            padding: 10px 15px;
            text-decoration: none;
        }

        .submenu-menu li a:hover {
            background: #f1f1f1;
        }

        /* 3x3 Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto 30px auto;
        }

        /* For responsive design */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Row 3: Expiry + Graph container */
        .row-three-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        @media (max-width: 992px) {
            .row-three-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }

        .summary-box {
            background: white;
            border-radius: 10px;
            padding: 25px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .summary-box:hover {
            transform: translateY(-5px);
        }

        .summary-box.cash-box {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .summary-box.bank-box {
            background: linear-gradient(135deg, #007bff, #17a2b8);
            color: white;
        }

        .summary-box.sales-box {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }

        .summary-title {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            opacity: 0.9;
            font-weight: 600;
        }

        .summary-amount {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .summary-detail {
            font-size: 14px;
            opacity: 0.8;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 10px;
            margin-top: 10px;
        }

        .bank-details {
            max-height: 150px;
            overflow-y: auto;
            text-align: left;
            font-size: 13px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            padding: 8px;
            margin-top: 10px;
        }

        .bank-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .bank-detail-item:last-child {
            border-bottom: none;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 300px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 2px solid #f0f0f0;
            background: white;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        .card-body {
            padding: 15px 20px;
            overflow-y: auto;
            flex: 1;
        }

        .view-all {
            color: #007bff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        /* Pending List */
        .pending-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .pending-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .pending-item:last-child {
            border-bottom: none;
        }

        .pending-info {
            flex: 2;
        }

        .pending-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .pending-phone {
            font-size: 12px;
            color: #777;
        }

        .pending-amount {
            font-weight: 700;
            font-size: 16px;
            color: #dc3545;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
           padding: 18px 20px; 
            border-top: 2px solid #333;
            font-weight: 700;
            font-size: 16px;
            position: sticky;
            bottom: 0;
            background: white;
            z-index: 10;
             min-height: 55px;  
        }

        .total-label {
            color: #333;
        }

        .total-amount {
            color: #dc3545;
        }

        /* Stock List */
        .stock-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .stock-item:last-child {
            border-bottom: none;
        }

        .stock-name {
            font-weight: 500;
            color: #333;
        }

        .stock-qty {
            font-weight: 600;
            color: #dc3545;
        }

        .stock-message {
            text-align: center;
            color: #28a745;
            padding: 15px 0;
        }

        .warning-icon {
            color: #dc3545;
            margin-right: 5px;
        }
        .card-body::-webkit-scrollbar {
            width: 6px;
        }

        .card-body::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        /* Graph Section */
        .graph-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
            height: 300px;
            display: flex;
            flex-direction: column;
        }

        .graph-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            
        }

        .graph-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
           
        }

        .graph-header h2 span {
            color: #007bff;
            font-size: 14px;
            font-weight: normal;
            margin-left: 10px;
        }

        /* Chart Type Toggle */
        .chart-toggle {
            display: flex;
            gap: 10px;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .toggle-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .toggle-btn:hover {
            background: #e9ecef;
        }

        .toggle-btn.active:hover {
            background: #0056b3;
        }

        canvas {
            width: 100% !important;
            height: 200px !important;
        }
        
        .sales-summary {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .sales-summary span {
            font-weight: bold;
            color: #28a745;
            font-size: 18px;
        }
        /* Expiry Alert Styles */
        .expiry-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .expiry-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .expiry-item:last-child {
            border-bottom: none;
        }

        .expiry-item.expired-item {
            background-color: #f8f9fa;
            border-left: 3px solid #dc3545;
            padding-left: 10px;
            margin-left: -10px;
        }

        .expiry-item.expiring-item {
            border-left: 3px solid #ffc107;
            padding-left: 10px;
            margin-left: -10px;
        }

        .expiry-info {
            flex: 2;
        }

        .expiry-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .expiry-date {
            font-size: 12px;
        }

        .expired-date {
            color: #dc3545;
            font-weight: 500;
        }

        .expiring-date {
            color: #856404;
        }

        .days-left {
            font-size: 11px;
            margin-left: 5px;
            font-weight: 500;
        }

        .days-left.warning {
            color: #ff9800;
        }

        .days-left.critical {
            color: #dc3545;
            font-weight: bold;
        }

        .expiry-stock {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        .expiry-message {
            text-align: center;
            color: #28a745;
            padding: 20px 0;
            font-size: 14px;
        }

        .expiry-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-top: 2px solid #f0f0f0;
            background: white;
        }

        .expired-count {
            color: #dc3545;
            font-weight: 600;
            margin-right: 15px;
        }

        .expiring-count {
            color: #ff9800;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include "dashboard.php"; ?>

    <div class="content">
        <!-- Row 1: Summary Boxes (3 items) -->
        <div class="dashboard-grid">
            <!-- Row 1 Col 1: Cash in Hand -->
            <div class="summary-box cash-box">
                <div class="summary-title">CASH IN HAND</div>
                <div class="summary-amount">₹<?= number_format($cash_total, 2) ?></div>
                <div class="summary-detail">Available Cash Balance</div>
            </div>

            <!-- Row 1 Col 2: Bank Balance -->
            <div class="summary-box bank-box">
                <div class="summary-title">BANK BALANCE</div>
                <div class="summary-amount">₹<?= number_format($bank_total, 2) ?></div>
                <div class="summary-detail">
                    Individual Bank Balances
                    <?php if (!empty($bank_details)): ?>
                        <div class="bank-details">
                            <?php foreach ($bank_details as $bank): ?>
                                <div class="bank-detail-item">
                                    <span><?= htmlspecialchars($bank['name'] ?? 'Bank') ?>:</span>
                                    <span>₹<?= number_format($bank['balance'] ?? 0, 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Row 1 Col 3: Today's Sales -->
            <div class="summary-box sales-box">
                <div class="summary-title">TODAY'S SALES</div>
                <div class="summary-amount">₹<?= number_format($today_sales, 2) ?></div>
                <div class="summary-detail"><?= date('d-m-Y') ?> total sales</div>
            </div>
        </div>

        <!-- Row 2: Cards (3 items) -->
        <div class="dashboard-grid" style="margin-bottom: 30px;">
            <!-- Row 2 Col 1: Pending Payments Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Pending Payments</h2>
                    <a href="cust_ledger.php" class="view-all">View All →</a>
                </div>
                <div class="card-body">
                <div class="pending-list">
                    <?php if (!empty($all_customers)): ?>
                        <?php foreach ($all_customers as $customer): ?>
                            <div class="pending-item">
                                <div class="pending-info">
                                    <div class="pending-name"><?= htmlspecialchars($customer['customername']) ?></div>
                                    <div class="pending-phone"><?= htmlspecialchars($customer['phone']) ?></div>
                                </div>
                                <div class="pending-amount">₹<?= number_format($customer['outstanding'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="pending-item">
                            <div class="pending-info">
                                <div class="pending-name">No pending payments</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                    </div>

                <div class="total-row">
                    <span class="total-label">Total Receivable</span>
                    <span class="total-amount">₹<?= number_format($total_customer_pending, 2) ?></span>
                </div>
            </div>

            <!-- Row 2 Col 2: Payables Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Payables</h2>
                    <a href="sup_ledger.php" class="view-all">View All →</a>
                </div>
             <div class="card-body">
                <div class="pending-list">
                    <?php if (!empty($all_suppliers)): ?>
                        <?php foreach ($all_suppliers as $supplier): ?>
                            <div class="pending-item">
                                <div class="pending-info">
                                    <div class="pending-name"><?= htmlspecialchars($supplier['suppliers_name']) ?></div>
                                    <div class="pending-phone"><?= htmlspecialchars($supplier['phone']) ?></div>
                                </div>
                                <div class="pending-amount">₹<?= number_format($supplier['outstanding'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="pending-item">
                            <div class="pending-info">
                                <div class="pending-name">No payables</div>
                            </div>
                        </div>
                    <?php endif; ?>
                 </div>
                    </div>
                 
                 <div class="total-row">
                    <span class="total-label">Total Payable</span>
                    <span class="total-amount">₹<?= number_format($total_supplier_pending, 2) ?></span>
              </div>
            </div>

            <!-- Row 2 Col 3: Low Stock Alert Card -->
            <div class="card">
                <div class="card-header">
                    <h2><span class="warning-icon">⚠</span> Low Stock Alert</h2>
                </div>
                 <div class="card-body">
                <div class="stock-list">
                    <?php if ($stock_result->num_rows > 0): ?>
                        <?php mysqli_data_seek($stock_result, 0); // Reset pointer ?>
                        <?php while($row = $stock_result->fetch_assoc()): ?>
                            <div class="stock-item">
                                <span class="stock-name"><?= htmlspecialchars($row['pro_name']) ?></span>
                                <span class="stock-qty"><?= $row['initialqty'] ?> units</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="stock-message">
                            ✓ All products stock is healthy
                        </div>
                    <?php endif; ?>
                </div>
                    </div>
            </div>
        </div>

        <!-- Row 3: Expiry Date Alert + Graph (2 items side by side) -->
        <div class="row-three-container">
            <!-- Expiry Date Alert Card -->
            <div class="card">
                <div class="card-header">
                    <h2><span class="warning-icon">📅</span> Expiry Date Alert</h2>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch products with expiry alerts
                    $today_date = date('Y-m-d');
                    $expiry_alert_date = date('Y-m-d', strtotime('+30 days'));
                    
                    $expiry_query = "SELECT pro_name, expirydate, initialqty 
                                    FROM products 
                                    WHERE expirydate IS NOT NULL 
                                    AND expirydate != '' 
                                    AND expirydate <= '$expiry_alert_date'
                                    ORDER BY expirydate ASC";
                    
                    $expiry_result = $conn->query($expiry_query);
                    
                    $expired_count = 0;
                    $expiring_soon_count = 0;
                    ?>
                    
                    <div class="expiry-list">
                        <?php if ($expiry_result && $expiry_result->num_rows > 0): ?>
                            <?php while($row = $expiry_result->fetch_assoc()): 
                                $expiry_date = $row['expirydate'];
                                $is_expired = (strtotime($expiry_date) < strtotime($today_date));
                                $days_remaining = ceil((strtotime($expiry_date) - strtotime($today_date)) / (60 * 60 * 24));
                                
                                if ($is_expired) {
                                    $expired_count++;
                                } else {
                                    $expiring_soon_count++;
                                }
                            ?>
                            <div class="expiry-item <?= $is_expired ? 'expired-item' : 'expiring-item' ?>">
                                <div class="expiry-info">
                                    <div class="expiry-name"><?= htmlspecialchars($row['pro_name']) ?></div>
                                    <div class="expiry-date">
                                        <?php if ($is_expired): ?>
                                            <span class="expired-date">⚠️ Expired on: <?= date('d-m-Y', strtotime($expiry_date)) ?></span>
                                        <?php else: ?>
                                            <span class="expiring-date">📅 Expires: <?= date('d-m-Y', strtotime($expiry_date)) ?></span>
                                            <span class="days-left <?= $days_remaining <= 7 ? 'critical' : 'warning' ?>">
                                                (<?= $days_remaining ?> days left)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="expiry-stock">Stock: <?= $row['initialqty'] ?> units</div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="expiry-message">
                                ✓ No products expiring soon
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($expiry_result && $expiry_result->num_rows > 0): ?>
                <div class="total-row expiry-summary">
                    <span class="total-label">
                        <?php if ($expired_count > 0): ?>
                            <span class="expired-count">🗑️ Expired: <?= $expired_count ?></span>
                        <?php endif; ?>
                        <?php if ($expiring_soon_count > 0): ?>
                            <span class="expiring-count">⚠️ Expiring soon: <?= $expiring_soon_count ?></span>
                        <?php endif; ?>
                    </span>
                    <a href="view_products.php" class="view-all" style="font-size: 12px;">View All Products →</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Graph Section -->
            <div class="graph-container">
                <div class="graph-header">
                    <h2>Last 30 Days Sales 
                        <span>(<?= $date_range_text ?>)</span>
                    </h2>
                    <div class="chart-toggle">
                        <button class="toggle-btn active" id="barBtn" onclick="changeChartType('bar')">Bar</button>
                        <button class="toggle-btn" id="lineBtn" onclick="changeChartType('line')">Line</button>
                    </div>
                </div>
                <canvas id="salesChart"></canvas>
                
                <!-- Sales Summary -->
                <div class="sales-summary">
                    Total Sales for Last 30 Days: <span>₹ <?= number_format($last_30_days_total, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get data from PHP
        const labels = <?php echo $chart_labels_json; ?>;
        const values = <?php echo $chart_values_json; ?>;
        
        let chart; // Variable to hold chart instance

        // Function to create/update chart
        function createChart(type) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (chart) {
                chart.destroy();
            }
            
            // Chart configuration
            const config = {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales Amount (₹)',
                        data: values,
                        backgroundColor: type === 'bar' ? 'rgba(54, 162, 235, 0.5)' : 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 3,
                        tension: 0.3, // For line smoothing
                        fill: type === 'line' ? false : true,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        ...(type === 'bar' && { borderRadius: 5, barPercentage: 0.6 })
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₹ ' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            };
            
            chart = new Chart(ctx, config);
        }

        // Function to change chart type
        function changeChartType(type) {
            // Update active button states
            document.getElementById('barBtn').classList.remove('active');
            document.getElementById('lineBtn').classList.remove('active');
            
            if (type === 'bar') {
                document.getElementById('barBtn').classList.add('active');
            } else {
                document.getElementById('lineBtn').classList.add('active');
            }
            
            // Recreate chart with new type
            createChart(type);
        }

        // Initialize with bar chart
        window.onload = function() {
            createChart('bar');
        };
    </script>
</body>
</html>