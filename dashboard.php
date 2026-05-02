<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
  
include "db.php";


//require_once "nav.php";


?> 
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding-top: 25px;
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

        /* Dropdown */
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

        /* Page content spacing */
        .content {
            margin-top: 90px;
            padding: 20px;
        }

        /* Submenu Style */
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

        /* Dashboard Summary Boxes */
        .summary-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .summary-box {
            flex: 1;
            min-width: 250px;
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

        .summary-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .summary-title {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .summary-amount {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
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

        .welcome-message {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 18px;
            color: #495057;
        }

        .welcome-message strong {
            color: #007bff;
        }
    </style>
</head>

<body>

<div class="navbar">
    <div class="logo">MyStore</div>

    <div class="nav-right">
        <ul class="nav-links">
            <li><a href="nav.php">Dashboard</a></li>

            <li class="dropdown">
                <span class="dropdown-toggle">Masters <span class="arrow">▼</span>
                </span>
                <ul class="dropdown-menu">
                    <li class="submenu">
                        <span class="submenu-toggle">Items ▶</span>
                        <ul class="submenu-menu">
                            <li><a href="view_products.php">Products</a></li>
                            <li><a href="view_categories.php">Category</a></li>
                        </ul>
                    </li>

                    <li><a href="companyinfo.php">Company</a></li>
                    <li><a href="view_gstslab.php">GST Slab</a></li>

                </ul>
            </li>

            

           <li class="dropdown">
                <span class="dropdown-toggle">Billing <span class="arrow">▼</span>
                </span>
                <ul class="dropdown-menu">
                    <li><a href="customer_display.php">Customers</a></li>
                     <li><a href="view_sales.php">Sales List</a></li>
                    <li><a href="sales.php">Add Sales</a></li>
                   
                    
                </ul>
            </li>

            <li class="dropdown">
                <span class="dropdown-toggle">Purchase <span class="arrow">▼</span>
                </span>
                <ul class="dropdown-menu">
                    <li><a href="supplier_display.php">Suppliers</a></li>
                    <li><a href="view_purchase.php">Purchase List</a></li>
                    <li><a href="purchase.php">Add Purchase</a></li>
                </ul>
            </li>


             <li class="dropdown">
                <span class="dropdown-toggle">Ledger <span class="arrow">▼</span>
                </span>
                <ul class="dropdown-menu">
                    <li><a href="sup_ledger.php">Supplier Ledger</a></li>
                    <li><a href="cust_ledger.php">Customer Ledger</a></li>
                    <li><a href="cash_in_hands_ledger.php">Cash in Hands Ledger</a></li>
                    <li><a href="bank_ledger.php">Bank Ledger</a></li>
                    <li><a href="stock_ledger.php">Stock Ledger</a></li>
                    
                </ul>
            </li>
            
          <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <li><a href="users.php">User Management</a></li>
            <?php endif; ?>
           
            <li><a href="logout.php" class="logout">Logout</a></li>

            

        </ul>

        <!-- Add this after the closing ul tag -->
        <div class="user-info">
            <?php 
            if(isset($_SESSION['username'])) {
                echo htmlspecialchars($_SESSION['username']); 
            }
            ?><br>
            <span>Role: 
                <?php 
                if(isset($_SESSION['role'])) {
                    echo htmlspecialchars($_SESSION['role']); 
                }
                ?>
            </span>
        </div>

       
    </div>
</div>



<script>
    // Attach click to ALL dropdown toggles
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();

            // Close other dropdowns
            document.querySelectorAll('.dropdown').forEach(function(d) {
                if (d !== toggle.parentElement) {
                    d.classList.remove('active');
                }
            });

            // Toggle current
            toggle.parentElement.classList.toggle('active');
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown').forEach(function(d) {
            d.classList.remove('active');
        });
    });

    // Submenu toggle (Items)
    document.querySelectorAll('.submenu-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();

            // Close other submenus inside same dropdown
            let parentDropdown = toggle.closest('.dropdown');

            parentDropdown.querySelectorAll('.submenu').forEach(function(sub) {
                if (sub !== toggle.parentElement) {
                    sub.classList.remove('active');
                }
            });

            // Toggle current submenu
            toggle.parentElement.classList.toggle('active');
        });
    });
</script>

</body>
</html>