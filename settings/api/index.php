<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
}

$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = (int)$_SESSION['user_id'];

// Fetch user's API keys
$stmt = $db->prepare('SELECT id, name, created_at, last_used, reqs_hour, key_hash FROM api_keys WHERE user_id = :uid ORDER BY created_at DESC');
$stmt->execute([':uid' => $userId]);
$apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Flash message for newly generated key
$newKey = null;
if (isset($_SESSION['new_api_key'])) {
    $newKey = $_SESSION['new_api_key'];
    unset($_SESSION['new_api_key']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="mobile-web-app-capable" content="yes">

    <link href="/src/styles/styles.css" rel="stylesheet">
    <link href="/src/styles/timeline.css" rel="stylesheet">
    <link href="/src/styles/menus.css" rel="stylesheet">
    <link href="/src/styles/responsive.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="/src/scripts/general.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <title>API Keys - Chirp</title>
    <style>
        .api-key-banner { background: #1AD06322; border: 1px solid #1AD063; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
        .api-key-banner code { font-family: monospace; word-break: break-all; font-size: 0.95em; }
        .api-key-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .api-key-table th, .api-key-table td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #eee; }
        .api-key-table th { font-size: 0.85em; opacity: 0.7; }
        .revoke-btn { background: none; border: 1px solid #D92D20; color: #D92D20; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 0.85em; }
        .revoke-btn:hover { background: #D92D2011; }
        .generate-form input[type=text] { padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; margin-right: 8px; font-size: 1em; }
        .rate-limits { font-size: 0.85em; opacity: 0.65; margin-bottom: 8px; }
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
                <a href="/notifications" style="position:relative;">
                    <img src="/src/images/icons/bell.svg" alt=""> Notifications
                    <span id="notifDot" style="display:none;position:absolute;top:2px;right:-4px;width:8px;height:8px;background:#1AD063;border-radius:50%;"></span>
                </a>
                <a href="/messages"><img src="/src/images/icons/envelope.svg" alt=""> Direct Messages</a>
                <a href="<?php echo isset($_SESSION['username']) ? '/user?id=' . htmlspecialchars($_SESSION['username']) : '/signin'; ?>">
                    <img src="/src/images/icons/person.svg" alt=""> Profile
                </a>
                    <button class="newchirp" onclick="openNewChirpModal()">Chirp</button>
                <?php endif; ?>
            </nav>
            <div id="menuSettings">
                <?php if (isset($_SESSION['username']) && $_SESSION['username'] == 'chirp'): ?>
                <a href="/admin">🛡️ Admin panel</a>
                <?php endif; ?>
                <a href="/settings/account">⚙️ Settings</a>
                <?php if (isset($_SESSION['username'])): ?>
                <a href="/signout.php">🚪 Sign out</a>
                <?php else: ?>
                <a href="/signin/">🚪 Sign in</a>
                <?php endif; ?>
            </div>
            <button id="settingsButtonWrapper" type="button" onclick="showMenuSettings()">
                <img class="userPic"
                    src="<?php echo isset($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : '/src/images/users/guest/user.svg'; ?>"
                    alt="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'guest'; ?>">
                <div>
                    <p class="usernameMenu">
                        <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Guest'; ?>
                        <?php if (isset($_SESSION['is_verified']) && $_SESSION['is_verified']): ?>
                        <img class="emoji" src="/src/images/icons/verified.svg" alt="Verified">
                        <?php endif; ?>
                    </p>
                    <p class="subText">
                        @<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'guest'; ?>
                    </p>
                </div>
                <p class="settingsButton">⚙️</p>
            </button>
        </div>
    </header>
    <main>
        <div id="feed" class="settingsPageContainer">
            <div id="iconChirp" onclick="playChirpSound()">
                <img src="/src/images/icons/chirp.svg" alt="Chirp">
            </div>
            <div class="title">
                <p class="selcted">Settings</p>
                <p class="selcted settingsTab">API Keys</p>
            </div>
            <div id="settings">
                <div id="settingsExpand">
                    <ul>
                        <li>
                            <a class="settingsMenuLink" href="/settings/account">👤 Account</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/content-you-see">📝 Content you see</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/appearance-and-accessibility">🎨 Appearance and accessibility</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/security-and-login">🔐 Security and Login</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/privacy-and-safety">👁️ Privacy and Safety</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/notifications">🔔 Notifications</a>
                        </li>
                        <li class="activeDesktop">
                            <a class="settingsMenuLink" href="/settings/api/">🔑 API Keys</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/authorized-apps/">🔗 Authorized Apps</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/developer/">🛠️ Developer Apps</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/docs/">📕 Help Center</a>
                        </li>
                        <li><p class="subText">Chirp Beta 0.7b</p></li>
                    </ul>
                </div>
                <div id="expandedSettings">
                    <?php if ($newKey): ?>
                    <div class="api-key-banner">
                        <strong>Your new API key (shown once — copy it now):</strong><br>
                        <code><?php echo htmlspecialchars($newKey); ?></code>
                    </div>
                    <?php endif; ?>

                    <div class="rate-limits">Rate limits: 300 reads / 100 writes per hour per key.</div>

                    <div class="title"><p class="selcted">Generate new key</p></div>
                    <form class="generate-form" method="POST" action="/settings/api/generate.php">
                        <input type="text" name="name" placeholder="Key name (e.g. My App)" maxlength="100" required>
                        <button type="submit" class="button followButton">Generate key</button>
                    </form>

                    <div class="title" style="margin-top:24px;"><p class="selcted">Your keys</p></div>
                    <?php if (empty($apiKeys)): ?>
                        <p class="subText">No API keys yet.</p>
                    <?php else: ?>
                    <table class="api-key-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key prefix</th>
                                <th>Created</th>
                                <th>Last used</th>
                                <th>Reqs this hour</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apiKeys as $k): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($k['name']); ?></td>
                                <td><code>chirp_<?php echo substr($k['key_hash'], 0, 8); ?>…</code></td>
                                <td class="subText"><?php echo date('Y-m-d', (int)$k['created_at']); ?></td>
                                <td class="subText"><?php echo $k['last_used'] ? date('Y-m-d H:i', (int)$k['last_used']) : 'Never'; ?></td>
                                <td class="subText"><?php echo (int)$k['reqs_hour']; ?></td>
                                <td>
                                    <form method="POST" action="/settings/api/revoke.php" onsubmit="return confirm('Revoke this key?')">
                                        <input type="hidden" name="key_id" value="<?php echo (int)$k['id']; ?>">
                                        <button type="submit" class="revoke-btn">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="mobileMenuFooter">
            <a href="/"><img src="/src/images/icons/house.svg" alt="Home"></a>
            <a href="/discover"><img src="/src/images/icons/search.svg" alt="Discover"></a>
            <a href="/notifications"><span style="position:relative"><img src="/src/images/icons/bell.svg" alt="Notifications"><span id="notifDotMobile" style="display:none;position:absolute;top:0;right:0;width:8px;height:8px;background:#1AD063;border-radius:50%;"></span></span></a>
            <a href="/messages"><img src="/src/images/icons/envelope.svg" alt="Direct Messages"></a>
            <a href="<?php echo isset($_SESSION['username']) ? '/user?id=' . htmlspecialchars($_SESSION['username']) : '/signin'; ?>"><img src="/src/images/icons/person.svg" alt="Profile"></a>
        </div>
    </footer>
    <?php include '../../include/compose.php'; ?>
    <script>
(function() {
    if (!document.getElementById('notifDot')) return;
    fetch('/notifications/get_count.php')
        .then(r => r.json())
        .then(d => {
            if (d.count > 0) {
                document.getElementById('notifDot').style.display = 'inline-block';
                var m = document.getElementById('notifDotMobile');
                if (m) m.style.display = 'inline-block';
            }
        })
        .catch(() => {});
})();
    </script>
</body>

</html>
