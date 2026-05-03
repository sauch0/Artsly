<?php
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
include 'database/dbconnect.php';
$user = $_SESSION['user'];
$user_id = intval($user['id']);

// Fetch products
$searchQuery = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$filterTags = isset($_GET['tags']) && is_array($_GET['tags']) ? array_map(function($t) use ($conn) { return mysqli_real_escape_string($conn, $t); }, $_GET['tags']) : [];

$sql = "SELECT p.*, u.name as artist_name FROM products p JOIN users u ON p.user_id = u.id";
$conditions = [];

if ($searchQuery) {
    $keywords = explode(' ', $searchQuery);
    foreach ($keywords as $word) {
        $word = trim($word);
        if ($word !== '') {
            $conditions[] = "(p.title LIKE '%$word%' OR p.tags LIKE '%$word%' OR u.name LIKE '%$word%')";
        }
    }
}

if (!empty($filterTags)) {
    $tagConditions = [];
    foreach ($filterTags as $tag) {
        $tagConditions[] = "p.tags LIKE '%$tag%'";
    }
    $conditions[] = "(" . implode(' OR ', $tagConditions) . ")";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

if ($sort === 'popular') {
    $sql .= " ORDER BY p.views DESC";
} else {
    $sql .= " ORDER BY p.created_at DESC";
}

$productsResult = $conn->query($sql);
$products = [];
while ($row = $productsResult->fetch_assoc()) {
    $products[] = $row;
}

// Get all tags for filtering
$allTags = ['Abstract','Digital Art','Oil Painting','Portrait','Landscape','Modern','Minimalist','Vibrant','Surrealism','Photorealism','Graffiti','Cyberpunk','Nature','Mythology','Geometric','Vintage','Typography','Dark Art'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Artsly | <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="stylesheet" href="index.css">
    <style>
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 8rem 5% 4rem; }
        .user-welcome { text-align: center; margin-bottom: 4rem; }
        .user-welcome h1 { font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: 1rem; letter-spacing: -2px; }
        .discovery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem; margin-top: 3rem; }
        .art-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 24px; overflow: hidden; transition: var(--transition); }
        .art-card:hover { transform: translateY(-10px); border-color: var(--accent); }
        .art-card img { width: 100%; height: 300px; object-fit: cover; }
        .art-card-body { padding: 1.5rem; }
        .art-card-tags { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
        .tag { font-size: 0.7rem; padding: 0.2rem 0.6rem; background: rgba(99,102,241,0.1); color: var(--accent); border-radius: 100px; border: 1px solid rgba(99,102,241,0.2); }
        .price { font-size: 1.2rem; font-weight: 700; color: var(--accent); margin-bottom: 0.5rem; }
        /* Discovery Tabs & Search Improvements */
        .discovery-tabs { display: flex; gap: 1.5rem; margin-bottom: 3rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem; align-items: center; }
        .tab-btn { background: none; border: none; color: var(--secondary); font-size: 1.1rem; font-weight: 500; cursor: pointer; padding: 0.5rem 0; transition: 0.3s; position: relative; text-decoration: none; }
        .tab-btn.active { color: var(--accent); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -1.1rem; left: 0; width: 100%; height: 3px; background: var(--accent); border-radius: 10px; }
        
        .search-container { flex: 1; max-width: 500px; position: relative; }
        .search-container input { width: 100%; padding: 0.9rem 1.5rem; padding-right: 6.5rem; background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 100px; color: white; font-family: inherit; transition: 0.4s cubic-bezier(0.4,0,0.2,1); backdrop-filter: blur(10px); }
        .search-container input:focus { outline: none; border-color: var(--accent); background: rgba(255,255,255,0.07); box-shadow: 0 0 30px rgba(99,102,241,0.15); transform: scale(1.02); }
        .search-btn { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: var(--accent); border: none; color: white; padding: 0.6rem 1.2rem; border-radius: 100px; cursor: pointer; transition: 0.3s; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; }
        .search-btn:hover { background: #4f46e5; transform: translateY(-50%) scale(1.05); }
        .clear-search { position: absolute; right: 6rem; top: 50%; transform: translateY(-50%); color: var(--secondary); font-size: 0.85rem; text-decoration: none; transition: 0.3s; margin-right: 10px;}
        .clear-search:hover { color: white; }

        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(12px); }
        .modal.show { display: flex; }
        .modal-content { background: #111; border: 1px solid var(--glass-border); padding: 3rem; border-radius: 32px; width: 90%; max-width: 600px; box-shadow: 0 50px 100px rgba(0,0,0,0.8); }
        .tag-chips { display: flex; flex-wrap: wrap; gap: 0.8rem; margin: 2rem 0; }
        .tag-chip { padding: 0.6rem 1.2rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 100px; cursor: pointer; transition: 0.3s; font-size: 0.9rem; user-select: none; }
        .tag-chip:hover { background: rgba(255,255,255,0.1); border-color: var(--secondary); }
        .tag-chip.active { background: var(--accent); border-color: var(--accent); color: white; }
        .tag-checkbox, .sort-radio { display: none; }

        /* Sort Options */
        .sort-options { display: flex; gap: 1rem; margin-top: 1rem; }
        .sort-option { padding: 0.6rem 1.2rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 100px; cursor: pointer; transition: 0.3s; font-size: 0.9rem; user-select: none; flex: 1; text-align: center; }
        .sort-option:hover { background: rgba(255,255,255,0.1); border-color: var(--secondary); }
        .sort-option.active { background: var(--accent); border-color: var(--accent); color: white; }
    </style>
</head>
<body>
    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>
    <header>
        <nav class="navbar">
            <div class="logo" onclick="window.location.href='landing.php'" style="cursor:pointer">Artsly</div>
            
            <form action="landing.php" method="GET" class="search-container">
                <input type="text" name="q" placeholder="Search by title, tag or artist..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <?php if($searchQuery): ?><a href="landing.php" class="clear-search">Clear</a><?php endif; ?>
                <button type="submit" class="search-btn">Search</button>
            </form>

            <div class="auth-buttons">
                <?php if($user['role'] === 'artist'): ?><a href="artist_dashboard.php" class="btn btn-secondary" style="margin-right:1rem">Artist Studio</a><?php endif; ?>
                <a href="cart.php" class="btn btn-secondary" style="margin-right:1rem">Cart</a>
                <a href="orders.php" class="btn btn-secondary" style="margin-right:1rem">My Orders</a>
                <a href="logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </nav>
    </header>

    <main class="dashboard-container">
        <!-- <div class="user-welcome">
            <h1>Discovery Stream</h1>
            <p>Visionary creations tailored to your aesthetic preference</p>
        </div> -->

        <div class="discovery-tabs">
            <?php 
                $tagParams = "";
                foreach($filterTags as $t) {
                    $tagParams .= "&tags[]=" . urlencode($t);
                }
            ?>
            <a href="landing.php?sort=popular<?php echo $searchQuery ? '&q='.urlencode($searchQuery) : ''; ?><?php echo $tagParams; ?>" class="tab-btn <?php echo $sort === 'popular' ? 'active' : ''; ?>">Popular</a>
            <a href="landing.php?sort=latest<?php echo $searchQuery ? '&q='.urlencode($searchQuery) : ''; ?><?php echo $tagParams; ?>" class="tab-btn <?php echo $sort === 'latest' ? 'active' : ''; ?>">Latest</a>
            <button class="tab-btn <?php echo !empty($filterTags) ? 'active' : ''; ?>" onclick="document.getElementById('filterModal').classList.add('show')">
                Filter <?php echo !empty($filterTags) ? "(".count($filterTags).")" : ''; ?>
            </button>
        </div>

        <div class="discovery-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 4rem; opacity: 0.5;">
                    <h3>No visions found matching your criteria.</h3>
                    <p>Try broadening your filter or search.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
            <div class="art-card">
                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
                <div class="art-card-body">
                    <div class="art-card-tags">
                        <?php foreach(explode(',', $p['tags']) as $tag): if($tag): ?>
                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endif; endforeach; ?>
                    </div>
                    <div class="price">Rs. <?php echo number_format($p['price']); ?></div>
                    <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                    <p style="color:var(--secondary); font-size:0.85rem; margin-bottom:1rem">By <?php echo htmlspecialchars($p['artist_name']); ?></p>
                    <a href="product_details.php?id=<?php echo $p['id']; ?>" class="btn btn-primary" style="width:100%; display:block; text-align:center; padding:0.6rem">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>


    <div class="modal" id="filterModal">
        <div class="modal-content">
            <h2 style="letter-spacing:-1px">Filter Visions</h2>
            <p style="color:var(--secondary); font-size:0.9rem">Select multiple tags to refine your discovery stream.</p>
            
            <form action="landing.php" method="GET">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <?php if($searchQuery): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <?php endif; ?>

                <div class="tag-chips">
                    <?php foreach($allTags as $t): ?>
                    <label class="tag-chip <?php echo in_array($t, $filterTags) ? 'active' : ''; ?>">
                        <input type="checkbox" name="tags[]" value="<?php echo $t; ?>" class="tag-checkbox" 
                               <?php echo in_array($t, $filterTags) ? 'checked' : ''; ?>
                               onchange="this.parentElement.classList.toggle('active', this.checked)">
                        <?php echo $t; ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <h4 style="margin-top:2rem; margin-bottom:1rem">Sort By</h4>
                <div class="sort-options">
                    <label class="sort-option <?php echo $sort === 'popular' ? 'active' : ''; ?>">
                        <input type="radio" name="sort" value="popular" class="sort-radio" <?php echo $sort === 'popular' ? 'checked' : ''; ?> onchange="document.querySelectorAll('.sort-option').forEach(el => el.classList.remove('active')); this.parentElement.classList.add('active')">
                        Popularity
                    </label>
                    <label class="sort-option <?php echo $sort === 'latest' ? 'active' : ''; ?>">
                        <input type="radio" name="sort" value="latest" class="sort-radio" <?php echo $sort === 'latest' ? 'checked' : ''; ?> onchange="document.querySelectorAll('.sort-option').forEach(el => el.classList.remove('active')); this.parentElement.classList.add('active')">
                        Latest
                    </label>
                </div>

                <div style="display:flex; gap:1rem; margin-top:2rem">
                    <button type="submit" class="btn btn-primary" style="flex:2">Apply Filters</button>
                    <button type="button" class="btn btn-secondary" style="flex:1" onclick="document.getElementById('filterModal').classList.remove('show')">Cancel</button>
                </div>
                <a href="landing.php?sort=<?php echo $sort; ?><?php echo $searchQuery ? '&q='.urlencode($searchQuery) : ''; ?>" 
                   style="display:block; text-align:center; margin-top:1.5rem; color:var(--secondary); text-decoration:none; font-size:0.9rem">Clear All Filters</a>
            </form>
        </div>
    </div>

    <footer><p>&copy; 2026 Artsly. By Saumya Chitrakar</p></footer>
</body>
</html>
