<?php
session_start();
include "db.php";
include "functions.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$message = "";

// Get supplier details
$supplier = [];
if ($supplier_id > 0) {
    $result = $conn->query("SELECT * FROM suppliers WHERE id = $supplier_id");
    $supplier = $result->fetch_assoc();
}

// Get outstanding amount
$outstanding = 0;
if ($supplier_id > 0) {
    $outstanding = sup_outstanding($supplier_id);
}

// Get banks for dropdown - from bank table
$banks = $conn->query("SELECT * FROM bank ORDER BY bank_name");

if (isset($_POST['makePayment'])) {
    $supplier_id = $_POST['supplier_id'];
    $payment_date = $_POST['payment_date'];
    $amount = $_POST['amount'];
    $payment_mode = $_POST['payment_mode'];
    $bank_id = $_POST['bank_id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $reference_no = "PAYMENT" ;
    
    // Get supplier name
    $sup_result = $conn->query("SELECT suppliers_name FROM suppliers WHERE id = $supplier_id");
    $sup_data = $sup_result->fetch_assoc();
    $supplier_name = $sup_data['suppliers_name'];
    
    // Get bank name if bank mode
    $bankname = null;
    if ($payment_mode == "bank" && !empty($bank_id)) {
        $bank_query = $conn->query("SELECT bank_name FROM bank WHERE id = $bank_id");
        if ($bank_row = $bank_query->fetch_assoc()) {
            $bankname = $bank_row['bank_name'];
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Insert into supplier_payments table first
        $particulars = "Payment to Supplier - " . $supplier_name;
        if (!empty($remarks)) {
            $particulars .= " - " . $remarks;
        }
        
        $payment_insert = $conn->prepare("INSERT INTO supplier_payments 
            (supplier_id, payment_date, amount, payment_mode, bank_name, reference_no, particulars) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $payment_insert->bind_param("isdssss", 
            $supplier_id,
            $payment_date,
            $amount,
            $payment_mode,
            $bankname,
            $reference_no,
            $particulars
        );
        
        if (!$payment_insert->execute()) {
            throw new Exception("Error inserting into supplier_payments: " . $conn->error);
        }
        
        // Get the generated supplier_payment_id
        $supplier_payment_id = $conn->insert_id;
        
        if ($payment_mode == "cash") {
            // Insert into cash_in_hand
            $particulars = "Payment to Supplier - " . $supplier_name;
            $amt = -$amount; // Negative for payment (money going out)
            
            $insert = $conn->prepare("INSERT INTO cash_in_hand 
                (transaction_date, transaction_type, reference_id, reference_no, payee, particulars, amt) 
                VALUES (?, 'Payment', ?, ?, ?, ?, ?)");
            
            $insert->bind_param("sisssd", 
                $payment_date,
                $supplier_payment_id,  // Using supplier_payment_id as reference_id(supplier_payment table id)
                $reference_no,
                $supplier_id,
                $particulars,
                $amt
            );
            
            if (!$insert->execute()) {
                throw new Exception("Error inserting into cash_in_hand: " . $conn->error);
            }
        }
        elseif ($payment_mode == "bank" && !empty($bank_id)) {
            // Insert into bank_ledger
            $particulars = "Payment to Supplier - " . $supplier_name;
            $amt = -$amount; // Negative for payment (money going out)
            
            $insert = $conn->prepare("INSERT INTO bank_ledger 
                (transaction_date, transaction_type, reference_id, reference_no, payee, particulars, amt,bank_id) 
                VALUES (?, 'Payment', ?, ?, ?, ?, ?,?)");
            
            $insert->bind_param("sisssdi", 
                $payment_date,
                $supplier_payment_id,  // Using supplier_payment_id as reference_id(supplier_payment table id)
                $reference_no,
                $supplier_id,
                $particulars,
                $amt,
                $bank_id
            );
            
            if (!$insert->execute()) {
                throw new Exception("Error inserting into bank_ledger: " . $conn->error);
            }
        }
        
        // Commit transaction if all successful
        $conn->commit();
        $message = "Payment saved successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction if any error occurs
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
    
    echo "<script>alert('$message'); window.location='sup_ledger.php?supplier_id=$supplier_id';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Payment - Supplier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .outstanding {
            color: #dc3545;
            font-weight: bold;
            font-size: 18px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
        }
        .bank-section {
            display: none;
            margin-top: 10px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #218838;
        }
        .cancel-btn {
            background: #6c757d;
            margin-top: 10px;
        }
        .cancel-btn:hover {
            background: #5a6268;
        }
        .message {
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
    <script>
        function toggleBankField() {
            var mode = document.getElementById('payment_mode').value;
            var bankSection = document.getElementById('bank_section');
            if (mode == 'bank') {
                bankSection.style.display = 'block';
            } else {
                bankSection.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <?php include "dashboard.php"; ?>
    
    <div class="container">
        <h2>Make Payment to Supplier</h2>
        
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Supplier:</span>
                <span class="info-value"><?php echo $supplier['suppliers_name'] ?? ''; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo $supplier['phoneno'] ?? ''; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Outstanding:</span>
                <span class="info-value outstanding">₹ <?php echo number_format($outstanding, 2); ?></span>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
            
            <div class="form-group">
                <label>Payment Date *</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Amount (₹) *</label>
                <input type="number" name="amount" step="0.01" min="1" max="<?php echo $outstanding; ?>" required placeholder="Enter amount">
            </div>
            
            <div class="form-group">
                <label>Payment Mode *</label>
                <select name="payment_mode" id="payment_mode" onchange="toggleBankField()" required>
                    <option value="">Select Mode</option>
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                </select>
            </div>
            
            <div class="form-group bank-section" id="bank_section">
                <label>Select Bank *</label>
                <select name="bank_id">
                    <option value="">Select Bank</option>
                    <?php while($bank = $banks->fetch_assoc()): ?>
                        <option value="<?php echo $bank['id']; ?>">
                            <?php echo $bank['bank_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Remarks (Optional)</label>
                <textarea name="remarks" rows="3" placeholder="Enter any notes"></textarea>
            </div>
            
            <button type="submit" name="makePayment">Make Payment</button>
            <button type="button" class="cancel-btn" onclick="window.location='sup_ledger.php?supplier_id=<?php echo $supplier_id; ?>'">Cancel</button>
        </form>
    </div>
</body>
</html>