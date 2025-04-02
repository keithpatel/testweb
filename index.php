<?php
require_once 'config.php';

$conn = getDBConnection();

// Get featured products (newest products)
$featured_products = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");

// Get all categories with their product counts
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY product_count DESC
");

include 'header.php';
?>

<!-- Hero Section -->
<div class="hero-section position-relative mb-5">
    <div class="hero-image position-relative" style="height: 500px; background: linear-gradient(135deg, #3498db, #2ecc71); overflow: hidden;">
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-md-6 text-white animate__animated animate__fadeInLeft">
                    <h1 class="display-4 fw-bold mb-4">Welcome to ShopEasy</h1>
                    <p class="lead mb-4">Discover amazing products at great prices. Shop now and enjoy exclusive deals!</p>
                    <a href="products.php" class="btn btn-light btn-lg">Start Shopping</a>
                </div>
                <div class="col-md-6 d-none d-md-block animate__animated animate__fadeInRight">
                    <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                         class="img-fluid rounded shadow-lg" alt="Shopping">
                </div>
            </div>
        </div>
        
        <!-- Wave shape at bottom -->
        <svg class="position-absolute bottom-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
            <path fill="#ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>
</div>

<!-- Featured Products Section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Featured Products</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php while ($product = $featured_products->fetch_assoc()): ?>
            <div class="col animate__animated animate__fadeIn">
                <div class="card h-100 shadow-sm">
                    <div class="position-relative">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-primary">New</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
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

<!-- Categories Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Shop by Category</h2>
        <div class="row row-cols-1 row-cols-md-4 g-4">
            <?php while ($category = $categories->fetch_assoc()): ?>
                <div class="col animate__animated animate__fadeIn">
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card h-100 shadow-sm hover-zoom">
                            <div class="card-body text-center">
                                <i class="fas fa-<?php 
                                    switch($category['name']) {
                                        case 'Electronics': echo 'laptop'; break;
                                        case 'Clothing': echo 'tshirt'; break;
                                        case 'Books': echo 'book'; break;
                                        case 'Home & Living': echo 'home'; break;
                                        default: echo 'shopping-bag';
                                    }
                                ?> fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title text-dark"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <p class="text-muted mb-0"><?php echo $category['product_count']; ?> Products</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container py-5">
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <div class="col animate__animated animate__fadeIn">
            <div class="text-center">
                <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                <h4>Free Shipping</h4>
                <p class="text-muted">On orders over $50</p>
            </div>
        </div>
        <div class="col animate__animated animate__fadeIn">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                <h4>Secure Payment</h4>
                <p class="text-muted">100% secure payment</p>
            </div>
        </div>
        <div class="col animate__animated animate__fadeIn">
            <div class="text-center">
                <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                <h4>24/7 Support</h4>
                <p class="text-muted">Dedicated support</p>
            </div>
        </div>
    </div>
</div>

<!-- Newsletter Section -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h3 class="mb-4">Subscribe to Our Newsletter</h3>
                <form class="row g-3 justify-content-center">
                    <div class="col-auto">
                        <input type="email" class="form-control" placeholder="Enter your email">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-light">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.hover-zoom {
    transition: transform 0.3s;
}
.hover-zoom:hover {
    transform: scale(1.05);
}
</style>

<script>
function addToCart(productId, button) {
    <?php if (!isLoggedIn()): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>
    
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
