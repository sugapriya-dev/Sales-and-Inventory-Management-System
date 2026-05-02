<?php
session_start();
include "db.php"; 

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Get purchase ID from URL
$purchase_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($purchase_id == 0) {
    header("Location: view_purchase.php");
    exit();
}

// Fetch purchase details
$purchase_query = "SELECT p.*, s.suppliers_name, s.phoneno 
                   FROM purchase p 
                   JOIN suppliers s ON p.supplier_id = s.id 
                   WHERE p.id = ?";
$stmt = $conn->prepare($purchase_query);
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$purchase_result = $stmt->get_result();
$purchase = $purchase_result->fetch_assoc();

if (!$purchase) {
    header("Location: view_purchase.php");
    exit();
}

// Fetch purchase items
$items_query = "SELECT pi.*, pr.pro_name, pr.initialqty 
                FROM purchase_items pi
                JOIN products pr ON pi.product_id = pr.pid
                WHERE pi.purchase_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$items_result = $stmt->get_result();
$purchase_items = $items_result->fetch_all(MYSQLI_ASSOC);

$item_count = count($purchase_items);
$message = "";

if (isset($_POST['updatePurchase'])) {

    $supplier_id = $_POST['supplier_id'];
    $date = !empty($_POST['date']) ? $_POST['date'] : date("Y-m-d");
    $invoiceno   = $_POST['invoiceno'] ?? '';
    $mode        = $_POST['mode'];
    $grand_total = $_POST['grandTotal'] ?? 0;
    $bank_id   = $_POST['bank_id'] ?? null;
    $credit = $_POST['credit'] ?? '';
    $totalamt = $_POST['subTotal'] ?? 0;
    $discount_total = $_POST['cashDiscount'] ?? 0;
    $gst_total = $_POST['totalGST'] ?? 0;
    $packing_charge = $_POST['packingCharge'] ?? 0;
    $round_off = $_POST['roundOff'] ?? 0;

    // Get bank name from bank table
    $bankname = null;
    if ($mode == "bank" && !empty($bank_id)) {
        $bank_query = $conn->query("SELECT bank_name FROM bank WHERE id = $bank_id");
        if ($bank_row = $bank_query->fetch_assoc()) {
            $bankname = $bank_row['bank_name'];
        }
    }

    // For credit mode
    if ($mode == "credit" && !empty($credit)) {
        $bankname = $credit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // STEP 1: Reverse stock changes from old purchase items
        $old_items_query = "SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?";
        $old_stmt = $conn->prepare($old_items_query);
        $old_stmt->bind_param("i", $purchase_id);
        $old_stmt->execute();
        $old_items = $old_stmt->get_result();
        
        while ($old_item = $old_items->fetch_assoc()) {
            // Decrease stock by old quantity (reverse the original addition)
            $update_stock = $conn->prepare("UPDATE products SET initialqty = initialqty - ? WHERE pid = ?");
            $update_stock->bind_param("ii", $old_item['quantity'], $old_item['product_id']);
            $update_stock->execute();
        }

                // STEP 2: Reverse previous financial transactions based on old mode
        if ($purchase['mode'] == "bank" && !empty($purchase['bank_id'])) {
            // Delete old bank ledger entry
            $conn->query("DELETE FROM bank_ledger WHERE reference_id = $purchase_id AND transaction_type = 'Purchase'");
        }
        else if ($purchase['mode'] == "cash" || $purchase['mode'] == "Cash") {
            // Delete old cash entry
            $conn->query("DELETE FROM cash_in_hand WHERE reference_id = $purchase_id AND transaction_type = 'Purchase'");
        }
        else if ($purchase['mode'] == "credit") {
            // If there's any credit ledger, delete it (if you have one)
            // $conn->query("DELETE FROM credit_ledger WHERE reference_id = $purchase_id AND transaction_type = 'Purchase'");
        }

        // STEP 3: Update purchase main record
        $stmt = $conn->prepare("UPDATE purchase 
                                SET supplier_id = ?, date = ?, invoiceno = ?, mode = ?, 
                                    bank_id = ?, bankname = ?, totalamt = ?,
                                    gst_total = ?, discount_total = ?, packing_charge = ?, 
                                    round_off = ?, grand_total = ?
                                WHERE id = ?");
        $stmt->bind_param("isssisddddddi", 
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
            $grand_total,
            $purchase_id
        );
        $stmt->execute();

        // STEP 4: Delete existing items
        $conn->query("DELETE FROM purchase_items WHERE purchase_id = $purchase_id");

        // STEP 5: Insert new items and update stock
        $product_ids = $_POST['product_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $prices = $_POST['price'] ?? [];
        $gsts = $_POST['gst'] ?? [];
        $totals = $_POST['total'] ?? [];

        $count = count($product_ids);
        $all_success = true;

        for($i = 0; $i < $count; $i++){
            if(empty($product_ids[$i])) continue;

            $product_id = $product_ids[$i];
            $qty   = $qtys[$i] ?? 0;
            $price = $prices[$i] ?? 0;
            $gst   = $gsts[$i] ?? 0;
            $total = $totals[$i] ?? 0;

            // Insert purchase item
            $itemStmt = $conn->prepare("INSERT INTO purchase_items 
                (purchase_id, product_id, gst_percent, quantity, unit_price, total) 
                VALUES (?,?,?,?,?,?)");
            $itemStmt->bind_param("iididd", 
                $purchase_id, 
                $product_id, 
                $gst, 
                $qty, 
                $price, 
                $total
            );
            $itemStmt->execute();

            // Update stock (add new quantity)
            $update_stock = $conn->prepare("UPDATE products SET initialqty = initialqty + ? WHERE pid = ?");
            $update_stock->bind_param("ii", $qty, $product_id);
            $update_stock->execute();
        }

                // STEP 6: Apply new financial transactions based on new mode
        if ($mode == "bank" || $mode == "Bank") {
            // Insert into bank_ledger
            $particulars = "Purchase - " . $invoiceno;
            $amt = -$grand_total; // Negative for cash out
            $bank_query = "INSERT INTO bank_ledger 
                (transaction_date, transaction_type, reference_id, reference_no, payee, particulars, amt, bank_id) 
                VALUES (?, 'Purchase', ?, ?, ?, ?, ?, ?)";
            
            $bank_stmt = $conn->prepare($bank_query);
            $bank_stmt->bind_param("sisssdi", 
                $date, 
                $purchase_id, 
                $invoiceno, 
                $supplier_id, 
                $particulars, 
                $amt,
                $bank_id
            );
            $bank_stmt->execute();
        }
        else if ($mode == "cash" || $mode == "Cash") {
            // Insert into cash_in_hand
            $particulars = "Purchase - " . $invoiceno;
            $amt = -$grand_total; // Negative for cash out
            $cash_query = "INSERT INTO cash_in_hand 
                (transaction_date, transaction_type, reference_id, reference_no, payee, particulars, amt) 
                VALUES (?, 'Purchase', ?, ?, ?, ?, ?)";
            
            $cash_stmt = $conn->prepare($cash_query);
            $cash_stmt->bind_param("sisssd", 
                $date, 
                $purchase_id, 
                $invoiceno, 
                $supplier_id, 
                $particulars, 
                $amt
            );
            $cash_stmt->execute();
        }
        // No need to handle credit mode as it doesn't affect cash/bank

        // Commit transaction
        $conn->commit();

        echo "<script>
            alert('Purchase Updated Successfully');
            window.location='view_purchase.php';
        </script>";
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error updating purchase: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Purchase</title>
    <style>
        /* Your existing styles remain exactly the same */
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container { 
            width: 95%;
            margin: 30px auto;
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

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
            color: #34495e;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52,152,219,0.3);
        }

        button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
        }

        button:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #229954;
        }

        a {
            text-decoration: none;
            color: #3498db;
        }

        a:hover {
            text-decoration: underline;
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
            margin-top: 0;
        }

        .form-row input, .form-row select {
            flex: 1;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
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

        td input {
            width: 100%;
            padding: 8px;
            margin: 0;
            border: 1px solid #bdc3c7;
        }

        .summary-table {
            width: 50%;
            margin: 20px 0;
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

        .summary-table input {
            width: 100%;
            padding: 8px;
            border: 1px solid #bdc3c7;
            border-radius: 3px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .warning-box {
            background-color: #fef9e7;
            color: #7d6608;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #f9e79f;
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
        <h1> Edit Purchase #<?php echo $purchase_id; ?></h1>

        <?php if($message): ?>
            <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($item_count == 0): ?>
            <div class="warning-box">
                 No items found for this purchase. You can add items below.
            </div>
        <?php endif; ?>

        <form method="post">
            <!-- ========== HEADER SECTION ========== -->
            <div class="header-section">
                <div class="form-row">
                    <label>Supplier *</label>
                    <input type="hidden" name="supplier_id" id="supplier_id" value="<?php echo $purchase['supplier_id']; ?>">
                    <input list="supplierList" id="supplierInput" 
                        placeholder="Type supplier name..." 
                        value="<?php echo htmlspecialchars($purchase['suppliers_name'] . ' (' . $purchase['phoneno'] . ')'); ?>"
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
                    <input type="date" name="date" value="<?php echo $purchase['date']; ?>" required>
                </div>

                <div class="form-row">
                    <label>Invoice No</label>
                    <input type="text" name="invoiceno" value="<?php echo htmlspecialchars($purchase['invoiceno']); ?>" placeholder="Enter invoice number">
                </div>

                <div class="form-row">
                    <label>Mode *</label>
                    <select name="mode" id="mode" required onchange="togglePaymentField()">
                        <option value="">-- Select Mode --</option>
                        <option value="cash" <?php echo ($purchase['mode'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                        <option value="credit" <?php echo ($purchase['mode'] == 'credit') ? 'selected' : ''; ?>>Credit</option>
                        <option value="bank" <?php echo ($purchase['mode'] == 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>

                <!-- Credit Field -->
                <div class="form-row" id="creditField" style="display: <?php echo ($purchase['mode'] == 'credit') ? 'flex' : 'none'; ?>;">
                   <!-- <label>Bank Credit</label>
                    <select name="credit" id="credit_select">
                        <option value="TMB" <?php echo ($purchase['bankname'] == 'TMB') ? 'selected' : ''; ?>>TMB Bank</option>
                        <option value="SBI" <?php echo ($purchase['bankname'] == 'SBI') ? 'selected' : ''; ?>>SBI Bank</option>
                    </select> -->
                </div>

                <!-- Bank Field -->
                <div class="form-row" id="bankField" style="display: <?php echo ($purchase['mode'] == 'bank') ? 'flex' : 'none'; ?>;">
                    <label>Bank Transfer</label>
                    <select name="bank_id" id="bank_id">
                        <option value="">Select Bank</option>
                        <?php
                        $banks = $conn->query("SELECT id, bank_name FROM bank");
                        if($banks && $banks->num_rows > 0){
                            while($row = $banks->fetch_assoc()){
                                $selected = ($purchase['bank_id'] == $row['id']) ? 'selected' : '';
                                echo "<option value='".$row['id']."' $selected>".$row['bank_name']."</option>";
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
                    <?php if (!empty($purchase_items)): ?>
                        <?php foreach($purchase_items as $index => $item): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="product_id[<?php echo $index; ?>]" class="product_id" value="<?php echo $item['product_id']; ?>">
                                <input list="productList" class="productInput" 
                                    value="<?php echo htmlspecialchars($item['pro_name']); ?>"
                                    placeholder="Type product name..."
                                    onchange="setProductDetails(this)" required>
                            </td>
                            <td>
                                <input type="number" name="gst[<?php echo $index; ?>]" value="<?php echo $item['gst_percent']; ?>" step="0.01" onchange="calculateRow(this)">
                            </td>
                            <td>
                                <input type="number" name="qty[<?php echo $index; ?>]" value="<?php echo $item['quantity']; ?>" min="1" oninput="calculateRow(this)">
                            </td>
                            <td>
                                <input type="number" name="price[<?php echo $index; ?>]" value="<?php echo $item['unit_price']; ?>" step="0.01" oninput="calculateRow(this)">
                            </td>
                            <td>
                                <input type="text" name="total[<?php echo $index; ?>]" value="<?php echo $item['total']; ?>" readonly class="total-field">
                            </td>
                            <td>
                                <button type="button" class="btn-danger" onclick="removeRow(this)">✕</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
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
                            <td><input type="text" name="total[0]" value="0" readonly></td>
                            <td><button type="button" class="btn-danger" onclick="removeRow(this)">✕</button></td>
                        </tr>
                    <?php endif; ?>
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

            <button type="button" class="btn-success" onclick="addRow()"> Add Item</button>

            <!-- ========== BILL SUMMARY ========== -->
            <h3> Bill Summary</h3>
            <table class="summary-table">
                <tr>
                    <td>Total Amount</td>
                    <td>
                        <input type="text" id="subTotal" name="subTotal" value="<?php echo $purchase['totalamt']; ?>" readonly>
                    </td>
                </tr> 
                <tr>
                    <td>GST Type</td>
                    <td>
                        <select id="gstType" name="gstType" onchange="updateBillSummary()" >
                            <option value="inclusive">Inclusive</option>
                            <option value="exclusive">Exclusive</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Total GST</td>
                    <td><input type="text" id="totalGST" name="totalGST" value="<?php echo $purchase['gst_total']; ?>" readonly></td>
                </tr>
                <tr>
                    <td>Cash Discount</td>
                    <td><input type="number" id="cashDiscount" name="cashDiscount" value="<?php echo $purchase['discount_total']; ?>" step="0.01" oninput="updateBillSummary()"></td>
                </tr>
                <tr>
                    <td>Packing Charge</td>
                    <td><input type="number" id="packingCharge" name="packingCharge" value="<?php echo $purchase['packing_charge']; ?>" step="0.01" oninput="updateBillSummary()"></td>
                </tr>
                <tr>
                    <td>Round Off</td>
                    <td><input type="text" id="roundOff" name="roundOff" value="<?php echo $purchase['round_off']; ?>" readonly></td>
                </tr>
                <tr>
                    <td><b>Grand Total</b></td>
                    <td><input type="text" id="grandTotal" name="grandTotal" value="<?php echo $purchase['grand_total']; ?>" readonly></td>
                </tr>
            </table>

            <!-- ========== ACTION BUTTONS ========== -->
            <div class="action-buttons">
                <button type="submit" name="updatePurchase">Update Purchase</button>
                <a href="view_purchase.php"><button type="button" class="btn-danger">⬅️ Back</button></a>
            </div>
        </form>
    </div>

    <script>
        // ========== TOGGLE PAYMENT FIELDS ==========
        function togglePaymentField() {
            var mode = document.getElementById("mode").value;
            var bankField = document.getElementById("bankField");
            var creditField = document.getElementById("creditField");
            var bankSelect = document.getElementById("bank_id");

            bankField.style.display = "none";
            creditField.style.display = "none";
            
            if (mode === "bank") {
                bankField.style.display = "flex";
                bankSelect.required = true;
            } else if (mode === "credit") {
                creditField.style.display = "flex";
                bankSelect.required = false;
            } else {
                bankSelect.required = false;
            }
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
            
            var total = price * qty;
            row.querySelector("input[name*='total']").value = total.toFixed(2);
            
            updateBillSummary();
        }

        // ========== UPDATE BILL SUMMARY WITH GST ==========
        function updateBillSummary() {
            var rows = document.querySelectorAll("#itemBody tr");
            var gstType = document.getElementById("gstType").value;

            var totalGST = 0;
            var totalAmount = 0;
            var subtotal = 0;

            rows.forEach(row => {
                var qty = parseFloat(row.querySelector("input[name*='qty']").value) || 0;
                var price = parseFloat(row.querySelector("input[name*='price']").value) || 0;
                var gstPercent = parseFloat(row.querySelector("input[name*='gst']").value) || 0;

                var amount = qty * price;
                subtotal += amount;
                var gstAmount = 0;

                if (gstType === "exclusive") {
                    // GST Exclusive: Price + GST
                    gstAmount = (amount * gstPercent) / 100;
                    totalAmount += amount + gstAmount;
                } else {
                    // GST Inclusive: Price includes GST
                    var baseAmount = (amount * 100) / (100 + gstPercent);
                    gstAmount = amount - baseAmount;
                    totalAmount += amount;
                }

                totalGST += gstAmount;
            });

            var discount = parseFloat(document.getElementById("cashDiscount").value) || 0;
            var packing = parseFloat(document.getElementById("packingCharge").value) || 0;

            var finalAmount = totalAmount - discount + packing;
            var roundedTotal = Math.round(finalAmount);
            var roundOff = roundedTotal - finalAmount;

            document.getElementById("subTotal").value = subtotal.toFixed(2);
            document.getElementById("totalGST").value = totalGST.toFixed(2);
            document.getElementById("roundOff").value = roundOff.toFixed(2);
            document.getElementById("grandTotal").value = roundedTotal.toFixed(2);
        }

        // ========== INITIALIZE ON LOAD ==========
        window.onload = function() {
            togglePaymentField();
            
            // Set default GST type based on your preference
            document.getElementById("gstType").value = "inclusive";
            
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                updateBillSummary();
            }, 100);
        };
    </script>
</body>
</html>