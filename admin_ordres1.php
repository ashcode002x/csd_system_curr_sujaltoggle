<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

session_start();

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Sorry, Connection with database is not built " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

// Handle form submission to update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $order_id = $_POST['order_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("UPDATE order_details SET quantity = ? WHERE item_id = ? AND order_id = ?");
    $stmt->bind_param("dii", $quantity, $item_id, $order_id);

    if ($stmt->execute()) {
        $success_message = 'Quantity updated successfully';
    } else {
        $error_message = 'Failed to update quantity';
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['delete_item'])) {
        $item_id = $_GET['delete_item'];

        $delete_stmt = $conn->prepare("DELETE FROM order_details WHERE item_id = ?");
        $delete_stmt->bind_param("i", $item_id);

        if ($delete_stmt->execute()) {
            header('Location: admin_orders.php');
            exit();
        } else {
            echo 'Failed to delete item';
        }

        $delete_stmt->close();
    }

    if (isset($_GET['approve_order'])) {
        $order_id = $_GET['approve_order'];

        $approve_stmt = $conn->prepare("UPDATE orders SET status = 2, date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $approve1_stmt = $conn->prepare("UPDATE order_details SET date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $approve_stmt->bind_param("i", $order_id);
        $approve1_stmt->bind_param("i", $order_id);

        $approve1_stmt->execute();

        if ($approve_stmt->execute()) {
            header('Location: admin_orders.php');
            exit();
        } else {
            echo 'Failed to approve order';
        }

        $approve_stmt->close();
    }

    if (isset($_GET['reject_order'])) {
        $order_id = $_GET['reject_order'];

        $reject_stmt = $conn->prepare("UPDATE orders SET status = 0, date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $reject1_stmt = $conn->prepare("UPDATE order_details SET date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $reject_stmt->bind_param("i", $order_id);
        $reject1_stmt->bind_param("i", $order_id);

        $reject1_stmt->execute();

        if ($reject_stmt->execute()) {
            header('Location: admin_orders.php');
            exit();
        } else {
            echo 'Failed to reject order';
        }

        $reject_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="all.min.css">
    <style>
        body {
            background-color: #e6f7ff; /* Light blue background color */
            font-family: Arial, sans-serif;
        }

        .section-title {
            margin-top: 20px;
            color: #2c3e50; /* Darker shade for heading */
            font-weight: bold;
        }

        .table-container {
            margin-top: 20px;
            background-color: #ffffff; /* White background for table */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-orders {
            text-align: center;
            font-size: 1.2rem;
            color: #95a5a6;
            margin-top: 20px;
        }

        .total-price {
            font-weight: bold;
        }

        h4 {
            color: #3498db; /* Bright blue color for Order ID heading */
            margin-bottom: 10px;
        }

        .table thead th {
            background-color: #ecf0f1; /* Light grey background for table header */
            color: #2c3e50; /* Dark text color for table header */
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9; /* Very light grey for zebra striping */
        }

        .table tbody tr:hover {
            background-color: #e0f7fa; /* Light cyan hover effect */
        }

        .btn-primary {
            background-color: #3498db; /* Bright blue for primary button */
            border-color: #3498db;
        }

        .btn-primary:hover {
            background-color: #2980b9; /* Darker blue for hover effect */
            border-color: #2980b9;
        }

        .btn-action {
            margin: 0 5px;
        }

        td.d-flex {
            border: none; /* Remove any border around the td */
            outline: none; /* Remove any outline */
            box-shadow: none; /* Remove any shadow */
            gap: 4px;
            padding: 0; /* Remove any default padding */
            margin: 0; /* Remove any default margin */
        }

        /* Remove any border, outline, or shadow from buttons */
        td.d-flex button {
            border: none; /* Remove border from buttons */
            outline: none; /* Remove outline from buttons */
            box-shadow: none; /* Remove any shadow from buttons */
            margin: 0; /* Ensure no extra margin around buttons */
        }
    </style>
</head>
<body>

    <!-- navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <!-- Current Orders Section -->
        <h2 class="section-title">Current Orders</h2>
        <div class="table-container">
            <?php
            if (isset($success_message)) {
                echo "<div class='alert alert-success'>$success_message</div>";
            }
            if (isset($error_message)) {
                echo "<div class='alert alert-danger'>$error_message</div>";
            }

            $query = "SELECT * FROM orders WHERE status = 1";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) == 0) {
                echo "<div class='no-orders'>No current orders.</div>";
            } else {
                while ($order = mysqli_fetch_assoc($result)) {
                    $order_id = $order['order_id'];
                    echo "<h4>Order ID: $order_id</h4>";
                    echo "<table class='table table-bordered' data-order-id='$order_id'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>Sno.</th>";
                    echo "<th>Item ID</th>";
                    echo "<th>Item Name</th>";
                    echo "<th>Category</th>";
                    echo "<th>Description</th>";
                    echo "<th>Quantity</th>";
                    echo "<th>Price</th>";
                    echo "<th>Unit</th>";
                    echo "<th>Remarks</th>";
                    echo "<th>Date and Time</th>"; 
                    echo "<th>Actions</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                    $item_query = "SELECT od.*, i.category, i.description, i.Unit as unit, i.Remarks as remarks, i.stock_quantity, od.date_and_time 
                                FROM order_details od 
                                JOIN items i ON od.item_id = i.itemId 
                                WHERE od.order_id = $order_id";
                    $item_result = mysqli_query($conn, $item_query);
                    $serial_number = 1;
                    $total_price = 0;

                    while ($item = mysqli_fetch_assoc($item_result)) {
                        $item_id = $item['item_id'];
                        $item_name = $item['item_name'];
                        $category = $item['category'];
                        $description = $item['description'];
                        $quantity = $item['quantity'];
                        $unit = $item['unit'];
                        $price = $item['price'];
                        $remarks = $item['remarks'];
                        $date_and_time = $item['date_and_time'];
                        $stock_quantity = $item['stock_quantity'];
                        $total_price += $price * $quantity;

                        echo "<tr id='item-$item_id'>";
                        echo "<td>$serial_number</td>";
                        echo "<td>$item_id</td>";
                        echo "<td>$item_name</td>";
                        echo "<td>$category</td>";
                        echo "<td>$description</td>";
                        echo "<td class='item-quantity'>$quantity</td>";
                        echo "<td class='item-price'>" . number_format($price, 2) . "</td>";
                        echo "<td>$unit</td>";
                        echo "<td>$remarks</td>";
                        echo "<td>$date_and_time</td>";
                        echo "<td class='d-flex mt-2 gap-2'>
                                <a href='admin_orders.php?approve_order=$order_id' class='btn btn-primary btn-action'>Approve</a>
                                <a href='admin_orders.php?reject_order=$order_id' class='btn btn-primary btn-action'>Reject</a>
                            </td>";
                        echo "</tr>";

                        $serial_number++;
                    }

                    echo "<tr>";
                    echo "<td colspan='6' class='total-price'>Total Price:</td>";
                    echo "<td colspan='5' class='total-price'>" . number_format($total_price, 2) . "</td>";
                    echo "</tr>";

                    echo "</tbody>";
                    echo "</table>";
                }
            }
            ?>
        </div>

        <!-- Past Orders Section -->
        <h2 class="section-title">Past Orders</h2>
        <div class="table-container">
            <?php
            $query = "SELECT * FROM orders WHERE status IN (2, 0)";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) == 0) {
                echo "<div class='no-orders'>No past orders.</div>";
            } else {
                while ($order = mysqli_fetch_assoc($result)) {
                    $order_id = $order['order_id'];
                    $status = $order['status'];
                    $status_text = $status == 2 ? 'Approved' : 'Rejected'; // Convert status to text for display
                    $status_color = $status == 2 ? 'green' : 'red'; // Define color based on status

                    echo "<h4>Order ID: $order_id</h4>";
                    echo "<table class='table table-bordered' data-order-id='$order_id'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>Sno.</th>";
                    echo "<th>Item ID</th>";
                    echo "<th>Item Name</th>";
                    echo "<th>Category</th>";
                    echo "<th>Description</th>";
                    echo "<th>Quantity</th>";
                    echo "<th>Price</th>";
                    echo "<th>Unit</th>";
                    echo "<th>Remarks</th>";
                    echo "<th>Date and Time</th>"; 
                    echo "<th>Status</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                    $item_query = "SELECT od.*, i.category, i.description, i.Unit as unit, i.Remarks as remarks, od.date_and_time 
                                FROM order_details od 
                                JOIN items i ON od.item_id = i.itemId 
                                WHERE od.order_id = $order_id";
                    $item_result = mysqli_query($conn, $item_query);
                    $serial_number = 1;
                    $total_price = 0;

                    while ($item = mysqli_fetch_assoc($item_result)) {
                        $item_id = $item['item_id'];
                        $item_name = $item['item_name'];
                        $category = $item['category'];
                        $description = $item['description'];
                        $quantity = $item['quantity'];
                        $unit = $item['unit'];
                        $price = $item['price'];
                        $remarks = $item['remarks'];
                        $date_and_time = $item['date_and_time'];
                        $total_price += $price * $quantity;

                        echo "<tr>";
                        echo "<td>$serial_number</td>";
                        echo "<td>$item_id</td>";
                        echo "<td>$item_name</td>";
                        echo "<td>$category</td>";
                        echo "<td>$description</td>";
                        echo "<td>$quantity</td>";
                        echo "<td>" . number_format($price, 2) . "</td>";
                        echo "<td>$unit</td>";
                        echo "<td>$remarks</td>";
                        echo "<td>$date_and_time</td>";
                        echo "<td style='color: $status_color;'>$status_text</td>"; // Status color
                        echo "</tr>";

                        $serial_number++;
                    }

                    echo "<tr>";
                    echo "<td colspan='6' class='total-price'>Total Price:</td>";
                    echo "<td colspan='5' class='total-price'>" . number_format($total_price, 2) . "</td>";
                    echo "</tr>";

                    echo "</tbody>";
                    echo "</table>";
                }
            }
            ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="bootstrap.bundle.min.js"></script>
</body>
</html>
