<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

// Establish database connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Pagination variables
$results_per_page = 10; // Number of items per page

// Determine current page number
if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

// Calculate SQL LIMIT starting row number for the pagination formula
$start_limit = ($page - 1) * $results_per_page;

// Search functionality
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Filter functionality
$category_filter = "";
if (isset($_GET['category_filter'])) {
    $category_filter = $_GET['category_filter'];
}

// Fetch items with pagination, search, and filter
$sql = "SELECT * FROM items WHERE (name LIKE '%$search%' OR itemId LIKE '%$search%' OR category LIKE '%$search%' OR description LIKE '%$search%' OR price LIKE '%$search%' OR stock_quantity LIKE '%$search%' OR Unit LIKE '%$search%' OR Remarks LIKE '%$search%')";
if (!empty($category_filter)) {
    $sql .= " AND category = '$category_filter'";
}
$sql .= " LIMIT $start_limit, $results_per_page";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        ?>
        <div class="card">
            <img src="<?php echo 'items_image/' . $row['item_image']; ?>" alt="<?php echo $row['name']; ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo $row['name']; ?></h5>
                <div class="card-text">
                    <span><strong>ID:</strong> <?php echo $row['itemId']; ?></span>
                    <span style="flex-grow: 1;"></span> <!-- Spacer -->
                    <span><strong>Category:</strong> <?php echo $row['category']; ?></span>
                </div>
                <div class="card-text">
                    <span><strong>Description:</strong> <?php echo $row['description']; ?></span>
                </div>
                <div class="card-text">
                    <span><strong>Price:</strong> Rs <?php echo number_format($row['price'], 2); ?></span>
                    <span style="flex-grow: 1;"></span> <!-- Spacer -->
                    <span><strong>Stock:</strong> <?php echo $row['stock_quantity']; ?></span>
                </div>
                <div class="card-text">
                    <span><strong>Unit</strong> <?php echo $row['Unit']; ?></span>
                </div>
            </div>
            <div class="card-footer">
                <form action="cartpage.php" method="POST" class="d-flex align-items-center">
                    <input type="hidden" name="itemId" value="<?php echo $row['itemId']; ?>">
                    <input type="hidden" name="name" value="<?php echo $row['name']; ?>">
                    <input type="hidden" name="category" value="<?php echo $row['category']; ?>">
                    <input type="hidden" name="description" value="<?php echo $row['description']; ?>">
                    <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                    <input type="hidden" name="stock_quantity" value="<?php echo $row['stock_quantity']; ?>">
                    <input type="hidden" name="remarks" value="<?php echo $row['Remarks']; ?>">
                    <input type="hidden" name="unit" value="<?php echo $row['Unit']; ?>">
                    <div class="select-quantity">
                        <input type="number" name="selected_quantity" min="1" step="<?php echo ($row['Unit'] == 'Packets') ? '1' : '1'; ?>" max="<?php echo $row['stock_quantity']; ?>" value="0">
                        <button type="submit" name="Add_To_Cart" class="btn btn-outline-primary" style="padding: 0.2rem 0.5rem; font-size: 0.8em;">Add To Cart</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    // Free result set
    mysqli_free_result($result);

    // Pagination links
    $sql_pagination = "SELECT COUNT(*) AS total FROM items WHERE (name LIKE '%$search%' OR itemId LIKE '%$search%' OR category LIKE '%$search%' OR description LIKE '%$search%' OR price LIKE '%$search%' OR stock_quantity LIKE '%$search%')";
    if (!empty($category_filter)) {
        $sql_pagination .= " AND category = '$category_filter'";
    }

    $result_pagination = mysqli_query($conn, $sql_pagination);
    $row_pagination = mysqli_fetch_assoc($result_pagination);
    $total_pages = ceil($row_pagination['total'] / $results_per_page);

    // Display pagination controls if there's more than one page
    if ($total_pages > 1) {
        echo '<div class="d-flex justify-content-center mt-4">';
        echo '<ul class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . $search . '&category_filter=' . $category_filter . '">' . $i . '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
    }
} else {
    echo "<p>No items found.</p>";
}
?>
