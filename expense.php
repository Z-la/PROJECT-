<?php
include 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission to log an expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $expense_date = $_POST['expense_date'];

    // Insert expense into the database
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isdss', $user_id, $category, $amount, $description, $expense_date);

    if ($stmt->execute()) {
        $message = "Expense added successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
    $stmt->close(); // Close the prepared statement
}

// Handle expense deletion
if (isset($_GET['delete'])) {
    $expense_id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $expense_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: expenses.php'); // Refresh the page after delete
    exit();
}

// Handle expense editing
if (isset($_GET['edit'])) {
    $expense_id = $_GET['edit'];

    // Fetch expense details for editing
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $expense_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $expense = $result->fetch_assoc();
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $expense_date = $_POST['expense_date'];

        // Update expense in the database
        $stmt = $conn->prepare("UPDATE expenses SET category = ?, amount = ?, description = ?, expense_date = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sdssii', $category, $amount, $description, $expense_date, $expense_id, $user_id);

        if ($stmt->execute()) {
            $message = "Expense updated successfully!";
            header('Location: expenses.php');
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch filtered expenses based on search parameters
$categoryFilter = $_GET['category'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$filterQuery = "SELECT * FROM expenses WHERE user_id = '$user_id'";

if ($categoryFilter) {
    $filterQuery .= " AND category = '$categoryFilter'";
}

if ($start_date && $end_date) {
    $filterQuery .= " AND expense_date BETWEEN '$start_date' AND '$end_date'";
}

$filteredResult = $conn->query($filterQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .form-container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .form-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .table-container {
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <!-- Expense Input Form -->
    <div class="form-container">
        <h2>Log Expense</h2>

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="expenses.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="category" class="form-label">Category:</label>
                <select class="form-select" id="category" name="category" required>
                    <option value="Food">Food</option>
                    <option value="Transport">Transport</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Health">Health</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Education">Education</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Amount:</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (optional):</label>
                <textarea class="form-control" id="description" name="description"></textarea>
            </div>
            <div class="mb-3">
                <label for="expense_date" class="form-label">Date:</label>
                <input type="date" class="form-control" id="expense_date" name="expense_date" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log Expense</button>
        </form>
    </div>

    <!-- Expense Search and Filter -->
    <div class="form-container mt-5">
        <h2>Search and Filter Expenses</h2>
        <form action="expenses.php" method="GET">
            <div class="mb-3">
                <label for="category" class="form-label">Category:</label>
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <option value="Food">Food</option>
                    <option value="Transport">Transport</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Health">Health</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Education">Education</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">Start Date:</label>
                <input type="date" class="form-control" name="start_date">
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">End Date:</label>
                <input type="date" class="form-control" name="end_date">
            </div>
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </form>
    </div>

    <!-- Display Expenses Table -->
    <div class="table-container mt-5">
        <h3>All Expenses</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $filteredResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td>$<?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['expense_date']); ?></td>
                        <td>
                            <a href="expenses.php?edit=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="expenses.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
