<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

session_start();

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$updateSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['Add_To_Cart'])) {
        if (isset($_SESSION['cart'])) {
            $item_array_id = array_column($_SESSION['cart'], "itemId");
            if (in_array($_POST['itemId'], $item_array_id)) {
                echo "<script>alert('Item is already added in the cart!')</script>";
                echo "<script>window.location = 'user_dashboard.php'</script>";
            } else {
                $count = count($_SESSION['cart']);
                $_SESSION['cart'][$count] = array(
                    'itemId' => $_POST['itemId'],
                    'name' => $_POST['name'],
                    'category' => $_POST['category'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'selected_quantity' => $_POST['selected_quantity']
                );

                echo "<script>alert('Item is Successfully added in the cart!')</script>";
                echo "<script>window.location = 'user_dashboard.php'</script>";
            }
        } else {
            $_SESSION['cart'][0] = array(
                'itemId' => $_POST['itemId'],
                'name' => $_POST['name'],
                'category' => $_POST['category'],
                'description' => $_POST['description'],
                'price' => $_POST['price'],
                'selected_quantity' => $_POST['selected_quantity']
            );

            echo "<script>alert('Item is Successfully added in the cart!')</script>";
            echo "<script>window.location = 'user_dashboard.php'</script>";
        }
    }

    if (isset($_POST['Remove_Item'])) {
        foreach ($_SESSION['cart'] as $key => $value) {
            if ($value['itemId'] == $_POST['itemId']) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                echo "<script>alert('Item is Successfully Removed from the cart!')</script>";
                echo "<script>window.location = 'cartpage.php'</script>";
            }
        }
    }

    if (isset($_POST['Update_Item'])) {
        $editItemId = $_POST['editItemId'];
        $newQuantity = $_POST['selected_quantity'];

        $stmt = $conn->prepare("SELECT stock_quantity FROM items WHERE itemId = ?");
        $stmt->bind_param("i", $editItemId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stockQuantity = $row['stock_quantity'];

            if ($newQuantity > $stockQuantity) {
                $newQuantity = $stockQuantity;
                echo "<script>alert('Selected quantity exceeds available stock. Updated to maximum available.')</script>";
            }

            foreach ($_SESSION['cart'] as $key => $value) {
                if ($value['itemId'] == $editItemId) {
                    $_SESSION['cart'][$key]['selected_quantity'] = $newQuantity;
                    $updateSuccess = true;
                    break;
                }
            }
        } else {
            die("Item with ID " . $editItemId . " not found in database.");
        }

        $stmt->close();
        header("Location: cartpage.php");
        exit;
    }

    if (isset($_POST['Make_Purchase'])) {
        $total = 0;

        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $value) {
                $total += $value['price'] * $value['selected_quantity'];
            }

            if ($total == 0) {
                echo "<script>alert('No items in the cart!')</script>";
                echo "<script>window.location = 'cartpage.php'</script>";
            } else {
                $order_id = 0;
                do {
                    $order_id = rand(100000, 999999);
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } while ($result->num_rows > 0);

                $stmt->close();

                $conn->begin_transaction();

                try {
                    $user_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_id, status) VALUES (?, ?, ?)");
                    $status = 1; // assuming status 1 means 'pending'
                    $stmt->bind_param("iii", $user_id, $order_id, $status);
                    $stmt->execute();

                    foreach ($_SESSION['cart'] as $value) {
                        $stmt = $conn->prepare("INSERT INTO order_details (order_id, item_id, item_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisii", $order_id, $value['itemId'], $value['name'], $value['selected_quantity'], $value['price']);
                        $stmt->execute();
                    }

                    $conn->commit();
                    unset($_SESSION['cart']);
                    echo "<script>alert('Purchase successful! Order ID: $order_id')</script>";
                    echo "<script>window.location = 'user_dashboard.php'</script>";
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<script>alert('Purchase failed: " . $e->getMessage() . "')</script>";
                }
            }
        } else {
            echo "<script>alert('No items in the cart!')</script>";
            echo "<script>window.location = 'cartpage.php'</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Page</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .header-row h1 {
            margin: 0;
            color: #343a40;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .btn-outline-primary {
            border-color: #007bff;
            color: #007bff;
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-outline-primary:hover {
            background-color: #007bff;
            color: #fff;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-back {
            margin-right: 10px;
        }

        .btn-print {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        .btn-orders {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
            transition: background-color 0.3s, border-color 0.3s, transform 0.3s;
        }

        .btn-orders:hover {
            background-color: #218838;
            border-color: #1e7e34;  
            transform: scale(1.05);
        }

        .btn-print:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .card-container .card {
            flex: 1 1 calc(25% - 20px);
            max-width: 280px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }

        .card-container .card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card-img-top {
            height: 50%;
            width: 50%;
            margin:auto;
            object-fit: cover;
            border-bottom: 1px solid #ddd;
        }

        .card-body {
            padding: 15px;
            text-align: center;
        }

        .card-title {
            font-size: 1.25rem;
            margin-bottom: 10px;
            color: #007bff;
        }

        .card-text {
            font-size: 1rem;
            color: #6c757d;
        }

        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        
        .modal-header {
            background-color: #007bff;
            color: white;
        }

        .modal-footer {
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-row">
            <h1>Cart Page</h1>
            <div>
                <a href="user_dashboard.php" class="btn btn-outline-primary btn-back">Back to Dashboard</a>
                <a href="print_order.php" class="btn btn-print">Print Order</a>
                <a href="order_history.php" class="btn btn-orders">Order History</a>
            </div>
        </div>
        <div class="card-container">
            <?php
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $value) {
                    echo '<div class="card">';
                    echo '<img src="path/to/image/' . $value['itemId'] . '.jpg" class="card-img-top" alt="Item Image">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . htmlspecialchars($value['name']) . '</h5>';
                    echo '<p class="card-text">Category: ' . htmlspecialchars($value['category']) . '</p>';
                    echo '<p class="card-text">Price: $' . htmlspecialchars($value['price']) . '</p>';
                    echo '<p class="card-text">Quantity: ' . htmlspecialchars($value['selected_quantity']) . '</p>';
                    echo '<p class="card-text">Total: $' . htmlspecialchars($value['price'] * $value['selected_quantity']) . '</p>';
                    echo '</div>';
                    echo '<div class="card-footer">';
                    echo '<form method="post" action="cartpage.php">';
                    echo '<input type="hidden" name="itemId" value="' . htmlspecialchars($value['itemId']) . '">';
                    echo '<button type="submit" name="Remove_Item" class="btn btn-outline-danger">Remove</button>';
                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No items in the cart!</p>';
            }
            ?>
        </div>
        <form method="post" action="cartpage.php" class="mt-3">
            <button type="submit" name="Make_Purchase" class="btn btn-primary">Make Purchase</button>
        </form>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Item Quantity</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="cartpage.php">
                    <div class="modal-body">
                        <input type="hidden" id="editItemId" name="editItemId">
                        <div class="form-group">
                            <label for="editQuantity">Quantity</label>
                            <input type="number" id="editQuantity" name="selected_quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="Update_Item" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="jquery.min.js"></script>
    <script src="bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            <?php if ($updateSuccess): ?>
                $('.container').prepend('<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">Item quantity updated successfully!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            <?php endif; ?>
            
            $('.edit-btn').on('click', function() {
                var itemId = $(this).data('itemid');
                var selectedQuantity = $(this).data('selectedquantity');
                var stockQuantity = $(this).data('stockquantity');

                $('#editItemId').val(itemId);
                $('#editQuantity').attr('max', stockQuantity); // Set max attribute dynamically
                $('#editQuantity').val(selectedQuantity);
                $('#editModal').modal('show');
            });
        });
    </script>
</body>
</html>
