<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sale_id == 0) {
    header("Location: view_sales.php");
    exit();
}

// Get company details for bill header
$company_query = "SELECT * FROM company WHERE id = 1";
$company_result = $conn->query($company_query);
$company = $company_result->fetch_assoc();

// Get sale details with customer information including state
$sale_query = "SELECT s.*, c.customername, c.phoneno, c.city, c.aadhaarno, c.state
               FROM sales s
               INNER JOIN customers c ON s.customer_id = c.id
               WHERE s.id = $sale_id";
$sale_result = $conn->query($sale_query);
$sale = $sale_result->fetch_assoc();

if (!$sale) {
    header("Location: view_sales.php");
    exit();
}

// Determine GST type based on customer state vs company state
$company_state = trim(strtolower($company['comp_state'])); // Tamilnadu
$customer_state = trim(strtolower($sale['state'])); // From customers table

// Check if same state (case-insensitive comparison)
if(($company_state == $customer_state))
    {
        $is_same_state =TRUE;
    }
    else{
        $is_same_state=FALSE;
    }



// Get sale items with product details
$items_query = "SELECT si.*, p.pro_name, p.hsncode
                FROM sales_items si
                INNER JOIN products p ON si.product_id = p.pid
                WHERE si.sales_id = $sale_id";
$items_result = $conn->query($items_query);

// Calculate CGST and SGST if same state
$cgst = $is_same_state ? $sale['gst_total'] / 2 : 0;
$sgst = $is_same_state ? $sale['gst_total'] / 2 : 0;
$igst = !$is_same_state ? $sale['gst_total'] : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Bill - Invoice #<?php echo $sale['invoiceno']; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            background: #f0f0f0;
        }
        
        .bill-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            border-radius: 8px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
            text-transform: uppercase;
        }
        
        .header h3 {
            margin: 5px 0;
            color: #34495e;
            font-weight: normal;
        }
        
        .header p {
            margin: 3px 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .bill-info-left, .bill-info-right {
            width: 48%;
        }
        
        .bill-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .bill-info strong {
            color: #2c3e50;
            width: 100px;
            display: inline-block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            font-size: 14px;
            text-align: center;
        }
        
        td {
            padding: 8px;
            border: 1px solid #bdc3c7;
            font-size: 13px;
        }
        
        td:not(:first-child) {
            text-align: right;
        }
        
        td:first-child {
            text-align: left;
        }
        
        .summary-table {
            width: 60%;
            margin-left: auto;
            margin-top: 20px;
            background: #f8f9fa;
        }
        
        .summary-table td {
            border: none;
            padding: 8px;
            font-size: 14px;
        }
        
        .summary-table td:first-child {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .summary-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        
        .grand-total {
            background: #27ae60;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        
        .grand-total td {
            background: #27ae60;
            color: white;
            border: none;
        }
        
        .gst-info {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            text-align: right;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #333;
            text-align: center;
        }
        
        .footer p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 13px;
        }
        
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding: 0 20px;
        }
        
        .signature div {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .print-btn {
            text-align: center;
            margin: 20px 0;
        }
        
        .print-btn button {
            padding: 12px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .print-btn button:hover {
            background: #2980b9;
        }
        
        .watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            opacity: 0.1;
            font-size: 50px;
            transform: rotate(-15deg);
            pointer-events: none;
            color: #333;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .bill-container {
                box-shadow: none;
                padding: 15px;
            }
            
            .print-btn {
                display: none;
            }
            
            .watermark {
                opacity: 0.05;
            }
            
            th {
                background: #333 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .grand-total td {
                background: #27ae60 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <div class="watermark"><?php echo $company['comp_name']; ?></div>
        
        <div class="print-btn">
            <button onclick="window.print()">🖨️ Print Bill</button>
            <button onclick="window.location.href='view_sales.php'" style="background:#95a5a6; margin-left:10px;">⬅️ Back</button>
        </div>
        
        <!-- Header -->
        <div class="header">
            <h1><?php echo $company['comp_name']; ?></h1>
            <h3><?php echo $company['comp_address']; ?></h3>
            <p>Phone: <?php echo $company['phone']; ?> | Email: <?php echo $company['email']; ?></p>
            <p>GST: <?php echo $company['gstno']; ?> | State: <?php echo $company['comp_state']; ?></p>
        </div>
        
        <!-- Bill Info -->
        <div class="bill-info">
            <div class="bill-info-left">
                <p><strong>Invoice No:</strong> <?php echo $sale['invoiceno']; ?></p>
                <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($sale['date'])); ?></p>
                <p><strong>Mode:</strong> <?php echo ucfirst($sale['mode']); ?> 
                <?php if($sale['mode'] == 'bank' && $sale['bankname']): ?> 
                    (<?php echo $sale['bankname']; ?>)
                <?php endif; ?></p>
            </div>
            <div class="bill-info-right">
                <p><strong>Customer:</strong> <?php echo $sale['customername']; ?></p>
                <p><strong>Phone:</strong> <?php echo $sale['phoneno']; ?></p>
                <p><strong>City:</strong> <?php echo $sale['city']; ?></p>
                <p><strong>State:</strong> <?php echo ucfirst($sale['state']); ?></p>
                <?php if($sale['aadhaarno']): ?>
                    <p><strong>Aadhaar:</strong> <?php echo $sale['aadhaarno']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Sl No.</th>
                    <th>Product Name</th>
                    <th>HSN</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>GST %</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sl_no = 1;
                while($item = $items_result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $sl_no++; ?></td>
                    <td><?php echo $item['pro_name']; ?></td>
                    <td><?php echo $item['hsncode'] ?: '-'; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td><?php echo $item['gst_percent']; ?>%</td>
                    <td><?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Summary Table with GST Breakdown -->
        <table class="summary-table">
            <tr>
                <td>Total Amount:</td>
                <td>₹ <?php echo number_format($sale['totalamt'], 2); ?></td>
            </tr>
            
            <?php if($is_same_state): ?>
                <!-- CGST & SGST for same state (Tamilnadu) -->
                <tr>
                    <td>CGST (50%):</td>
                    <td>₹ <?php echo number_format($cgst, 2); ?></td>
                </tr>
                <tr>
                    <td>SGST (50%):</td>
                    <td>₹ <?php echo number_format($sgst, 2); ?></td>
                </tr>
                <tr>
                    <td>Total GST (CGST+SGST):</td>
                    <td>₹ <?php echo number_format($sale['gst_total'], 2); ?></td>
                </tr>
            <?php else: ?>
                <!-- IGST for other states (Karnataka, Kerala, etc.) -->
                <tr>
                    <td>IGST:</td>
                    <td>₹ <?php echo number_format($igst, 2); ?></td>
                </tr>
            <?php endif; ?>
            
            <?php if($sale['discount_total'] > 0): ?>
            <tr>
                <td>Discount:</td>
                <td>₹ <?php echo number_format($sale['discount_total'], 2); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if($sale['packing_charge'] > 0): ?>
            <tr>
                <td>Packing Charge:</td>
                <td>₹ <?php echo number_format($sale['packing_charge'], 2); ?></td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <td>Round Off:</td>
                <td>₹ <?php echo number_format($sale['round_off'], 2); ?></td>
            </tr>
            <tr class="grand-total">
                <td>Grand Total:</td>
                <td>₹ <?php echo number_format($sale['grand_total'], 2); ?></td>
            </tr>
        </table>
        
        <!-- GST Type Info -->
        <div class="gst-info">
            <?php if($is_same_state): ?>
                * Intra-state Transaction (CGST + SGST Applied)
            <?php else: ?>
                * Inter-state Transaction (IGST Applied)
            <?php endif; ?>
            | Company: <?php echo $company['comp_state']; ?>, Customer: <?php echo ucfirst($sale['state']); ?>
        </div>
        
        <p style="margin-top:20px;font-size:14px;">
            <strong>Amount in Words:</strong>
            <?php
            function numberToWords($num) {
                $ones = array(
                    0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 
                    5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                    10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 
                    14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
                    18 => 'Eighteen', 19 => 'Nineteen'
                );
                $tens = array(
                    2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
                    6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
                );
                
                if ($num < 20) return $ones[$num];
                if ($num < 100) return $tens[floor($num/10)] . ' ' . $ones[$num%10];
                if ($num < 1000) return $ones[floor($num/100)] . ' Hundred ' . numberToWords($num%100);
                if ($num < 100000) return numberToWords(floor($num/1000)) . ' Thousand ' . numberToWords($num%1000);
                if ($num < 10000000) return numberToWords(floor($num/100000)) . ' Lakh ' . numberToWords($num%100000);
                return numberToWords(floor($num/10000000)) . ' Crore ' . numberToWords($num%10000000);
            }
            
            $amount = floor($sale['grand_total']);
            $paise = round(($sale['grand_total'] - $amount) * 100);
            
            if ($paise > 0) {
                echo "Rupees " . numberToWords($amount) . " and " . numberToWords($paise) . " Paise Only";
            } else {
                echo "Rupees " . numberToWords($amount) . " Only";
            }
            ?>
        </p>
        
        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Goods once sold will not be taken back</p>
          
        </div>
        
        <!-- Signatures 
        <div class="signature">
            <div>
                <div class="signature-line">Customer Signature</div>
            </div>
            <div>
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>-->
        
        <div class="print-btn" style="margin-top: 30px;">
            <button onclick="window.print()">🖨️ Print Bill</button>
        </div>
    </div>
</body>
</html>