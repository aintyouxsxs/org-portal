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

// Fetch approved events with status 'active'
$approvedEvents = fetchApprovedEvents();

// Handle event actions (update or remove)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['updateEvent'])) {
        if (isset($_POST['eventId'])) {
            $eventId = $_POST['eventId'];
            // Redirect to an event update page with the event ID as a parameter
            header("Location: update-event.php?id=$eventId");
            exit();
        } else {
            // Handle missing eventId error
            echo "Error: Event ID not specified.";
        }
    } elseif (isset($_POST['removeEvent'])) {
        if (isset($_POST['eventId'])) {
            $eventId = $_POST['eventId'];
            removeEvent($eventId);  // Function to remove the event from the database
            // Redirect to avoid re-submission on refresh
            header("Location: admin-events-approved.php");
            exit();
        } else {
            // Handle missing eventId error
            echo "Error: Event ID not specified.";
        }
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
            <li class="active">
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

            <!-- Approved Events Section -->
            <div class="events-log">
                <h2>Approved Events</h2>
                <div id="event-requests-list">
                    <?php if (empty($approvedEvents)): ?>
                        <p>No approved events at the moment.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Venue</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Image</th>
                                    <th>Description</th>
                                    <th>Status</th> <!-- Added Status Header -->
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approvedEvents as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars($event['venue']); ?></td>
                                        <td><?php echo htmlspecialchars($event['date']); ?></td>
                                        <td><?php echo htmlspecialchars($event['start_time'] . ' - ' . $event['end_time']); ?></td>
                                        <td><img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" width="100"></td>
                                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                                        <td><?php echo htmlspecialchars($event['status']); ?></td> <!-- Added Status Data -->
                                        <td>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="eventId" value="<?php echo htmlspecialchars($event['event_id']); ?>">
                                                <button type="submit" name="removeEvent" class="remove-btn" onclick="return confirm('Are you sure you want to remove this event?');">Remove</button>
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
