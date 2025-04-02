<?php
require_once 'config.php';

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$conn = getDBConnection();

// Get categories
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");

// Get products with filters
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

if ($category_id) {
    $sql .= " AND p.category_id = " . $category_id;
}

if ($search) {
    $sql .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

$sql .= " ORDER BY p.created_at DESC";
$products_result = $conn->query($sql);

include 'header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar with categories -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Categories</h5>
                    <div class="list-group">
                        <a href="products.php" class="list-group-item list-group-item-action <?php echo !$category_id ? 'active' : ''; ?>">
                            All Products
                        </a>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <a href="products.php?category=<?php echo $category['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <!-- Search bar -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="d-flex">
                        <?php if ($category_id): ?>
                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
            
            <!-- Products grid -->
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <div class="col animate__animated animate__fadeIn">
                        <div class="card h-100 shadow-sm">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo formatPrice($product['price']); ?></h5>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button class="btn btn-primary add-to-cart" 
                                                onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<script>
function addToCart(productId, button) {
    $.post('ajax/add_to_cart.php', {
        product_id: productId,
        quantity: 1
    }, function(response) {
        if (response.success) {
            addToCartAnimation(button);
            updateCartCount();
            
            // Show success toast
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '11';
            toast.innerHTML = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            Product added to cart successfully!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            const toastEl = new bootstrap.Toast(toast.querySelector('.toast'));
            toastEl.show();
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    });
}
</script>

<?php 
$conn->close();
include 'footer.php'; 
?>
