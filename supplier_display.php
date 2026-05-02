<?php
session_start();
include "db.php"; 
include "functions.php";

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$search = $_GET['search'] ?? "";

// Get all suppliers for dropdown suggestions
$all_suppliers_sql = "SELECT id, suppliers_name, phoneno FROM suppliers ORDER BY suppliers_name";
$all_suppliers_result = $conn->query($all_suppliers_sql);
$all_suppliers = [];
while ($supp = $all_suppliers_result->fetch_assoc()) {
    $all_suppliers[] = $supp;
}

// Modified query to include search
if (!empty($search)) {
    $sql = "SELECT * FROM suppliers 
            WHERE suppliers_name LIKE ? OR phoneno LIKE ?
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("select * from suppliers order by created_at desc");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Supplier List</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f6f8;
        display: flex;
        justify-content: center;  
        align-items: center;     
        min-height: 100vh;        
        margin: 0;                 
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
    
    /* Search section styles */
    .search-section {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .search-wrapper {
        position: relative;
        flex: 1;
        max-width: 400px;
    }
    
    .search-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0,123,255,0.1);
    }
    
    .search-button {
        padding: 10px 20px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .search-button:hover {
        background: #0056b3;
    }
    
    .reset-link {
        color: #dc3545;
        text-decoration: none;
        font-size: 14px;
    }
    
    .reset-link:hover {
        text-decoration: underline;
    }
    
    .results-count {
        margin-bottom: 15px;
        font-size: 14px;
        color: #666;
    }
    
    /* Dropdown styling */
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
        border-radius: 0 0 4px 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        margin-top: 2px;
    }
    
    #dropdown div {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
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
        border-left: 3px solid #007bff;
    }
    
    .dropdown-name {
        font-weight: 600;
        display: block;
        margin-bottom: 3px;
    }
    
    .dropdown-phone {
        font-size: 11px;
        color: #666;
        display: block;
    }
    
    #dropdown .active .dropdown-phone {
        color: #0056b3;
    }
    
    /* Search icon */
    .search-wrapper::before {
        content: "🔍";
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        color: #999;
        pointer-events: none;
    }
    
    .search-input {
        padding-left: 32px;
    }
    
    .outstanding-amount {
        font-weight: bold;
        color: #28a745;
    }
    
    .negative-outstanding {
        color: #dc3545;
    }
</style>
</head>
<body>
    <?php include "dashboard.php"; ?>

<div class="container">
    <h1>Supplier List</h1>
    
    <!-- Search Form with Dropdown -->
    <form method="get" id="searchForm">
        <div class="search-section">
            <div class="search-wrapper">
                <input type="text" 
                       name="search" 
                       id="searchInput"
                       class="search-input" 
                       placeholder="Search by supplier name or phone number..." 
                       value="<?= htmlspecialchars($search) ?>"
                       autocomplete="off">
                <div id="dropdown"></div>
            </div>
            <button type="submit" class="search-button"> Search</button>
           
        </div>
    </form>
    
    <div class="top-links">
        <a href="supplier.php">➕ ADD SUPPLIER</a>
    </div>
    
    <div class="results-count">
        📊 Found <?= $res->num_rows ?> supplier(s)
        <?php if (!empty($search)): ?>
            <span style="color: #007bff;">(Searching for: "<?= htmlspecialchars($search) ?>")</span>
        <?php endif; ?>
    </div>
    
     <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier Name</th>
                <th>Phone Number</th>
                <th>GST</th>
                <th>State</th>
                <th>Outstanding</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if($res->num_rows > 0) :?>
            <?php while($row = $res->fetch_assoc()) :?>
                <?php 
                $outstanding = sup_outstanding($row['id']);
                $outstanding_class = $outstanding < 0 ? 'negative-outstanding' : 'outstanding-amount';
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['suppliers_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phoneno']); ?></td>
                    <td><?php echo htmlspecialchars($row['gst_no']); ?></td>
                    <td><?php echo htmlspecialchars($row['sup_state']); ?></td>
                    <td class="<?php echo $outstanding_class; ?>">
                        <?php echo number_format($outstanding, 2); ?>
                    </td>
                    <td>
                        <a href="edit_supplier.php?id=<?php echo $row['id']; ?>">Edit</a> |
                        <a href="delete_supplier.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</a> |
                        <a href="sup_ledger.php?supplier_id=<?php echo $row['id']; ?>">View</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                    <?php if (!empty($search)): ?>
                        😕 No suppliers found matching "<?= htmlspecialchars($search) ?>"
                    <?php else: ?>
                        No suppliers found
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
     </table>
</div>

<script>
// Supplier data for dropdown
const suppliers = <?= json_encode($all_suppliers) ?>;

const searchInput = document.getElementById('searchInput');
const dropdown = document.getElementById('dropdown');
let selectedIndex = -1;
let suggestions = [];
let debounceTimer;

// Debounce function to avoid too many requests
function debounce(func, delay) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(func, delay);
}

// Function to show dropdown with matching suppliers
function showDropdown(searchText) {
    if (!searchText || searchText.trim() === '') {
        dropdown.style.display = 'none';
        suggestions = [];
        return;
    }
    
    const searchLower = searchText.toLowerCase().trim();
    const matches = suppliers.filter(supplier => 
        supplier.suppliers_name.toLowerCase().includes(searchLower) || 
        supplier.phoneno.toLowerCase().includes(searchLower)
    ).slice(0, 10); // Show max 10 results
    
    if (matches.length === 0) {
        dropdown.style.display = 'none';
        suggestions = [];
        return;
    }
    
    suggestions = matches;
    dropdown.innerHTML = '';
    
    matches.forEach((supplier, index) => {
        // Highlight matching text in supplier name
        let displayName = supplier.suppliers_name;
        if (searchLower) {
            const regex = new RegExp(`(${searchLower})`, 'gi');
            displayName = displayName.replace(regex, '<strong>$1</strong>');
        }
        
        let displayPhone = supplier.phoneno;
        if (searchLower && supplier.phoneno.toLowerCase().includes(searchLower)) {
            const regex = new RegExp(`(${searchLower})`, 'gi');
            displayPhone = displayPhone.replace(regex, '<strong>$1</strong>');
        }
        
        const div = document.createElement('div');
        div.innerHTML = `
            <span class="dropdown-name">${displayName}</span>
            <span class="dropdown-phone">📞 ${displayPhone}</span>
        `;
        
        div.onclick = () => {
            searchInput.value = supplier.suppliers_name;
            dropdown.style.display = 'none';
            document.getElementById('searchForm').submit();
        };
        
        dropdown.appendChild(div);
    });
    
    dropdown.style.display = 'block';
}

// Keyboard navigation
searchInput.addEventListener('keydown', function(e) {
    const items = dropdown.querySelectorAll('div');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (items.length > 0) {
            selectedIndex++;
            if (selectedIndex >= items.length) selectedIndex = 0;
            updateActiveItem(items);
        }
    } 
    else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (items.length > 0) {
            selectedIndex--;
            if (selectedIndex < 0) selectedIndex = items.length - 1;
            updateActiveItem(items);
        }
    }
    else if (e.key === 'Enter') {
        if (selectedIndex >= 0 && items.length > 0) {
            e.preventDefault();
            const selectedName = suggestions[selectedIndex]?.suppliers_name;
            if (selectedName) {
                searchInput.value = selectedName;
                dropdown.style.display = 'none';
                document.getElementById('searchForm').submit();
            }
        }
    }
    else if (e.key === 'Escape') {
        dropdown.style.display = 'none';
        selectedIndex = -1;
    }
});

function updateActiveItem(items) {
    items.forEach(item => item.classList.remove('active'));
    if (selectedIndex >= 0 && items[selectedIndex]) {
        items[selectedIndex].classList.add('active');
        items[selectedIndex].scrollIntoView({ block: 'nearest' });
        // Update input with selected supplier name
        if (suggestions[selectedIndex]) {
            searchInput.value = suggestions[selectedIndex].suppliers_name;
        }
    }
}

// Show dropdown on input with debounce
searchInput.addEventListener('input', function(e) {
    selectedIndex = -1;
    const searchText = e.target.value;
    
    if (searchText.length === 0) {
        dropdown.style.display = 'none';
        suggestions = [];
        return;
    }
    
    debounce(() => {
        showDropdown(searchText);
    }, 300);
});

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (e.target !== searchInput && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
        selectedIndex = -1;
    }
});

// Optional: Focus on search input when page loads
searchInput.focus();
</script>

</body>
</html>