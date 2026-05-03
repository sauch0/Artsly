<?php
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
include 'database/dbconnect.php';
$user = $_SESSION['user'];
$user_id = intval($user['id']);

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$items = [];
$total = 0;

if ($product_id > 0) {
    // Single product buy now
    $res = $conn->query("SELECT p.*, u.name as artist_name, av.esewa_number 
                         FROM products p 
                         JOIN users u ON p.user_id = u.id 
                         LEFT JOIN artist_verification av ON p.user_id = av.user_id
                         WHERE p.id = '$product_id'");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $row['qty'] = 1;
        $items[] = $row;
    }
} else {
    // Buy all from cart
    $res = $conn->query("SELECT p.*, c.quantity, u.name as artist_name, av.esewa_number 
                         FROM cart c 
                         JOIN products p ON c.product_id = p.id 
                         JOIN users u ON p.user_id = u.id 
                         LEFT JOIN artist_verification av ON p.user_id = av.user_id
                         WHERE c.user_id = '$user_id'");
    while ($row = $res->fetch_assoc()) {
        $row['qty'] = $row['quantity'];
        $items[] = $row;
    }
}

if (empty($items)) {
    header('Location: landing.php');
    exit;
}

foreach ($items as $i) $total += ($i['price'] * $i['qty']);

// Group items by artist for multi-vendor payment handling
$artist_groups = [];
foreach ($items as $item) {
    $aid = $item['user_id'];
    if (!isset($artist_groups[$aid])) {
        $artist_groups[$aid] = [
            'artist_name' => $item['artist_name'],
            'esewa_number' => $item['esewa_number'] ?? 'Not provided',
            'items' => [],
            'subtotal' => 0
        ];
    }
    $artist_groups[$aid]['items'][] = $item;
    $artist_groups[$aid]['subtotal'] += ($item['price'] * $item['qty']);
}

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'eSewa');
    
    if (strlen($phone) !== 10 || $phone[0] !== '9') {
        echo "<script>alert('Phone number must be 10 digits and start with 9'); window.history.back();</script>";
        exit;
    }
    
    // Process each artist group separately
    foreach ($artist_groups as $aid => $group) {
        $receipt_file = '';
        if ($payment_method === 'eSewa') {
            $file_key = 'receipt_' . $aid;
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === 0) {
                $ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $receipt_file = 'uploads/orders/receipt_' . $user_id . '_' . $aid . '_' . time() . '.' . $ext;
                if (!is_dir('uploads/orders')) mkdir('uploads/orders', 0777, true);
                move_uploaded_file($_FILES[$file_key]['tmp_name'], $receipt_file);
            } else {
                echo "<script>alert('Please upload payment receipt for artist: " . addslashes($group['artist_name']) . "'); window.history.back();</script>";
                exit;
            }
        }

        foreach ($group['items'] as $item) {
            $pid = $item['id'];
            $qty = $item['qty'];
            $price = $item['price'] * $qty;
            $sql = "INSERT INTO orders (user_id, product_id, artist_id, shipping_address, phone_number, total_price, payment_method, receipt_image)
                    VALUES ('$user_id', '$pid', '$aid', '$address', '$phone', '$price', '$payment_method', '$receipt_file')";
            $conn->query($sql);
            // Decrease stock count
            $conn->query("UPDATE products SET stock_count = GREATEST(0, stock_count - $qty) WHERE id = '$pid'");
        }
    }

    // Clear cart if it was a cart checkout
    if ($product_id == 0) {
        $conn->query("DELETE FROM cart WHERE user_id = '$user_id'");
    }

    echo "<script>alert('Order placed successfully!'); window.location.href='orders.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Artsly</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .checkout-container { max-width: 1000px; margin: 0 auto; padding: 8rem 5% 4rem; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; }
        .card { background: var(--glass-bg); padding: 2.5rem; border-radius: 24px; border: 1px solid var(--glass-border); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-control { width: 100%; padding: 1rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 12px; color: white; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent); background: rgba(255,255,255,0.1); }
        .esewa-box { background: #60bb46; color: white; padding: 2rem; border-radius: 20px; margin-bottom: 2rem; text-align: center; }
        .pay-option { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 12px; cursor: pointer; transition: 0.3s; margin-bottom: 1rem; }
        .pay-option:hover { background: rgba(255,255,255,0.06); }
        .pay-option input { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }
        .pay-option.active { border-color: var(--accent); background: rgba(99, 102, 241, 0.08); }
    </style>
</head>
<body>
    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>
    <header>
        <nav class="navbar">
            <div class="logo" onclick="window.location.href='landing.php'" style="cursor:pointer">Artsly</div>
        </nav>
    </header>

    <main class="checkout-container">
        <div>
            <h1>Final Steps</h1>
            <p style="color:var(--secondary); margin-bottom:3rem">Provide your shipping details and complete the payment.</p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="place_order">
                <div class="card">
                    <h2>Shipping Information</h2>
                    <div class="form-group"><label>Shipping Address (Max 20 chars)</label><textarea name="address" class="form-control" required maxlength="20"></textarea></div>
                    <div class="form-group"><label>Phone Number <span style="color:var(--secondary);font-size:0.8rem">(starts with 9, 10 digits)</span></label><input type="text" name="phone" id="checkout_phone" class="form-control" required maxlength="10" minlength="10" pattern="9[0-9]{9}" title="10-digit mobile number starting with 9" oninput="this.value = this.value.replace(/[^0-9]/g, '')"></div>
                </div>

                <div class="card">
                    <h2>Payment Options</h2>
                    <label class="pay-option active">
                        <input type="radio" name="payment_method" value="eSewa" checked onchange="updatePayment('eSewa')">
                        <span>Digital Payment (eSewa)</span>
                    </label>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="COD" onchange="updatePayment('COD')">
                        <span>Cash on Delivery (COD)</span>
                    </label>

                    <div id="esewaFields" style="margin-top: 2rem;">
                        <p style="margin-bottom:1.5rem; font-size:0.9rem; color:var(--secondary)">Please transfer the respective amounts to each artist's eSewa ID shown on the right, then upload your transaction receipts below.</p>
                        <?php foreach($artist_groups as $aid => $group): ?>
                            <div class="form-group" style="padding:1rem; background:rgba(255,255,255,0.03); border-radius:12px; border:1px solid var(--glass-border); margin-bottom:1rem">
                                <label style="display:block; margin-bottom:0.5rem; font-weight:600">Receipt for <?php echo htmlspecialchars($group['artist_name']); ?></label>
                                <p style="font-size:0.8rem; color:var(--accent); margin-bottom:0.8rem">Pay Rs. <?php echo number_format($group['subtotal']); ?> to <?php echo htmlspecialchars($group['esewa_number']); ?></p>
                                <input type="file" name="receipt_<?php echo $aid; ?>" class="form-control receipt-input" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="codFields" style="display:none; margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                        <p style="color:var(--secondary); font-size:0.9rem">Pay Rs. <?php echo number_format($total); ?> directly to our courier when your art arrives.</p>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem; margin-top:1.5rem">Complete Transaction</button>
                </div>
            </form>
        </div>

        <div>
            <div class="card">
                <h2>Order Summary</h2>
                <?php foreach($artist_groups as $aid => $group): ?>
                <div style="margin-bottom:2rem; padding:1.5rem; background:rgba(255,255,255,0.03); border-radius:16px; border:1px solid var(--glass-border)">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.5rem">
                        <span style="font-weight:700; color:var(--accent)"><?php echo htmlspecialchars($group['artist_name']); ?></span>
                        <span style="font-size:0.85rem; color:#60bb46; font-weight:700">eSewa: <?php echo htmlspecialchars($group['esewa_number']); ?></span>
                    </div>
                    <?php foreach($group['items'] as $item): ?>
                    <div style="display:flex; justify-content:space-between; margin-bottom:0.8rem">
                        <div style="font-size:0.9rem"><?php echo htmlspecialchars($item['title']); ?> (x<?php echo $item['qty']; ?>)</div>
                        <div style="font-size:0.9rem; color:rgba(255,255,255,0.6)">Rs. <?php echo number_format($item['price'] * $item['qty']); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <div style="display:flex; justify-content:space-between; margin-top:1rem; padding-top:0.5rem; border-top:1px solid rgba(255,255,255,0.05); font-weight:700">
                        <span>Subtotal</span>
                        <span>Rs. <?php echo number_format($group['subtotal']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="display:flex; justify-content:space-between; font-size:1.5rem; font-weight:700; margin-top:2rem">
                    <span>Total</span>
                    <span style="color:var(--accent)">Rs. <?php echo number_format($total); ?></span>
                </div>
            </div>
            
        </div>
    </main>

    <script>
        function updatePayment(method) {
            // Update UI active state
            document.querySelectorAll('.pay-option').forEach(el => {
                const radio = el.querySelector('input');
                if (radio.checked) el.classList.add('active');
                else el.classList.remove('active');
            });

            // Toggle field visibility
            const esewaFields = document.getElementById('esewaFields');
            const codFields = document.getElementById('codFields');
            const receiptInputs = document.querySelectorAll('.receipt-input');

            if (method === 'eSewa') {
                esewaFields.style.display = 'block';
                codFields.style.display = 'none';
                receiptInputs.forEach(input => input.required = true);
            } else {
                esewaFields.style.display = 'none';
                codFields.style.display = 'block';
                receiptInputs.forEach(input => {
                    input.required = false;
                    input.value = '';
                });
            }
        }

        // Initialize state
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.receipt-input').forEach(input => input.required = true);
        });
    </script>
    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
