<?php
session_start();
include 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: home.php"); // Redirect to community.php after logout
    exit();
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['role'] !== 'admin') {
    header("Location: home.php"); // Redirect if not an admin
    exit();
}

// Fetch user details excluding admins
$users = fetchUsers();

// Handle user removal if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user_id'])) {
    $removeUserID = $_POST['remove_user_id'];
    if (removeUser($removeUserID)) {
        // Redirect to the same page after deletion to refresh the user list
        header("Location: admin-user-dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="content">
    <div class="sidebar">
        <div class="logo">
            <img src="image/avatar.jpg" alt=""/>
        </div>
        <ul class="menu">
            <li class="active">
                <a href="admin-user-dashboard.php">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Users Info</span>
                </a>
            </li>
            <li>
                <a href="admin-events-requests.php">
                    <i class="fa-solid fa-calendar"></i>
                    <span>Event Requests</span>
                </a>
            </li>
            <li>
                <a href="admin-events-approved.php">
                    <i class="fa-solid fa-calendar"></i>
                    <span>Event Approved</span>
                </a>
            </li>
            <li>
                <a href="admin-organization-requests.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Organizations Requests</span>
                </a>
            </li>
            <li>
                <a href="admin-organizations-approved.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Organizations Approved</span>
                </a>
            </li>
            <!-- Other menu items -->
            <form method="POST" class="logout">
                <a href="home.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
            </form>
        </ul>
    </div>

    <div id="main-content">
        <div class="container">
            <div class="admin-panel">
                <h2>Admin Panel</h2>
            </div>

            <!-- Registered Users Section -->
            <div class="user-log">
                <h2>Registered Users</h2>
                <div id="user-list">
                    <table>
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Age</th>
                                <th>Address</th>
                                <th>School</th>
                                <th>Organization</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['FirstName']); ?></td>
                                <td><?php echo htmlspecialchars($user['LastName']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td><?php echo htmlspecialchars($user['Age']); ?></td>
                                <td><?php echo htmlspecialchars($user['Address']); ?></td>
                                <td><?php echo htmlspecialchars($user['School']); ?></td>
                                <td><?php echo htmlspecialchars($user['Org_Name']); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to remove this user?');">
                                        <input type="hidden" name="remove_user_id" value="<?php echo htmlspecialchars($user['UserID']); ?>">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
