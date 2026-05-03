<?php
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
include 'database/dbconnect.php';
$user = $_SESSION['user'];
$user_id = intval($user['id']);

if (!isset($_GET['id'])) {
    header('Location: landing.php');
    exit;
}

$product_id = intval($_GET['id']);
$productResult = $conn->query("SELECT p.*, u.name as artist_name, av.esewa_number 
                               FROM products p 
                               JOIN users u ON p.user_id = u.id 
                               LEFT JOIN artist_verification av ON p.user_id = av.user_id
                               WHERE p.id = '$product_id'");

if ($productResult->num_rows === 0) {
    header('Location: landing.php');
    exit;
}

$p = $productResult->fetch_assoc();

// Increment views
$conn->query("UPDATE products SET views = views + 1 WHERE id = '$product_id'");

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $check = $conn->query("SELECT id FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = '$user_id' AND product_id = '$product_id'");
    } else {
        $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', 1)");
    }
    echo "<script>alert('Added to cart!'); window.location.href='cart.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($p['title']); ?> | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .details-container { max-width: 1000px; margin: 0 auto; padding: 8rem 5% 4rem; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; }
        .product-image img { width: 100%; border-radius: 24px; border: 1px solid var(--glass-border); box-shadow: 0 40px 100px rgba(0,0,0,0.5); }
        .product-info h1 { font-size: 3rem; margin-bottom: 1rem; }
        .artist-info { color: var(--secondary); margin-bottom: 2rem; font-size: 1.1rem; }
        .price { font-size: 2.5rem; color: var(--accent); font-weight: 700; margin-bottom: 2rem; }
        .description { font-size: 1.1rem; line-height: 1.6; color: rgba(255,255,255,0.8); margin-bottom: 3rem; }
        .actions { display: flex; gap: 1rem; }
        .tag { display: inline-block; padding: 0.4rem 1rem; background: rgba(99,102,241,0.1); color: var(--accent); border-radius: 100px; border: 1px solid rgba(99,102,241,0.2); font-size: 0.9rem; margin-right: 0.5rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>
    <header>
        <nav class="navbar">
            <div class="logo" onclick="window.location.href='landing.php'" style="cursor:pointer">Artsly</div>
            <div class="auth-buttons">
                <a href="cart.php" class="btn btn-secondary" style="margin-right:1rem">Cart</a>
                <a href="logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </nav>
    </header>

    <main class="details-container">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
        </div>
        <div class="product-info">
            <div style="margin-bottom:1rem">
                <?php foreach(explode(',', $p['tags']) as $tag): if($tag): ?>
                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                <?php endif; endforeach; ?>
            </div>
            <h1><?php echo htmlspecialchars($p['title']); ?></h1>
            <p class="artist-info">Masterpiece by <?php echo htmlspecialchars($p['artist_name']); ?></p>
            <div class="price">Rs. <?php echo number_format($p['price']); ?></div>
            <div style="margin-bottom:2rem; font-weight:600;">
                <?php if($p['stock_count'] > 0): ?>
                    <span style="color:#10b981">In Stock (<?php echo $p['stock_count']; ?> remaining)</span>
                <?php else: ?>
                    <span style="color:#ef4444">Currenty Out of Stock</span>
                <?php endif; ?>
            </div>
            <p class="description"><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>
            
            <div class="actions">
                <?php if($p['stock_count'] > 0): ?>
                <form method="POST" style="flex:1">
                    <input type="hidden" name="action" value="add_to_cart">
                    <button type="submit" class="btn btn-secondary" style="width:100%">Add to Cart</button>
                </form>
                <a href="checkout.php?product_id=<?php echo $p['id']; ?>" class="btn btn-primary" style="flex:1; text-align:center">Buy Now</a>
                <?php else: ?>
                <button class="btn btn-secondary" disabled style="width:100%; opacity:0.5; cursor:not-allowed">Product Unavailable</button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
