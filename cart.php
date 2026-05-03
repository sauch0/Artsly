<?php
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
include 'database/dbconnect.php';
$user = $_SESSION['user'];
$user_id = intval($user['id']);

// Handle quantity updates
if (isset($_GET['update_qty']) && isset($_GET['cart_id'])) {
    $cid = intval($_GET['cart_id']);
    $change = intval($_GET['update_qty']);
    $conn->query("UPDATE cart SET quantity = GREATEST(1, quantity + $change) WHERE id = '$cid' AND user_id = '$user_id'");
    header('Location: cart.php');
    exit;
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $conn->query("DELETE FROM cart WHERE id = '$cart_id' AND user_id = '$user_id'");
    header('Location: cart.php');
    exit;
}

$cartResult = $conn->query("SELECT c.id as cart_id, c.quantity as cart_qty, p.*, u.name as artist_name 
                            FROM cart c 
                            JOIN products p ON c.product_id = p.id 
                            JOIN users u ON p.user_id = u.id 
                            WHERE c.user_id = '$user_id'");
$total = 0;
$allowCheckout = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .cart-container { max-width: 1000px; margin: 0 auto; padding: 8rem 5% 4rem; }
        .cart-item { display: grid; grid-template-columns: 120px 1fr auto; gap: 2rem; background: var(--glass-bg); padding: 1.5rem; border-radius: 20px; border: 1px solid var(--glass-border); margin-bottom: 1.5rem; align-items: center; }
        .cart-item img { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; }
        .qty-controls { display: flex; align-items: center; gap: 1rem; margin-top: 1rem; }
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.05); color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .qty-btn:hover { border-color: var(--accent); background: rgba(255,255,255,0.1); }
        .cart-summary { background: var(--glass-bg); padding: 2rem; border-radius: 24px; border: 1px solid var(--glass-border); margin-top: 3rem; text-align: right; }
    </style>
</head>
<body>
    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>
    <header>
        <nav class="navbar">
            <div class="logo" onclick="window.location.href='landing.php'" style="cursor:pointer">Artsly</div>
            <div class="auth-buttons">
                <a href="landing.php" class="btn btn-secondary">Back to Gallery</a>
            </div>
        </nav>
    </header>

    <main class="cart-container">
        <h1>Your Visionary Collection</h1>
        <p style="color:var(--secondary); margin-bottom: 3rem;">Review your selected pieces before they join your world.</p>

        <?php if ($cartResult->num_rows > 0): ?>
            <?php while ($item = $cartResult->fetch_assoc()): ?>
            <div class="cart-item">
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                <div>
                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                    <p style="color:var(--secondary)">By <?php echo htmlspecialchars($item['artist_name']); ?></p>
                    <div class="qty-controls">
                        <a href="cart.php?update_qty=-1&cart_id=<?php echo $item['cart_id']; ?>" class="qty-btn">-</a>
                        <span style="font-weight:700"><?php echo $item['cart_qty']; ?></span>
                        <a href="cart.php?update_qty=1&cart_id=<?php echo $item['cart_id']; ?>" class="qty-btn">+</a>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="color:var(--accent); font-weight:700; font-size:1.2rem">Rs. <?php echo number_format($item['price'] * $item['cart_qty']); ?></div>
                    <?php if($item['cart_qty'] > $item['stock_count']): ?>
                        <div style="color:#ef4444; font-size:0.75rem; margin-top:0.5rem; font-weight:600">Only <?php echo $item['stock_count']; ?> available</div>
                    <?php endif; ?>
                    <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" style="color:#ef4444; font-size:0.8rem; margin-top:1rem; display:block">Remove</a>
                </div>
            </div>
            <?php 
                $total += ($item['price'] * $item['cart_qty']); 
                if ($item['cart_qty'] > $item['stock_count']) $allowCheckout = false;
            ?>
            <?php endwhile; ?>

            <div class="cart-summary">
                <div style="font-size:1.5rem; margin-bottom:1.5rem">Total: <span style="color:var(--accent); font-weight:700">Rs. <?php echo number_format($total); ?></span></div>
                <?php if($allowCheckout): ?>
                    <a href="checkout.php" class="btn btn-primary" style="padding: 1rem 3rem">Proceed to Checkout</a>
                <?php else: ?>
                    <button class="btn btn-secondary" style="padding: 1rem 3rem; opacity:0.5; cursor:not-allowed" onclick="alert('Some items in your cart exceed available stock. Please reduce the quantity.')">Insufficient Stock</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:5rem; background:var(--glass-bg); border-radius:24px; border:1px solid var(--glass-border)">
                <h2>Your cart is empty.</h2>
                <p style="color:var(--secondary); margin-bottom:2rem">Find something that speaks to your soul.</p>
                <a href="landing.php" class="btn btn-primary">Browse Gallery</a>
            </div>
        <?php endif; ?>
    </main>

    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
