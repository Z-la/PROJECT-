<?php
include 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch expenditures for the selected period (daily, weekly, monthly)
$period = $_GET['period'] ?? 'monthly';
$start_date = '';
$end_date = '';

switch ($period) {
    case 'daily':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'weekly':
        $start_date = date('Y-m-d', strtotime('last sunday'));
        $end_date = date('Y-m-d', strtotime('next saturday'));
        break;
    case 'monthly':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
}

$expenseQuery = "SELECT category, SUM(amount) AS total_spent FROM expenses WHERE user_id = '$user_id' AND expense_date BETWEEN '$start_date' AND '$end_date' GROUP BY category";
$expenseResult = $conn->query($expenseQuery);

// Fetch budgets
$budgetQuery = "SELECT category, monthly_limit FROM budgets WHERE user_id = '$user_id'";
$budgetResult = $conn->query($budgetQuery);

$expenses = [];
$budgets = [];

while ($row = $expenseResult->fetch_assoc()) {
    $expenses[$row['category']] = $row['total_spent'];
}

while ($row = $budgetResult->fetch_assoc()) {
    $budgets[$row['category']] = $row['monthly_limit'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenditure Report</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Background color */
        body {
            background-color: #f0f8ff; /* Sky Blue */
            color: #000080; /* Navy Blue */
        }

        /* Page header color */
        h2, h5 {
            color: #000080; /* Navy Blue */
        }

        /* Button styles */
        .btn-primary {
            background-color: #87CEEB; /* Sky Blue */
            border-color: #000080; /* Navy Blue */
        }
        
        .btn-primary:hover {
            background-color: #4682B4; /* Steel Blue */
            border-color: #000080; /* Navy Blue */
        }

        .btn-secondary {
            background-color: #000080; /* Navy Blue */
            border-color: #87CEEB; /* Sky Blue */
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4682B4; /* Steel Blue */
            border-color: #87CEEB; /* Sky Blue */
        }

        /* Table styling */
        table {
            background-color: white;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #87CEEB; /* Sky Blue */
            color: #000080; /* Navy Blue */
        }

        td {
            border: 1px solid #ddd;
        }

        /* Status indicators */
        .status-indicator {
            font-weight: bold;
        }
        .over-budget {
            color: red;
        }

        .on-track {
            color: green;
        }

        .under-budget {
            color: orange;
        }

        /* Bar chart customization */
        .chart-container {
            margin-top: 20px;
        }

        /* Custom font color for text in the page */
        .container h4, .container p {
            color: #000080; /* Navy Blue */
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Expenditure Report</h2>
        <h5>Period: <?php echo ucfirst($period); ?></h5>

        <div class="row mt-4">
            <div class="col-md-6">
                <h4>Total Expenditures</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount Spent</th>
                            <th>Remaining Budget</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $category => $spent): ?>
                            <?php
                            // Check if the category exists in the budgets array
                            $remaining = isset($budgets[$category]) ? $budgets[$category] - $spent : 0;
                            $status = '';
                            $statusClass = '';

                            // Determine status and class based on remaining budget
                            if ($remaining < 0) {
                                $status = 'Over Budget';
                                $statusClass = 'over-budget';
                            } elseif ($remaining <= 0.1 * ($budgets[$category] ?? 0)) {
                                $status = 'On Track';
                                $statusClass = 'on-track';
                            } else {
                                $status = 'Under Budget';
                                $statusClass = 'under-budget';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category); ?></td>
                                <td>$<?php echo number_format($spent, 2); ?></td>
                                <td>$<?php echo number_format($remaining, 2); ?></td>
                                <td class="status-indicator <?php echo $statusClass; ?>"><?php echo $status; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <h4>Spending Trends (Bar Chart)</h4>
                <canvas id="spendingChart"></canvas>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary" onclick="exportCSV()">Export as CSV</button>
            <button class="btn btn-secondary" onclick="exportPDF()">Export as PDF</button>
        </div>
    </div>

    <script>
        // Ensure data is not empty
        const expensesData = <?php echo json_encode(array_values($expenses)); ?>;
        const categories = <?php echo json_encode(array_keys($expenses)); ?>;

        // Create the chart for spending trends with medium-sized bars
        if (expensesData.length > 0) {
            const ctx = document.getElementById('spendingChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Total Spending',
                        data: expensesData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',  // Light blue bars
                        borderColor: 'rgba(54, 162, 235, 1)',  // Dark blue border
                        borderWidth: 1,
                        barThickness: 30, // Medium size bar thickness
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `Spent: $${context.raw}`;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('spendingChart').innerHTML = "<p>No spending data available for this period.</p>";
        }

        // Export the data as CSV
        function exportCSV() {
            const rows = [
                ['Category', 'Amount Spent', 'Remaining Budget', 'Status']
            ];

            <?php foreach ($expenses as $category => $spent): ?>
                const remaining = <?php echo json_encode($budgets); ?>[<?php echo json_encode($category); ?>] ?? 0 - <?php echo json_encode($spent); ?>;
                let status = '';
                if (remaining < 0) {
                    status = 'Over Budget';
                } else if (remaining <= 0.1 * <?php echo json_encode($budgets); ?>[<?php echo json_encode($category); ?>] ?? 0) {
                    status = 'On Track';
                } else {
                    status = 'Under Budget';
                }
                rows.push([<?php echo json_encode($category); ?>, <?php echo json_encode($spent); ?>, remaining, status]);
            <?php endforeach; ?>

            let csvContent = "data:text/csv;charset=utf-8,";

            rows.forEach(function(rowArray) {
                csvContent += rowArray.join(",") + "\r\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "expenditure_report.csv");
            link.click();
        }

        // Export the data as PDF (using jsPDF library)
        function exportPDF() {
            const doc = new jsPDF();

            doc.text("Expenditure Report", 20, 20);
            doc.text("Period: <?php echo ucfirst($period); ?>", 20, 30);
            doc.text("Category-wise Breakdown", 20, 40);

            let yOffset = 50;

            <?php foreach ($expenses as $category => $spent): ?>
                const remaining = <?php echo json_encode($budgets); ?>[<?php echo json_encode($category); ?>] ?? 0 - <?php echo json_encode($spent); ?>;
                let status = '';
                if (remaining < 0) {
                    status = 'Over Budget';
                } else if (remaining <= 0.1 * <?php echo json_encode($budgets); ?>[<?php echo json_encode($category); ?>] ?? 0) {
                    status = 'On Track';
                } else {
                    status = 'Under Budget';
                }
                doc.text("<?php echo json_encode($category); ?>: $<?php echo json_encode($spent); ?>, Remaining: $"+remaining.toFixed(2)+", Status: "+status, 20, yOffset);
                yOffset += 10;
            <?php endforeach; ?>

            doc.save("expenditure_report.pdf");
        }
    </script>
</body>
</html>
