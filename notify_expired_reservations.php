<?php
require_once('../config/config.php');
require_once('../config/notifications.php');

$current_time = date('Y-m-d H:i:s');
$query = "SELECT r.id, r.user_id, r.parking_date, r.parking_duration, c.name FROM reservations r 
          JOIN clients c ON r.user_id = c.id 
          WHERE DATE_ADD(r.parking_date, INTERVAL r.parking_duration HOUR) <= ? AND r.status = 'Active'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $current_time);
$stmt->execute();
$result = $stmt->get_result();

while ($reservation = $result->fetch_object()) {
    // Send notification for expired parking duration
    addNotification($reservation->user_id, 'Parking Duration Over', 'Your parking duration has expired. Please vacate the parking lot.', 'duration', $mysqli);

    // Optionally, update the reservation status
    $update_query = "UPDATE reservations SET status = 'Expired' WHERE id = ?";
    $update_stmt = $mysqli->prepare($update_query);
    $update_stmt->bind_param('i', $reservation->id);
    $update_stmt->execute();
}
?>