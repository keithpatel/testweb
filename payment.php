<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get cart total
$sql = "SELECT SUM(c.quantity * p.price) as total 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['total'] ?? 0;

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simple payment processing simulation
    // In a real application, you would integrate with a payment gateway like Stripe or PayPal
    
    $card_number = sanitize($_POST['card_number']);
    $card_expiry = sanitize($_POST['card_expiry']);
    $card_cvv = sanitize($_POST['card_cvv']);
    $shipping_address = sanitize($_POST['shipping_address']);
    
    if (empty($card_number) || empty($card_expiry) || empty($card_cvv) || empty($shipping_address)) {
        $error = "All fields are required!";
    } else {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status, payment_status) VALUES (?, ?, ?, 'processing', 'completed')");
        $stmt->bind_param("ids", $user_id, $total, $shipping_address);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // Move items from cart to order_items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_time) 
                                  SELECT ?, c.product_id, c.quantity, p.price 
                                  FROM cart c 
                                  JOIN products p ON c.product_id = p.id 
                                  WHERE c.user_id = ?");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();
            
            // Update product stock
            $stmt = $conn->prepare("UPDATE products p 
                                  JOIN cart c ON p.id = c.product_id 
                                  SET p.stock_quantity = p.stock_quantity - c.quantity 
                                  WHERE c.user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $success = "Order placed successfully! Your order ID is #" . $order_id;
            header("refresh:3;url=order_confirmation.php?order_id=" . $order_id);
        } else {
            $error = "Failed to process payment. Please try again.";
        }
    }
}

include 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg animate__animated animate__fadeIn">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Payment Details</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                        <form action="" method="POST" class="needs-validation" novalidate>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Order Summary</h5>
                                    <p class="mb-1">Total Amount: <strong><?php echo formatPrice($total); ?></strong></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Shipping Address</h5>
                                    <textarea name="shipping_address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                            </div>
                            
                            <hr class="mb-4">
                            
                            <h5>Card Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="card_number">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" 
                                           pattern="[0-9]{16}" placeholder="1234 5678 9012 3456" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="card_expiry">Expiry Date</label>
                                    <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                                           pattern="(0[1-9]|1[0-2])\/[0-9]{2}" placeholder="MM/YY" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="card_cvv">CVV</label>
                                    <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                                           pattern="[0-9]{3,4}" placeholder="123" required>
                                </div>
                            </div>
                            
                            <hr class="mb-4">
                            
                            <button class="btn btn-primary btn-lg w-100" type="submit">
                                Complete Payment
                            </button>
                        </form>
                    <?php endif; ?>
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
    
    // Format card number
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 16) value = value.slice(0, 16);
        e.target.value = value;
    });
    
    // Format expiry date
    document.getElementById('card_expiry').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4) value = value.slice(0, 4);
        if (value.length > 2) {
            value = value.slice(0, 2) + '/' + value.slice(2);
        }
        e.target.value = value;
    });
    
    // Format CVV
    document.getElementById('card_cvv').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4) value = value.slice(0, 4);
        e.target.value = value;
    });
})()
</script>

<?php 
$conn->close();
include 'footer.php'; 
?>
