<?php
session_start();
// Admin gatekeeper
if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'database/dbconnect.php';

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_artist') {
        $vid = intval($_POST['verification_id']);
        $conn->query("UPDATE artist_verification SET status='verified' WHERE id='$vid'");
    }
    if ($_POST['action'] === 'reject_artist') {
        $vid = intval($_POST['verification_id']);
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
        $conn->query("UPDATE artist_verification SET status='rejected', admin_reason='$reason' WHERE id='$vid'");
    }
    if ($_POST['action'] === 'delete_product') {
        $pid = intval($_POST['product_id']);
        $conn->query("DELETE FROM products WHERE id='$pid'");
    }
    if ($_POST['action'] === 'ban_user') {
        $uid = intval($_POST['user_id']);
        $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
        $conn->query("UPDATE users SET status='banned', ban_reason='$reason' WHERE id='$uid'");
    }
    if ($_POST['action'] === 'unban_user') {
        $uid = intval($_POST['user_id']);
        $conn->query("UPDATE users SET status='active' WHERE id='$uid'");
    }
    if ($_POST['action'] === 'remove_artist') {
        $uid = intval($_POST['user_id']);
        // 1. Delete products
        $conn->query("DELETE FROM products WHERE user_id='$uid'");
        // 2. Delete verification records
        $conn->query("DELETE FROM artist_verification WHERE user_id='$uid'");
        // 3. Downgrade role
        $conn->query("UPDATE users SET role='user' WHERE id='$uid'");
    }
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch stats
$totalUsers = $conn->query("SELECT id FROM users")->num_rows;
$pendingArtists = $conn->query("SELECT id FROM artist_verification WHERE status='pending'")->num_rows;
$totalProducts = $conn->query("SELECT id FROM products")->num_rows;

// Fetch pending verifications
$pendingVerifications = $conn->query("SELECT v.*, u.email FROM artist_verification v JOIN users u ON v.user_id = u.id WHERE v.status='pending' ORDER BY v.created_at DESC");

// Fetch all products for moderation
$allProducts = $conn->query("SELECT p.*, u.name as artist_name FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");

// Fetch verified artists for management
$verifiedArtists = $conn->query("SELECT u.id, u.name, u.email, u.status, v.full_name, v.mobile, v.address FROM users u JOIN artist_verification v ON u.id = v.user_id WHERE u.role='artist' AND v.status='verified' ORDER BY u.id DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .admin-dashboard { max-width: 1300px; margin: 0 auto; padding: 8rem 5% 4rem; }
        .admin-header { text-align: center; margin-bottom: 3rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 4rem; }
        .stat-card { background: var(--glass-bg); padding: 2.5rem; border-radius: 24px; border: 1px solid var(--glass-border); text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .stat-card .label { color: var(--secondary); font-size: 0.95rem; display: block; margin-bottom: 0.5rem; }
        .stat-card .number { font-size: 3rem; font-weight: 700; color: var(--accent); }
        
        .section-title { margin: 3rem 0 1.5rem; font-size: 1.8rem; letter-spacing: -1px; display: flex; align-items: center; gap: 1rem; }
        .section-title span { background: var(--accent); width: 40px; height: 4px; border-radius: 2px; }

        .data-table-container { background: var(--glass-bg); border-radius: 24px; border: 1px solid var(--glass-border); overflow: hidden; margin-bottom: 3rem; }
        table { width: 100%; border-collapse: collapse; color: var(--primary); }
        th { background: rgba(255,255,255,0.05); text-align: left; padding: 1.2rem 1.5rem; font-weight: 600; color: var(--secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--glass-border); }
        tr:last-child td { border-bottom: none; }
        
        .thumbnail { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid var(--glass-border); cursor: pointer; transition: 0.3s; }
        .thumbnail:hover { transform: scale(3.5); z-index: 100; position: relative; border-color: var(--accent); }

        .badge { padding: 0.3rem 0.8rem; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }

        .btn-action { padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.85rem; cursor: pointer; border: none; transition: 0.3s; font-weight: 500; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; transform: translateY(-2px); }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; transform: translateY(-2px); }
        .btn-ban { background: #f59e0b; color: white; }
        .btn-ban:hover { background: #d97706; transform: translateY(-2px); }
        .btn-remove { background: #4b5563; color: white; }
        .btn-remove:hover { background: #374151; transform: translateY(-2px); }

        .reason-input { width: 100%; padding: 0.5rem 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: white; font-family: inherit; font-size: 0.8rem; resize: none; margin-bottom: 0.5rem; transition: 0.3s; }
        .reason-input:focus { outline: none; border-color: #ef4444; background: rgba(255,255,255,0.08); }
        .reason-input.ban { border-color: rgba(245,158,11,0.4); }
        .reason-input.ban:focus { border-color: #f59e0b; }
        .action-form { display: flex; flex-direction: column; gap: 0.3rem; }

        .product-moderation-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; }
        .mod-card { background: var(--glass-bg); border-radius: 24px; border: 1px solid var(--glass-border); overflow: hidden; transition: 0.3s; }
        .mod-card:hover { border-color: #ef4444; }
        .mod-card img { width: 100%; height: 200px; object-fit: cover; opacity: 0.8; }
        .mod-card-body { padding: 1.5rem; }
        .mod-card-artist { font-size: 0.8rem; color: var(--secondary); margin-bottom: 0.5rem; }
        .mod-card-title { font-size: 1.1rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>
    
    <header><nav class="navbar"><div class="logo">Artsly Admin</div><div class="auth-buttons"><a href="logout.php" class="btn btn-secondary">System Exit</a></div></nav></header>

    <main class="admin-dashboard">
        <div class="admin-header">
            <h1>Command Center</h1>
            <p>Managing the future of visionary art</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><span class="label">Total Users</span><span class="number"><?php echo $totalUsers; ?></span></div>
            <div class="stat-card"><span class="label">Pending Artists</span><span class="number"><?php echo $pendingArtists; ?></span></div>
            <div class="stat-card"><span class="label">Live Creations</span><span class="number"><?php echo $totalProducts; ?></span></div>
        </div>

        <h2 class="section-title"><span></span> Artist Verifications</h2>
        <div class="data-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Artist</th>
                        <th>Location</th>
                        <th>Number</th>
                        <th>Email</th>
                        <th>Documents</th>
                        <th>Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pendingVerifications->num_rows > 0): ?>
                        <?php while ($v = $pendingVerifications->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($v['full_name']); ?></strong><br>
                                <span style="font-size:0.8rem; color:var(--secondary)">ID: #<?php echo $v['user_id']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($v['address']); ?></td>
                            <td><?php echo htmlspecialchars($v['mobile']); ?></td>
                            <td style="font-size:0.85rem; color:var(--accent)"><?php echo htmlspecialchars($v['email']); ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($v['citizenship_card']); ?>" class="thumbnail" title="Citizenship Card">
                                <img src="<?php echo htmlspecialchars($v['selfie']); ?>" class="thumbnail" title="Selfie" style="margin-left:0.5rem">
                            </td>
                            <td style="max-width:200px; font-size:0.85rem; color:var(--secondary)">
                                <?php echo htmlspecialchars($v['message']); ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:0.5rem">
                                    <form method="POST"><input type="hidden" name="action" value="approve_artist"><input type="hidden" name="verification_id" value="<?php echo $v['id']; ?>"><button type="submit" class="btn-action btn-approve">Approve</button></form>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="action" value="reject_artist">
                                        <input type="hidden" name="verification_id" value="<?php echo $v['id']; ?>">
                                        <textarea name="reason" class="reason-input" rows="2" placeholder="Rejection reason (optional)"></textarea>
                                        <button type="submit" class="btn-action btn-reject">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:3rem; color:var(--secondary)">No pending verification requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 class="section-title"><span></span> Artist Management</h2>
        <div class="data-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Artist</th>
                        <th>Location</th>
                        <th>Number</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($verifiedArtists->num_rows > 0): ?>
                        <?php while ($a = $verifiedArtists->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($a['full_name'] ?: $a['name']); ?></strong><br>
                                <span style="font-size:0.8rem; color:var(--secondary)">ID: #<?php echo $a['id']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($a['address'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($a['mobile']); ?></td>
                            <td style="font-size:0.85rem; color:var(--accent)"><?php echo htmlspecialchars($a['email']); ?></td>
                            <td>
                                <?php if ($a['status'] === 'banned'): ?>
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Banned</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:0.5rem; flex-wrap:wrap">
                                    <?php if ($a['status'] === 'banned'): ?>
                                        <form method="POST"><input type="hidden" name="action" value="unban_user"><input type="hidden" name="user_id" value="<?php echo $a['id']; ?>"><button type="submit" class="btn-action btn-approve">Unban</button></form>
                                    <?php else: ?>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="action" value="ban_user">
                                            <input type="hidden" name="user_id" value="<?php echo $a['id']; ?>">
                                            <textarea name="reason" class="reason-input ban" rows="2" placeholder="Ban reason (optional)"></textarea>
                                            <button type="submit" class="btn-action btn-ban" onclick="return confirm('Ban this artist? They will lose access to their account.')">Ban</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" onsubmit="return confirm('Completely remove this artist? This will DELETE their products and revert them to a regular user.')"><input type="hidden" name="action" value="remove_artist"><input type="hidden" name="user_id" value="<?php echo $a['id']; ?>"><button type="submit" class="btn-action btn-remove">Remove Artist</button></form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:3rem; color:var(--secondary)">No verified artists yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 class="section-title"><span></span> Content Moderation</h2>
        <div class="product-moderation-grid">
            <?php if ($allProducts->num_rows > 0): ?>
                <?php while ($p = $allProducts->fetch_assoc()): ?>
                <div class="mod-card">
                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                    <div class="mod-card-body">
                        <div class="mod-card-artist">Posted by <strong><?php echo htmlspecialchars($p['artist_name']); ?></strong></div>
                        <h3 class="mod-card-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                        <form method="POST" onsubmit="return confirm('Absolutely sure about deleting this creation?')">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-secondary" style="width:100%; border-color:#ef4444; color:#ef4444">Remove from Gallery</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align:center; color:var(--secondary); padding:3rem">The gallery is currently empty.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
