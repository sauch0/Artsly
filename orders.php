<?php
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
include 'database/dbconnect.php';
$user = $_SESSION['user'];
$user_id = intval($user['id']);

$ordersResult = $conn->query("SELECT o.*, p.title as product_title, p.image as product_image, u.name as artist_name
                             FROM orders o
                             JOIN products p ON o.product_id = p.id
                             JOIN users u ON o.artist_id = u.id
                             WHERE o.user_id = '$user_id'
                             ORDER BY o.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .orders-container { max-width: 1000px; margin: 0 auto; padding: 8rem 5% 4rem; }
        .order-card { background: var(--glass-bg); padding: 2rem; border-radius: 24px; border: 1px solid var(--glass-border); margin-bottom: 2rem; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .order-content { display: grid; grid-template-columns: 120px 1fr auto; gap: 2rem; align-items: center; }
        .order-content img { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; }
        .status-badge { display: inline-block; padding: 0.4rem 1.2rem; border-radius: 100px; font-size: 0.85rem; font-weight: 600; }
        .status-completed { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-pending, .status-order-received { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
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

    <main class="orders-container">
        <h1>My Orders</h1>
        <p style="color:var(--secondary); margin-bottom: 3rem;">Track your journey to owning visionary masterpieces.</p>

        <?php if ($ordersResult->num_rows > 0): ?>
            <?php while ($o = $ordersResult->fetch_assoc()): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span style="color:var(--secondary); font-size:0.9rem">Order ID: #<?php echo $o['id']; ?></span>
                        <div style="font-size:0.8rem; color:rgba(255,255,255,0.5)"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></div>
                    </div>
                    <div class="status-badge <?php echo 'status-' . str_replace(' ', '-', $o['status']); ?>">
                        <?php echo ucfirst($o['status']); ?>
                    </div>
                </div>
                <div class="order-content">
                    <img src="<?php echo htmlspecialchars($o['product_image']); ?>" alt="">
                    <div>
                        <h3><?php echo htmlspecialchars($o['product_title']); ?></h3>
                        <p style="color:var(--secondary)">By <?php echo htmlspecialchars($o['artist_name']); ?></p>
                    </div>
                    <div style="text-align:right">
                        <div style="color:var(--accent); font-weight:700; font-size:1.2rem">Rs. <?php echo number_format($o['total_price']); ?></div>
                        <p style="font-size:0.8rem; color:var(--secondary); margin-top:0.5rem">
                            <?php echo ($o['payment_method'] === 'COD') ? 'Cash on Delivery' : 'Paid via eSewa'; ?>
                        </p>
                    </div>
                </div>
                <?php if ($o['status'] === 'cancelled' && !empty($o['cancellation_message'])): ?>
                <div style="margin-top:1.5rem; padding:1rem; background:rgba(239, 68, 68, 0.1); border-radius:12px; border:1px solid rgba(239, 68, 68, 0.3)">
                    <p style="color:#ef4444; font-weight:600; font-size:0.85rem; margin-bottom:0.5rem">Cancellation Message from Artist:</p>
                    <p style="color:var(--secondary); font-size:0.9rem; margin:0"><?php echo nl2br(htmlspecialchars($o['cancellation_message'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:5rem; background:var(--glass-bg); border-radius:24px; border:1px solid var(--glass-border)">
                <h2>No orders yet.</h2>
                <p style="color:var(--secondary); margin-bottom:2rem">Your collection is waiting for its first piece.</p>
                <a href="landing.php" class="btn btn-primary">Start Exploring</a>
            </div>
        <?php endif; ?>
    </main>

    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
