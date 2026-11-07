<?php
// store/api/cart.php — AJAX cart handler
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/cart.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$action     = $_POST['action'] ?? $_GET['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$qty        = max(1, (int)($_POST['qty'] ?? 1));

switch ($action) {

     case 'add':
    if (!$product_id) {
        echo json_encode(['success'=>false,'message'=>'Invalid product.']); exit;
    }

    // Detect which columns actually exist
    $cols       = getColumns($pdo, 'products');
    $hasStatus  = in_array('status', $cols);
    $hasStock   = in_array('stock',  $cols);

    // Build safe SELECT
    $selectCols = 'id, name';
    if ($hasStock)  $selectCols .= ', stock';

    $sql    = "SELECT $selectCols FROM products WHERE id = ?";
    if ($hasStatus) $sql .= " AND status = 'active'";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'Database error.']); exit;
    }

    if (!$product) {
        echo json_encode(['success'=>false,'message'=>'Product not found.']); exit;
    }

    // Stock check only if column exists and has a value
    if ($hasStock && isset($product['stock']) && $product['stock'] !== null && (int)$product['stock'] < 1) {
        echo json_encode(['success'=>false,'message'=>'Out of stock.']); exit;
    }

    addToCart($product_id, $qty);
    echo json_encode([
        'success'    => true,
        'message'    => htmlspecialchars($product['name']) . ' added to cart!',
        'cart_count' => cartCount(),
    ]);
    break;

  case 'update':
    updateCartItem($product_id, $qty);
    echo json_encode(['success'=>true,'cart_count'=>cartCount()]);
    break;

  case 'remove':
    removeFromCart($product_id);
    echo json_encode(['success'=>true,'cart_count'=>cartCount()]);
    break;

  case 'count':
    echo json_encode(['count'=>cartCount()]);
    break;

  case 'clear':
    clearCart();
    echo json_encode(['success'=>true,'cart_count'=>0]);
    break;

  default:
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Unknown action.']);
   
}
exit;
