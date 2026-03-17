<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../include/trends.php';

// ── Auth ───────────────────────────────────────────────────────────────────────
// Admin is restricted to a single hardcoded account.
define('ADMIN_USERNAME', 'jack');

if (!isset($_SESSION['username']) || $_SESSION['username'] !== ADMIN_USERNAME) {
    http_response_code(403);
    // Show a plain 403 — don't leak the admin username
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <link href="/src/styles/styles.css" rel="stylesheet">
    <link href="/src/styles/timeline.css" rel="stylesheet">
    <title>403 Forbidden</title></head>
    <body><div id="feed" class="settingsPageContainer">
    <div class="title"><p class="selected">403 Forbidden</p></div>
    <div id="noMoreChirps"><p class="subText">You are not allowed to access this page.</p>
    <a class="followButton following" href="/">Go home</a></div>
    </div></body></html>';
    exit;
}

// ── Security headers ───────────────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

// ── CSRF token ─────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Helpers ────────────────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function ts(int $t): string {
    return date('Y-m-d H:i', $t);
}

// ── Database ───────────────────────────────────────────────────────────────────
try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<p>Database error.</p>';
    exit;
}

// ── AJAX / POST actions ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // All POST actions verify CSRF
    $tok = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $tok)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ── Test Ollama connection ─────────────────────────────────────────────────
    if ($action === 'test_ollama') {
        $url = rtrim(OLLAMA_HOST, '/') . '/api/tags';
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            echo json_encode(['error' => 'Could not connect to ' . OLLAMA_HOST]);
            exit;
        }
        $data = json_decode($raw, true);
        $models = array_column($data['models'] ?? [], 'name');
        $embedModelPresent = in_array(OLLAMA_EMBED_MODEL, $models, true);
        // also check partial match (model might have :latest suffix stripped)
        if (!$embedModelPresent) {
            foreach ($models as $m) {
                if (strpos($m, explode(':', OLLAMA_EMBED_MODEL)[0]) === 0) {
                    $embedModelPresent = true;
                    break;
                }
            }
        }
        $modelStr = implode(', ', array_slice($models, 0, 10));
        if (!$embedModelPresent && !empty($models)) {
            $modelStr .= ' ⚠️ embed model "' . OLLAMA_EMBED_MODEL . '" not found — run: ollama pull ' . OLLAMA_EMBED_MODEL;
        } elseif (empty($models)) {
            $modelStr = '(no models pulled yet) — run: ollama pull ' . OLLAMA_EMBED_MODEL;
        }
        echo json_encode(['ok' => true, 'models' => $modelStr]);
        exit;
    }

    // ── Generate invite code ───────────────────────────────────────────────────
    if ($action === 'generate_invite') {
        $raw  = strtoupper(bin2hex(random_bytes(6)));
        $code = substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
        echo json_encode(['code' => $code]);
        exit;
    }

    // ── Save invite code ───────────────────────────────────────────────────────
    if ($action === 'save_invite') {
        $code = trim($_POST['code'] ?? '');
        if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
            echo json_encode(['error' => 'Invalid code format']);
            exit;
        }
        try {
            $stmt = $db->prepare('INSERT INTO invites (invite) VALUES (:invite)');
            $stmt->execute([':invite' => $code]);
            echo json_encode(['ok' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Code already exists']);
        }
        exit;
    }

    // ── Delete post ────────────────────────────────────────────────────────────
    if ($action === 'delete_post') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['error' => 'Invalid ID']); exit; }

        // Verify post exists
        $check = $db->prepare('SELECT id FROM chirps WHERE id = :id');
        $check->execute([':id' => $id]);
        if (!$check->fetch()) { echo json_encode(['error' => 'Post not found']); exit; }

        // Cascade delete replies first (one level deep — delete their engagement too)
        $replies = $db->prepare('SELECT id FROM chirps WHERE parent = :id');
        $replies->execute([':id' => $id]);
        foreach ($replies->fetchAll(PDO::FETCH_COLUMN) as $rid) {
            $db->prepare('DELETE FROM likes WHERE chirp_id = :id')->execute([':id' => $rid]);
            $db->prepare('DELETE FROM rechirps WHERE chirp_id = :id')->execute([':id' => $rid]);
        }
        $db->prepare('DELETE FROM chirps WHERE parent = :id')->execute([':id' => $id]);

        // Delete engagement on the post itself
        $db->prepare('DELETE FROM likes WHERE chirp_id = :id')->execute([':id' => $id]);
        $db->prepare('DELETE FROM rechirps WHERE chirp_id = :id')->execute([':id' => $id]);

        // Delete notifications referencing this post (if table exists)
        try {
            $db->prepare('DELETE FROM notifications WHERE chirp_id = :id')->execute([':id' => $id]);
        } catch (PDOException $e) { /* table may not exist */ }

        // Delete the post
        $db->prepare('DELETE FROM chirps WHERE id = :id')->execute([':id' => $id]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // ── Delete user ────────────────────────────────────────────────────────────
    if ($action === 'delete_user') {
        $username = trim($_POST['username'] ?? '');
        if ($username === '' || $username === ADMIN_USERNAME) {
            echo json_encode(['error' => 'Cannot delete this account']);
            exit;
        }

        $uStmt = $db->prepare('SELECT id FROM users WHERE username = :u');
        $uStmt->execute([':u' => $username]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) { echo json_encode(['error' => 'User not found']); exit; }

        $uid = (int)$user['id'];

        // Get all post IDs owned by this user
        $postIds = $db->prepare('SELECT id FROM chirps WHERE user = :uid');
        $postIds->execute([':uid' => $uid]);
        foreach ($postIds->fetchAll(PDO::FETCH_COLUMN) as $pid) {
            // Cascade replies
            $repIds = $db->prepare('SELECT id FROM chirps WHERE parent = :pid');
            $repIds->execute([':pid' => $pid]);
            foreach ($repIds->fetchAll(PDO::FETCH_COLUMN) as $rid) {
                $db->prepare('DELETE FROM likes WHERE chirp_id = :id')->execute([':id' => $rid]);
                $db->prepare('DELETE FROM rechirps WHERE chirp_id = :id')->execute([':id' => $rid]);
            }
            $db->prepare('DELETE FROM chirps WHERE parent = :pid')->execute([':pid' => $pid]);
            $db->prepare('DELETE FROM likes WHERE chirp_id = :id')->execute([':id' => $pid]);
            $db->prepare('DELETE FROM rechirps WHERE chirp_id = :id')->execute([':id' => $pid]);
            try {
                $db->prepare('DELETE FROM notifications WHERE chirp_id = :id')->execute([':id' => $pid]);
            } catch (PDOException $e) {}
        }
        // Delete user's chirps
        $db->prepare('DELETE FROM chirps WHERE user = :uid')->execute([':uid' => $uid]);

        // Delete likes/rechirps left by this user on others' posts
        $db->prepare('DELETE FROM likes WHERE user_id = :uid')->execute([':uid' => $uid]);
        $db->prepare('DELETE FROM rechirps WHERE user_id = :uid')->execute([':uid' => $uid]);

        // Delete following relationships
        $db->prepare('DELETE FROM following WHERE follower_id = :uid OR following_id = :uid')->execute([':uid' => $uid]);

        // Delete messages
        $db->prepare('DELETE FROM messages WHERE `from` = :u OR `to` = :u')->execute([':u' => $username]);

        // Delete API keys, OAuth tokens (if tables exist)
        foreach (['api_keys', 'oauth_tokens', 'oauth_codes'] as $tbl) {
            try {
                $db->prepare("DELETE FROM $tbl WHERE user_id = :uid")->execute([':uid' => $uid]);
            } catch (PDOException $e) {}
        }
        try {
            $db->prepare('DELETE FROM oauth_apps WHERE owner_user_id = :uid')->execute([':uid' => $uid]);
        } catch (PDOException $e) {}

        // Delete notifications for/from this user
        try {
            $db->prepare('DELETE FROM notifications WHERE user_id = :uid OR actor_id = :uid')->execute([':uid' => $uid]);
        } catch (PDOException $e) {}

        // Finally delete the user
        $db->prepare('DELETE FROM users WHERE id = :uid')->execute([':uid' => $uid]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // ── Migrate username ───────────────────────────────────────────────────────
    if ($action === 'migrate_user') {
        $from = trim($_POST['from'] ?? '');
        $to   = trim($_POST['to']   ?? '');
        if ($from === '' || $to === '') { echo json_encode(['error' => 'Both fields required']); exit; }
        if (!preg_match('/^[a-zA-Z0-9_]{1,50}$/', $to)) {
            echo json_encode(['error' => 'Invalid username format (letters, numbers, _ only)']);
            exit;
        }
        $check = $db->prepare('SELECT id FROM users WHERE username = :u');
        $check->execute([':u' => $to]);
        if ($check->fetch()) { echo json_encode(['error' => 'Username already taken']); exit; }

        $stmt = $db->prepare('UPDATE users SET username = :to WHERE username = :from');
        $stmt->execute([':to' => $to, ':from' => $from]);
        if ($stmt->rowCount() === 0) { echo json_encode(['error' => "User '$from' not found"]); exit; }
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}

// ── Stats ──────────────────────────────────────────────────────────────────────
$stats = [];
$stats['users']   = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['posts']   = (int)$db->query("SELECT COUNT(*) FROM chirps WHERE type = 'post'")->fetchColumn();
$stats['replies'] = (int)$db->query("SELECT COUNT(*) FROM chirps WHERE type = 'reply'")->fetchColumn();
$stats['likes']   = (int)$db->query("SELECT COUNT(*) FROM likes")->fetchColumn();
$stats['rechirps']= (int)$db->query("SELECT COUNT(*) FROM rechirps")->fetchColumn();
$stats['invites'] = (int)$db->query("SELECT COUNT(*) FROM invites WHERE reservedFor IS NULL")->fetchColumn();

// ── Recent users ───────────────────────────────────────────────────────────────
$recentUsers = $db->query("SELECT id, username, name, created_at FROM users ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// ── Recent posts ───────────────────────────────────────────────────────────────
$recentPosts = $db->query("
    SELECT c.id, c.chirp, c.timestamp, c.type, c.via, u.username
    FROM chirps c
    JOIN users u ON u.id = c.user
    ORDER BY c.timestamp DESC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

// ── Search ─────────────────────────────────────────────────────────────────────
$searchResults = [];
$searchType    = '';
$searchQuery   = '';
if (isset($_GET['q']) && isset($_GET['t'])) {
    $searchQuery = trim($_GET['q']);
    $searchType  = $_GET['t'] === 'users' ? 'users' : 'posts';
    if ($searchQuery !== '') {
        $like = '%' . $searchQuery . '%';
        if ($searchType === 'users') {
            $s = $db->prepare("SELECT id, username, name, created_at FROM users WHERE username LIKE :q OR name LIKE :q ORDER BY created_at DESC LIMIT 50");
            $s->execute([':q' => $like]);
        } else {
            $s = $db->prepare("SELECT c.id, c.chirp, c.timestamp, c.type, c.via, u.username FROM chirps c JOIN users u ON u.id = c.user WHERE c.chirp LIKE :q OR u.username LIKE :q ORDER BY c.timestamp DESC LIMIT 50");
            $s->execute([':q' => $like]);
        }
        $searchResults = $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <link href="/src/styles/styles.css" rel="stylesheet">
    <link href="/src/styles/timeline.css" rel="stylesheet">
    <link href="/src/styles/menus.css" rel="stylesheet">
    <link href="/src/styles/responsive.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>
    <script src="/src/scripts/general.js"></script>
    <title>Admin — NeoChirp</title>
    <style>
        .admin-stats { display:flex; gap:12px; flex-wrap:wrap; margin:16px 0; }
        .stat-card { background:var(--bg-secondary,#1a1a1a); border:1px solid var(--border,#333); border-radius:12px; padding:14px 20px; min-width:110px; text-align:center; }
        .stat-card .num { font-size:1.6rem; font-weight:700; }
        .stat-card .lbl { font-size:.75rem; color:#888; margin-top:2px; }
        .admin-table { width:100%; border-collapse:collapse; font-size:.85rem; }
        .admin-table th { text-align:left; padding:8px 10px; border-bottom:1px solid #333; color:#888; font-weight:600; }
        .admin-table td { padding:8px 10px; border-bottom:1px solid #222; vertical-align:top; word-break:break-word; }
        .admin-table tr:hover td { background:rgba(255,255,255,.03); }
        .chirp-text { max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .del-btn { background:#c0392b; color:#fff; border:none; border-radius:6px; padding:4px 10px; cursor:pointer; font-size:.8rem; }
        .del-btn:hover { background:#e74c3c; }
        .section-title { font-weight:700; font-size:1rem; margin:24px 0 10px; color:#fff; }
        .search-bar { display:flex; gap:8px; margin-bottom:16px; align-items:center; flex-wrap:wrap; }
        .search-bar input { flex:1; min-width:180px; padding:8px 12px; border-radius:8px; border:1px solid #333; background:#111; color:#fff; font-size:.9rem; }
        .search-bar select { padding:8px 10px; border-radius:8px; border:1px solid #333; background:#111; color:#fff; font-size:.9rem; }
        .search-bar button { padding:8px 16px; border-radius:8px; border:none; background:var(--accent,#1d9bf0); color:#fff; cursor:pointer; font-size:.9rem; }
        .tab-bar { display:flex; gap:4px; margin-bottom:20px; }
        .tab-btn { padding:8px 18px; border-radius:20px; border:1px solid #333; background:none; color:#aaa; cursor:pointer; font-size:.85rem; }
        .tab-btn.active { background:var(--accent,#1d9bf0); border-color:var(--accent,#1d9bf0); color:#fff; }
        .toast { position:fixed; bottom:24px; right:24px; background:#333; color:#fff; padding:12px 18px; border-radius:10px; font-size:.9rem; z-index:9999; display:none; }
        .toast.ok  { background:#27ae60; }
        .toast.err { background:#c0392b; }
        .invite-display { font-family:monospace; font-size:1.1rem; letter-spacing:.1em; padding:10px 14px; background:#111; border-radius:8px; border:1px solid #333; margin:10px 0; user-select:all; }
    </style>
</head>
<body>
<header>
    <div id="desktopMenu">
        <nav>
            <img src="/src/images/icons/chirp.svg" alt="Chirp" onclick="playChirpSound()">
            <a href="/"><img src="/src/images/icons/house.svg" alt=""> Home</a>
            <a href="/discover"><img src="/src/images/icons/search.svg" alt=""> Discover</a>
            <?php if (isset($_SESSION['username'])): ?>
            <a href="/notifications"><img src="/src/images/icons/bell.svg" alt=""> Notifications</a>
            <a href="/messages"><img src="/src/images/icons/envelope.svg" alt=""> Direct Messages</a>
            <a href="/user?id=<?php echo h($_SESSION['username']); ?>"><img src="/src/images/icons/person.svg" alt=""> Profile</a>
            <button class="newchirp" onclick="openNewChirpModal()">Chirp</button>
            <?php endif; ?>
        </nav>
        <div id="menuSettings">
            <a href="/admin">🛡️ Admin panel</a>
            <a href="/settings/account">⚙️ Settings</a>
            <a href="/signout.php">🚪 Sign out</a>
        </div>
        <button id="settingsButtonWrapper" type="button" onclick="showMenuSettings()">
            <img class="userPic" src="<?php echo h($_SESSION['profile_pic'] ?? '/src/images/users/guest/user.svg'); ?>" alt="<?php echo h($_SESSION['username'] ?? 'guest'); ?>">
            <div>
                <p class="usernameMenu"><?php echo h($_SESSION['name'] ?? 'Admin'); ?></p>
                <p class="subText">@<?php echo h($_SESSION['username'] ?? ''); ?></p>
            </div>
            <p class="settingsButton">⚙️</p>
        </button>
    </div>
</header>

<main>
<div id="feed" class="settingsPageContainer" style="max-width:900px;">
    <div id="iconChirp" onclick="playChirpSound()">
        <img src="/src/images/icons/chirp.svg" alt="Chirp">
    </div>
    <div class="title">
        <p class="selected">🛡️ Admin Panel</p>
    </div>

    <!-- Stats -->
    <div class="admin-stats">
        <div class="stat-card"><div class="num"><?php echo $stats['users']; ?></div><div class="lbl">Users</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['posts']; ?></div><div class="lbl">Posts</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['replies']; ?></div><div class="lbl">Replies</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['likes']; ?></div><div class="lbl">Likes</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['rechirps']; ?></div><div class="lbl">Rechirps</div></div>
        <div class="stat-card"><div class="num"><?php echo $stats['invites']; ?></div><div class="lbl">Unused Invites</div></div>
    </div>

    <!-- Tab bar -->
    <div class="tab-bar">
        <button class="tab-btn active" onclick="showTab('posts', this)">Recent Posts</button>
        <button class="tab-btn" onclick="showTab('users', this)">Recent Users</button>
        <button class="tab-btn" onclick="showTab('search', this)">Search</button>
        <button class="tab-btn" onclick="showTab('tools', this)">Tools</button>
        <button class="tab-btn" onclick="showTab('trends', this)">Trends</button>
    </div>

    <!-- Posts tab -->
    <div id="tab-posts">
        <p class="section-title">Recent Posts (last 30)</p>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>User</th><th>Content</th><th>Via</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentPosts as $p): ?>
            <tr id="row-post-<?php echo (int)$p['id']; ?>">
                <td><?php echo (int)$p['id']; ?></td>
                <td><a href="/user?id=<?php echo h($p['username']); ?>">@<?php echo h($p['username']); ?></a></td>
                <td class="chirp-text" title="<?php echo h($p['chirp']); ?>"><?php echo h($p['chirp']); ?></td>
                <td><?php echo h($p['via'] ?? ''); ?></td>
                <td><?php echo ts((int)$p['timestamp']); ?></td>
                <td><button class="del-btn" onclick="deletePost(<?php echo (int)$p['id']; ?>)">Delete</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Users tab -->
    <div id="tab-users" style="display:none;">
        <p class="section-title">Recent Users (last 20)</p>
        <table class="admin-table">
            <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentUsers as $u): ?>
            <tr id="row-user-<?php echo h($u['username']); ?>">
                <td><?php echo (int)$u['id']; ?></td>
                <td><a href="/user?id=<?php echo h($u['username']); ?>">@<?php echo h($u['username']); ?></a></td>
                <td><?php echo h($u['name']); ?></td>
                <td><?php echo ts((int)$u['created_at']); ?></td>
                <td><?php if ($u['username'] !== ADMIN_USERNAME): ?>
                    <button class="del-btn" onclick="deleteUser('<?php echo h($u['username']); ?>')">Delete</button>
                <?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Search tab -->
    <div id="tab-search" style="display:none;">
        <p class="section-title">Search</p>
        <form class="search-bar" method="get" action="/admin/">
            <input type="text" name="q" value="<?php echo h($searchQuery); ?>" placeholder="Search…">
            <select name="t">
                <option value="posts" <?php echo $searchType === 'posts' ? 'selected' : ''; ?>>Posts</option>
                <option value="users" <?php echo $searchType === 'users' ? 'selected' : ''; ?>>Users</option>
            </select>
            <button type="submit">Search</button>
        </form>
        <?php if (!empty($searchResults)): ?>
            <?php if ($searchType === 'users'): ?>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Joined</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($searchResults as $u): ?>
                <tr id="row-user-<?php echo h($u['username']); ?>">
                    <td><?php echo (int)$u['id']; ?></td>
                    <td><a href="/user?id=<?php echo h($u['username']); ?>">@<?php echo h($u['username']); ?></a></td>
                    <td><?php echo h($u['name']); ?></td>
                    <td><?php echo ts((int)$u['created_at']); ?></td>
                    <td><?php if ($u['username'] !== ADMIN_USERNAME): ?>
                        <button class="del-btn" onclick="deleteUser('<?php echo h($u['username']); ?>')">Delete</button>
                    <?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>ID</th><th>User</th><th>Content</th><th>Via</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($searchResults as $p): ?>
                <tr id="row-post-<?php echo (int)$p['id']; ?>">
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><a href="/user?id=<?php echo h($p['username']); ?>">@<?php echo h($p['username']); ?></a></td>
                    <td class="chirp-text" title="<?php echo h($p['chirp']); ?>"><?php echo h($p['chirp']); ?></td>
                    <td><?php echo h($p['via'] ?? ''); ?></td>
                    <td><?php echo ts((int)$p['timestamp']); ?></td>
                    <td><button class="del-btn" onclick="deletePost(<?php echo (int)$p['id']; ?>)">Delete</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        <?php elseif ($searchQuery !== ''): ?>
            <p class="subText">No results found.</p>
        <?php endif; ?>
    </div>

    <!-- Trends tab -->
    <div id="tab-trends" style="display:none;">
        <p class="section-title">Ollama Status</p>
        <p class="subText">Model: <strong><?php echo h(OLLAMA_EMBED_MODEL); ?></strong> &nbsp;·&nbsp; Host: <strong><?php echo h(OLLAMA_HOST); ?></strong></p>
        <button class="followButton" onclick="testOllama()" style="margin-top:8px;">Test Ollama connection</button>
        <div id="ollama-status" style="margin-top:10px;font-size:.88rem;"></div>

        <p class="section-title" style="margin-top:24px;">Trends Cache</p>
        <?php
        $cacheInfo = '';
        if (file_exists(TRENDS_CACHE_FILE)) {
            $raw = @file_get_contents(TRENDS_CACHE_FILE);
            $cacheData = $raw ? json_decode($raw, true) : null;
            if ($cacheData && isset($cacheData['timestamp'])) {
                $age = time() - $cacheData['timestamp'];
                $cacheInfo = 'Cache age: ' . round($age/60) . ' min (' . count($cacheData['trends'] ?? []) . ' trends stored)';
            }
        } else {
            $cacheInfo = 'No cache file yet.';
        }
        ?>
        <p class="subText"><?php echo h($cacheInfo); ?></p>
        <button class="followButton" onclick="runRescan()" style="margin-top:8px;">Force Rescan Trends</button>
        <div id="rescan-log" style="margin-top:12px;font-size:.85rem;font-family:monospace;background:#111;border-radius:8px;padding:12px;display:none;"></div>

        <p class="section-title" style="margin-top:24px;">Ollama Setup</p>
        <p class="subText">Ollama must be running on the host machine. In Docker on Linux, the host is reachable at <code>172.17.0.1</code>.</p>
        <pre style="background:#111;border-radius:8px;padding:12px;font-size:.82rem;overflow:auto;"># Pull the embedding model (run on the host, not in Docker)
ollama pull <?php echo h(OLLAMA_EMBED_MODEL); ?>

# Verify it's accessible from inside Docker
docker compose exec app curl -s <?php echo h(OLLAMA_HOST); ?>/api/tags | head -c 200</pre>
    </div>

    <!-- Tools tab -->
    <div id="tab-tools" style="display:none;">
        <p class="section-title">Invite Codes</p>
        <button class="followButton" onclick="generateInvite()">Generate Invite Code</button>
        <div id="invite-output" style="display:none;">
            <div class="invite-display" id="invite-code-display"></div>
            <button class="followButton" onclick="saveInvite()" style="margin-top:8px;">Save &amp; Copy</button>
        </div>

        <p class="section-title" style="margin-top:28px;">Migrate Username</p>
        <p class="subText">Move a user to a new username handle.</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
            <input id="migrate-from" type="text" placeholder="Current username" style="padding:8px 12px;border-radius:8px;border:1px solid #333;background:#111;color:#fff;flex:1;min-width:140px;">
            <input id="migrate-to"   type="text" placeholder="New username"     style="padding:8px 12px;border-radius:8px;border:1px solid #333;background:#111;color:#fff;flex:1;min-width:140px;">
            <button class="followButton" onclick="migrateUser()">Migrate</button>
        </div>
        <p id="migrate-status" class="subText" style="margin-top:8px;"></p>

        <p class="section-title" style="margin-top:28px;">Delete Post by ID</p>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <input id="delete-post-id" type="number" min="1" placeholder="Post ID" style="padding:8px 12px;border-radius:8px;border:1px solid #333;background:#111;color:#fff;width:160px;">
            <button class="del-btn" style="padding:8px 16px;font-size:.9rem;" onclick="deletePostById()">Delete Post</button>
        </div>

        <p class="section-title" style="margin-top:28px;">Delete User by Username</p>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <input id="delete-user-name" type="text" placeholder="Username" style="padding:8px 12px;border-radius:8px;border:1px solid #333;background:#111;color:#fff;width:200px;">
            <button class="del-btn" style="padding:8px 16px;font-size:.9rem;" onclick="deleteUserByName()">Delete User</button>
        </div>
    </div>
</div>
</main>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<footer>
    <div class="mobileMenuFooter">
        <a href="/"><img src="/src/images/icons/house.svg" alt="Home"></a>
        <a href="/discover"><img src="/src/images/icons/search.svg" alt="Discover"></a>
        <a href="/notifications"><img src="/src/images/icons/bell.svg" alt="Notifications"></a>
        <a href="/messages"><img src="/src/images/icons/envelope.svg" alt="Direct Messages"></a>
        <a href="/user?id=<?php echo h($_SESSION['username']); ?>"><img src="/src/images/icons/person.svg" alt="Profile"></a>
    </div>
</footer>

<script>
const CSRF = <?php echo json_encode($csrf); ?>;

// ── Tabs ───────────────────────────────────────────────────────────────────────
function showTab(name, btn) {
    ['posts','users','search','tools'].forEach(t => {
        document.getElementById('tab-' + t).style.display = t === name ? '' : 'none';
    });
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
<?php if ($searchQuery !== ''): ?>
// Auto-open search tab if there's a query in URL
document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.querySelectorAll('.tab-btn')[2];
    showTab('search', searchBtn);
});
<?php endif; ?>

// ── Toast ──────────────────────────────────────────────────────────────────────
function toast(msg, type='ok') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + type;
    t.style.display = 'block';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.style.display = 'none', 3000);
}

// ── Generic POST ───────────────────────────────────────────────────────────────
async function adminPost(data) {
    data.csrf_token = CSRF;
    const r = await fetch('/admin/', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    });
    return r.json();
}

// ── Delete post ────────────────────────────────────────────────────────────────
async function deletePost(id) {
    if (!confirm('Permanently delete post #' + id + ' and all its replies/likes?')) return;
    const res = await adminPost({action:'delete_post', id});
    if (res.ok) {
        document.querySelectorAll('#row-post-' + id).forEach(r => r.remove());
        toast('Post #' + id + ' deleted');
    } else {
        toast(res.error || 'Error', 'err');
    }
}
function deletePostById() {
    const id = parseInt(document.getElementById('delete-post-id').value);
    if (!id) return;
    deletePost(id);
}

// ── Delete user ────────────────────────────────────────────────────────────────
async function deleteUser(username) {
    if (!confirm('Permanently delete @' + username + ' and ALL their data? This cannot be undone.')) return;
    const res = await adminPost({action:'delete_user', username});
    if (res.ok) {
        document.querySelectorAll('#row-user-' + username).forEach(r => r.remove());
        toast('@' + username + ' deleted');
    } else {
        toast(res.error || 'Error', 'err');
    }
}
function deleteUserByName() {
    const u = document.getElementById('delete-user-name').value.trim();
    if (!u) return;
    deleteUser(u);
}

// ── Invite code ────────────────────────────────────────────────────────────────
let pendingCode = '';
async function generateInvite() {
    const res = await adminPost({action:'generate_invite'});
    if (res.code) {
        pendingCode = res.code;
        document.getElementById('invite-code-display').textContent = res.code;
        document.getElementById('invite-output').style.display = '';
    } else {
        toast(res.error || 'Error', 'err');
    }
}
async function saveInvite() {
    if (!pendingCode) return;
    const res = await adminPost({action:'save_invite', code: pendingCode});
    if (res.ok) {
        navigator.clipboard.writeText(pendingCode).catch(() => {});
        toast('Code saved & copied: ' + pendingCode);
        document.getElementById('invite-output').style.display = 'none';
        pendingCode = '';
    } else {
        toast(res.error || 'Error', 'err');
    }
}

// ── Ollama test ────────────────────────────────────────────────────────────────
async function testOllama() {
    const el = document.getElementById('ollama-status');
    el.textContent = 'Testing…';
    const res = await adminPost({action: 'test_ollama'});
    if (res.ok) {
        el.style.color = '#27ae60';
        el.textContent = '✓ Ollama reachable. Models available: ' + (res.models || '(none listed)');
    } else {
        el.style.color = '#e74c3c';
        el.textContent = '✗ ' + (res.error || 'Cannot reach Ollama');
    }
}

// ── Rescan trends ──────────────────────────────────────────────────────────────
function runRescan() {
    const log = document.getElementById('rescan-log');
    log.style.display = 'block';
    log.textContent = 'Connecting…\n';

    const es = new EventSource('/dev/rescan.php');
    es.addEventListener('progress', e => {
        const d = JSON.parse(e.data);
        log.textContent += `[${d.progress}%] ${d.message}\n`;
        log.scrollTop = log.scrollHeight;
    });
    es.addEventListener('done', e => {
        const d = JSON.parse(e.data);
        log.textContent += `[100%] ${d.message}\n`;
        if (d.trends && d.trends.length) {
            log.textContent += '\nTrends:\n' + d.trends.map(t => `  ${t.word} (${t.count})`).join('\n');
        }
        log.scrollTop = log.scrollHeight;
        es.close();
        toast('Trends rescanned!');
    });
    es.onerror = () => { log.textContent += '\n[error] Connection closed.\n'; es.close(); };
}

// ── Migrate user ───────────────────────────────────────────────────────────────
async function migrateUser() {
    const from = document.getElementById('migrate-from').value.trim();
    const to   = document.getElementById('migrate-to').value.trim();
    if (!from || !to) { toast('Both fields required', 'err'); return; }
    const res = await adminPost({action:'migrate_user', from, to});
    const el = document.getElementById('migrate-status');
    if (res.ok) {
        el.textContent = '✓ Migrated @' + from + ' → @' + to;
        toast('User migrated');
    } else {
        el.textContent = '✗ ' + (res.error || 'Error');
        toast(res.error || 'Error', 'err');
    }
}
</script>
</body>
</html>
