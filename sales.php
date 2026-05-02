<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if (isset($_POST['saveSale']) || isset($_POST['saveSalePrint'])) {

    // ========== GET FORM DATA ==========
    $customer_id = $_POST['customer_id'];
    $date = !empty($_POST['date']) ? $_POST['date'] : date("Y-m-d");
    $invoiceno = $_POST['invoiceno'] ?? '';
    $mode = $_POST['mode'];
    $bank_id = $_POST['bank_id'] ?? '';
    $totalamt = $_POST['subTotal'] ?? 0;
    $credit = $_POST['credit'] ?? null;
    $discount_total = $_POST['cashDiscount'] ?? 0;
    $gst_total = $_POST['totalGST'] ?? 0;
    $packing_charge = $_POST['packingCharge'] ?? 0;
    $round_off = $_POST['roundOff'] ?? 0;
    $grand_total = $_POST['grandTotal'] ?? 0;
    $round_off = $_POST['roundOff'] ?? 0;

    // ========== GET BANK NAME ==========
     $bankname = null;
    if ($mode == "bank" && !empty($bank_id)) {
        $bank_query = $conn->query("SELECT bank_name FROM bank WHERE id = $bank_id");
        if ($bank_row = $bank_query->fetch_assoc()) {
            $bankname = $bank_row['bank_name'];
        }
    }
    
 

    // ========== INSERT PURCHASE ==========
            $stmt = $conn->prepare("INSERT INTO sales 
        (customer_id, date, invoiceno, mode, bank_id, bankname, 
        totalamt, gst_total, discount_total, packing_charge, round_off, grand_total) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssisdddddd",
            $customer_id,
            $date,
            $invoiceno,
            $mode,
            $bank_id,
            $bankname,
            $totalamt,        
            $gst_total,
            $discount_total,
            $packing_charge,
            $round_off,
            $grand_total       
        );

    if ($stmt->execute()) {

        $sales_id = $stmt->insert_id;

        // ========== INSERT ITEMS ==========
        $product_ids = $_POST['product_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $prices = $_POST['price'] ?? [];
        $gsts = $_POST['gst'] ?? [];
        $totals = $_POST['total'] ?? [];
        $item_count = count($product_ids);
        $insert_success = true;

        for($i = 0; $i < $item_count; $i++){

            if(empty($product_ids[$i])) continue;

            $product_id = $product_ids[$i];
            $qty = $qtys[$i] ?? 0;
            $price = $prices[$i] ?? 0;
            $gst = $gsts[$i] ?? 0;
            $itemTotal = $totals[$i] ?? 0;
            
            // Check current stock and expiry before processing
            $stockCheck = $conn->prepare("SELECT initialqty, expirydate FROM products WHERE pid = ?");
            $stockCheck->bind_param("i", $product_id);
            $stockCheck->execute();
            $stockResult = $stockCheck->get_result();
            $stockData = $stockResult->fetch_assoc();
            
            if (!$stockData) {
                $insert_success = false;
                $message = "Product not found!";
                break;
            }
            
            // Check if product is expired
            if (!empty($stockData['expirydate']) && strtotime($stockData['expirydate']) < time()) {
                $insert_success = false;
                $message = "Cannot sell expired product!";
                break;
            }
            
            // Check if sufficient stock is available
            if ($stockData['initialqty'] < $qty) {
                $insert_success = false;
                $message = "Insufficient stock for product. Available: " . $stockData['initialqty'];
                break;
            }

            $itemStmt = $conn->prepare("INSERT INTO sales_items 
                (sales_id, product_id, gst_percent, quantity, unit_price, total) 
                VALUES (?, ?, ?, ?, ?, ?)");

            $itemStmt->bind_param("iididd", 
                $sales_id, 
                $product_id, 
                $gst, 
                $qty, 
                $price, 
                $itemTotal
            );

            if(!$itemStmt->execute()){
                $insert_success = false;
                $message = "Error inserting item: " . $itemStmt->error;
            }
            $itemStmt->close();
 
             //update stock qty
            $update_stock = $conn->prepare("UPDATE products SET initialqty = initialqty - ? WHERE pid = ?");
           $update_stock->bind_param("ii", $qty, $product_id);
            if(!$update_stock->execute())
            {
                $insert_success = false;
                $message = "Error updating stock: " . $update_stock->error;
            }
            $update_stock->close();
            $stockCheck->close();
        }

        // ========== UPDATE BANK BALANCE ==========
        if ($mode == "bank" && !empty($bank_id) && $insert_success) {
              
            $particulars = "Sales payment - Customer ID: " . $customer_id . ", Invoice: " . $invoiceno;
            $newtotal = + $grand_total;
            // Insert into accounts table as transaction record
            $bank_transaction = $conn->prepare("INSERT INTO bank_ledger 
                (bank_id,  transaction_date, transaction_type, reference_id, reference_no,payee, particulars, amt) 
                VALUES (?, ?, 'Sale', ?, ?, ?,?, ?)");
            
            $bank_transaction->bind_param("isisisd", 
                $bank_id,       
                $date,    
                $sales_id,      
                $invoiceno,
                $customer_id,         
                $particulars,       
                $newtotal       
            );
            
            if (!$bank_transaction->execute()) {
                error_log("Bank transaction insert failed: " . $bank_transaction->error);
            }
            $bank_transaction->close();
        }
        
        // ========== UPDATE CASH BALANCE ==========
        elseif ($mode == "cash" && $insert_success) {
           
            $particulars = "Sales payment - Customer ID: " . $customer_id . ", Invoice: " . $invoiceno;
            $newtotal = +$grand_total;
            $cash_stmt = $conn->prepare("INSERT INTO cash_in_hand 
                 ( transaction_date, transaction_type, reference_id, reference_no,payee, particulars, amt) 
                VALUES (?, 'Sale', ?, ?, ?,?, ?)");
            
            $cash_stmt->bind_param("sisisd", 
                $date,
                $sales_id,
                $invoiceno,
                $customer_id,
                $particulars,
                $newtotal
            );
            
            if (!$cash_stmt->execute()) {
                error_log("Cash transaction insert failed: " . $cash_stmt->error);
            }
            $cash_stmt->close();
        }
        // ========== CREDIT MODE ==========
        elseif ($mode == "credit" && $insert_success) {
            // Only purchase is saved, no transaction record needed
            // Credit purchases will be handled separately when payment is made
        }

        if($insert_success){

    if(isset($_POST['saveSalePrint'])){
        echo "<script>
            window.location='print_bill.php?id=$sales_id';
        </script>";
    } else {
        echo "<script>
            alert('Sales Saved Successfully');
            window.location='sales.php';
        </script>";
    }
}
        exit();
        

    } else {
        $message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Entry</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: Arial, sans-serif; background: #f4f6f8; }
        
        .container { 
            width: 95%;
            margin: 20px auto;
            background: #fff; 
            padding: 25px; 
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 { 
            text-align: center; 
            color: #2c3e50; 
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            width: 48%;
        }

        .form-row label {
            width: 130px;
            font-weight: bold;
            color: #34495e;
        }

        .form-row input, .form-row select {
            flex: 1;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-row input:focus, .form-row select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52,152,219,0.3);
        }

        .header-section {
            display: flex;
            flex-wrap: wrap;
            gap: 2%;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }

        th {
            background: #3498db;
            color: white;
            padding: 12px;
            font-weight: 500;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        td input, td select {
            width: 100%;
            padding: 8px;
            border: 1px solid #bdc3c7;
            border-radius: 3px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .summary-table {
            width: 50%;
            margin: 25px 0;
            background: #f8f9fa;
        }

        .summary-table td {
            padding: 12px;
            border: none;
            border-bottom: 1px solid #ddd;
        }

        .summary-table td:first-child {
            font-weight: bold;
            width: 40%;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 25px;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 22px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            transition: 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 5px rgba(39,174,96,0.3);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-submit {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #229954;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .customer-input-group {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .customer-input-group input {
            flex: 1;
        }

        .btn-new-customer {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            white-space: nowrap;
        }

        .btn-new-customer:hover {
            background: #229954;
        }
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1> Sales Entry</h1>

        <?php if($message): ?>
            <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post" id="purchaseForm">
            <!-- ========== HEADER SECTION ========== -->
            <div class="header-section">
                <div class="form-row">
                    <label>Customer *</label>
                    <input type="hidden" name="customer_id" id="customer_id">
                    <div class="customer-input-group">
                        <input list="customerList" id="customerInput" 
                            placeholder="Type customer name..." 
                            oninput="setCustomerId()" required>
                        <button type="button" class="btn-new-customer" onclick="openCustomerModal()">+ New</button>
                    </div>
                    <datalist id="customerList">
                        <?php
                        $result = $conn->query("SELECT id, customername, phoneno FROM customers ORDER BY customername");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='".$row['customername']." (".$row['phoneno'].")' data-id='".$row['id']."'></option>";
                        }
                        ?>
                    </datalist>
                </div>

                <div class="form-row">
                    <label>Purchase Date *</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-row">
                    <label>Invoice No</label>
                    <!--<input type="text" name="invoiceno" placeholder="Enter invoice number">-->
                    <input type="text" name="invoiceno" value="<?php echo 'INV-' . str_pad(((int)($conn->query('SELECT MAX(id) AS last FROM sales')->fetch_assoc()['last']) + 1), 4, '0', STR_PAD_LEFT); ?>" readonly>


                </div>

                <div class="form-row">
                    <label>Mode *</label> 
                    <select name="mode" id="mode" required onchange="togglePaymentField()"> 
                        <option value="">-- Select Mode --</option> 
                        <option value="cash">Cash</option> 
                        <option value="credit">Credit</option> 
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>


                <div class="form-row" id="bankField" style="display:none;">
                    <label>Bank Transfer</label>
                    <select name="bank_id" id="bank_id">
                        <option value="">Select Bank</option>
                        <?php
                        $banks = $conn->query("SELECT id, bank_name ,accname FROM bank ");
                        if($banks && $banks->num_rows > 0){
                            while($row = $banks->fetch_assoc()){
                                $bank_fullname= $row['bank_name'].'('.$row['accname'].')';
                                echo "<option value='".$row['id']."'>" .$bank_fullname . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- ========== ITEMS TABLE ========== -->
            <h3> Items</h3>
            <table id="itemsTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>GST %</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="itemBody">
                    <tr>
                        <td>
                            <input type="hidden" name="product_id[0]" class="product_id">
                            <input list="productList" class="productInput"
                                   placeholder="Type product name..."
                                   onchange="setProductDetails(this)" required>
                        </td>
                        <td><input type="number" name="gst[0]" value="0" step="0.01" onchange="calculateRow(this)"></td>
                        <td><input type="number" name="qty[0]" value="1" min="1" oninput="calculateRow(this)"></td>
                        <td><input type="number" name="price[0]" value="0" step="0.01" oninput="calculateRow(this)"></td>
                        <td><input type="text" name="total[0]" value="0" readonly class="total-field"></td>
                        <td><button type="button" class="btn btn-danger" onclick="removeRow(this)">✕</button></td>
                    </tr>
                </tbody>
            </table>

            <datalist id="productList">
                <?php
                // Modified query to exclude out-of-stock and expired products
                $currentDate = date('Y-m-d');
                $productQuery = $conn->query("SELECT pid, pro_name, taxtype, purchaseprice, initialqty, expirydate 
                    FROM products 
                    WHERE initialqty > 0 
                    AND (expirydate IS NULL OR expirydate >= '$currentDate')
                    ORDER BY pro_name ASC");
                
                while($product = $productQuery->fetch_assoc()){
                    echo "<option value='".$product['pro_name']."' 
                        data-id='".$product['pid']."' 
                        data-gst='".$product['taxtype']."' 
                        data-price='".$product['purchaseprice']."'></option>";
                }
                ?>
            </datalist>

            <button type="button" class="btn btn-success" onclick="addRow()"> Add Item</button>

            <!-- ========== BILL SUMMARY ========== -->
            <h3> Bill Summary</h3>
            <table class="summary-table">

                <tr>
                    <td>Total Amount</td>
                    <td>
                        <input type="text" id="subTotal" name="subTotal" readonly>
                    </td>
                </tr>

                <tr>
                    <td>GST Type</td>
                    <td>
                        <select id="gstType" name="gstType" onchange="updateBillSummary()">
                            <option value="inclusive">Inclusive</option>
                            <option value="exclusive">Exclusive</option>
                        </select>
                    </td>
                </tr>

                <!-- <tr>
                    <td>GST Type</td>
                    <td><input type="text" id="gstType" name="gstType" value="Inclusive" readonly></td>
                </tr> -->

                <tr>
                    <td>Total GST</td>
                    <td><input type="text" id="totalGST" name="totalGST" readonly></td>
                </tr>
                <tr>
                    <td>Cash Discount</td>
                    <td><input type="number" id="cashDiscount" name="cashDiscount" value="0" oninput="updateBillSummary()"></td>
                </tr>
                <tr>
                    <td>Packing Charge</td>
                    <td><input type="number" id="packingCharge" name="packingCharge" value="0" oninput="updateBillSummary()"></td>
                </tr>
                <tr>
                    <td>Round Off</td>
                    <td><input type="text" id="roundOff" name="roundOff" value="0.00" readonly></td>
                </tr>
                <tr>
                    <td><b>Grand Total</b></td>
                    <td><input type="text" id="grandTotal" name="grandTotal" readonly></td>
                </tr>
            </table>
        
        
            

            <!-- ========== SUBMIT BUTTONS ========== -->
            <div class="action-buttons">
                <button type="submit" name="saveSale" class="btn btn-primary"> Save Sale</button>
                <a href="view_purchase.php"><button type="button" class="btn" style="background:#95a5a6; color:white;">⬅️ Back</button></a>
                <button type="submit" name="saveSalePrint" class="btn" style="background:red; color:white;">
                    Save & Print
                </button>
            </div>
        </form>
    </div>

    <!-- Add Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Customer</h3>
                <span class="close" onclick="closeCustomerModal()">&times;</span>
            </div>
            <form id="addCustomerForm">
                <div class="form-group">
                    <label>Customer Name *</label>
                    <input type="text" id="new_customer_name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="number" id="new_customer_phone" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" id="new_customer_city">
                </div>
                <div class="form-group">
                    <label>State</label>
                    <select id="new_customer_state">
                        <option value="">Select State</option>
                        <option value="Tamilnadu">Tamilnadu</option>
                        <option value="Karnataka">Karnataka</option>
                        <option value="Kerala">Kerala</option>
                        <option value="Andhra Pradesh">Andhra Pradesh</option>
                        <option value="Maharashtra">Maharashtra</option>
                        <option value="Delhi">Delhi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Aadhaar Number</label>
                    <input type="text" id="new_customer_aadhar" placeholder="Optional">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeCustomerModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========== TOGGLE PAYMENT FIELDS ==========
        function togglePaymentField() {
            var mode = document.getElementById("mode").value;
            document.getElementById("bankField").style.display = mode === "bank" ? "flex" : "none";
            document.getElementById("creditField").style.display = mode === "credit" ? "flex" : "none";
        }

        // ========== SET customer ID ==========
        function setCustomerId() {
            var input = document.getElementById("customerInput");
            var list = document.getElementById("customerList").options;
            var hidden = document.getElementById("customer_id");

            hidden.value = "";
            for (var i = 0; i < list.length; i++) {
                if (list[i].value === input.value) {
                    hidden.value = list[i].getAttribute("data-id");
                    break;
                }
            }
        }

        // ========== ADD NEW ROW ==========
        function addRow() {
            var table = document.getElementById("itemBody");
            var rowCount = table.rows.length;
            var firstRow = table.rows[0];
            var newRow = firstRow.cloneNode(true);

            // Clear values
            newRow.querySelector(".productInput").value = "";
            newRow.querySelector(".product_id").value = "";

            // Update indices
            newRow.querySelectorAll("input").forEach((input) => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, '[' + rowCount + ']');
                }
                if(input.type !== 'hidden' && !input.readOnly){
                    if(input.name.includes('gst')) input.value = '0';
                    if(input.name.includes('qty')) input.value = '1';
                    if(input.name.includes('price')) input.value = '0';
                }
            });

            table.appendChild(newRow);
        }

        // ========== REMOVE ROW ==========
        function removeRow(btn) {
            var table = document.getElementById("itemBody");
            if(table.rows.length > 1){
                btn.closest('tr').remove();
                updateBillSummary();
            }
        }

        // ========== SET PRODUCT DETAILS ==========
        function setProductDetails(input) {
            var row = input.closest("tr");
            var list = document.getElementById("productList").options;
            var hiddenId = row.querySelector(".product_id");
            
            hiddenId.value = "";
            var productFound = false;
            var selectedOption = null;
            
            for (var i = 0; i < list.length; i++) {
                if (list[i].value.trim() === input.value.trim()) {
                    productFound = true;
                    selectedOption = list[i];
                    break;
                }
            }
            
            if (productFound && selectedOption) {
                hiddenId.value = selectedOption.dataset.id;
                row.querySelector("input[name*='gst']").value = selectedOption.dataset.gst || 0;
                row.querySelector("input[name*='price']").value = selectedOption.dataset.price || 0;
                calculateRow(input);
            } else if (input.value.trim() !== "") {
                alert("This product is either out of stock or has expired. Please select an available product.");
                input.value = "";
            }
        }

        // ========== CALCULATE ROW TOTAL ==========
        function calculateRow(element) {
            var row = element.closest("tr");
            var qty = parseFloat(row.querySelector("input[name*='qty']").value) || 0;
            var price = parseFloat(row.querySelector("input[name*='price']").value) || 0;

            var total = qty * price;
            row.querySelector("input[name*='total']").value = total.toFixed(2);

            updateBillSummary();
        }

        // ========== UPDATE BILL SUMMARY ==========
    function updateBillSummary() {

            var rows = document.querySelectorAll("#itemBody tr");
            var gstType = document.getElementById("gstType").value;

            var totalGST = 0;
            var totalAmount = 0;
            var baseTotal = 0; // actual item total

            rows.forEach(row => {

                var qty = parseFloat(row.querySelector("input[name*='qty']").value) || 0;
                var price = parseFloat(row.querySelector("input[name*='price']").value) || 0;
                var gstPercent = parseFloat(row.querySelector("input[name*='gst']").value) || 0;

                var amount = qty * price;
                baseTotal += amount;

                var gstAmount = 0;

                if (gstType === "exclusive") {

                    gstAmount = (amount * gstPercent) / 100;
                    totalAmount += amount + gstAmount;

                } else {

                    var baseAmount = (amount * 100) / (100 + gstPercent);
                    gstAmount = amount - baseAmount;
                    totalAmount += amount;
                }

                totalGST += gstAmount;
            });

            document.getElementById("subTotal").value = baseTotal.toFixed(2);

            var discount = parseFloat(document.getElementById("cashDiscount").value) || 0;
            var packing = parseFloat(document.getElementById("packingCharge").value) || 0;

            var finalAmount = totalAmount - discount + packing;
            var roundedTotal = Math.round(finalAmount);
            var roundOff = roundedTotal - finalAmount;

            document.getElementById("totalGST").value = totalGST.toFixed(2);
            document.getElementById("roundOff").value = roundOff.toFixed(2);
            document.getElementById("grandTotal").value = roundedTotal.toFixed(2);
        }

        // ========== CUSTOMER MODAL FUNCTIONS ==========
        function openCustomerModal() {
            document.getElementById("customerModal").style.display = "block";
            document.getElementById("new_customer_name").focus();
        }

        function closeCustomerModal() {
            document.getElementById("customerModal").style.display = "none";
            document.getElementById("addCustomerForm").reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("customerModal");
            if (event.target == modal) {
                closeCustomerModal();
            }
        }

        // Add Customer via AJAX
        document.getElementById("addCustomerForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            var customerData = {
                name: document.getElementById("new_customer_name").value.trim(),
                phone: document.getElementById("new_customer_phone").value.trim(),
                city: document.getElementById("new_customer_city").value.trim(),
                state: document.getElementById("new_customer_state").value,
                aadhar: document.getElementById("new_customer_aadhar").value.trim()
            };
            
            if(customerData.name === "") {
                alert("Please enter customer name");
                return;
            }
            
            if(customerData.phone === "") {
                alert("Please enter phone number");
                return;
            }
            
            fetch('add_customer_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(customerData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Add new customer to datalist
                    var datalist = document.getElementById("customerList");
                    var newOption = document.createElement("option");
                    newOption.value = data.customername + " (" + data.phone + ")";
                    newOption.setAttribute("data-id", data.id);
                    datalist.appendChild(newOption);
                    
                    // Auto-select the new customer
                    document.getElementById("customerInput").value = newOption.value;
                    document.getElementById("customer_id").value = data.id;
                    
                    closeCustomerModal();
                    alert("Customer added successfully!");
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert("Error adding customer: " + error);
            });
        });

        // ========== INITIALIZE ON LOAD ==========
        window.onload = function() {
            togglePaymentField();
            updateBillSummary();
        };
    </script>
</body>
</html>