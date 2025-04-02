<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = (int)$_POST['cart_id'];
$action = $_POST['action'];

$conn = getDBConnection();

// Verify cart item belongs to user
$stmt = $conn->prepare("SELECT c.quantity, p.stock_quantity 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit;
}

$item = $result->fetch_assoc();
$current_quantity = $item['quantity'];
$max_quantity = $item['stock_quantity'];

switch ($action) {
    case 'increase':
        $new_quantity = $current_quantity + 1;
        break;
    case 'decrease':
        $new_quantity = $current_quantity - 1;
        break;
    case 'set':
        $new_quantity = (int)$_POST['value'];
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

if ($new_quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity cannot be less than 1']);
    exit;
}

if ($new_quantity > $max_quantity) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock']);
    exit;
}

$stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}

$conn->close();
