<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
}

$db = new PDO('sqlite:' . __DIR__ . '/../../../chirp.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = (int)$_SESSION['user_id'];

// Fetch apps owned by current user, with active token count
$stmt = $db->prepare(
    'SELECT a.*, (SELECT COUNT(*) FROM oauth_tokens t WHERE t.client_id = a.client_id AND t.revoked = 0) as user_count
     FROM oauth_apps a WHERE a.owner_user_id = :uid ORDER BY a.created_at DESC'
);
$stmt->execute([':uid' => $userId]);
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Flash message for newly created app
$newApp = null;
if (isset($_SESSION['new_oauth_app'])) {
    $newApp = $_SESSION['new_oauth_app'];
    unset($_SESSION['new_oauth_app']);
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
    <title>Developer Apps - Chirp</title>
    <style>
        .api-key-banner { background: #1AD06322; border: 1px solid #1AD063; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
        .api-key-banner code { font-family: monospace; word-break: break-all; font-size: 0.95em; display: block; margin-top: 4px; }
        .api-key-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .api-key-table th, .api-key-table td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #eee; }
        .api-key-table th { font-size: 0.85em; opacity: 0.7; }
        .revoke-btn { background: none; border: 1px solid #D92D20; color: #D92D20; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 0.85em; }
        .revoke-btn:hover { background: #D92D2011; }
        .create-app-form input[type=text], .create-app-form input[type=url], .create-app-form textarea { display: block; width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #3f3f46; background: #0e0e10; color: #e4e4e7; margin-bottom: 10px; font-size: 1em; font-family: inherit; }
        .create-app-form textarea { resize: vertical; min-height: 80px; }
        .create-app-form label { display: block; font-size: 0.85em; opacity: 0.7; margin-bottom: 4px; }
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
                <p class="selcted settingsTab">Developer Apps</p>
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
                        <li>
                            <a class="settingsMenuLink" href="/settings/api/">🔑 API Keys</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/settings/authorized-apps/">🔗 Authorized Apps</a>
                        </li>
                        <li class="activeDesktop">
                            <a class="settingsMenuLink" href="/settings/developer/">🛠️ Developer Apps</a>
                        </li>
                        <li>
                            <a class="settingsMenuLink" href="/docs/">📕 Help Center</a>
                        </li>
                        <li><p class="subText">Chirp Beta 0.7b</p></li>
                    </ul>
                </div>
                <div id="expandedSettings">
                    <?php if ($newApp): ?>
                    <div class="api-key-banner">
                        <strong>App "<?php echo htmlspecialchars($newApp['name']); ?>" created! Save these credentials — they are shown only once:</strong>
                        <code><strong>Client ID:</strong> <?php echo htmlspecialchars($newApp['client_id']); ?></code>
                        <code><strong>Client Secret:</strong> <?php echo htmlspecialchars($newApp['client_secret']); ?></code>
                    </div>
                    <?php endif; ?>

                    <div class="title"><p class="selcted">Your Apps</p></div>

                    <?php if (empty($apps)): ?>
                        <p class="subText">You haven't registered any apps yet.</p>
                    <?php else: ?>
                    <table class="api-key-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Client ID prefix</th>
                                <th>Created</th>
                                <th>Active users</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $a): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a['name']); ?></td>
                                <td><code><?php echo htmlspecialchars(substr($a['client_id'], 0, 8)); ?>…</code></td>
                                <td class="subText"><?php echo date('Y-m-d', (int)$a['created_at']); ?></td>
                                <td class="subText"><?php echo (int)$a['user_count']; ?></td>
                                <td>
                                    <form method="POST" action="/settings/developer/delete.php" onsubmit="return confirm('Delete this app? All tokens will be revoked.')">
                                        <input type="hidden" name="app_id" value="<?php echo (int)$a['id']; ?>">
                                        <button type="submit" class="revoke-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <div class="title" style="margin-top:24px;"><p class="selcted">Create new app</p></div>
                    <form class="create-app-form" method="POST" action="/settings/developer/create.php">
                        <label>App name <span style="color:#D92D20">*</span></label>
                        <input type="text" name="name" placeholder="My Awesome App" maxlength="60" required>

                        <label>Description</label>
                        <input type="text" name="description" placeholder="What does your app do?" maxlength="200">

                        <label>Website</label>
                        <input type="url" name="website" placeholder="https://example.com">

                        <label>Redirect URIs <span style="color:#D92D20">*</span> <span style="font-weight:normal;opacity:0.6">(one per line)</span></label>
                        <textarea name="redirect_uris" placeholder="https://example.com/callback" required></textarea>

                        <button type="submit" class="button followButton">Create app</button>
                    </form>
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
