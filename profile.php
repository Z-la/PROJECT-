<?php
include 'config.php'; // Ensure this file contains the correct database connection logic
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the user's profile data from the user_profile table
$query = "SELECT * FROM user_profile WHERE user_id = '$user_id' LIMIT 1";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
} else {
    // If no profile exists for the user, set empty fields or create a new one
    $profile = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'profile_picture' => '',
        'budget_goal' => '',
        'preferred_category' => '',
        'contact_number' => '',
        'currency_preference' => '',
        'locale' => '',
        'notifications_enabled' => 1
    ];
}

$message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $profile_picture = $conn->real_escape_string($_POST['profile_picture']);
    $budget_goal = $conn->real_escape_string($_POST['budget_goal']);
    $preferred_category = $conn->real_escape_string($_POST['preferred_category']);
    $contact_number = $conn->real_escape_string($_POST['contact_number']);
    $currency_preference = $conn->real_escape_string($_POST['currency_preference']);
    $locale = $conn->real_escape_string($_POST['locale']);
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;

    // Update profile in the database
    $updateQuery = "
        UPDATE user_profile 
        SET first_name = '$first_name', 
            last_name = '$last_name', 
            email = '$email', 
            profile_picture = '$profile_picture',
            budget_goal = '$budget_goal', 
            preferred_category = '$preferred_category', 
            contact_number = '$contact_number', 
            currency_preference = '$currency_preference', 
            locale = '$locale', 
            notifications_enabled = '$notifications_enabled', 
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = '$user_id'";

    if ($conn->query($updateQuery) === TRUE) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIOGRAPHY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #d3e9f7; /* Light shade of sky blue */
            font-family: 'Arial', sans-serif;
        }

        .container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 50px;
            max-width: 800px;
        }

        h2 {
            font-size: 2.5rem;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: bold;
            color: #555;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px;
            font-size: 1rem;
        }

        .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px;
            font-size: 1rem;
        }

        .form-check-label {
            color: #555;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .alert {
            margin-top: 20px;
            border-radius: 8px;
        }

        .back-btn {
            background-color: #6c757d;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 8px;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-check-input {
            border-radius: 4px;
        }

        .container a {
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>BIOGRAPHY</h2>

        <!-- Display success/error message -->
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Profile Form -->
        <form action="profile.php" method="POST">
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture URL</label>
                <input type="text" class="form-control" id="profile_picture" name="profile_picture" value="<?php echo htmlspecialchars($profile['profile_picture']); ?>">
            </div>
            <div class="mb-3">
                <label for="budget_goal" class="form-label">Budget Goal</label>
                <input type="number" class="form-control" id="budget_goal" name="budget_goal" value="<?php echo htmlspecialchars($profile['budget_goal']); ?>" step="0.01" required>
            </div>
            <div class="mb-3">
                <label for="preferred_category" class="form-label">Preferred Budget Category</label>
                <select class="form-select" id="preferred_category" name="preferred_category" required>
                    <option value="Food" <?php echo $profile['preferred_category'] == 'Food' ? 'selected' : ''; ?>>Food</option>
                    <option value="Transport" <?php echo $profile['preferred_category'] == 'Transport' ? 'selected' : ''; ?>>Transport</option>
                    <option value="Entertainment" <?php echo $profile['preferred_category'] == 'Entertainment' ? 'selected' : ''; ?>>Entertainment</option>
                    <option value="Health" <?php echo $profile['preferred_category'] == 'Health' ? 'selected' : ''; ?>>Health</option>
                    <option value="Utilities" <?php echo $profile['preferred_category'] == 'Utilities' ? 'selected' : ''; ?>>Utilities</option>
                    <option value="Shopping" <?php echo $profile['preferred_category'] == 'Shopping' ? 'selected' : ''; ?>>Shopping</option>
                    <option value="Education" <?php echo $profile['preferred_category'] == 'Education' ? 'selected' : ''; ?>>Education</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($profile['contact_number']); ?>">
            </div>
            <div class="mb-3">
                <label for="currency_preference" class="form-label">Currency Preference</label>
                <input type="text" class="form-control" id="currency_preference" name="currency_preference" value="<?php echo htmlspecialchars($profile['currency_preference']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="locale" class="form-label">Preferred Language/Region</label>
                <input type="text" class="form-control" id="locale" name="locale" value="<?php echo htmlspecialchars($profile['locale']); ?>" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="notifications_enabled" name="notifications_enabled" <?php echo $profile['notifications_enabled'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="notifications_enabled">Enable Notifications</label>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>

        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
