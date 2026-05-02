<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$messageClass = "";

// Get supplier ID from URL
$supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

// Fetch supplier details
$supplier_name = "";
if ($supplier_id > 0) {
    $stmt = $conn->prepare("SELECT suppliers_name FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $supplier_name = $row['suppliers_name'];
    }
    $stmt->close();
}

// Fetch purchases for dropdown
$purchases = [];
if ($supplier_id > 0) {
    $purchaseQuery = "SELECT p.id, p.invoiceno, p.date, p.totalamt 
                      FROM purchase p 
                      WHERE p.supplier_id = ? 
                      ORDER BY p.date DESC";
    $stmt = $conn->prepare($purchaseQuery);
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $purchases = $stmt->get_result();
}

// Create purchase_returns table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS purchase_returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        purchase_id INT,
        return_date DATE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        reason TEXT,
        reference_no VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
        FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE SET NULL
    )
");

if (isset($_POST['saveReturn'])) {
    $supplier_id = $_POST['supplier_id'];
    $purchase_id = $_POST['purchase_id'];
    $return_date = $_POST['return_date'];
    $amount = $_POST['amount'];
    $reason = $_POST['reason'];
    $reference_no = $_POST['reference_no'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert purchase return
        $returnStmt = $conn->prepare(
            "INSERT INTO purchase_returns 
            (supplier_id, purchase_id, return_date, amount, reason, reference_no) 
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $returnStmt->bind_param("iisdss", $supplier_id, $purchase_id, $return_date, $amount, $reason, $reference_no);
        
        if (!$returnStmt->execute()) {
            throw new Exception("Error inserting return: " . $returnStmt->error);
        }
        
        $return_id = $returnStmt->insert_id;
        
        // Create ledger table if not exists
        $conn->query("
            CREATE TABLE IF NOT EXISTS supplier_ledger (
                id INT AUTO_INCREMENT PRIMARY KEY,
                supplier_id INT NOT NULL,
                transaction_date DATE NOT NULL,
                transaction_type ENUM('purchase', 'payment', 'return', 'opening') NOT NULL,
                reference_id INT,
                reference_no VARCHAR(100),
                particulars VARCHAR(255),
                debit DECIMAL(10,2) DEFAULT 0,
                credit DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
            )
        ");
        
        // Insert ledger entry (Credit - Return reduces liability)
        $particulars = "Purchase Return: " . substr($reason, 0, 200);
        $ledgerStmt = $conn->prepare(
            "INSERT INTO supplier_ledger 
            (supplier_id, transaction_date, transaction_type, reference_id, reference_no, particulars, credit) 
            VALUES (?, ?, 'return', ?, ?, ?, ?)"
        );
        
        $ledgerStmt->bind_param("isissd", $supplier_id, $return_date, $return_id, $reference_no, $particulars, $amount);
        
        if (!$ledgerStmt->execute()) {
            throw new Exception("Error inserting ledger entry: " . $ledgerStmt->error);
        }
        
        $conn->commit();
        
        echo "<script>
            alert('Purchase Return Saved Successfully');
            window.location='sup_ledger.php?supplier_id=" . $supplier_id . "';
          </script>";
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $messageClass = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Purchase Return</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
            color: #555;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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

        .action-links {
            margin-top: 20px;
            text-align: center;
        }

        .action-links a {
            text-decoration: none;
            color: #007bff;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1>Purchase Return / Credit Note</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($supplier_id > 0 && !empty($supplier_name)): ?>
            <form method="POST" action="">
                <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">

                <label>Supplier</label>
                <input type="text" value="<?php echo htmlspecialchars($supplier_name); ?>" readonly>

                <label>Select Purchase Invoice *</label>
                <select name="purchase_id" required>
                    <option value="">-- Select Invoice --</option>
                    <?php 
                    if ($purchases && $purchases->num_rows > 0) {
                        while($purchase = $purchases->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $purchase['id']; ?>">
                            <?php echo $purchase['invoiceno']; ?> - <?php echo date('d-m-Y', strtotime($purchase['date'])); ?> (₹<?php echo $purchase['totalamt']; ?>)
                        </option>
                    <?php 
                        endwhile;
                    } else {
                        echo '<option value="">No purchases found</option>';
                    }
                    ?>
                </select>

                <label>Return Date *</label>
                <input type="date" name="return_date" value="<?php echo date('Y-m-d'); ?>" required>

                <label>Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required>

                <label>Reference No (Optional)</label>
                <input type="text" name="reference_no">

                <label>Reason for Return *</label>
                <textarea name="reason" rows="3" required></textarea>

                <button type="submit" name="saveReturn">Save Return</button>
            </form>

            <div class="action-links">
              <!--  <a href="supplier_ledger.php?supplier_id=<?php echo $supplier_id; ?>">View Ledger</a>-->
                <a href="sup_ledger.php">Back to Suppliers</a>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #dc3545;">Invalid Supplier</p>
            <div class="action-links">
                <a href="supplier_display.php">Back to Suppliers</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>