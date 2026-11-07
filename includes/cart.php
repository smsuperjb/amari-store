<?php
// store/includes/cart.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['store_cart'])) $_SESSION['store_cart'] = [];

function getCart(): array {
    return $_SESSION['store_cart'] ?? [];
}

function cartCount(): int {
    $cart = getCart();
    return empty($cart) ? 0 : array_sum(array_column($cart, 'qty'));
}

function addToCart(int $productId, int $qty = 1): void {
    if (isset($_SESSION['store_cart'][$productId])) {
        $_SESSION['store_cart'][$productId]['qty'] += $qty;
    } else {
        $_SESSION['store_cart'][$productId] = ['product_id' => $productId, 'qty' => $qty];
    }
}

function updateCartItem(int $productId, int $qty): void {
    if ($qty <= 0) {
        unset($_SESSION['store_cart'][$productId]);
    } else {
        $_SESSION['store_cart'][$productId]['qty'] = $qty;
    }
}

function removeFromCart(int $productId): void {
    unset($_SESSION['store_cart'][$productId]);
}

function clearCart(): void {
    $_SESSION['store_cart'] = [];
}

function getCartWithProducts(PDO $pdo): array {
    $cart = getCart();
    if (empty($cart)) return [];
    $ids = array_keys($cart);
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $rows = $pdo->prepare("SELECT * FROM products WHERE id IN ($ph)");
    $rows->execute($ids);
    $products = $rows->fetchAll(PDO::FETCH_ASSOC);
    $map = array_column($products, null, 'id');
    $result = [];
    foreach ($cart as $pid => $item) {
        if (isset($map[$pid])) {
            $p = $map[$pid];
            $result[] = [
                'product_id' => $pid,
                'qty'        => $item['qty'],
                'product'    => $p,
                'subtotal'   => $p['price'] * $item['qty'],
            ];
        }
    }
    return $result;
}

function cartTotal(PDO $pdo): float {
    $items = getCartWithProducts($pdo);
    return array_sum(array_column($items, 'subtotal'));
}
