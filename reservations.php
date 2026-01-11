<?php
session_start();
include('../config/config.php');
require_once('../config/checklogin.php');
client();
include('../config/codeGen.php');
require_once('../config/notifications.php'); // Ensure notifications helper is included

/* Update Client Reservations */
if (isset($_POST['update_reservation'])) {
    $error = 0;

    // Validate inputs
    $id = !empty($_POST['id']) ? mysqli_real_escape_string($mysqli, trim($_POST['id'])) : $error = 1;
    $code = !empty($_POST['code']) ? mysqli_real_escape_string($mysqli, trim($_POST['code'])) : $error = 1;
    $client_name = !empty($_POST['client_name']) ? mysqli_real_escape_string($mysqli, trim($_POST['client_name'])) : $error = 1;
    $client_phone = !empty($_POST['client_phone']) ? mysqli_real_escape_string($mysqli, trim($_POST['client_phone'])) : $error = 1;
    $car_regno = !empty($_POST['car_regno']) ? mysqli_real_escape_string($mysqli, trim($_POST['car_regno'])) : $error = 1;
    $lot_number = !empty($_POST['lot_number']) ? mysqli_real_escape_string($mysqli, trim($_POST['lot_number'])) : $error = 1;
    $parking_duration = !empty($_POST['parking_duration']) ? mysqli_real_escape_string($mysqli, trim($_POST['parking_duration'])) : $error = 1;
    $parking_date = !empty($_POST['parking_date']) ? mysqli_real_escape_string($mysqli, trim($_POST['parking_date'])) : $error = 1;
    $amt = !empty($_POST['amt']) ? mysqli_real_escape_string($mysqli, trim($_POST['amt'])) : $error = 1;
    $status = !empty($_POST['status']) ? mysqli_real_escape_string($mysqli, trim($_POST['status'])) : $error = 1;

    if (!$error) {
        $query = 'UPDATE reservations SET code =?, client_name =?, client_phone =?, car_regno=?, lot_number =?, parking_duration =?, parking_date =?, amt =?, status =? WHERE id = ?';
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssssssss', $code, $client_name, $client_phone, $car_regno, $lot_number, $parking_duration, $parking_date, $amt, $status, $id);
        $stmt->execute();

        if ($stmt) {
            // Add notification for reservation update
            addNotification($_SESSION['id'], 'Reservation Updated', 'Your reservation has been successfully updated.', 'booking', $mysqli);

            $success = 'Client Account Parking Reservation Updated';
            header('refresh:1; url=reservations.php');
        } else {
            $info = 'Please Try Again Or Try Later';
        }
    } else {
        $err = 'All fields are required.';
    }
}

/* Add Client Reservations */
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
            require_once('../config/notifications.php'); // Ensure the notifications helper is included
            addNotification($_SESSION['id'], 'Reservation Confirmed', 'You have successfully reserved a parking slot.', 'booking', $mysqli);

            $success = "Reservation Added Successfully";
        } else {
            $err = "Failed, Please Try Again";
        }
    } else {
        $err = 'All fields are required.';
    }
}

/* Delete Reservations */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = 'DELETE FROM reservations WHERE id=?';
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $id);
    $stmt->execute();

    if ($stmt) {
        // Add notification for reservation cancellation
        addNotification($_SESSION['id'], 'Reservation Cancelled', 'Your reservation has been successfully cancelled.', 'booking', $mysqli);

        $success = 'Reservation Deleted Successfully';
        header('refresh:1; url=reservations.php');
    } else {
        $info = 'Failed to delete reservation. Please try again.';
    }
}

require_once("../partials/head.php");
?>

<body>

    <!-- Navigation Bar-->
    <?php require_once('../partials/client_nav.php'); ?>
    <!-- End Navigation Bar-->

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="wrapper">
        <div class="container">

            <!-- Page-Title -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <div class="btn-group float-right m-t-15">
                            <a href="parking_lots.php" class="btn btn-primary waves-effect waves-light m-r-5 m-t-10">Add Parking Lot Reservation</a>
                        </div>
                        <h4 class="page-title">My Reservations</h4>
                    </div>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card-box">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>My Name</th>
                                    <th>My Phone No</th>
                                    <th>Car Regno</th>
                                    <th>Lot No</th>
                                    <th>Fee</th>
                                    <th>Parking Duration</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $phone = $_SESSION['phone'];
                                $ret = "SELECT * FROM `reservations` WHERE client_phone = ?";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->bind_param('s', $phone);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($reserv = $res->fetch_object()) { ?>
                                    <tr>
                                        <td><?php echo $reserv->client_name; ?></td>
                                        <td><?php echo $reserv->client_phone; ?></td>
                                        <td><?php echo $reserv->car_regno; ?></td>
                                        <td><?php echo $reserv->lot_number; ?></td>
                                        <td>₹ <?php echo $reserv->amt; ?></td>
                                        <td><?php echo $reserv->parking_duration; ?> Hours</td>
                                        <td><?php echo $reserv->parking_date; ?></td>
                                        <td>
                                            <!-- Actions -->
                                            <?php if ($reserv->status == 'Paid') { ?>
                                                <a href='#receipt-<?php echo $reserv->id; ?>' data-toggle='modal' class='badge bg-success'>Receipt</a>
                                            <?php } ?>
                                            <a href="#update-<?php echo $reserv->id; ?>" data-toggle="modal" class="badge bg-warning">Update</a>
                                            <a href="reservations.php?delete=<?php echo $reserv->id; ?>" class="badge bg-danger">Delete</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- end row -->
        </div> <!-- container -->
        <!-- Footer -->
        <?php require_once("../partials/footer.php"); ?>
        <!-- End Footer -->

    </div>
    <!-- End wrapper -->
    <?php require_once("../partials/scripts.php"); ?>

</body>

</html>