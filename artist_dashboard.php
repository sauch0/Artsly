<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Robust session check
if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'artist') {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$user_id = isset($user['id']) ? intval($user['id']) : 0;
include 'database/dbconnect.php';

if ($user_id === 0) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle verification and product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify') {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $esewa = mysqli_real_escape_string($conn, $_POST['esewa_number']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        
        // Server-side validation
        if (empty($full_name) || empty($address) || empty($mobile) || empty($esewa)) {
            echo "<script>alert('All required fields must be filled'); window.history.back();</script>";
            exit;
        }
        if (strlen($mobile) !== 10 || !ctype_digit($mobile) || $mobile[0] !== '9') {
            echo "<script>alert('Mobile number must be 10 digits and start with 9'); window.history.back();</script>";
            exit;
        }
        if (strlen($esewa) !== 10 || !ctype_digit($esewa) || $esewa[0] !== '9') {
            echo "<script>alert('eSewa number must be 10 digits and start with 9'); window.history.back();</script>";
            exit;
        }

        $citizenship_file = '';
        if (isset($_FILES['citizenship']) && $_FILES['citizenship']['error'] === 0) {
            $ext = pathinfo($_FILES['citizenship']['name'], PATHINFO_EXTENSION);
            $citizenship_file = 'uploads/citizenship/' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['citizenship']['tmp_name'], $citizenship_file);
        }

        $selfie_file = '';
        if (isset($_FILES['selfie']) && $_FILES['selfie']['error'] === 0) {
            $ext = pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
            $selfie_file = 'uploads/selfies/' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['selfie']['tmp_name'], $selfie_file);
        }

        // Check if a rejected record exists — update it; otherwise insert fresh
        $existingCheck = $conn->query("SELECT id FROM artist_verification WHERE user_id='$user_id' AND status='rejected' LIMIT 1");
        if ($existingCheck->num_rows > 0) {
            $imgUpdate = $citizenship_file ? ", citizenship_card='$citizenship_file'" : "";
            $imgUpdate .= $selfie_file ? ", selfie='$selfie_file'" : "";
            $sql = "UPDATE artist_verification SET full_name='$full_name', address='$address', mobile='$mobile', esewa_number='$esewa', message='$message', status='pending', admin_reason=NULL$imgUpdate WHERE user_id='$user_id' AND status='rejected'";
        } else {
            $sql = "INSERT INTO artist_verification (user_id, full_name, address, mobile, esewa_number, message, citizenship_card, selfie)
                    VALUES ('$user_id', '$full_name', '$address', '$mobile', '$esewa', '$message', '$citizenship_file', '$selfie_file')";
        }
        $conn->query($sql);
    }

    if ($_POST['action'] === 'update_esewa') {
        $esewa = mysqli_real_escape_string($conn, $_POST['esewa_number']);
        if (strlen($esewa) !== 10 || !ctype_digit($esewa) || $esewa[0] !== '9') {
            echo "<script>alert('eSewa number must be 10 digits and start with 9'); window.history.back();</script>";
            exit;
        }
        $conn->query("UPDATE artist_verification SET esewa_number='$esewa' WHERE user_id='$user_id'");
    }

    if ($_POST['action'] === 'update_order_status') {
        $order_id = intval($_POST['order_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $cancellation_message = isset($_POST['cancellation_message']) ? mysqli_real_escape_string($conn, $_POST['cancellation_message']) : '';
        error_log("Order: $order_id, Status: $status, Message: $cancellation_message");
        if ($status === 'cancelled' && !empty($cancellation_message)) {
            $conn->query("UPDATE orders SET status='$status', cancellation_message='$cancellation_message' WHERE id='$order_id' AND artist_id='$user_id'");
        } else {
            $conn->query("UPDATE orders SET status='$status' WHERE id='$order_id' AND artist_id='$user_id'");
        }
    }

    if ($_POST['action'] === 'add_product') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $stock = intval($_POST['stock_count']);
        $tags = isset($_POST['tags']) ? mysqli_real_escape_string($conn, implode(',', $_POST['tags'])) : '';

        $image_file = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $image_file = 'uploads/products/' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['product_image']['tmp_name'], $image_file);
        }

        $sql = "INSERT INTO products (user_id, title, description, price, stock_count, tags, image)
                VALUES ('$user_id', '$title', '$description', '$price', '$stock', '$tags', '$image_file')";
        $conn->query($sql);
    }

    if ($_POST['action'] === 'edit_product') {
        $pid = mysqli_real_escape_string($conn, $_POST['product_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $stock = intval($_POST['stock_count']);
        $tags = isset($_POST['tags']) ? mysqli_real_escape_string($conn, implode(',', $_POST['tags'])) : '';

        $sql = "UPDATE products SET title='$title', description='$description', price='$price', stock_count='$stock', tags='$tags' WHERE id='$pid' AND user_id='$user_id'";

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $image_file = 'uploads/products/' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['product_image']['tmp_name'], $image_file);
            $sql = "UPDATE products SET title='$title', description='$description', price='$price', stock_count='$stock', tags='$tags', image='$image_file' WHERE id='$pid' AND user_id='$user_id'";
        }
        $conn->query($sql);
    }

    if ($_POST['action'] === 'delete_product') {
        $pid = mysqli_real_escape_string($conn, $_POST['product_id']);
        $conn->query("DELETE FROM products WHERE id='$pid' AND user_id='$user_id'");
    }

    header('Location: artist_dashboard.php');
    exit;
}

// Fetch artist data
$verifyResult = $conn->query("SELECT * FROM artist_verification WHERE user_id='$user_id' ORDER BY id DESC LIMIT 1");
$verification = $verifyResult->fetch_assoc();
$isVerified = ($verification && $verification['status'] === 'verified');
$isPending = ($verification && $verification['status'] === 'pending');
$isRejected = ($verification && $verification['status'] === 'rejected');

// Fetch ban reason for this user
$userRow = $conn->query("SELECT status, ban_reason FROM users WHERE id='$user_id' LIMIT 1")->fetch_assoc();
$isBanned = ($userRow && $userRow['status'] === 'banned');

$productsResult = $conn->query("SELECT * FROM products WHERE user_id='$user_id' ORDER BY created_at DESC");
$totalProducts = $productsResult->num_rows;

$ordersResult = $conn->query("SELECT o.*, u.name as buyer_name, u.email as buyer_email, p.title as product_title 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id 
                             JOIN products p ON o.product_id = p.id 
                             WHERE o.artist_id='$user_id' 
                             ORDER BY o.created_at DESC");
$pendingOrdersCount = $conn->query("SELECT COUNT(*) as count FROM orders WHERE artist_id='$user_id' AND status='order received'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Dashboard | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .dashboard { max-width: 1200px; margin: 0 auto; padding: 8rem 5% 4rem; }
        .dashboard-header { text-align: center; margin-bottom: 3rem; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { background: var(--glass-bg); padding: 2rem; border-radius: 20px; border: 1px solid var(--glass-border); text-align: center; }
        .stat-card h3 { color: var(--secondary); font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: var(--accent); }
        .card { background: var(--glass-bg); padding: 2.5rem; border-radius: 24px; border: 1px solid var(--glass-border); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-control { width: 100%; padding: 1rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 12px; color: white; transition: var(--transition); }
        .form-control:focus { outline: none; border-color: var(--accent); background: rgba(255,255,255,0.1); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .product-card { background: rgba(255,255,255,0.03); border-radius: 20px; border: 1px solid var(--glass-border); overflow: hidden; transition: var(--transition); }
        .product-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .product-card img { width: 100%; height: 220px; object-fit: cover; }
        .product-info { padding: 1.5rem; }
        .price { color: var(--accent); font-weight: 700; font-size: 1.2rem; }
        .status-badge { display: inline-block; padding: 0.4rem 1.2rem; border-radius: 100px; font-size: 0.85rem; font-weight: 600; }
        .status-verified, .status-completed { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-pending, .status-order-received { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .status-unverified, .status-cancelled, .status-rejected { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-card { border-radius: 20px; padding: 2rem 2.5rem; margin-bottom: 2rem; border: 1px solid; }
        .alert-ban { background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.35); }
        .alert-rejected { background: rgba(239, 68, 68, 0.06); border-color: rgba(239, 68, 68, 0.25); }
        .alert-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.4rem; }
        .alert-reason { color: var(--secondary); font-size: 0.92rem; margin-top: 0.5rem; padding: 0.7rem 1rem; background: rgba(255,255,255,0.04); border-radius: 10px; font-style: italic; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
        .modal.active { display: flex; }
        .modal-content { background: #111; border: 1px solid var(--glass-border); padding: 3rem; border-radius: 24px; width: 90%; max-width: 500px; }
        .file-upload { border: 2px dashed var(--glass-border); padding: 2rem; border-radius: 16px; text-align: center; cursor: pointer; transition: 0.3s; position: relative; overflow: hidden; min-height: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; }
        .file-upload:hover { border-color: var(--accent); background: rgba(99, 102, 241, 0.05); }
        .file-upload img { max-width: 100%; max-height: 100px; border-radius: 8px; margin-top: 0.5rem; display: none; }
        .file-upload .file-name { font-size: 0.8rem; color: var(--accent); font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 100%; }
        .tag-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; }
        .tag-chip { padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 100px; font-size: 0.8rem; cursor: pointer; transition: 0.3s; }
        .tag-chip.active { background: var(--accent); border-color: var(--accent); color: white; }
        .tag-checkbox { display: none; }
        .same-as-label { font-size: 0.8rem; color: var(--secondary); cursor: pointer; display: flex; align-items: center; gap: 0.4rem; user-select: none; }
        .same-as-label input { cursor: pointer; }
        .form-control:read-only { opacity: 0.6; cursor: not-allowed; background: rgba(255,255,255,0.02); }
        .num-hint { display: block; font-size: 0.75rem; margin-top: 0.3rem; transition: 0.3s; }
        .num-hint.error { color: #ef4444; }
        .num-hint.success { color: #10b981; }
        .thumbnail { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--glass-border); cursor: pointer; transition: 0.3s; }
        .thumbnail:hover { transform: scale(4); z-index: 100; position: relative; border-color: var(--accent); }

        /* Redesigned Orders */
        .order-card { background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; display: grid; grid-template-columns: auto 1fr auto; gap: 2rem; align-items: start; transition: 0.3s; }
        .order-card:hover { border-color: rgba(255,255,255,0.15); background: rgba(255,255,255,0.04); }
        .order-img { width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 1px solid var(--glass-border); }
        .order-details h4 { margin: 0 0 0.5rem 0; font-size: 1.1rem; }
        .order-buyer-box { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); }
        .info-item { display: flex; flex-direction: column; gap: 0.2rem; }
        .info-label { font-size: 0.7rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; }
        .info-value { font-size: 0.85rem; color: white; }
        .order-status-actions { text-align: right; display: flex; flex-direction: column; gap: 1rem; justify-content: space-between; height: 100%; min-width: 150px; }
        .receipt-btn { font-size: 0.75rem; color: var(--accent); cursor: pointer; text-decoration: underline; background: none; border: none; padding: 0; }
    </style>
</head>
<body>
    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>
    <header>
        <nav class="navbar">
            <div class="logo">Artsly</div>
            <div class="auth-buttons">
                <span style="color: var(--secondary); margin-right: 1.5rem; align-self: center;"><?php echo htmlspecialchars($user['name'] ?? 'Artist'); ?></span>
                <a href="logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </nav>
    </header>

    <main class="dashboard">
        <div class="dashboard-header">
            <h1>Artist Dashboard</h1>
            <p>Empower your creativity through visionary tools</p>
        </div>

        <?php if ($isBanned): ?>
        <div class="alert-card alert-ban">
            <div class="alert-title" style="color:#ef4444">🚫 Your account has been banned</div>
            <p style="color:var(--secondary); font-size:0.9rem; margin: 0.3rem 0 0">You no longer have access to artist features. Contact support if you believe this is a mistake.</p>
            <?php if (!empty($userRow['ban_reason'])): ?>
            <div class="alert-reason">Reason: <?php echo htmlspecialchars($userRow['ban_reason']); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <h3>Account Status</h3>
                <?php if ($isBanned): ?><div class="status-badge status-unverified">Banned</div>
                <?php elseif ($isVerified): ?><div class="status-badge status-verified">Verified</div>
                <?php elseif ($isPending): ?><div class="status-badge status-pending">Pending</div>
                <?php elseif ($isRejected): ?><div class="status-badge status-unverified">Rejected</div>
                <?php else: ?><div class="status-badge status-unverified">Unverified</div><?php endif; ?>
            </div>
            <div class="stat-card">
                <h3>Total Creations</h3>
                <div class="value"><?php echo $totalProducts; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo $pendingOrdersCount; ?></div>
            </div>
        </div>

        <?php if (!$isVerified && !$isPending && !$isRejected): ?>
        <div class="card">
            <h2>Identity Verification</h2>
            <p style="color: var(--secondary); margin-bottom: 2rem;">Upload your credentials to start showcasing your art.</p>
            <form method="POST" enctype="multipart/form-data" id="verificationForm">
                <input type="hidden" name="action" value="verify">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" id="v_full_name" class="form-control" required maxlength="20"></div>
                    <div class="form-group">
                        <label>Mobile <span style="color:var(--secondary);font-size:0.8rem">(starts with 9, 10 digits)</span></label>
                        <input type="text" name="mobile" id="v_mobile" class="form-control" required maxlength="10" minlength="10"
                               pattern="9[0-9]{9}" title="Must start with 9 and be 10 digits"
                               oninput="this.value=this.value.replace(/[^0-9]/g,''); syncEsewaDirect('v_mobile','v_esewa','v_esewa_samecheck'); validateForm();">
                        <small class="num-hint" id="v_mobile_hint"></small>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group"><label>Address</label><input type="text" name="address" id="v_address" class="form-control" required maxlength="20"></div>
                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem">
                            <label style="margin:0">eSewa Number <span style="color:var(--secondary);font-size:0.8rem">(starts with 9)</span></label>
                            <label class="same-as-label"><input type="checkbox" id="v_esewa_samecheck" onchange="toggleSameAs('v_mobile','v_esewa',this)"> Same as mobile</label>
                        </div>
                        <input type="text" name="esewa_number" id="v_esewa" class="form-control" required maxlength="10" minlength="10"
                               pattern="9[0-9]{9}" title="Must start with 9 and be 10 digits"
                               oninput="this.value=this.value.replace(/[^0-9]/g,''); validateForm();">
                        <small class="num-hint" id="v_esewa_hint"></small>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
                    <div class="form-group">
                        <label>Citizenship Card</label>
                        <div class="file-upload" onclick="this.querySelector('input').click()">
                            <input type="file" name="citizenship" id="v_citizenship" style="display:none" required onchange="handleFileSelect(this, 'citizenship-preview', 'citizenship-name', 'Upload ID')">
                            <span class="upload-text">Upload ID</span>
                            <span class="file-name" id="citizenship-name"></span>
                            <img id="citizenship-preview" src="" alt="Preview">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Selfie</label>
                        <div class="file-upload" onclick="this.querySelector('input').click()">
                            <input type="file" name="selfie" id="v_selfie" style="display:none" required onchange="handleFileSelect(this, 'selfie-preview', 'selfie-name', 'Upload Selfie')">
                            <span class="upload-text">Upload Selfie</span>
                            <span class="file-name" id="selfie-name"></span>
                            <img id="selfie-preview" src="" alt="Preview">
                        </div>
                    </div>
                </div>
                <div class="form-group"><label>Artistic Message</label><textarea name="message" class="form-control" maxlength="60"></textarea></div>
                <button type="submit" id="submitVerification" class="btn btn-primary" style="width:100%; margin-top: 1rem; opacity: 0.5; cursor: not-allowed;" disabled>Submit Verification</button>
            </form>
        </div>
        <?php elseif ($isPending): ?>
        <div class="card" style="text-align:center">
            <h2>Under Review</h2>
            <p>Our curators are currently reviewing your profile. Stay visionary!</p>
        </div>
        <?php elseif ($isRejected): ?>
        <div class="card">
            <h2 style="color:#ef4444; margin-bottom:1rem">Verification Rejected</h2>
            <p style="color:var(--secondary); margin-bottom:1.5rem">Your verification request was not approved. You may resubmit after addressing the issue below.</p>
            <?php if (!empty($verification['admin_reason'])): ?>
            <div class="alert-card alert-rejected" style="margin-bottom:1.5rem">
                <div class="alert-title" style="color:#ef4444; font-size:0.9rem">Admin Feedback</div>
                <div class="alert-reason"><?php echo htmlspecialchars($verification['admin_reason']); ?></div>
            </div>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="document.getElementById('resubmitSection').style.display='block'; this.style.display='none'">Resubmit Verification</button>
            <div id="resubmitSection" style="display:none; margin-top:2rem">
                <form method="POST" enctype="multipart/form-data" id="verificationForm">
                    <input type="hidden" name="action" value="verify">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" id="v_full_name" class="form-control" required maxlength="20"></div>
                        <div class="form-group">
                            <label>Mobile <span style="color:var(--secondary);font-size:0.8rem">(starts with 9, 10 digits)</span></label>
                            <input type="text" name="mobile" id="v_mobile" class="form-control" required maxlength="10" minlength="10"
                                   pattern="9[0-9]{9}" title="Must start with 9 and be 10 digits"
                                   oninput="this.value=this.value.replace(/[^0-9]/g,''); syncEsewaDirect('v_mobile','v_esewa','v_esewa_samecheck'); validateForm();">
                            <small class="num-hint" id="v_mobile_hint"></small>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group"><label>Address</label><input type="text" name="address" id="v_address" class="form-control" required maxlength="20"></div>
                        <div class="form-group">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem">
                                <label style="margin:0">eSewa Number <span style="color:var(--secondary);font-size:0.8rem">(starts with 9)</span></label>
                                <label class="same-as-label"><input type="checkbox" id="v_esewa_samecheck" onchange="toggleSameAs('v_mobile','v_esewa',this)"> Same as mobile</label>
                            </div>
                            <input type="text" name="esewa_number" id="v_esewa" class="form-control" required maxlength="10" minlength="10"
                                   pattern="9[0-9]{9}" title="Must start with 9 and be 10 digits"
                                   oninput="this.value=this.value.replace(/[^0-9]/g,''); validateForm();">
                            <small class="num-hint" id="v_esewa_hint"></small>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
                        <div class="form-group">
                            <label>Citizenship Card</label>
                            <div class="file-upload" onclick="this.querySelector('input').click()">
                                <input type="file" name="citizenship" id="v_citizenship" style="display:none" required onchange="handleFileSelect(this, 'citizenship-preview', 'citizenship-name', 'Upload ID')">
                                <span class="upload-text">Upload ID</span>
                                <span class="file-name" id="citizenship-name"></span>
                                <img id="citizenship-preview" src="" alt="Preview">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Selfie</label>
                            <div class="file-upload" onclick="this.querySelector('input').click()">
                                <input type="file" name="selfie" id="v_selfie" style="display:none" required onchange="handleFileSelect(this, 'selfie-preview', 'selfie-name', 'Upload Selfie')">
                                <span class="upload-text">Upload Selfie</span>
                                <span class="file-name" id="selfie-name"></span>
                                <img id="selfie-preview" src="" alt="Preview">
                            </div>
                        </div>
                    </div>
                    <div class="form-group"><label>Artistic Message</label><textarea name="message" class="form-control" maxlength="60"></textarea></div>
                    <button type="submit" id="submitVerification" class="btn btn-primary" style="width:100%; margin-top: 1rem; opacity: 0.5; cursor: not-allowed;" disabled>Submit Verification</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
                    <h2>Your Gallery</h2>
                    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">+ New Artwork</button>
                </div>
                <div class="products-grid">
                    <?php while ($p = $productsResult->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                        <div class="product-info">
                            <div class="price">Rs. <?php echo number_format($p['price']); ?></div>
                            <div style="font-size:0.75rem; margin:0.5rem 0; font-weight:600;">
                                <?php if($p['stock_count'] > 0): ?>
                                    <span style="color:#10b981">In Stock (<?php echo $p['stock_count']; ?>)</span>
                                <?php else: ?>
                                    <span style="color:#ef4444">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                            <p style="font-size:0.8rem; color:var(--secondary); margin-bottom:1rem"><?php echo htmlspecialchars($p['tags']); ?></p>
                            <div style="display:flex; gap:0.5rem; margin-top:1rem">
                                <button class="btn btn-secondary btn-sm" style="flex:1" onclick='openEdit(<?php echo json_encode($p); ?>)'>Edit</button>
                                <form method="POST" style="flex:1" onsubmit="return confirm('Are you sure you want to delete this vision? This action cannot be undone.')"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><button type="submit" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#ef4444; border:1px solid rgba(239,68,68,0.3); width:100%">Delete</button></form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
                    <h2>eSewa Number</h2>
                    <form method="POST" style="display:flex; flex-direction:column; gap:0.4rem; align-items:flex-end; min-width:200px">
                        <input type="hidden" name="action" value="update_esewa">
                        <div style="display:flex; gap:0.5rem; align-items:center">
                            <input type="text" name="esewa_number" id="ue_esewa" class="form-control" value="<?php echo htmlspecialchars($verification['esewa_number'] ?? ''); ?>" required maxlength="10" minlength="10" pattern="9[0-9]{9}" style="width:150px" title="Must start with 9 and be 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                        </div>
                        <label class="same-as-label" style="font-size:0.78rem">
                            <input type="checkbox" onchange="toggleSameAsVerified(this)"> Use mobile number
                        </label>
                    </form>
                </div>
                
                <h2>Order Management</h2>
                <div style="margin-top:2rem">
                    <?php if ($ordersResult->num_rows > 0): ?>
                        <div class="orders-list">
                            <?php while ($o = $ordersResult->fetch_assoc()): ?>
                            <div class="order-card">
                                <div>
                                    <?php 
                                        // Fetch product image for better UI
                                        $pid = $o['product_id'];
                                        $pImg = $conn->query("SELECT image FROM products WHERE id='$pid'")->fetch_assoc()['image'] ?? '';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pImg); ?>" class="order-img" alt="Product">
                                    <div style="margin-top: 0.5rem; text-align: center;">
                                        <?php if($o['payment_method'] === 'eSewa'): ?>
                                            <?php if($o['receipt_image']): ?>
                                                <a href="<?php echo htmlspecialchars($o['receipt_image']); ?>" target="_blank" class="receipt-btn">View Receipt</a>
                                            <?php else: ?>
                                                <span style="color:var(--secondary); font-size:0.7rem">No Receipt</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#fbbf24; font-size:0.75rem; font-weight:700">Cash on Delivery</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="order-details">
                                    <h4><?php echo htmlspecialchars($o['product_title']); ?></h4>
                                    <div style="font-size: 0.85rem; color: var(--accent); margin-bottom: 1rem;">Order ID: #<?php echo $o['id']; ?> • Rs. <?php echo number_format($o['total_price']); ?></div>
                                    
                                    <div class="order-buyer-box">
                                        <div class="info-item">
                                            <span class="info-label">Customer</span>
                                            <span class="info-value"><?php echo htmlspecialchars($o['buyer_name']); ?></span>
                                            <span class="info-value" style="color:var(--secondary); font-size:0.75rem"><?php echo htmlspecialchars($o['buyer_email']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Contact & Address</span>
                                            <span class="info-value"><?php echo htmlspecialchars($o['phone_number']); ?></span>
                                            <span class="info-value"><?php echo htmlspecialchars($o['shipping_address']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-status-actions">
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                                        <div style="text-align: right">
                                            <span class="info-label" style="display:block; margin-bottom:0.2rem">Payment</span>
                                            <span class="badge" style="<?php echo ($o['payment_method'] === 'COD') ? 'background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3);' : 'background: rgba(96, 187, 70, 0.15); color: #60bb46; border: 1px solid rgba(96, 187, 70, 0.3);'; ?> font-size: 0.7rem;">
                                                <?php echo htmlspecialchars($o['payment_method']); ?>
                                            </span>
                                        </div>
                                        <div style="text-align: right">
                                            <span class="info-label" style="display:block; margin-bottom:0.2rem">Status</span>
                                            <span class="status-badge <?php echo 'status-' . str_replace(' ', '-', $o['status']); ?>">
                                                <?php echo ucfirst($o['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" id="orderForm_<?php echo $o['id']; ?>">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <input type="hidden" name="cancellation_message" id="cancelMsg_<?php echo $o['id']; ?>" value="">
                                        <select name="status" class="form-control" style="padding:0.6rem; font-size:0.8rem; background: rgba(255,255,255,0.08);" onchange="handleStatusChange(this, <?php echo $o['id']; ?>)">
                                            <option value="order received" <?php echo $o['status'] == 'order received' ? 'selected' : ''; ?>>Received</option>
                                            <option value="pending" <?php echo $o['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="completed" <?php echo $o['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $o['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>

                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--secondary)">No orders yet. Keep creating!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <div class="modal" id="addModal"><div class="modal-content"><h2>Upload Work</h2><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add_product"><div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required></div><div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div><div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem"><div class="form-group"><label>Price (Rs.)</label><input type="number" name="price" class="form-control" required min="0"></div><div class="form-group"><label>Stock Count</label><input type="number" name="stock_count" class="form-control" required min="1" value="1"></div></div>
    <div class="form-group"><label>Tags</label>
        <div class="tag-chips">
            <?php foreach(['Abstract','Digital Art','Oil Painting','Portrait','Landscape','Modern','Minimalist','Vibrant','Surrealism','Photorealism','Graffiti','Cyberpunk','Nature','Mythology','Geometric','Vintage','Typography','Dark Art'] as $t): ?>
            <label class="tag-chip"><input type="checkbox" name="tags[]" value="<?php echo $t; ?>" class="tag-checkbox" onchange="this.parentElement.classList.toggle('active', this.checked)"><?php echo $t; ?></label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="form-group"><label>Image</label><input type="file" name="product_image" class="form-control" required></div><button type="submit" class="btn btn-primary" style="width:100%">Upload</button><button type="button" class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('active')" style="width:100%; margin-top:0.5rem">Cancel</button></form></div></div>

    <div class="modal" id="editModal"><div class="modal-content"><h2>Edit Work</h2><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="edit_product"><input type="hidden" name="product_id" id="editId"><div class="form-group"><label>Title</label><input type="text" name="title" id="editTitle" class="form-control" required></div><div class="form-group"><label>Description</label><textarea name="description" id="editDesc" class="form-control"></textarea></div><div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem"><div class="form-group"><label>Price (Rs.)</label><input type="number" name="price" id="editPrice" class="form-control" required min="0"></div><div class="form-group"><label>Stock Count</label><input type="number" name="stock_count" id="editStock" class="form-control" required min="0"></div></div>
    <div class="form-group"><label>Tags</label>
        <div class="tag-chips" id="editTags">
            <?php foreach(['Abstract','Digital Art','Oil Painting','Portrait','Landscape','Modern','Minimalist','Vibrant','Surrealism','Photorealism','Graffiti','Cyberpunk','Nature','Mythology','Geometric','Vintage','Typography','Dark Art'] as $t): ?>
            <label class="tag-chip"><input type="checkbox" name="tags[]" value="<?php echo $t; ?>" class="tag-checkbox" onchange="this.parentElement.classList.toggle('active', this.checked)"><?php echo $t; ?></label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="form-group"><label>Replace Image (Optional)</label><input type="file" name="product_image" class="form-control"></div><button type="submit" class="btn btn-primary" style="width:100%">Save</button><button type="button" class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('active')" style="width:100%; margin-top:0.5rem">Cancel</button></form></div></div>

    <script>
        function handleFileSelect(input, previewId, nameId, originalText) {
            const preview = document.getElementById(previewId);
            const nameSpan = document.getElementById(nameId);
            const uploadText = input.parentElement.querySelector('.upload-text');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                nameSpan.textContent = file.name;
                uploadText.textContent = 'File selected:';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                nameSpan.textContent = '';
                uploadText.textContent = originalText;
                preview.style.display = 'none';
                preview.src = '';
            }
            validateForm();
        }

        function validateForm() {
            const form = document.getElementById('verificationForm');
            if (!form) return;
            
            const fullName = document.getElementById('v_full_name').value.trim();
            const mobile = document.getElementById('v_mobile').value.trim();
            const address = document.getElementById('v_address').value.trim();
            const esewa = document.getElementById('v_esewa').value.trim();
            const citizenship = document.getElementById('v_citizenship').files.length > 0;
            const selfie = document.getElementById('v_selfie').files.length > 0;
            
            const mobileHint = document.getElementById('v_mobile_hint');
            const esewaHint = document.getElementById('v_esewa_hint');
            
            // Validate first digit is 9 and length is 10
            const isMobileValid = mobile.length === 10 && mobile[0] === '9';
            const isEsewaValid = esewa.length === 10 && esewa[0] === '9';
            
            if (mobile.length > 0) {
                mobileHint.textContent = isMobileValid ? "" : "Must start with 9 and be 10 digits";
                mobileHint.className = "num-hint " + (isMobileValid ? "success" : "error");
            } else { mobileHint.textContent = ""; }

            if (esewa.length > 0) {
                esewaHint.textContent = isEsewaValid ? "" : "Must start with 9 and be 10 digits";
                esewaHint.className = "num-hint " + (isEsewaValid ? "success" : "error");
            } else { esewaHint.textContent = ""; }

            const submitBtn = document.getElementById('submitVerification');
            const isValid = fullName !== "" && address !== "" && isMobileValid && isEsewaValid && citizenship && selfie;
            
            if (isValid) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
                submitBtn.style.cursor = "pointer";
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.5";
                submitBtn.style.cursor = "not-allowed";
            }
        }

        function toggleSameAs(mobileId, esewaId, checkbox) {
            const mobile = document.getElementById(mobileId);
            const esewa = document.getElementById(esewaId);
            if (checkbox.checked) {
                esewa.value = mobile.value;
                esewa.readOnly = true;
                esewa.style.opacity = "0.6";
            } else {
                esewa.readOnly = false;
                esewa.style.opacity = "1";
                esewa.value = "";
            }
            validateForm();
        }

        function syncEsewaDirect(mobileId, esewaId, checkboxId) {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox && checkbox.checked) {
                const mobile = document.getElementById(mobileId);
                const esewa = document.getElementById(esewaId);
                esewa.value = mobile.value;
            }
        }

        function toggleSameAsVerified(checkbox) {
            const esewaInput = document.getElementById('ue_esewa');
            const artistMobile = "<?php echo htmlspecialchars($verification['mobile'] ?? ''); ?>";
            if (checkbox.checked) {
                esewaInput.value = artistMobile;
                esewaInput.readOnly = true;
            } else {
                esewaInput.readOnly = false;
            }
        }

        // Add event listeners for live validation
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('verificationForm');
            if (form) {
                ['v_full_name', 'v_mobile', 'v_address', 'v_esewa'].forEach(id => {
                    document.getElementById(id).addEventListener('input', validateForm);
                });
            }
        });

        function openEdit(p){
            document.getElementById('editId').value = p.id;
            document.getElementById('editTitle').value = p.title;
            document.getElementById('editDesc').value = p.description;
            document.getElementById('editPrice').value = p.price;
            document.getElementById('editStock').value = p.stock_count;
            
            // Set tags
            const tags = p.tags ? p.tags.split(',') : [];
            const checkboxes = document.querySelectorAll('#editTags input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = tags.includes(cb.value);
                cb.parentElement.classList.toggle('active', cb.checked);
            });

            document.getElementById('editModal').classList.add('active');
        }
    </script>
    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>

    <div class="modal" id="cancelModal">
        <div class="modal-content">
            <h2>Cancel Order</h2>
            <p style="color:var(--secondary); margin-bottom:1rem">Please provide a reason for cancellation:</p>
            <form method="POST" id="cancelForm">
                <input type="hidden" name="action" value="update_order_status">
                <input type="hidden" name="order_id" id="cancelOrderId">
                <input type="hidden" name="cancellation_message" id="cancelMessageInput">
                <div class="form-group">
                    <label>Cancellation Message</label>
                    <textarea id="cancelMessage" class="form-control" rows="4" placeholder="Explain why you are cancelling this order..." required></textarea>
                </div>
                <button type="button" class="btn btn-primary" style="width:100%" onclick="submitCancellation()">Confirm Cancellation</button>
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()" style="width:100%; margin-top:0.5rem">Go Back</button>
            </form>
        </div>
    </div>

    <script>
    let pendingOrderSelect = null;
    let pendingOrderId = null;

    function handleStatusChange(select, orderId) {
        if (select.value === 'cancelled') {
            pendingOrderSelect = select;
            pendingOrderId = orderId;
            document.getElementById('cancelOrderId').value = orderId;
            document.getElementById('cancelMessage').value = '';
            document.getElementById('cancelModal').classList.add('active');
        } else {
            select.form.submit();
        }
    }

    function submitCancellation() {
        const message = document.getElementById('cancelMessage').value.trim();
        if (!message) {
            alert('Please enter a cancellation message');
            return;
        }
        // Set the cancellation message on the ORDER FORM's hidden input
        const hiddenInput = document.getElementById('cancelMsg_' + pendingOrderId);
        if (hiddenInput) {
            hiddenInput.value = message;
            // Force the name attribute to ensure it's submitted
            hiddenInput.setAttribute('name', 'cancellation_message');
        }
        document.getElementById('cancelModal').classList.remove('active');
        if (pendingOrderSelect) {
            // Small delay to ensure DOM is updated before submit
            setTimeout(function() {
                pendingOrderSelect.form.submit();
            }, 50);
        }
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.remove('active');
        if (pendingOrderSelect) {
            pendingOrderSelect.value = 'order received';
            pendingOrderSelect = null;
            pendingOrderId = null;
        }
    }
    </script>
</body>
</html>
