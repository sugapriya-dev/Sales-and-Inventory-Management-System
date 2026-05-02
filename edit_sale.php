<?php
session_start();
include "db.php"; 

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Get sale ID from URL
$sales_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sales_id == 0) {
    header("Location: view_sales.php");
    exit();
}

// Fetch sale details
$sale_query = "SELECT s.*, c.customername, c.phoneno 
               FROM sales s 
               JOIN customers c ON s.customer_id = c.id 
               WHERE s.id = ?";
$stmt = $conn->prepare($sale_query);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$sale_result = $stmt->get_result();
$sale = $sale_result->fetch_assoc();

if (!$sale) {
    header("Location: view_sales.php");
    exit();
}

// Fetch sale items
$items_query = "SELECT si.*, p.pro_name 
                FROM sales_items si
                JOIN products p ON si.product_id = p.pid
                WHERE si.sales_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$items_result = $stmt->get_result();
$sale_items = $items_result->fetch_all(MYSQLI_ASSOC);

$item_count = count($sale_items);
$message = "";

if (isset($_POST['updateSale'])) {

    $customer_id = $_POST['customer_id'];
    $date = !empty($_POST['date']) ? $_POST['date'] : date("Y-m-d");
    $invoiceno   = $_POST['invoiceno'] ?? '';
    $mode        = $_POST['mode'];
    $totalamt = $_POST['subTotal'] ?? 0;
    $bank_id   = $_POST['bank_id'] ?? null;
    $credit = $_POST['credit'] ?? '';
    $discount_total = $_POST['cashDiscount'] ?? 0;
    $gst_total = $_POST['totalGST'] ?? 0;
    $packing_charge = $_POST['packingCharge'] ?? 0;
    $round_off = $_POST['roundOff'] ?? 0;
    $grand_total = $_POST['grandTotal'] ?? 0;

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
        // STEP 1: Reverse stock changes from old sale items (add back to stock)
        $old_items_query = "SELECT product_id, quantity FROM sales_items WHERE sales_id = ?";
        $old_stmt = $conn->prepare($old_items_query);
        $old_stmt->bind_param("i", $sales_id);
        $old_stmt->execute();
        $old_items = $old_stmt->get_result();
        
        while ($old_item = $old_items->fetch_assoc()) {
            // Increase stock by old quantity (reverse the original deduction)
            $update_stock = $conn->prepare("UPDATE products SET initialqty = initialqty + ? WHERE pid = ?");
            $update_stock->bind_param("ii", $old_item['quantity'], $old_item['product_id']);
            $update_stock->execute();
        }

        // STEP 2: Reverse previous financial transactions based on old mode
        if ($sale['mode'] == "bank" && !empty($sale['bank_id'])) {
            // Delete old bank ledger entry
            $conn->query("DELETE FROM bank_ledger WHERE reference_id = $sales_id AND transaction_type = 'Sale'");
        }
        else if ($sale['mode'] == "cash" || $sale['mode'] == "Cash") {
            // Delete old cash entry
            $conn->query("DELETE FROM cash_in_hand WHERE reference_id = $sales_id AND transaction_type = 'Sale'");
        }
        else if ($sale['mode'] == "credit") {
            // If there's any credit ledger, delete it (if you have one)
            // $conn->query("DELETE FROM credit_ledger WHERE reference_id = $sales_id AND transaction_type = 'Sale'");
        }

        // STEP 3: Update sale main record
        $stmt = $conn->prepare("UPDATE sales 
                                SET customer_id = ?, date = ?, invoiceno = ?, mode = ?, 
                                    bank_id = ?, bankname = ?, totalamt = ?, 
                                    gst_total = ?, discount_total = ?, packing_charge = ?, 
                                    round_off = ?, grand_total = ?
                                WHERE id = ?");
        $stmt->bind_param("isssisddddddi", 
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
            $grand_total,
            $sales_id
        );
        $stmt->execute();

        // STEP 4: Delete existing items
        $conn->query("DELETE FROM sales_items WHERE sales_id = $sales_id");

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

            // Insert sale item
            $itemStmt = $conn->prepare("INSERT INTO sales_items 
                (sales_id, product_id, gst_percent, quantity, unit_price, total) 
                VALUES (?,?,?,?,?,?)");
            $itemStmt->bind_param("iididd", 
                $sales_id, 
                $product_id, 
                $gst, 
                $qty, 
                $price, 
                $total
            );
            $itemStmt->execute();

            // Update stock (deduct new quantity)
            $update_stock = $conn->prepare("UPDATE products SET initialqty = initialqty - ? WHERE pid = ?");
            $update_stock->bind_param("ii", $qty, $product_id);
            $update_stock->execute();
        }

        // STEP 6: Apply new financial transactions based on new mode
        if ($mode == "bank" || $mode == "Bank") {
            // Insert into bank_ledger
            $particulars = "Sale - " . $invoiceno;
            $amt = $grand_total; // Positive for cash in
            $bank_query = "INSERT INTO bank_ledger 
                (transaction_date, transaction_type, reference_id, reference_no, payee, particulars, amt, bank_id) 
                VALUES (?, 'Sale', ?, ?, ?, ?, ?, ?)";
            
            $bank_stmt = $conn->prepare($bank_query);
            $bank_stmt->bind_param("sisssdi", 
                $date, 
                $sales_id, 
                $invoiceno, 
                $customer_id, 
                $particulars, 
                $amt,
                $bank_id
            );
            $bank_stmt->execute();
        }
        else if ($mode == "cash" || $mode == "Cash") {
            // Insert into cash_in_hand
            $particulars = "Sale - " . $invoiceno;
            $amt = $grand_total; // Positive for cash in
            $cash_query = "INSERT INTO cash_in_hand 
                (transaction_date, transaction_type, reference_id, reference_no, payee, particulars, amt) 
                VALUES (?, 'Sale', ?, ?, ?, ?, ?)";
            
            $cash_stmt = $conn->prepare($cash_query);
            $cash_stmt->bind_param("sisssd", 
                $date, 
                $sales_id, 
                $invoiceno, 
                $customer_id, 
                $particulars, 
                $amt
            );
            $cash_stmt->execute();
        }
        // No need to handle credit mode as it doesn't affect cash/bank

        // Commit transaction
        $conn->commit();

        echo "<script>
            alert('Sale Updated Successfully');
            window.location='view_sales.php';
        </script>";
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error updating sale: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Sale</title>
    <style>
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
        <h1> Edit Sale #<?php echo $sales_id; ?></h1>

        <?php if($message): ?>
            <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($item_count == 0): ?>
            <div class="warning-box">
                 No items found for this sale. You can add items below.
            </div>
        <?php endif; ?>

        <form method="post">
            <!-- ========== HEADER SECTION ========== -->
            <div class="header-section">
                <div class="form-row">
                    <label>Customer *</label>
                    <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $sale['customer_id']; ?>">
                    <input list="customerList" id="customerInput" 
                        placeholder="Type customer name..." 
                        value="<?php echo htmlspecialchars($sale['customername'] . ' (' . $sale['phoneno'] . ')'); ?>"
                        oninput="setCustomerId()" required>
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
                    <label>Sale Date *</label>
                    <input type="date" name="date" value="<?php echo $sale['date']; ?>" required>
                </div>

                <div class="form-row">
                    <label>Invoice No</label>
                    <input type="text" name="invoiceno" value="<?php echo htmlspecialchars($sale['invoiceno']); ?>" placeholder="Enter invoice number">
                </div>

                <div class="form-row">
                    <label>Mode *</label>
                    <select name="mode" id="mode" required onchange="togglePaymentField()">
                        <option value="">-- Select Mode --</option>
                        <option value="cash" <?php echo ($sale['mode'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                        <option value="credit" <?php echo ($sale['mode'] == 'credit') ? 'selected' : ''; ?>>Credit</option>
                        <option value="bank" <?php echo ($sale['mode'] == 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>

                <!-- Credit Field -->
                <div class="form-row" id="creditField" style="display: <?php echo ($sale['mode'] == 'credit') ? 'flex' : 'none'; ?>;">
                    <!--   <label>Bank Credit</label>
                     <select name="credit" id="credit_select">
                        <option value="TMB" <?php echo ($sale['bankname'] == 'TMB') ? 'selected' : ''; ?>>TMB Bank</option>
                        <option value="SBI" <?php echo ($sale['bankname'] == 'SBI') ? 'selected' : ''; ?>>SBI Bank</option>
                    </select> -->
                </div> 

                <!-- Bank Field -->
                <div class="form-row" id="bankField" style="display: <?php echo ($sale['mode'] == 'bank') ? 'flex' : 'none'; ?>;">
                    <label>Bank Transfer</label>
                    <select name="bank_id" id="bank_id">
                        <option value="">Select Bank</option>
                        <?php
                        $banks = $conn->query("SELECT id, bank_name FROM bank");
                        if($banks && $banks->num_rows > 0){
                            while($row = $banks->fetch_assoc()){
                                $selected = ($sale['bank_id'] == $row['id']) ? 'selected' : '';
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
                    <?php if (!empty($sale_items)): ?>
                        <?php foreach($sale_items as $index => $item): ?>
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

            <button type="button" class="btn-success" onclick="addRow()"> + Add Item</button>

            <!-- ========== BILL SUMMARY ========== -->
            <h3> Bill Summary</h3>
            <table class="summary-table">
                <tr>
                    <td>Total Amount</td>
                    <td>
                        <input type="text" id="subTotal" name="subTotal" value="<?php echo $sale['totalamt']; ?>" readonly>
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
                    <td><input type="text" id="totalGST" name="totalGST" value="<?php echo $sale['gst_total']; ?>" readonly></td>
                </tr>
                <tr>
                    <td>Cash Discount</td>
                    <td><input type="number" id="cashDiscount" name="cashDiscount" value="<?php echo $sale['discount_total']; ?>" step="0.01" oninput="updateBillSummary()"></td>
                </tr>
                <tr>
                    <td>Packing Charge</td>
                    <td><input type="number" id="packingCharge" name="packingCharge" value="<?php echo $sale['packing_charge']; ?>" step="0.01" oninput="updateBillSummary()"></td>
                </tr>
                <tr>
                    <td>Round Off</td>
                    <td><input type="text" id="roundOff" name="roundOff" value="<?php echo $sale['round_off']; ?>" readonly></td>
                </tr>
                <tr>
                    <td><b>Grand Total</b></td>
                    <td><input type="text" id="grandTotal" name="grandTotal" value="<?php echo $sale['grand_total']; ?>" readonly></td>
                </tr>
            </table>

            <!-- ========== ACTION BUTTONS ========== -->
            <div class="action-buttons">
                <button type="submit" name="updateSale"> Update Sale</button>
                <a href="view_sales.php"><button type="button" class="btn-danger">⬅️ Back</button></a>
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

        // ========== SET CUSTOMER ID ==========
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
            
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                updateBillSummary();
            }, 100);
        };
    </script>
</body>
</html>