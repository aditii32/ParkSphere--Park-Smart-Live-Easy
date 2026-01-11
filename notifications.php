<?php
require_once('../config/config.php'); // Include database configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch notifications for the logged-in user
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $mysqli->error);
}
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

// Display notifications
while ($notification = $result->fetch_object()) {
    echo "<div class='notification-item'>";
    echo "<h5>{$notification->title}</h5>";
    echo "<p>{$notification->message}</p>";
    echo "<small>Created at: {$notification->created_at}</small>";
    echo "</div>";
}

// Mark notifications as read (optional)
$update_query = "UPDATE notifications SET status = 'read' WHERE user_id = ?";
$update_stmt = $mysqli->prepare($update_query);
$update_stmt->bind_param('i', $_SESSION['id']);
$update_stmt->execute();

function addNotification($user_id, $title, $message, $type, $mysqli) {
    $stmt = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, status, created_at) VALUES (?, ?, ?, ?, 'unread', NOW())");
    if (!$stmt) {
        die("SQL Error in addNotification: " . $mysqli->error);
    }
    $stmt->bind_param('isss', $user_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['add_reservation'])) {
    $error = 0;

    // Validate inputs
    $id = uniqid(); // Generate unique ID
    $code = !empty($_POST['code']) ? mysqli_real_escape_string($mysqli, trim($_POST['code'])) : $error = 1;
    $client_name = !empty($_POST['client_name']) ? mysqli_real_escape_string($mysqli, trim($_POST['client_name'])) : $error = 1;
    $client_phone = !empty($_POST['client_phone']) ? mysqli_real_escape_string($mysqli, trim($_POST['client_phone'])) : $error = 1;
    $car_regno = !empty($_POST['car_regno']) ? mysqli_real_escape_string($mysqli, trim($_POST['car_regno'])) : $error = 1;
    $lot_number = !empty($_POST['lot_number']) ? mysqli_real_escape_string($mysqli, trim($_POST['lot_number'])) : $error = 1;
    $parking_duration = !empty($_POST['parking_duration']) ? mysqli_real_escape_string($mysqli, trim($_POST['parking_duration'])) : $error = 1;
    $parking_date = !empty($_POST['parking_date']) ? mysqli_real_escape_string($mysqli, trim($_POST['parking_date'])) : $error = 1;
    $amt = !empty($_POST['amt']) ? mysqli_real_escape_string($mysqli, trim($_POST['amt'])) : $error = 1;
    $status = 'Pending'; // Default status

    if (!$error) {
        $query = "INSERT INTO reservations (id, code, client_name, client_phone, car_regno, lot_number, parking_duration, parking_date, amt, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssssssss', $id, $code, $client_name, $client_phone, $car_regno, $lot_number, $parking_duration, $parking_date, $amt, $status);
        $stmt->execute();
        if ($stmt) {
            // Add notification for booking confirmation
            addNotification($_SESSION['id'], 'Reservation Confirmed', 'You booked slot successfully.', 'booking', $mysqli);

            $success = "Reservation Added Successfully";
        } else {
            $err = "Failed, Please Try Again";
        }
    } else {
        $err = 'All fields are required.';
    }
}
?>