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
    <title>Notifications - Chirp</title>
</head>

<body>
    <header>
        <div id="desktopMenu">
            <nav>
                <img src="/src/images/icons/chirp.svg" alt="Chirp" onclick="playChirpSound()">
                <a href="/"><img src="/src/images/icons/house.svg" alt=""> Home</a>
                <a href="/discover"><img src="/src/images/icons/search.svg" alt=""> Discover</a>
                <?php if (isset($_SESSION['username'])): ?>
                <a href="/notifications" class="activeDesktop" style="position:relative;">
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
                <?php if (DEV_MODE): ?>
                <a href="/dev/">🛠️ Dev Console</a>
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
        <div id="feed">
            <div id="timelineSelect" class="extraBlur">
                <div class="TL">
                    <a class="menuMobileTL">
                        <img class="userPicTL"
                            src="<?php echo isset($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : '/src/images/users/guest/user.svg'; ?>"
                            alt="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'guest'; ?>">
                    </a>
                    <div id="iconChirp" onclick="playChirpSound()">
                        <img src="/src/images/icons/chirp.svg" alt="Chirp">
                    </div>
                    <a class="menuMobileTL" href="/settings/account">⚙️</a>
                </div>
                <div>
                    <a id="forYou" class="selected" href="/notifications">Notifications</a>
                </div>
            </div>
            <div id="notifications-list">
                <!-- Notifications will be injected here -->
            </div>
            <div id="notif-loading" style="display:none;">
                <div class="lds-ring">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
            <div id="notif-end" style="display:none;">
                <p class="subText" style="text-align:center;padding:1rem;">No more notifications.</p>
            </div>
        </div>
    </main>
    <aside id="sideBar">
        <?php include '../include/sideBar.php'; ?>
    </aside>
    <footer>
        <div class="mobileMenuFooter">
            <a href="/"><img src="/src/images/icons/house.svg" alt="Home"></a>
            <a href="/discover"><img src="/src/images/icons/search.svg" alt="Discover"></a>
            <a href="/notifications" class="active"><span style="position:relative"><img src="/src/images/icons/bell.svg" alt="Notifications"><span id="notifDotMobile" style="display:none;position:absolute;top:0;right:0;width:8px;height:8px;background:#1AD063;border-radius:50%;"></span></span></a>
            <a href="/messages"><img src="/src/images/icons/envelope.svg" alt="Direct Messages"></a>
            <a href="<?php echo isset($_SESSION['username']) ? '/user?id=' . htmlspecialchars($_SESSION['username']) : '/signin'; ?>"><img src="/src/images/icons/person.svg" alt="Profile"></a>
        </div>
    </footer>
    <?php include '../include/compose.php'; ?>

    <script>
    (function() {
        // Notification badge fetch
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

    // Inline updateChirpInteraction for notifications page
    function updateChirpInteraction(chirpId, action, button) {
        fetch('/interact_chirp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ chirpId, action })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const countElement = button.querySelector('.' + action + '-count');
                if (action === 'like') {
                    button.querySelector('img').src = data.like ? '/src/images/icons/liked.svg' : '/src/images/icons/like.svg';
                    button.classList.toggle('liked', data.like);
                    countElement.textContent = data.like_count;
                    button.style.color = data.like ? '#D92D20' : '';
                } else if (action === 'rechirp') {
                    button.querySelector('img').src = data.rechirp ? '/src/images/icons/rechirped.svg' : '/src/images/icons/rechirp.svg';
                    button.classList.toggle('rechirped', data.rechirp);
                    countElement.textContent = data.rechirp_count;
                    button.style.color = data.rechirp ? '#12B76A' : '';
                }
            } else if (data.error === 'not_signed_in') {
                window.location.href = '/signin/';
            }
        })
        .catch(() => {});
    }

    function relativeTime(ts) {
        const diff = Math.floor(Date.now() / 1000) - ts;
        if (diff < 60) return diff + 's';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return Math.floor(diff / 86400) + 'd';
    }

    function renderActorAvatars(actors) {
        let html = '<div style="display:inline-flex;margin-right:8px;">';
        actors.forEach((a, i) => {
            const pic = a.profilePic || '/src/images/users/guest/user.svg';
            html += `<img src="${pic}" alt="${a.name || 'user'}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;${i > 0 ? 'margin-left:-10px;' : ''}border:2px solid var(--bg,#fff);">`;
        });
        html += '</div>';
        return html;
    }

    function renderActorText(notif) {
        const first = notif.actors[0];
        const name = first ? (first.name || first.username) : 'Someone';
        const extra = notif.actor_count - 1;
        let verb = '';
        if (notif.type === 'like') verb = 'liked your chirp';
        else if (notif.type === 'rechirp') verb = 'rechirped your chirp';
        else if (notif.type === 'reply') verb = 'replied to your chirp';
        if (extra > 0) {
            return `<strong>${name}</strong> and <strong>${extra} other${extra > 1 ? 's' : ''}</strong> ${verb}`;
        }
        return `<strong>${name}</strong> ${verb}`;
    }

    function renderNotifCard(notif) {
        const iconMap = { like: '❤️', rechirp: '🔁', reply: '💬' };
        const icon = iconMap[notif.type] || '🔔';

        let cardHtml = '';

        if (notif.type === 'reply' && notif.reply) {
            const r = notif.reply;
            const pic = r.profilePic || '/src/images/users/guest/user.svg';
            const verifiedBadge = r.isVerified ? '<img style="width:14px;vertical-align:middle;" src="/src/images/icons/verified.svg" alt="Verified">' : '';
            cardHtml = `
            <div class="chirp${notif.unread ? ' notif-unread' : ''}" style="cursor:default;">
                <a class="chirpClicker" href="/chirp/?id=${r.id}">
                    <div class="chirpInfo">
                        <div>
                            <span style="font-size:1.4rem;margin-right:6px;">${icon}</span>
                            <img class="userPic" src="${pic}" alt="${r.name || 'user'}">
                            <div>
                                <p>${r.name || r.username} ${verifiedBadge}</p>
                                <p class="subText">@${r.username} &middot; replied to your chirp</p>
                            </div>
                        </div>
                        <div class="timestampTimeline">
                            <p class="subText">${relativeTime(r.timestamp)}</p>
                        </div>
                    </div>
                    <pre style="margin-top:4px;opacity:0.85;">${r.chirp}</pre>
                    ${notif.chirp_text ? `<p class="subText" style="margin-top:4px;padding-left:4px;border-left:2px solid #ccc;">${notif.chirp_text}</p>` : ''}
                </a>
                <div class="chirpInteract">
                    <button type="button" class="reply" onclick="location.href='/chirp/?id=${r.id}'"><img alt="Reply" src="/src/images/icons/reply.svg"> <span class="reply-count">${r.reply_count}</span></button>
                    <button type="button" class="rechirp" onclick="updateChirpInteraction(${r.id},'rechirp',this)"><img alt="Rechirp" src="/src/images/icons/${r.rechirped_by_me ? 'rechirped' : 'rechirp'}.svg"> <span class="rechirp-count">${r.rechirp_count}</span></button>
                    <button type="button" class="like" onclick="updateChirpInteraction(${r.id},'like',this)"><img alt="Like" src="/src/images/icons/${r.liked_by_me ? 'liked' : 'like'}.svg"> <span class="like-count">${r.like_count}</span></button>
                    <a class="interactLinkerPost" href="/chirp/?id=${r.id}"></a>
                </div>
            </div>`;
        } else {
            cardHtml = `
            <a class="chirp chirpClicker${notif.unread ? ' notif-unread' : ''}" href="/chirp/?id=${notif.chirp_id}" style="display:block;text-decoration:none;">
                <div class="chirpInfo">
                    <div>
                        <span style="font-size:1.4rem;margin-right:6px;">${icon}</span>
                        ${renderActorAvatars(notif.actors)}
                        <div>
                            <p>${renderActorText(notif)}</p>
                            ${notif.chirp_text ? `<p class="subText">${notif.chirp_text}</p>` : ''}
                        </div>
                    </div>
                    <div class="timestampTimeline">
                        <p class="subText">${relativeTime(notif.latest_ts)}</p>
                    </div>
                </div>
            </a>`;
        }

        return cardHtml;
    }

    let notifOffset = 0;
    let notifLoading = false;
    let notifDone = false;

    function loadNotifications() {
        if (notifLoading || notifDone) return;
        notifLoading = true;
        document.getElementById('notif-loading').style.display = 'block';

        fetch('/notifications/get_notifications.php?offset=' + notifOffset)
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    notifDone = true;
                    if (notifOffset === 0) {
                        document.getElementById('notifications-list').innerHTML = '<p class="subText" style="text-align:center;padding:2rem;">No notifications yet.</p>';
                    } else {
                        document.getElementById('notif-end').style.display = 'block';
                    }
                    return;
                }
                const list = document.getElementById('notifications-list');
                data.forEach(notif => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = renderNotifCard(notif);
                    list.appendChild(wrapper.firstElementChild);
                });
                notifOffset += data.length;
                if (typeof twemoji !== 'undefined') twemoji.parse(list);
                if (typeof updatePostedDates === 'function') updatePostedDates();
            })
            .catch(() => {})
            .finally(() => {
                notifLoading = false;
                document.getElementById('notif-loading').style.display = 'none';
            });
    }

    // Mark all read after loading
    function markRead() {
        fetch('/notifications/mark_read.php').catch(() => {});
    }

    loadNotifications();
    markRead();

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 120) {
            loadNotifications();
        }
    });
    </script>
</body>

</html>
