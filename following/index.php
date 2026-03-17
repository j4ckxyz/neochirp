<?php
session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /signin/');
    exit;
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
    <title>Following - Chirp</title>
</head>

<body>
    <header>
        <div id="desktopMenu">
            <nav>
                <img src="/src/images/icons/chirp.svg" alt="Chirp" onclick="playChirpSound()">
                <a href="/" class="activeDesktop"><img src="/src/images/icons/house.svg" alt=""> Home</a>
                <a href="/discover"><img src="/src/images/icons/search.svg" alt=""> Discover</a>
                <a href="/notifications" style="position:relative;">
                    <img src="/src/images/icons/bell.svg" alt=""> Notifications
                    <span id="notifDot" style="display:none;position:absolute;top:2px;right:-4px;width:8px;height:8px;background:#1AD063;border-radius:50%;"></span>
                </a>
                <a href="/messages"><img src="/src/images/icons/envelope.svg" alt=""> Direct Messages</a>
                <a href="<?php echo '/user?id=' . htmlspecialchars($_SESSION['username']); ?>">
                    <img src="/src/images/icons/person.svg" alt=""> Profile
                </a>
                <button class="newchirp" onclick="openNewChirpModal()">Chirp</button>
            </nav>
            <div id="menuSettings">
                <?php if ($_SESSION['username'] == 'chirp'): ?>
                <a href="/admin">🛡️ Admin panel</a>
                <?php endif; ?>
                <?php if (DEV_MODE): ?>
                <a href="/dev/">🛠️ Dev Console</a>
                <?php endif; ?>
                <a href="/settings/account">⚙️ Settings</a>
                <a href="/signout.php">🚪 Sign out</a>
            </div>
            <button id="settingsButtonWrapper" type="button" onclick="showMenuSettings()">
                <img class="userPic"
                    src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>"
                    alt="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                <div>
                    <p class="usernameMenu"><?php echo htmlspecialchars($_SESSION['name']); ?>
                        <?php if (isset($_SESSION['is_verified']) && $_SESSION['is_verified']): ?>
                            <img class="emoji" src="/src/images/icons/verified.svg" alt="Verified">
                        <?php endif; ?>
                    </p>
                    <p class="subText">@<?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <p class="settingsButton">⚙️</p>
            </button>
        </div>
    </header>
    <main>
        <div id="feed">
            <div id="timelineSelect" class="extraBlur">
                <div class="TL">
                    <a class="menuMobileTL">
                        <img class="userPicTL"
                            src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>"
                            alt="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                    </a>
                    <div id="iconChirp" onclick="playChirpSound()">
                        <img src="/src/images/icons/chirp.svg" alt="Chirp">
                    </div>
                    <a class="menuMobileTL" href="/settings/account">⚙️</a>
                </div>
                <div>
                    <a id="forYou" href="/">Latest</a>
                    <a id="whatsHot" href="/hot">What's Hot</a>
                    <a id="following" class="selected" href="/following">Following</a>
                </div>
            </div>
            <div id="chirps" data-offset="0" data-feed-url="/following/fetch_chirps.php">
            </div>
            <div id="noMoreChirps" style="display: none;">
                <div class="lds-ring">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    </main>
    <aside id="sideBar">
        <?php include '../include/sideBar.php'; ?>
    </aside>
    <footer>
        <div class="mobileCompose">
            <button class="newchirpmobile" onclick="openNewChirpModal()">Chirp</button>
        </div>
        <div class="mobileMenuFooter">
            <a href="/" class="active"><img src="/src/images/icons/house.svg" alt="Home"></a>
            <a href="/discover"><img src="/src/images/icons/search.svg" alt="Discover"></a>
            <a href="/notifications"><span style="position:relative"><img src="/src/images/icons/bell.svg" alt="Notifications"><span id="notifDotMobile" style="display:none;position:absolute;top:0;right:0;width:8px;height:8px;background:#1AD063;border-radius:50%;"></span></span></a>
            <a href="/messages"><img src="/src/images/icons/envelope.svg" alt="Direct Messages"></a>
            <a href="<?php echo '/user?id=' . htmlspecialchars($_SESSION['username']); ?>">
                <img src="/src/images/icons/person.svg" alt="Profile">
            </a>
        </div>
    </footer>
    <?php include '../include/compose.php'; ?>
    <script defer src="/src/scripts/loadChirps.js"></script>
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
