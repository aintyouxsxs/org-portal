<?php
session_start();
include 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: community.php"); // Redirect to community.php after logout
    exit();
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['role'] !== 'admin') {
    header("Location: home.php"); // Redirect if not an admin
    exit();
}

// Fetch approved organizations
$approvedOrgs = fetchApprovedOrganizations(); // This function should return approved organizations

// Handle removal of an organization
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['removeOrg'])) {
        $orgId = $_POST['orgId'];
        echo "Removing organization with ID: " . htmlspecialchars($orgId); // Debugging line
        removeOrganization($orgId);
        header("Location: admin-organizations-approved.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Approved Organizations</title>
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
            <li>
                <a href="admin-user-dashboard.php">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Users-Info</span>
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
            <li class="active">
                <a href="admin-organizations-approved.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Organizations Approved</span>
                </a>
            </li>
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

            <!-- Approved Organizations Section -->
            <div class="organizations-log">
                <h2>Approved Organizations</h2>
                <div id="organizations-list">
                    <?php if (empty($approvedOrgs)): ?>
                        <p>No approved organizations at the moment.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Logo</th>
                                    <th>President ID</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($approvedOrgs as $org): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($org['org_name']); ?></td>
                                    <td><?php echo htmlspecialchars($org['description']); ?></td>
                                    <td><img src="<?php echo htmlspecialchars($org['logo_url']); ?>" alt="<?php echo htmlspecialchars($org['org_name']); ?>" width="100"></td>
                                    <td><?php echo htmlspecialchars($org['president_id']); ?></td>
                                    <td><?php echo htmlspecialchars($org['status']); ?></td>
                                    <td>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="orgId" value="<?php echo htmlspecialchars($org['org_id']); ?>">
                                        <button type="submit" name="removeOrg" class="remove-btn" onclick="return confirm('Are you sure you want to remove this organization?');">Remove</button>
                                    </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
