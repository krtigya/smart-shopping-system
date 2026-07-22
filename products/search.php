<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "ecommerce";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($query) {
    $escaped_query = $conn->real_escape_string($query);
    
    $sql = "SELECT p.id, p.name, p.price, p.description, p.image 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            JOIN brands b ON p.brand_id = b.id 
            WHERE p.name LIKE '%$escaped_query%' 
            OR c.name LIKE '%$escaped_query%' 
            OR b.name LIKE '%$escaped_query%'";

    $productsResult = $conn->query($sql);

    if ($productsResult->num_rows > 0) {
        while ($product = $productsResult->fetch_assoc()) {
            echo '<div class="product-card">';
            echo '<img src="' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['name']) . '">';
            echo '<h3>' . htmlspecialchars($product['name']) . '</h3>';
            echo '<p>' . htmlspecialchars($product['description']) . '</p>';
            echo '<div class="price">$' . htmlspecialchars($product['price']) . '</div>';
            echo '<button>Add to Cart</button>';
            echo '</div>';
        }
    } else {
        echo '<p>No products found matching your criteria.</p>';
    }
} else {
    echo '<p>Invalid search query.</p>';
}

$conn->close();
?>
