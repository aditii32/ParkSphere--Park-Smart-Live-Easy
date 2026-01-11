<?php
session_start();
if (!isset($mysqli)) {
    include('../config/config.php'); // Include database configuration
}
require_once('../config/checklogin.php');
client();
include('../config/codeGen.php');

if (!isset($_SESSION['id']) || !isset($_SESSION['phone'])) {
    die("Error: User is not logged in.");
}

/* Add Notification Function */
function addNotification($userId, $title, $message, $type, $mysqli) {
    $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("SQL Error: " . $mysqli->error);
    }
    $stmt->bind_param('ssss', $userId, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

/* Pay Reservations */
if (isset($_POST['pay_reservations'])) {
    $error = 0;

    // Validate inputs
    $id = isset($_POST['id']) ? mysqli_real_escape_string($mysqli, trim($_POST['id'])) : $error = 1;
    $code = isset($_POST['code']) ? mysqli_real_escape_string($mysqli, trim($_POST['code'])) : $error = 1;
    $client_name = isset($_POST['client_name']) ? mysqli_real_escape_string($mysqli, trim($_POST['client_name'])) : $error = 1;
    $client_phone = isset($_POST['client_phone']) ? mysqli_real_escape_string($mysqli, trim($_POST['client_phone'])) : $error = 1;
    $r_id = isset($_POST['r_id']) ? mysqli_real_escape_string($mysqli, trim($_POST['r_id'])) : $error = 1;
    $amt = isset($_POST['amt']) ? mysqli_real_escape_string($mysqli, trim($_POST['amt'])) : $error = 1;
    $status = isset($_POST['status']) ? mysqli_real_escape_string($mysqli, trim($_POST['status'])) : $error = 1;

    if ($error) {
        $err = 'All fields are required.';
    } else {
        // Prevent double entries
        $sql = "SELECT * FROM payments WHERE code = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            die("SQL Error: " . $mysqli->error);
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $err = "Payment with that code already exists.";
        } else {
            // Insert payment and update reservation status
            $query = 'INSERT INTO payments (id, code, client_name, client_phone, amt, r_id) VALUES (?, ?, ?, ?, ?, ?)';
            $reservationqry = "UPDATE reservations SET status = 'Paid' WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            $reservationstmt = $mysqli->prepare($reservationqry);

            if (!$stmt || !$reservationstmt) {
                die("SQL Error: " . $mysqli->error);
            }

            $stmt->bind_param('ssssss', $id, $code, $client_name, $client_phone, $amt, $r_id);
            $reservationstmt->bind_param('s', $r_id);

            $stmt->execute();
            $reservationstmt->execute();

            if ($stmt->affected_rows > 0 && $reservationstmt->affected_rows > 0) {
                // Add notification for successful payment
                addNotification($_SESSION['id'], 'Payment Successful', 'Your payment has been successfully processed.', 'payment', $mysqli);
                $success = 'Payment added successfully.';
                header('refresh:1; url=add_payment.php');
            } else {
                // Add notification for failed payment
                addNotification($_SESSION['id'], 'Payment Failed', 'Your payment attempt failed. Please try again.', 'payment', $mysqli);
                $err = 'Failed to process payment. Please try again.';
            }
        }
    }
}

require_once("../partials/head.php");
?>

<body>

    <!-- Navigation Bar -->
    <?php require_once('../partials/client_nav.php'); ?>
    <!-- End Navigation Bar -->

    <div class="wrapper">
        <div class="container">

            <!-- Page-Title -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <h4 class="page-title">My Unpaid Reservations</h4>
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
                                    <th>Code</th>
                                    <th>Client Name</th>
                                    <th>Phone No</th>
                                    <th>Car Regno</th>
                                    <th>Lot No</th>
                                    <th>Fee</th>
                                    <th>Parking Duration</th>
                                    <th>Date Reserved</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $phone = $_SESSION['phone'];
                                $ret = "SELECT * FROM reservations WHERE status != 'Paid' AND client_phone = ?";
                                $stmt = $mysqli->prepare($ret);
                                if (!$stmt) {
                                    die("SQL Error: " . $mysqli->error);
                                }
                                $stmt->bind_param('s', $phone);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($reserv = $res->fetch_object()) { ?>
                                    <tr>
                                        <td><?php echo $reserv->code; ?></td>
                                        <td><?php echo $reserv->client_name; ?></td>
                                        <td><?php echo $reserv->client_phone; ?></td>
                                        <td><?php echo $reserv->car_regno; ?></td>
                                        <td><?php echo $reserv->lot_number; ?></td>
                                        <td>₹ <?php echo $reserv->amt; ?></td>
                                        <td><?php echo $reserv->parking_duration; ?> Hours</td>
                                        <td><?php echo $reserv->parking_date; ?></td>
                                        <td>
                                            <a href="#pay-<?php echo $reserv->id; ?>" data-toggle="modal" class="badge bg-warning">Add Payment</a>
                                            <!-- Payment Modal -->
                                            <div class="modal fade" id="pay-<?php echo $reserv->id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">Pay <?php echo $reserv->client_name ?> Reservation</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="post">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <input type="hidden" name="id" value="<?php echo uniqid(); ?>" class="form-control">
                                                                        <input type="hidden" name="r_id" value="<?php echo $reserv->id; ?>" class="form-control">
                                                                        <input type="hidden" name="status" value="Paid" class="form-control">
                                                                        <div class="form-group col-md-6">
                                                                            <label for="">Client Phone Number</label>
                                                                            <input type="text" value="<?php echo $reserv->client_phone; ?>" name="client_phone" class="form-control" readonly>
                                                                        </div>
                                                                        <div class="form-group col-md-6">
                                                                            <label for="">Client Name</label>
                                                                            <input type="text" value="<?php echo $reserv->client_name; ?>" name="client_name" class="form-control" readonly>
                                                                        </div>
                                                                        <div class="form-group col-md-6">
                                                                            <label for="">Parking Fee</label>
                                                                            <input type="text" name="amt" value="<?php echo $reserv->amt; ?>" class="form-control" readonly>
                                                                        </div>
                                                                        <div class="form-group col-md-6">
                                                                            <label for="">Payment Code</label>
                                                                            <input type="text" name="code" value="<?php echo uniqid(); ?>" class="form-control" readonly>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <button type="submit" name="pay_reservations" class="btn btn-primary">Submit</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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