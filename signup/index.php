<?php
session_start();
require_once __DIR__ . '/../config.php';
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
    <script src="/src/scripts/general.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <title>Sign up — Chirp</title>
    <style>
        #signupForm { display: flex; flex-direction: column; gap: 14px; margin-top: 16px; }
        .su-field { display: flex; flex-direction: column; gap: 4px; }
        .su-field input { width: 100%; box-sizing: border-box; }
        .su-hint {
            font-size: .8rem;
            min-height: 1.1em;
            transition: color .15s;
        }
        .su-hint.ok  { color: var(--accent-color, #1AD063); }
        .su-hint.err { color: #e05; }
        .su-hint.info { color: var(--subText-color, #888); }

        /* password strength bar */
        #pwStrengthBar {
            height: 4px; border-radius: 2px;
            background: var(--contrastColor, #333);
            overflow: hidden;
            margin-top: 2px;
        }
        #pwStrengthFill {
            height: 100%; width: 0%;
            border-radius: 2px;
            transition: width .25s, background .25s;
        }

        /* username prefix */
        .su-username-wrap { position: relative; }
        .su-username-wrap .at-prefix {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--subText-color, #888);
            pointer-events: none;
            font-size: .95rem;
        }
        .su-username-wrap input { padding-left: 24px !important; }

        #submitBtn { margin-top: 6px; width: 100%; }
        #submitBtn:disabled { opacity: .45; cursor: not-allowed; }

        .su-divider { text-align: center; }
        .su-divider a { font-size: .9rem; }
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
                <a href="<?php echo '/user?id=' . htmlspecialchars($_SESSION['username']); ?>">
                    <img src="/src/images/icons/person.svg" alt=""> Profile
                </a>
                <button class="newchirp" onclick="openNewChirpModal()">Chirp</button>
                <?php endif; ?>
            </nav>
            <div id="menuSettings">
                <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'jack'): ?>
                <a href="/admin/">🛡️ Admin Panel</a>
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
                    <p class="subText">@<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'guest'; ?></p>
                </div>
                <p class="settingsButton">⚙️</p>
            </button>
        </div>
    </header>

    <main>
        <div id="feed">
            <div id="iconChirp" onclick="playChirpSound()">
                <img src="/src/images/icons/chirp.svg" alt="Chirp">
            </div>
            <div class="title">
                <p class="selected">Sign up</p>
            </div>
            <div id="signUp">

                <form id="signupForm" method="post" action="/signup/signup.php" novalidate autocomplete="off">

                    <?php if (!DEV_MODE): ?>
                    <div class="su-field">
                        <input type="text" id="code" name="code" placeholder="Invite code" autocomplete="off" spellcheck="false">
                        <span class="su-hint info" id="codeHint">
                            Need one? DM <a href="https://twitter.com/jglypt" target="_blank" rel="noopener noreferrer">@jglypt</a> or <a href="https://bsky.app/profile/j4ck.xyz" target="_blank" rel="noopener noreferrer">@j4ck.xyz</a>.
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="su-field">
                        <input type="text" id="name" name="name" placeholder="Display name" autocomplete="off" maxlength="50">
                        <span class="su-hint" id="nameHint"></span>
                    </div>

                    <div class="su-field">
                        <div class="su-username-wrap">
                            <span class="at-prefix">@</span>
                            <input type="text" id="username" name="username" placeholder="username" autocomplete="off" spellcheck="false" maxlength="30">
                        </div>
                        <span class="su-hint" id="usernameHint"></span>
                    </div>

                    <div class="su-field">
                        <input type="email" id="email" name="email" placeholder="Email address" autocomplete="off">
                        <span class="su-hint" id="emailHint"></span>
                    </div>

                    <div class="su-field">
                        <input type="password" id="pword" name="pword" placeholder="Password" autocomplete="new-password">
                        <div id="pwStrengthBar"><div id="pwStrengthFill"></div></div>
                        <span class="su-hint" id="pwordHint"></span>
                    </div>

                    <div class="su-field">
                        <input type="password" id="pwordConfirm" name="pwordConfirm" placeholder="Confirm password" autocomplete="new-password">
                        <span class="su-hint" id="confirmHint"></span>
                    </div>

                    <button type="submit" id="submitBtn" class="followButton" disabled>Create account</button>

                </form>

                <p class="su-divider subText" style="margin-top:20px;">
                    Already have an account? <a href="/signin/">Sign in</a>
                </p>

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
            <a href="/notifications"><img src="/src/images/icons/bell.svg" alt="Notifications"></a>
            <a href="/messages"><img src="/src/images/icons/envelope.svg" alt="Direct Messages"></a>
            <a href="<?php echo isset($_SESSION['username']) ? '/user?id=' . htmlspecialchars($_SESSION['username']) : '/signin'; ?>">
                <img src="/src/images/icons/person.svg" alt="Profile">
            </a>
        </div>
    </footer>

    <?php include '../include/compose.php'; ?>

    <script>
    const DEV_MODE = <?php echo DEV_MODE ? 'true' : 'false'; ?>;

    // ── state ──────────────────────────────────────────────────────────────────
    const valid = {
        code:     DEV_MODE,   // skip in dev
        name:     false,
        username: false,
        email:    false,
        pword:    false,
        confirm:  false,
    };

    function setHint(id, msg, type) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.className = 'su-hint ' + (type || '');
    }

    function recheck() {
        const allOk = Object.values(valid).every(Boolean);
        document.getElementById('submitBtn').disabled = !allOk;
    }

    // ── debounce helper ────────────────────────────────────────────────────────
    function debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    // ── invite code ────────────────────────────────────────────────────────────
    <?php if (!DEV_MODE): ?>
    let reservedFor = null;

    const checkCode = debounce(async (val) => {
        val = val.trim().toUpperCase();
        if (!val) { setHint('codeHint', 'Need one? DM @jglypt or @j4ck.xyz.', 'info'); valid.code = false; recheck(); return; }
        setHint('codeHint', 'Checking…', 'info');
        try {
            const r = await fetch('/signup/validate.php?action=code&code=' + encodeURIComponent(val));
            const d = await r.json();
            valid.code = d.ok;
            reservedFor = d.reservedFor || null;
            setHint('codeHint', d.msg, d.ok ? 'ok' : 'err');
            // If code is reserved, re-validate username against it
            validateUsername(document.getElementById('username').value);
        } catch { setHint('codeHint', 'Could not check code.', 'err'); valid.code = false; }
        recheck();
    }, 500);

    document.getElementById('code').addEventListener('input', e => checkCode(e.target.value));
    <?php endif; ?>

    // ── display name ───────────────────────────────────────────────────────────
    document.getElementById('name').addEventListener('input', function () {
        const v = this.value.trim();
        if (!v) { setHint('nameHint', 'Required.', 'err'); valid.name = false; }
        else if (v.length > 50) { setHint('nameHint', 'Max 50 characters.', 'err'); valid.name = false; }
        else { setHint('nameHint', '', ''); valid.name = true; }
        recheck();
    });

    // ── username ───────────────────────────────────────────────────────────────
    const checkUsernameRemote = debounce(async (val) => {
        if (!val) return;
        setHint('usernameHint', 'Checking…', 'info');
        try {
            const r = await fetch('/signup/validate.php?action=username&username=' + encodeURIComponent(val));
            const d = await r.json();
            // Also enforce reserved-for constraint client-side
            if (d.ok && reservedFor && val.toLowerCase() !== reservedFor.toLowerCase()) {
                setHint('usernameHint', 'This invite code is reserved for @' + reservedFor + '.', 'err');
                valid.username = false;
            } else {
                valid.username = d.ok;
                setHint('usernameHint', d.msg, d.ok ? 'ok' : 'err');
            }
        } catch { setHint('usernameHint', 'Could not check username.', 'err'); valid.username = false; }
        recheck();
    }, 500);

    function validateUsername(val) {
        val = val.trim();
        if (!val) { setHint('usernameHint', 'Required.', 'err'); valid.username = false; recheck(); return; }
        if (!/^[A-Za-z0-9_]{1,30}$/.test(val)) {
            setHint('usernameHint', 'Letters, numbers and underscores only. Max 30 chars.', 'err');
            valid.username = false; recheck(); return;
        }
        checkUsernameRemote(val);
    }

    // store reservedFor for non-dev mode (initialized from code check)
    <?php if (DEV_MODE): ?>
    let reservedFor = null;
    <?php endif; ?>

    document.getElementById('username').addEventListener('input', e => validateUsername(e.target.value));

    // ── email ──────────────────────────────────────────────────────────────────
    document.getElementById('email').addEventListener('input', function () {
        const v = this.value.trim();
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        valid.email = ok;
        setHint('emailHint', ok ? '' : (v ? 'Enter a valid email address.' : 'Required.'), ok ? '' : 'err');
        recheck();
    });

    // ── password ───────────────────────────────────────────────────────────────
    function passwordStrength(pw) {
        if (pw.length < 8)  return { score: 0, label: 'Too short (min 8 chars)', color: '#e05' };
        let score = 0;
        if (/[a-z]/.test(pw)) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        if (pw.length >= 12) score++;
        if (score <= 1) return { score: 1, label: 'Weak', color: '#e05' };
        if (score === 2) return { score: 2, label: 'Fair', color: '#f80' };
        if (score === 3) return { score: 3, label: 'Good', color: '#fb0' };
        return { score: 4, label: 'Strong', color: 'var(--accent-color, #1AD063)' };
    }

    document.getElementById('pword').addEventListener('input', function () {
        const v = this.value;
        const fill = document.getElementById('pwStrengthFill');
        if (!v) {
            fill.style.width = '0%';
            setHint('pwordHint', '', '');
            valid.pword = false;
        } else {
            const s = passwordStrength(v);
            fill.style.width = (s.score / 4 * 100) + '%';
            fill.style.background = s.color;
            const ok = s.score >= 2; // Fair or better is accepted
            valid.pword = ok;
            setHint('pwordHint', s.label, ok ? (s.score >= 4 ? 'ok' : 'info') : 'err');
        }
        // Re-validate confirm field
        const confirm = document.getElementById('pwordConfirm').value;
        if (confirm) {
            valid.confirm = v === confirm;
            setHint('confirmHint', valid.confirm ? 'Passwords match.' : 'Passwords don\'t match.', valid.confirm ? 'ok' : 'err');
        }
        recheck();
    });

    document.getElementById('pwordConfirm').addEventListener('input', function () {
        const pw = document.getElementById('pword').value;
        valid.confirm = this.value === pw && pw.length > 0;
        setHint('confirmHint', valid.confirm ? 'Passwords match.' : 'Passwords don\'t match.', valid.confirm ? 'ok' : 'err');
        recheck();
    });

    // ── submit ─────────────────────────────────────────────────────────────────
    document.getElementById('signupForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Creating account…';

        try {
            const r = await fetch(this.action, { method: 'POST', body: new FormData(this) });
            const d = await r.json();
            if (d.success) {
                window.location.href = '/signin/';
            } else {
                // Show error under the relevant field
                const map = {
                    'Invalid invite code':        ['codeHint',     'Invalid invite code.'],
                    'Invite not reserved for this username': ['usernameHint', 'This invite is reserved for a different username.'],
                    'This username is reserved.': ['usernameHint', 'This username is reserved.'],
                    'Username already in use':    ['usernameHint', 'Username already taken.'],
                    'Passwords do not match':     ['confirmHint',  'Passwords don\'t match.'],
                    'Invalid username. Only letters, numbers, and underscores are allowed.': ['usernameHint', 'Invalid username.'],
                };
                const entry = map[d.error];
                if (entry) setHint(entry[0], entry[1], 'err');
                else setHint('confirmHint', d.error || 'Something went wrong.', 'err');
                btn.disabled = false;
                btn.textContent = 'Create account';
            }
        } catch {
            setHint('confirmHint', 'Network error. Please try again.', 'err');
            btn.disabled = false;
            btn.textContent = 'Create account';
        }
    });
    </script>
</body>
</html>
