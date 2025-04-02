<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get cart items
$sql = "SELECT c.id, c.quantity, p.* 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;

include 'header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Shopping Cart</h2>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php while ($item = $result->fetch_assoc()): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                            <div class="cart-item mb-3 pb-3 border-bottom animate__animated animate__fadeIn">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-0"><?php echo formatPrice($item['price']); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary" 
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                            <input type="number" class="form-control text-center" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                   onchange="updateQuantity(<?php echo $item['id']; ?>, 'set', this.value)">
                                            <button class="btn btn-outline-secondary" 
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <p class="mb-0"><?php echo formatPrice($subtotal); ?></p>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button class="btn btn-link text-danger" 
                                                onclick="removeItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong><?php echo formatPrice($total); ?></strong>
                        </div>
                        <a href="checkout.php" class="btn btn-primary w-100">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Looks like you haven't added any items to your cart yet.</p>
            <a href="products.php" class="btn btn-primary mt-3">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(cartId, action, value = null) {
    let data = {
        cart_id: cartId,
        action: action
    };
    
    if (value !== null) {
        data.value = value;
    }
    
    $.post('ajax/update_cart.php', data, function(response) {
        if (response.success) {
            location.reload();
        }
    });
}

function removeItem(cartId) {
    if (confirm('Are you sure you want to remove this item?')) {
        $.post('ajax/remove_from_cart.php', {
            cart_id: cartId
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    }
}
</script>

<?php 
$conn->close();
include 'footer.php'; 
?>
