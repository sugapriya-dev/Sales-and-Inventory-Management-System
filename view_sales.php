<?php
session_start();
include "db.php"; 

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Get filter values
$customer_id = isset($_GET['customer_id']) && $_GET['customer_id'] != '' ? intval($_GET['customer_id']) : 0;
$from_date = isset($_GET['from_date']) && $_GET['from_date'] != '' ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) && $_GET['to_date'] != '' ? $_GET['to_date'] : '';
$invoice_no = isset($_GET['invoice_no']) && $_GET['invoice_no'] != '' ? $_GET['invoice_no'] : '';

$limit = 10; // records per page

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

$offset = ($page - 1) * $limit;


// Build query with filters
$query = "SELECT 
       s.id,
       s.customer_id,
       c.customername,
       s.date,
       s.invoiceno,
       s.mode,
       s.bankname AS bank_name,  
       s.grand_total,
       s.created_at
FROM sales s
INNER JOIN customers c ON s.customer_id = c.id
WHERE 1=1";


$countQuery = "SELECT COUNT(*) as total 
FROM sales s
INNER JOIN customers c ON s.customer_id = c.id
WHERE 1=1";

// Add filters
if($customer_id > 0) {
    $query .= " AND s.customer_id = $customer_id";
    $countQuery .= " AND s.customer_id = $customer_id";
}

if($from_date != '') {
    $query .= " AND s.date >= '$from_date'";
    $countQuery .= " AND s.date >= '$from_date'";
}

if($to_date != '') {
    $query .= " AND s.date <= '$to_date'";
    $countQuery .= " AND s.date <= '$to_date'";
}

if($invoice_no != '') {
    $query .= " AND s.invoiceno LIKE '%$invoice_no%'";
    $countQuery .= " AND s.invoiceno LIKE '%$invoice_no%'";
}

$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

$query .= " ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset";

$res = $conn->query($query);

$username = $_SESSION['username'];

$stmt = $conn->prepare( "SELECT lastlogin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$resultUser = $stmt->get_result();
$userData = $resultUser->fetch_assoc();
$stmt->close();

    
    
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales List</title>
    <style>
    body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;display: flex;
    justify-content: center;   /* horizontal center */
    align-items: center;       /* vertical center */
    min-height: 100vh;         /* full viewport height */
    margin: 0;                 /* remove default body margin */
    background: #f0f0f0;
        }

        .container {
            width: 80%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        a {
            text-decoration: none;
            color: #007bff;
            
        }

        .top-links {
            text-align: right;
            margin-bottom: 10px;
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
            box-sizing: border-box;
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

        .filter-group a {
            display: block;
            margin-top: 5px;
            text-align: center;
        }

        /* Pagination Styles */
        .pagination {
            margin-top: 30px;
            text-align: center;
            padding: 20px 0;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 4px;
            background: white;
            color: #007bff;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            font-weight: 500;
            font-size: 14px;
            min-width: 40px;
        }

        .pagination a:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,123,255,0.3);
            border-color: #007bff;
        }

        .pagination span.current {
            background: #007bff;
            color: white;
            border-color: #007bff;
            cursor: default;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .pagination .disabled:hover {
            transform: none;
            box-shadow: none;
        }
        .reset-btn {
            display: block;
            margin-top: 5px;
            text-align: center;
            padding: 8px 15px;
            background: #6c757d; /* grey color */
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: 0.3s;
        }

        .reset-btn:hover {
            background: #5a6268;
        }

</style>

</head>
<body>
    <?php include "dashboard.php"; ?>

<div class="container">
  <h1>Sale List</h1>

  <form method="GET" action="">
    <div class="filter-section">
        <div class="filter-group">
            <label>Customer</label>
            <select name="customer_id">
                <option value="">-- All Customers --</option>
                <?php
                $customers = $conn->query("SELECT id, customername FROM customers ORDER BY customername");
                while($cust = $customers->fetch_assoc()):
                    $selected = (isset($_GET['customer_id']) && $_GET['customer_id'] == $cust['id']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $cust['id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($cust['customername']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="from_date" value="<?php echo isset($_GET['from_date']) ? $_GET['from_date'] : ''; ?>">
        </div>

        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="to_date" value="<?php echo isset($_GET['to_date']) ? $_GET['to_date'] : ''; ?>">
        </div>

        <div class="filter-group">
            <label>Invoice No</label>
            <input type="text" name="invoice_no" placeholder="Search invoice..." value="<?php echo isset($_GET['invoice_no']) ? $_GET['invoice_no'] : ''; ?>">
        </div>

        <div class="filter-group">
            <button type="submit">Filter</button>
            <a href="view_sales.php" class="reset-btn">Reset</a>
        </div>
    </div>
</form>

  
  <div class="top-links">
      <a href="sales.php">ADD SALE</a>
  </div>
      <table>
        <tr>
            <th>Id</th>
            <th>Customer</th>
            <th>Sale Date</th>
            <th>Invoice No</th>
            <th>Mode</th>
            <th>Bank Name</th>
            <th>Total Amount</th>
            <th>Actions</th>
        </tr>


       <?php
           if($res->num_rows > 0) :?>
           
           <?php while($row =$res->fetch_assoc()) :?>
            <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['customername']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['invoiceno']); ?></td>
                    <td><?php echo htmlspecialchars($row['mode']); ?></td>
                    <td><?php echo !empty($row['bank_name']) ? htmlspecialchars($row['bank_name']) : '-';?> </td>
                   <td><?php echo htmlspecialchars($row['grand_total']); ?></td>
                    <td>
                          <a href="print_bill.php?id=<?php echo $row['id']; ?>" target="_blank">Print</a> |
                        <a href="edit_sale.php?id=<?php echo $row['id'];?>">Edit</a> |
                         <a href="delete_sale.php?id=<?php echo $row['id'];?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                    
            </tr>
             <?php endwhile; ?>
        <?php else: ?>
            <tr>
               <td colspan="8">No Sales found</td>
            </tr>
        <?php endif; ?>
      </table>
        
      <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&customer_id=<?php echo $customer_id; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&invoice_no=<?php echo $invoice_no; ?>">« Prev</a>
            <?php else: ?>
                <span class="disabled">« Prev</span>
            <?php endif; ?>

            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <?php if($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&customer_id=<?php echo $customer_id; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&invoice_no=<?php echo $invoice_no; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&customer_id=<?php echo $customer_id; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&invoice_no=<?php echo $invoice_no; ?>">Next »</a>
            <?php else: ?>
                <span class="disabled">Next »</span>
            <?php endif; ?>
        </div>



</div>   


</body>
</html>
