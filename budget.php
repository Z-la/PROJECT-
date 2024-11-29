<?php
include 'config.php'; 
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';


$categories = [
    'Food',
    'Transport',
    'Entertainment',
    'Health',
    'Utilities',
    'Shopping',
    'Education'
];


if (isset($_GET['delete_budget'])) {
    $budget_id = $_GET['delete_budget'];
    
    
    $deleteQuery = "DELETE FROM budgets WHERE id = '$budget_id' AND user_id = '$user_id'";
    if ($conn->query($deleteQuery) === TRUE) {
        $message = "Budget deleted successfully!";
    } else {
        $message = "Error deleting budget: " . $conn->error;
    }
}


if (isset($_POST['update_budget'])) {
    $category = $_POST['category'];
    $monthly_limit = $_POST['monthly_limit'];
    $budget_id = $_POST['budget_id']; 

    
    $updateQuery = "UPDATE budgets SET category = '$category', monthly_limit = '$monthly_limit' WHERE id = '$budget_id' AND user_id = '$user_id'";

    if ($conn->query($updateQuery) === TRUE) {
        $message = "Budget updated successfully!";
    } else {
        $message = "Error updating budget: " . $conn->error;
    }
}


if (isset($_POST['add_budget'])) {
    $category = $_POST['category'];
    $monthly_limit = $_POST['monthly_limit'];

    
    $insertQuery = "INSERT INTO budgets (user_id, category, monthly_limit) VALUES ('$user_id', '$category', '$monthly_limit')";
    if ($conn->query($insertQuery) === TRUE) {
        $message = "Budget added successfully!";
    } else {
        $message = "Error adding budget: " . $conn->error;
    }
}


$budgetsQuery = "
    SELECT b.*, 
           COALESCE(SUM(e.amount), 0) AS total_spent 
    FROM budgets b
    LEFT JOIN expenses e 
        ON b.user_id = e.user_id 
       AND b.category = e.category 
       AND MONTH(e.expense_date) = MONTH(CURRENT_DATE)  
       AND YEAR(e.expense_date) = YEAR(CURRENT_DATE)    
    WHERE b.user_id = '$user_id'
    GROUP BY b.id";
$budgetsResult = $conn->query($budgetsQuery);


$historicalQuery = "
    SELECT b.category, 
           b.monthly_limit, 
           COALESCE(SUM(e.amount), 0) AS total_spent, 
           MONTHNAME(e.expense_date) AS month, 
           YEAR(e.expense_date) AS year
    FROM budgets b
    LEFT JOIN expenses e 
        ON b.user_id = e.user_id 
       AND b.category = e.category
    WHERE b.user_id = '$user_id' 
      AND e.expense_date IS NOT NULL
    GROUP BY b.category, MONTH(e.expense_date), YEAR(e.expense_date)";
$historicalResult = $conn->query($historicalQuery);


if (isset($_GET['edit_budget'])) {
    $budget_id = $_GET['edit_budget'];
    
    
    $editQuery = "SELECT * FROM budgets WHERE id = '$budget_id' AND user_id = '$user_id'";
    $editResult = $conn->query($editQuery);
    if ($editResult->num_rows > 0) {
        $budget = $editResult->fetch_assoc();
    } else {
        $message = "Budget not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Budget</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: white; 
            color: #333; 
            font-family: 'Arial', sans-serif;
        }

        h2, h3 {
            color: skyblue; 
        }

        .container {
            max-width: 1200px;
            margin-top: 30px;
        }

        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: bold;
        }

        .btn-success {
            background-color: skyblue; 
        }

        .btn-danger {
            background-color: lightnavy; 
        }

        .btn-warning {
            background-color: #3b4a75; 
        }

        .alert-info {
            background-color: #2980b9; 
            color: white;
            font-weight: bold;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid #fff;
        }

        .table {
            background-color: #f2f2f2; 
        }

        .table th {
            background-color: skyblue; 
            color: white;
        }

        .table tbody tr:nth-child(odd) {
            background-color: white; 
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9; 
        }

        .alert {
            margin-top: 20px;
        }

        .btn-alert {
            background-color: lightnavy;
            border-radius: 5px;
            padding: 10px;
            font-size: 1rem;
            font-weight: bold;
            display: inline-block;
            color: white;
        }

        .btn-back {
            background-color: skyblue;
            color: white;
            border-radius: 5px;
            padding: 10px;
            font-weight: bold;
        }

        .btn-back:hover, .btn-alert:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Manage Your Budget</h2>

        
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row">
            
            <div class="col-md-6">
                <h3>Add Budget</h3>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        
                        <select class="form-control" id="category" name="category" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="monthly_limit" class="form-label">Monthly Limit</label>
                        <input type="number" class="form-control" id="monthly_limit" name="monthly_limit" step="0.01" required>
                    </div>
                    <button type="submit" name="add_budget" class="btn btn-success">Add Budget</button>
                </form>
            </div>

            
            <div class="col-md-6">
                <h3>Current Budgets</h3>
                <?php if ($budgetsResult->num_rows > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Monthly Limit</th>
                                <th>Total Spent This Month</th>
                                <th>Remaining Budget</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $budgetsResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo number_format($row['monthly_limit'], 2); ?></td>
                                    <td><?php echo number_format($row['total_spent'], 2); ?></td>
                                    <td><?php echo number_format($row['monthly_limit'] - $row['total_spent'], 2); ?></td>
                                    <td>
                                        
                                        <a href="?edit_budget=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="?delete_budget=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this budget?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No budgets set yet. Please add a budget.</p>
                <?php endif; ?>
            </div>
        </div>

        
        <h3>Historical Budget and Spending Data</h3>
        <?php if ($historicalResult->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Monthly Limit</th>
                        <th>Total Spent</th>
                        <th>Month</th>
                        <th>Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $historicalResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo number_format($row['monthly_limit'], 2); ?></td>
                            <td><?php echo number_format($row['total_spent'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['month']); ?></td>
                            <td><?php echo htmlspecialchars($row['year']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No historical data found. Spend more to track your history!</p>
        <?php endif; ?>
    </div>
</body>
</html>
