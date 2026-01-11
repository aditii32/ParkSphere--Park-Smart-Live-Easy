<?php
session_start();
include('../config/config.php');
require_once('../config/checklogin.php');
client();
include('../config/codeGen.php');
require_once('../config/notifications.php'); // Ensure the notifications helper is included

/* Add Reservations */
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
            require_once('../config/notifications.php');
            addNotification($_SESSION['id'], 'Reservation Confirmed', 'Parking slot booked successfully.', 'booking', $mysqli);

            $success = "Reservation Added Successfully";
        } else {
            $err = "Failed, Please Try Again";
        }
    } else {
        $err = 'All fields are required.';
    }
}

require_once("../partials/head.php");
?>

<body>

    <!-- Navigation Bar-->
    <?php
    require_once('../partials/client_nav.php');
    $id  = $_SESSION['id'];
    $ret = "SELECT * FROM clients WHERE id = ?";
    $stmt = $mysqli->prepare($ret);
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($client = $res->fetch_object()) {
    ?>
        <!-- End Navigation Bar-->

        <div class="wrapper">
            <div class="container">

                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Parking Lots</h4>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row">
                    <div class="col-12">
                        <div class="card-box">
                            <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Code Number</th>
                                        <th>Parking Lot Location</th>
                                        <th>Parking Slots</th>
                                        <th>Price Per Slot</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    $ret = 'SELECT * FROM parking_lots';
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    while ($parking = $res->fetch_object()) { ?>
                                        <tr>
                                            <td><?php echo $parking->code; ?></td>
                                            <td><?php echo $parking->location; ?></td>
                                            <td><?php echo $parking->parking_slots; ?></td>
                                            <td>₹ <?php echo $parking->price_per_slot; ?></td>
                                            <td>
                                                <a href="#reserve-<?php echo $parking->id; ?>" data-toggle="modal" class="badge bg-warning">Reserve Parking Lot</a>
                                                <!-- Reserve Modal -->
                                                <div class="modal fade" id="reserve-<?php echo $parking->id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel">Reserve A Parking Slot On <?php echo $parking->code; ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="post" enctype="multipart/form-data">
                                                                    <div class="card-body">
                                                                        <div class="row">
                                                                            <input type="hidden" required name="id" value="<?php echo uniqid(); ?>" class="form-control">
                                                                            <input type="hidden" required name="code" value="<?php echo $parking->code; ?>" class="form-control">
                                                                            <input type="hidden" required name="lot_number" value="<?php echo $parking->code; ?>" class="form-control">
                                                                            <input type="hidden" required name="amt" value="<?php echo $parking->price_per_slot; ?>" class="form-control">

                                                                            <div class="form-group col-md-6">
                                                                                <label for="">Client Name</label>
                                                                                <input type="text" required name="client_name" value="<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : ''; ?>" class="form-control">
                                                                            </div>
                                                                            <div class="form-group col-md-6">
                                                                                <label for="">Client Phone Number</label>
                                                                                <input type="text" required name="client_phone" value="<?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : ''; ?>" class="form-control">
                                                                            </div>
                                                                            <div class="form-group col-md-6">
                                                                                <label for="">Client Car Reg Number</label>
                                                                                <input type="text" required name="car_regno" class="form-control">
                                                                            </div>
                                                                            <div class="form-group col-md-6">
                                                                                <label for="">Parking Duration (Hours)</label>
                                                                                <input type="text" required name="parking_duration" class="form-control">
                                                                            </div>
                                                                            <div class="form-group col-md-12">
                                                                                <label for="">Parking Date And Time</label>
                                                                                <input type="text" value="<?php echo date('d M Y g:ia'); ?>" required name="parking_date" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="text-right">
                                                                            <button type="submit" name="add_reservation" class="btn btn-primary">Submit</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
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
    <?php require_once("../partials/scripts.php");
    } ?>

</body>

</html>