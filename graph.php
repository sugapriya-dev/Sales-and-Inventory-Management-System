
<?php

include "db.php";

// ========== Fetch Last Month Sales Data for Graph ==========
$last_month_sales = [];
$labels = [];
$values = [];

// Get first and last day of last month (previous month)
$first_day = date('Y-m-01', strtotime('first day of last month'));
$last_day = date('Y-m-t', strtotime('last day of last month'));

// Display which month we're showing
$current_month = date('F', strtotime('now'));
$last_month_name = date('F', strtotime('first day of last month'));

// FIXED QUERY: Use DATE() function and proper GROUP BY
$sales_query = "SELECT 
                    DATE(date) as sale_date,
                    COALESCE(SUM(grand_total), 0) as daily_total 
                FROM sales 
                WHERE date BETWEEN '$first_day' AND '$last_day'
                GROUP BY DATE(date)
                ORDER BY sale_date ASC";

$sales_result = $conn->query($sales_query);

if (!$sales_result) {
    die("Query failed: " . $conn->error);
}

// Create an array with all dates of last month initialized to 0
$current_date = $first_day;
$all_dates = [];
while (strtotime($current_date) <= strtotime($last_day)) {
    $date_key = $current_date;
    $day_name = date('D', strtotime($current_date));
    $formatted_date = date('d-m', strtotime($current_date));
    
    $all_dates[$date_key] = [
        'day' => $day_name,
        'formatted' => $formatted_date,
        'total' => 0
    ];
    
    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
}

// Fill in the actual sales data
if ($sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $sale_date = $row['sale_date'];
        if (isset($all_dates[$sale_date])) {
            $all_dates[$sale_date]['total'] = floatval($row['daily_total']);
        }
    }
}

// Prepare data for chart
foreach ($all_dates as $date_key => $data) {
    $labels[] = $data['day'] . ' ' . $data['formatted'];
    $values[] = $data['total'];
    $last_month_sales[] = [
        'date' => $date_key,
        'day' => $data['day'],
        'total' => $data['total']
    ];
}

// Convert to JSON for JavaScript
$chart_labels_json = json_encode($labels);
$chart_values_json = json_encode($values);

// Get total sales for last month
$total_query = "SELECT COALESCE(SUM(grand_total), 0) as total_sales 
                FROM sales 
                WHERE date BETWEEN '$first_day' AND '$last_day'";
$total_result = $conn->query($total_query);
$total_data = $total_result->fetch_assoc();
$last_month_total = $total_data['total_sales'];
?>

<html>
<head>
    <title>Dashboard</title>
    <!-- Add Chart.js library for the graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            
        }
        html, body{
            height:100%;
        }
        

        /* Graph Container */
        .graph-container {
            grid-column: span 3;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 10px;
            
           
        }

        .graph-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            
        }

        .graph-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
           
        }

        .graph-header h2 span {
            color: #007bff;
            font-size: 14px;
            font-weight: normal;
            margin-left: 10px;
        }

        /* Chart Type Toggle */
        .chart-toggle {
            display: flex;
            gap: 10px;
        }

        .toggle-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .toggle-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .toggle-btn:hover {
            background: #e9ecef;
        }

        .toggle-btn.active:hover {
            background: #0056b3;
        }

        canvas {
            width: 100% !important;
            height: 300px !important;
        }
        
        .sales-summary {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .sales-summary span {
            font-weight: bold;
            color: #28a745;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <?php include "dashboard.php"; ?>

    <div class="content">
       
        <!-- Last Month Sales Graph with Toggle -->
        <div class="graph-container">
            <div class="graph-header">
               <h2>Last Month Sales (<?= $last_month_name ?>) 
<span>(<?= date('01-m-Y', strtotime('first day of last month')) ?> to <?= date('t-m-Y', strtotime('last day of last month')) ?>)</span></h2>

                <div class="chart-toggle">
                    <button class="toggle-btn active" id="barBtn" onclick="changeChartType('bar')">Bar</button>
                    <button class="toggle-btn" id="lineBtn" onclick="changeChartType('line')">Line</button>
                </div>
            </div>
            <canvas id="salesChart"></canvas>
            
            <!-- Sales Summary -->
            <div class="sales-summary">
                Total Sales for <?= $last_month_name ?>: <span>₹ <?= number_format($last_month_total, 2) ?></span>
            </div>
        </div>
    </div>

    <script>
        // Get data from PHP
        const labels = <?php echo $chart_labels_json; ?>;
        const values = <?php echo $chart_values_json; ?>;
        
        let chart; // Variable to hold chart instance

        // Function to create/update chart
        function createChart(type) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (chart) {
                chart.destroy();
            }
            
            // Chart configuration
            const config = {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales Amount (₹)',
                        data: values,
                        backgroundColor: type === 'bar' ? 'rgba(54, 162, 235, 0.5)' : 'rgba(54, 162, 235, 0.1)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 3,
                        tension: 0.3, // For line smoothing
                        fill: type === 'line' ? false : true,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        ...(type === 'bar' && { borderRadius: 5, barPercentage: 0.6 })
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₹ ' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            };
            
            chart = new Chart(ctx, config);
        }

        // Function to change chart type
        function changeChartType(type) {
            // Update active button states
            document.getElementById('barBtn').classList.remove('active');
            document.getElementById('lineBtn').classList.remove('active');
            
            if (type === 'bar') {
                document.getElementById('barBtn').classList.add('active');
            } else {
                document.getElementById('lineBtn').classList.add('active');
            }
            
            // Recreate chart with new type
            createChart(type);
        }

        // Initialize with bar chart
        window.onload = function() {
            createChart('bar');
        };
    </script>
</body>
</html>