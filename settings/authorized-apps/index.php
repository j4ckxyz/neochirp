<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
}

$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare(
    'SELECT t.*, a.name as app_name, a.description, a.website, a.client_id as app_client_id
     FROM oauth_tokens t
     JOIN oauth_apps a ON a.client_id = t.client_id
     WHERE t.user_id = :uid AND t.revoked = 0
     ORDER BY t.created_at DESC'
);
$stmt->execute([':uid' => $userId]);
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Authorized Apps - Chirp</title>
    <style>
        .app-card { border: 1px solid #2a2a2f; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .app-card-info { flex: 1; }
        .app-card-name { font-weight: 600; margin: 0 0 4px; }
        .app-card-desc { font-size: 0.88em; color: #a1a1aa; margin: 0 0 6px; }
        .app-card-meta { font-size: 0.82em; color: #71717a; }
        .app-card-meta a { color: #1AD063; text-decoration: none; }
        .revoke-btn { background: none; border: 1px solid #D92D20; color: #D92D20; border-radius: 6px; padding: 5px 12px; cursor: pointer; font-size: 0.85em; white-space: nowrap; flex-shrink: 0; }
        .revoke-btn:hover { background: #D92D2011; }
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
                <p class="selcted settingsTab">Authorized Apps</p>
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
                        <li class="activeDesktop">
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
                    <div class="title"><p class="selcted">Apps with access to your account</p></div>

                    <?php if (empty($tokens)): ?>
                        <p class="subText">No apps have been authorized yet.</p>
                    <?php else: ?>
                        <?php foreach ($tokens as $t): ?>
                        <div class="app-card">
                            <div class="app-card-info">
                                <p class="app-card-name"><?php echo htmlspecialchars($t['app_name']); ?></p>
                                <?php if (!empty($t['description'])): ?>
                                <p class="app-card-desc"><?php echo htmlspecialchars($t['description']); ?></p>
                                <?php endif; ?>
                                <div class="app-card-meta">
                                    Authorized: <?php echo date('Y-m-d', (int)$t['created_at']); ?>
                                    <?php if (!empty($t['website'])): ?>
                                    &nbsp;·&nbsp; <a href="<?php echo htmlspecialchars($t['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($t['website']); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <form method="POST" action="/settings/authorized-apps/revoke.php" onsubmit="return confirm('Revoke access for this app?')">
                                <input type="hidden" name="token_id" value="<?php echo (int)$t['id']; ?>">
                                <button type="submit" class="revoke-btn">Revoke access</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
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
