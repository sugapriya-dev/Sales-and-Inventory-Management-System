<?php
session_start();
include "db.php"; 

// If not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$search = $_GET['search'] ?? "";

// Get all customers for dropdown suggestions
$all_customers_sql = "SELECT id, customername, phoneno FROM customers ORDER BY customername";
$all_customers_result = $conn->query($all_customers_sql);
$all_customers = [];
while ($cust = $all_customers_result->fetch_assoc()) {
    $all_customers[] = $cust;
}

// Modified query to include search
if (!empty($search)) {
    $sql = "SELECT * FROM customers 
            WHERE customername LIKE ? OR phoneno LIKE ?
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("select * from customers order by created_at desc");
}

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
    <title>Customer Details</title>
    <style>
    body {
        display: flex;
        justify-content: center;   /* horizontal center */
        align-items: center;       /* vertical center */
        min-height: 100vh;         /* full viewport height */
        margin: 0;                 /* remove default body margin */
        background: #f0f0f0;       /* optional background */
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
    </style>
</head>
<body>
    <?php include "dashboard.php"; ?>

<div class="container">
    <h1>Customer Details</h1>
    
    <!-- Search Form with Dropdown -->
    <form method="get" id="searchForm">
        <div class="search-section">
            <div class="search-wrapper">
                <input type="text" 
                       name="search" 
                       id="searchInput"
                       class="search-input" 
                       placeholder="Search by customer name or phone number..." 
                       value="<?= htmlspecialchars($search) ?>"
                       autocomplete="off">
                <div id="dropdown"></div>
            </div>
            <button type="submit" class="search-button">Search</button>
           
        </div>
    </form>
    
    <div class="top-links">
        <a href="customers.php">➕ ADD CUSTOMER</a>
    </div>
    
    <div class="results-count">
        📊 Found <?= $res->num_rows ?> customer(s)
        <?php if (!empty($search)): ?>
            <span style="color: #007bff;">(Searching for: "<?= htmlspecialchars($search) ?>")</span>
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Phone Number</th>
                <th>City</th>
                <th>State</th>
                <th>Aadhaar No</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if($res->num_rows > 0) :?>
            <?php while($row = $res->fetch_assoc()) :?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['customername']); ?></td>
                <td><?php echo htmlspecialchars($row['phoneno']); ?></td>
                <td><?php echo htmlspecialchars($row['city']); ?></td>
                <td><?php echo htmlspecialchars($row['state']); ?></td>
                <td><?php echo htmlspecialchars($row['aadhaarno']); ?></td>
                <td>
                    <a href="edit_customer.php?id=<?php echo $row['id']; ?>">Edit</a> |
                    <a href="delete_customer.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                    <?php if (!empty($search)): ?>
                        😕 No customers found matching "<?= htmlspecialchars($search) ?>"
                    <?php else: ?>
                        No customers found
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Customer data for dropdown
const customers = <?= json_encode($all_customers) ?>;

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

// Function to show dropdown with matching customers
function showDropdown(searchText) {
    if (!searchText || searchText.trim() === '') {
        dropdown.style.display = 'none';
        suggestions = [];
        return;
    }
    
    const searchLower = searchText.toLowerCase().trim();
    const matches = customers.filter(customer => 
        customer.customername.toLowerCase().includes(searchLower) || 
        customer.phoneno.toLowerCase().includes(searchLower)
    ).slice(0, 10); // Show max 10 results
    
    if (matches.length === 0) {
        dropdown.style.display = 'none';
        suggestions = [];
        return;
    }
    
    suggestions = matches;
    dropdown.innerHTML = '';
    
    matches.forEach((customer, index) => {
        // Highlight matching text in customer name
        let displayName = customer.customername;
        if (searchLower) {
            const regex = new RegExp(`(${searchLower})`, 'gi');
            displayName = displayName.replace(regex, '<strong>$1</strong>');
        }
        
        let displayPhone = customer.phoneno;
        if (searchLower && customer.phoneno.toLowerCase().includes(searchLower)) {
            const regex = new RegExp(`(${searchLower})`, 'gi');
            displayPhone = displayPhone.replace(regex, '<strong>$1</strong>');
        }
        
        const div = document.createElement('div');
        div.innerHTML = `
            <span class="dropdown-name">${displayName}</span>
            <span class="dropdown-phone">📞 ${displayPhone}</span>
        `;
        
        div.onclick = () => {
            searchInput.value = customer.customername;
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
            const selectedItem = items[selectedIndex];
            const selectedName = suggestions[selectedIndex]?.customername;
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
        // Update input with selected customer name
        if (suggestions[selectedIndex]) {
            searchInput.value = suggestions[selectedIndex].customername;
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