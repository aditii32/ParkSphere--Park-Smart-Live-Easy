<?php
session_start();
include('../config/config.php');

// Mark all notifications as read for the logged-in user
$stmt = $mysqli->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$stmt->close();
?>