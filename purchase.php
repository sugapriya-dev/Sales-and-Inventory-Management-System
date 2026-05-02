<?php
session_start();
include "db.php";




if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$message = "";

if (isset($_POST['savePurchase'])) {

    // ========== GET FORM DATA ==========
    $supplier_id = $_POST['supplier_id'];
    $date = !empty($_POST['date']) ? $_POST['date'] : date("Y-m-d");
    $invoiceno = $_POST['invoiceno'] ?? '';
    $mode = $_POST['mode'];  //cash, bank or credit
    $totalamt = $_POST['subTotal'] ?? 0;
    $bank_id = $_POST['bank_id'] ?? '';
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
    $stmt = $conn->prepare("INSERT INTO purchase 
        (supplier_id, date, invoiceno, mode, bank_id, bankname, totalamt, gst_total, discount_total, packing_charge, round_off, grand_total) 
        VALUES (?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");

    $stmt->bind_param("isssisdddddd", 
        $supplier_id, 
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

        $purchase_id = $stmt->insert_id;

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

            $itemStmt = $conn->prepare("INSERT INTO purchase_items 
                (purchase_id, product_id, gst_percent, quantity, unit_price, total) 
                VALUES (?, ?, ?, ?, ?, ?)");

            $itemStmt->bind_param("iididd", 
                $purchase_id, 
                $product_id, 
                $gst, 
                $qty, 
                $price, 
                $itemTotal
            );

            if(!$itemStmt->execute()){
                $insert_success = false;
            }
            $itemStmt->close();
           
            //update stock qty
           $update_stock = $conn->prepare("UPDATE products SET initialqty = initialqty + ? WHERE pid = ?");
           $update_stock->bind_param("ii", $qty, $product_id);
            if(!$update_stock->execute())
            {
                $insert_success = false;
            }
            $update_stock->close();

           
        }

       
        // ========== UPDATE BANK BALANCE ==========
        if ($mode == "bank" && !empty($bank_id) && $insert_success) {
              
            $particulars = "Purchase payment - Supplier ID: " . $supplier_id . ", Invoice: " . $invoiceno;
            $newtotal = -$grand_total;
            // Insert into accounts table as transaction record
            $bank_transaction = $conn->prepare("INSERT INTO bank_ledger 
                (bank_id,  transaction_date, transaction_type, reference_id, reference_no,payee, particulars, amt) 
                VALUES (?, ?, 'Purchase', ?, ?, ?,?, ?)");
            
            $bank_transaction->bind_param("isisisd", 
                $bank_id,       
                $date,    
                $purchase_id,      
                $invoiceno, 
                $supplier_id,        
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
           
            $particulars = "Purchase payment - Supplier ID: " . $supplier_id . ", Invoice: " . $invoiceno;
            $newtotal = -$grand_total;
            $cash_stmt = $conn->prepare("INSERT INTO cash_in_hand 
                 ( transaction_date, transaction_type, reference_id, reference_no,payee, particulars, amt) 
                VALUES (?, 'Purchase', ?, ?, ?, ?,?)");
            
            $cash_stmt->bind_param("sisisd", 
                $date,
                $purchase_id,
                $invoiceno,
                $supplier_id,
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
            echo "<script>
                alert('Purchase Saved Successfully');
                window.location='purchase.php';
            </script>";
        } else {
            echo "<script>alert('Some items failed to save');</script>";
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
    <title>Purchase Entry</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f6f8; }
        
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
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

    <div class="container">
        <h1> Purchase Entry</h1>

        <?php if($message): ?>
            <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post" id="purchaseForm">
            <!-- ========== HEADER SECTION ========== -->
            <div class="header-section">
                <div class="form-row">
                    <label>Supplier *</label>
                    <input type="hidden" name="supplier_id" id="supplier_id">
                    <input list="supplierList" id="supplierInput" 
                        placeholder="Type supplier name..." 
                        oninput="setSupplierId()" required>
                    <datalist id="supplierList">
                        <?php
                        $result = $conn->query("SELECT id, suppliers_name, phoneno FROM suppliers ORDER BY suppliers_name");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='".$row['suppliers_name']." (".$row['phoneno'].")' data-id='".$row['id']."'></option>";
                        }
                        ?>
                    </datalist>
                </div>

                <div class="form-row">
                    <label>Purchase Date *</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-row">
                    <label>Invoice No *</label>
                    <input type="text" name="invoiceno" placeholder="Enter invoice number" required>
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

                <!-- <div class="form-row" id="creditField" style="display:none;">
                    <label>Bank Credit</label>
                    <select name="credit">
                        <option value="TMB">TMB Bank</option>
                        <option value="SBI">SBI Bank</option>
                    </select>
                </div> -->

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
                $productQuery = $conn->query("SELECT pid, pro_name, taxtype, purchaseprice FROM products ORDER BY pro_name ASC");
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
                <button type="submit" name="savePurchase" class="btn btn-primary"> Save Purchase</button>
                <a href="view_purchase.php"><button type="button" class="btn" style="background:#95a5a6; color:white;">⬅️ Back</button></a>
            </div>
        </form>
    </div>

    <script>




        // ========== TOGGLE PAYMENT FIELDS ==========
        function togglePaymentField() {
            var mode = document.getElementById("mode").value;
            document.getElementById("bankField").style.display = mode === "bank" ? "flex" : "none";
            document.getElementById("creditField").style.display = mode === "credit" ? "flex" : "none";
        }

        // ========== SET SUPPLIER ID ==========
        function setSupplierId() {
            var input = document.getElementById("supplierInput");
            var list = document.getElementById("supplierList").options;
            var hidden = document.getElementById("supplier_id");

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
            
            for (var i = 0; i < list.length; i++) {
                if (list[i].value.trim() === input.value.trim()) {
                    hiddenId.value = list[i].dataset.id;
                    row.querySelector("input[name*='gst']").value = list[i].dataset.gst || 0;
                    row.querySelector("input[name*='price']").value = list[i].dataset.price || 0;
                    calculateRow(input);
                    return;
                }
            }
            
            alert("Please select product from list only");
            input.value = "";
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

        // ========== INITIALIZE ON LOAD ==========
        window.onload = function() {
            togglePaymentField();
            updateBillSummary();
        };





    </script>
</body>
</html>