<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get cart items with product details
$sql = "SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

$subtotal = 0;
$items = [];
while ($item = $cart_items->fetch_assoc()) {
    $subtotal += $item['price'] * $item['quantity'];
    $items[] = $item;
}

// Calculate shipping cost (free for orders over $50)
$shipping_cost = ($subtotal >= 50) ? 0 : 10;
$total = $subtotal + $shipping_cost;

// Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = sanitize($_POST['shipping_address']);
    $shipping_city = sanitize($_POST['shipping_city']);
    $shipping_state = sanitize($_POST['shipping_state']);
    $shipping_zip = sanitize($_POST['shipping_zip']);
    $phone = sanitize($_POST['phone']);
    
    if (empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || empty($shipping_zip) || empty($phone)) {
        $error = "All fields are required!";
    } else {
        // Save shipping details in session
        $_SESSION['shipping_details'] = [
            'address' => $shipping_address,
            'city' => $shipping_city,
            'state' => $shipping_state,
            'zip' => $shipping_zip,
            'phone' => $phone
        ];
        
        // Check if all items are still in stock
        $all_in_stock = true;
        foreach ($items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $all_in_stock = false;
                $error = "Sorry, {$item['name']} is no longer available in the requested quantity.";
                break;
            }
        }
        
        if ($all_in_stock) {
            header("Location: payment.php");
            exit();
        }
    }
}

include 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Order Summary -->
        <div class="col-md-4 order-md-2 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-primary">Order Summary</span>
                        <span class="badge bg-primary rounded-pill"><?php echo count($items); ?></span>
                    </h4>
                    <ul class="list-group mb-3">
                        <?php foreach ($items as $item): ?>
                            <li class="list-group-item d-flex justify-content-between lh-sm">
                                <div>
                                    <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                </div>
                                <span class="text-muted"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                            </li>
                        <?php endforeach; ?>
                        
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subtotal</span>
                            <strong><?php echo formatPrice($subtotal); ?></strong>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Shipping</span>
                            <strong><?php echo $shipping_cost > 0 ? formatPrice($shipping_cost) : 'FREE'; ?></strong>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between bg-light">
                            <span class="text-success">
                                <h6 class="my-0">Total (USD)</h6>
                            </span>
                            <strong class="text-success"><?php echo formatPrice($total); ?></strong>
                        </li>
                    </ul>
                    
                    <?php if ($shipping_cost > 0): ?>
                        <div class="alert alert-info small">
                            Add <?php echo formatPrice(50 - $subtotal); ?> more to get free shipping!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <div class="col-md-8 order-md-1">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Shipping Details</h4>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form class="needs-validation" method="POST" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="shipping_address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="shipping_address" name="shipping_address" 
                                       value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="shipping_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="shipping_city" name="shipping_city" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="shipping_state" class="form-label">State</label>
                                <input type="text" class="form-control" id="shipping_state" name="shipping_state" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="shipping_zip" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" required>
                            </div>
                            
                            <div class="col-12">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="save-info">
                            <label class="form-check-label" for="save-info">Save this information for next time</label>
                        </div>
                        
                        <button class="btn btn-primary btn-lg w-100" type="submit">
                            Continue to Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Save info checkbox handler
document.getElementById('save-info').addEventListener('change', function(e) {
    if (e.target.checked) {
        localStorage.setItem('save_shipping_info', 'true');
    } else {
        localStorage.removeItem('save_shipping_info');
    }
});

// Load saved info
if (localStorage.getItem('save_shipping_info') === 'true') {
    document.getElementById('save-info').checked = true;
}
</script>

<?php 
$conn->close();
include 'footer.php'; 
?>
