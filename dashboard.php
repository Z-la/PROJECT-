<?php
include 'config.php'; // Ensure this file contains the correct database connection logic
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Retrieve the user_id from the session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch expenses for the user
$expenseQuery = "SELECT SUM(amount) AS total_spent FROM expenses WHERE user_id = '$user_id'";
$expenseResult = $conn->query($expenseQuery);
$expenseData = $expenseResult->fetch_assoc();
$totalSpent = $expenseData['total_spent'] ?? 0;

// Fetch budget for the user
$budgetQuery = "SELECT SUM(monthly_limit) AS total_budget FROM budgets WHERE user_id = '$user_id'";
$budgetResult = $conn->query($budgetQuery);
$budgetData = $budgetResult->fetch_assoc();
$totalBudget = $budgetData['total_budget'] ?? 0;

// Calculate remaining budget
$remainingBudget = $totalBudget - $totalSpent;

// Fetch spending breakdown by category
$categoryQuery = "SELECT category, SUM(amount) AS total FROM expenses WHERE user_id = '$user_id' GROUP BY category";
$categoryResult = $conn->query($categoryQuery);
$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[] = $row;
}

// Dummy data for line chart (monthly expense trend)
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyExpenses = [120, 150, 170, 200, 180, 220, 240, 260, 300, 320, 340, 400];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #e1f4fd; /* Light sky blue */
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50; /* Navy blue */
            color: #fff;
            padding: 20px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .sidebar h4 {
            color: #3498db; /* Light blue */
            margin-bottom: 20px;
        }

        .sidebar a {
            color: #ddd;
            text-decoration: none;
            margin: 10px 0;
            font-size: 1.1rem;
        }

        .sidebar a:hover {
            color: #fff;
            background-color: #2980b9; /* Blue hover effect */
            padding-left: 10px;
            transition: all 0.3s ease;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }

        h2 {
            color: #2c3e50;
        }

        .card-header {
            font-weight: bold;
            background-color: #2980b9; /* Navy Blue Header */
            color: #fff;
        }

        .card-body {
            background-color: #f0f8ff; /* Lighter blue for card body */
            color: #333;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 1.5rem;
        }

        .card-header, .card-body {
            border-radius: 8px;
        }

        .row .col-md-6 {
            margin-bottom: 20px;
        }

        canvas {
            max-width: 100%;
            height: 400px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>Welcome, <?php echo htmlspecialchars($username); ?>!</h4>
        <a href="dashboard.php">Dashboard</a>
        <a href="expense.php">Expenses</a>
        <a href="budget.php">Budget</a>
        <a href="report.php">Report</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="content">
        <h2>Dashboard</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Total Spent</div>
                    <div class="card-body">
                        <h5 class="card-title">$<?php echo number_format($totalSpent, 2); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Total Budget</div>
                    <div class="card-body">
                        <h5 class="card-title">$<?php echo number_format($totalBudget, 2); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Remaining Budget</div>
                    <div class="card-body">
                        <h5 class="card-title">$<?php echo number_format($remainingBudget, 2); ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spending Visualization -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Spending Breakdown by Category</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="spendingChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Monthly Expense Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare the data for the pie chart
        const categories = <?php echo json_encode(array_column($categoryData, 'category')); ?>;
        const amounts = <?php echo json_encode(array_column($categoryData, 'total')); ?>;

        // Create the pie chart
        const pieCtx = document.getElementById('spendingChart').getContext('2d');
        const spendingChart = new Chart(pieCtx, {
            type: 'pie', // Pie chart
            data: {
                labels: categories,
                datasets: [{
                    label: 'Spending Breakdown',
                    data: amounts,
                    backgroundColor: ['#1abc9c', '#e74c3c', '#8e44ad', '#3498db', '#f39c12'], // Darker shades
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': $' + tooltipItem.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Prepare the data for the line chart (monthly expenses)
        const months = <?php echo json_encode($months); ?>;
        const monthlyExpenses = <?php echo json_encode($monthlyExpenses); ?>;

        // Create the line chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'line', // Line chart
            data: {
                labels: months,
                datasets: [{
                    label: 'Monthly Expenses',
                    data: monthlyExpenses,
                    borderColor: '#2980b9', // Blue line
                    backgroundColor: 'rgba(41, 128, 185, 0.2)', // Light blue fill
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.dataset.label + ': $' + tooltipItem.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
