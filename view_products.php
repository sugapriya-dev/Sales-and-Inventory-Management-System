<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check if this is an AJAX request for suggestions
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $search = $_GET['search'] ?? "";
    $sql = "SELECT pro_name, hsncode FROM products 
            WHERE pro_name LIKE ? OR hsncode LIKE ?
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'name' => $row['pro_name'],
            'hsn' => $row['hsncode']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    exit();
}

$search = $_GET['search'] ?? "";

$sql = "SELECT * FROM products 
        WHERE pro_name LIKE ? OR hsncode LIKE ?
        ORDER BY pid DESC";

$stmt = $conn->prepare($sql);
$like = "%$search%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>View Products</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
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
.container {
    width: 98%;
    margin: 40px auto;
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    overflow-x: auto;
}

/* Improved Search Section Styles */
.search-section {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
}

#searchBox {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
    box-sizing: border-box;
}

#searchBox:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

#searchBox:hover {
    border-color: #007bff;
}

.search-button {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
}

.search-button:active {
    transform: translateY(0);
}

#dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    display: none;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-top: 2px;
}

#dropdown div {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
    font-size: 14px;
}

#dropdown div:last-child {
    border-bottom: none;
}

#dropdown div:hover {
    background: #f8f9fa;
    padding-left: 20px;
}

#dropdown .active {
    background: #e7f3ff;
    color: #007bff;
    font-weight: 500;
    border-left: 3px solid #007bff;
}

/* Style for product name and HSN code in dropdown */
.dropdown-product-name {
    font-weight: 600;
    display: block;
    margin-bottom: 3px;
}

.dropdown-hsn-code {
    font-size: 11px;
    color: #666;
    display: block;
}

#dropdown .active .dropdown-hsn-code {
    color: #0056b3;
}

/* Optional: Add a search icon */
.search-wrapper::before {
    content: "🔍";
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: #999;
    pointer-events: none;
}

#searchBox {
    padding-left: 35px;
}

/* Responsive design */
@media (max-width: 768px) {
    .search-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .search-button {
        width: 100%;
    }
}

/* Stock Status Styles */
.low-stock {
    background-color: #fff3cd;
    color: #856404;
}

.low-stock td:nth-child(6) {
    background-color: #fff3cd;
    font-weight: bold;
    color: #856404;
}

.out-of-stock {
    background-color: #f8d7da;
    opacity: 0.8;
}

.out-of-stock td:nth-child(6) {
    background-color: #f8d7da;
    font-weight: bold;
    color: #dc3545;
}

.expired {
    background-color: #e9ecef;
    opacity: 0.7;
}

.expired td:nth-child(6) {
    background-color: #e9ecef;
    color: #6c757d;
}

.expired td:nth-child(7) {
    color: #dc3545;
    font-weight: bold;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    margin-top: 5px;
}

.status-low {
    background-color: #ffc107;
    color: #856404;
}

.status-out {
    background-color: #dc3545;
    color: white;
}

.status-expired {
    background-color: #6c757d;
    color: white;
}
</style>
</head>
<body>
<?php include "dashboard.php"; ?>

<form method="get">
<div class="container">
    <h2>Products</h2>
    
    <div class="search-section">
        <div class="search-wrapper">
            <input type="text" name="search" id="searchBox"
                placeholder="Search by name or HSN code..."
                value="<?= htmlspecialchars($search) ?>"
                autocomplete="off">
            <div id="dropdown"></div>
        </div>
        <button type="submit" class="search-button">🔍 Search</button>
    </div>

    <div class="top-links">
        <a href="products.php">➕ ADD PRODUCT</a>
    </div>

     <table>
        <thead>
             <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Unit</th>
                <th>Price</th>
                <th>Tax</th>
                <th>Stock</th>
                <th>Expiry Date</th>
                <th>Action</th>
             </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                $isExpired = false;
                $isOutOfStock = false;
                $isLowStock = false;
                $stockQty = $row['initialqty'];
                $statusText = "";
                $statusClass = "";
                
                // Check expiry date
                if (!empty($row['expirydate']) && strtotime($row['expirydate']) < time()) {
                    $isExpired = true;
                    $isOutOfStock = true; // Expired products are also out of stock
                    $statusText = "EXPIRED";
                    $statusClass = "status-expired";
                }
                // Check stock availability
                elseif ($stockQty <= 0) {
                    $isOutOfStock = true;
                    $statusText = "OUT OF STOCK";
                    $statusClass = "status-out";
                }
                // Check low stock (less than 5 but greater than 0)
                elseif ($stockQty < 5 && $stockQty > 0) {
                    $isLowStock = true;
                    $statusText = "LOW STOCK";
                    $statusClass = "status-low";
                }
                
                $rowClass = '';
                if ($isExpired) {
                    $rowClass = 'expired';
                } elseif ($isOutOfStock) {
                    $rowClass = 'out-of-stock';
                } elseif ($isLowStock) {
                    $rowClass = 'low-stock';
                }
            ?>
            <tr class="<?= $rowClass ?>">
                 <td>
                    <?= htmlspecialchars($row['pro_name']) ?><br>
                    <small style="color:#666;">HSN: <?= htmlspecialchars($row['hsncode']) ?></small>
                    <?php if ($statusText): ?>
                        <br><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                    <?php endif; ?>
                 </td>
                 <td><?= htmlspecialchars($row['category']) ?></td>
                 <td><?= htmlspecialchars($row['unittype']) ?></td>
                 <td><?= htmlspecialchars($row['regularprice']) ?></td>
                 <td>
                    <?= !empty($row['taxtype']) ? $row['taxtype'] . '%' : '5 %' ?>
                 </td>
                <td>
                    <?= $row['initialqty'] ?>
                    <?php if ($isLowStock && !$isExpired): ?>
                        <br><small style="color:#856404;">⚠️ Low Stock!</small>
                    <?php endif; ?>
                    <?php if ($isOutOfStock && !$isExpired): ?>
                        <br><small style="color:#dc3545;">❌ No Stock!</small>
                    <?php endif; ?>
                    <?php if ($isExpired): ?>
                        <br><small style="color:#6c757d;">⛔ Product Expired</small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isExpired): ?>
                        <span style="color:#dc3545; font-weight:bold;">EXPIRED ON: <?= $row['expirydate'] ?></span>
                    <?php elseif ($row['expirydate'] && strtotime($row['expirydate']) < strtotime('+30 days') && strtotime($row['expirydate']) > time()): ?>
                        <span style="color:orange;">⚠️ <?= $row['expirydate'] ?> (Expiring soon)</span>
                    <?php elseif ($row['expirydate']): ?>
                        <?= $row['expirydate'] ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit_product.php?id=<?= $row['pid'] ?>">Edit</a> |
                    <a href="delete_product.php?id=<?= $row['pid'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px;">
                    No products found matching your search.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</form>

<script>
let selectedIndex = -1;
let suggestions = [];

const input = document.getElementById("searchBox");
const dropdown = document.getElementById("dropdown");
let debounceTimer;

// Debounce function to avoid too many requests
function debounce(func, delay) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(func, delay);
}

input.addEventListener("keyup", function(e) {
    let query = this.value.trim();

    if (e.key === "ArrowDown") {
        move(1);
        return;
    }
    if (e.key === "ArrowUp") {
        move(-1);
        return;
    }
    if (e.key === "Enter") {
        if (selectedIndex >= 0 && suggestions[selectedIndex]) {
            input.value = suggestions[selectedIndex].name;
            dropdown.style.display = "none";
            // Submit the form when Enter is pressed on selected item
            document.querySelector('form').submit();
        }
        return;
    }
    if (e.key === "Escape") {
        dropdown.style.display = "none";
        selectedIndex = -1;
        return;
    }

    if (query.length === 0) {
        dropdown.style.display = "none";
        suggestions = [];
        selectedIndex = -1;
        return;
    }

    // Fetch suggestions with debounce
    debounce(() => {
        fetch("?ajax=1&search=" + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            suggestions = data;
            dropdown.innerHTML = "";
            selectedIndex = -1;
            
            if (suggestions.length === 0) {
                dropdown.style.display = "none";
                return;
            }
            
            suggestions.forEach((item, index) => {
                let div = document.createElement("div");
                
                // Highlight matching text
                let displayName = item.name;
                let searchTerm = query.toLowerCase();
                if (searchTerm) {
                    let regex = new RegExp(`(${searchTerm})`, 'gi');
                    displayName = displayName.replace(regex, '<strong>$1</strong>');
                }
                
                div.innerHTML = `
                    <span class="dropdown-product-name">${displayName}</span>
                    <span class="dropdown-hsn-code">HSN: ${escapeHtml(item.hsn)}</span>
                `;
                
                div.onclick = () => {
                    input.value = item.name;
                    dropdown.style.display = "none";
                    // Submit form when clicking on suggestion
                    document.querySelector('form').submit();
                };
                
                dropdown.appendChild(div);
            });
            
            dropdown.style.display = "block";
        })
        .catch(error => {
            console.error('Error:', error);
            dropdown.style.display = "none";
        });
    }, 300);
});

function move(step) {
    let list = dropdown.querySelectorAll("div");
    if (!list.length) return;
    
    // Remove active class from all
    list.forEach(el => el.classList.remove("active"));
    
    selectedIndex += step;
    if (selectedIndex < 0) selectedIndex = list.length - 1;
    if (selectedIndex >= list.length) selectedIndex = 0;
    
    // Add active class to selected item
    list[selectedIndex].classList.add("active");
    
    // Fill input with selected product name
    if (suggestions[selectedIndex]) {
        input.value = suggestions[selectedIndex].name;
    }
    
    // Scroll into view
    list[selectedIndex].scrollIntoView({ block: 'nearest' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (e.target !== input && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
        selectedIndex = -1;
    }
});

// Optional: Auto-focus on search input
input.focus();
</script>
</body>
</html>