<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!DEV_MODE) {
    header('Location: /');
    exit;
}

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="/src/styles/styles.css" rel="stylesheet">
    <link href="/src/styles/timeline.css" rel="stylesheet">
    <link href="/src/styles/menus.css" rel="stylesheet">
    <link href="/src/styles/responsive.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="/src/scripts/general.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <title>Dev Console - Chirp</title>
    <style>
        #devConsole {
            padding: 20px;
            max-width: 700px;
        }
        #devConsole h1 {
            font-size: 1.4em;
            margin-bottom: 16px;
        }
        .devSection {
            margin-bottom: 32px;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 16px 20px;
        }
        .devSection h2 {
            font-size: 1.05em;
            margin: 0 0 12px 0;
        }
        .devBtn {
            padding: 10px 20px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 8px;
            border: none;
            font-weight: 700;
        }
        .devBtn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .devBtn-yellow { background: #f0c040; color: #1a1a1a; }
        .devBtn-green  { background: #1AD063; color: #0a0a0a; }
        .progressArea {
            margin-top: 18px;
            display: none;
        }
        .statusText {
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        .progressBar {
            width: 100%;
            height: 12px;
            margin-bottom: 10px;
        }
        .logDiv {
            background: #111;
            color: #cfc;
            font-family: monospace;
            font-size: 0.82em;
            padding: 10px;
            border-radius: 6px;
            height: 140px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        #trendsResult {
            margin-top: 20px;
            display: none;
        }
        #trendsResult table {
            border-collapse: collapse;
            width: 100%;
            max-width: 400px;
        }
        #trendsResult th, #trendsResult td {
            border: 1px solid #333;
            padding: 6px 12px;
            text-align: left;
        }
        #trendsResult th {
            background: #222;
        }
        .errorMsg {
            margin-top: 12px;
            color: #f55;
            display: none;
        }
        .countRow {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .countRow label { font-size: 0.95em; }
        .countRow input[type=number] {
            width: 90px;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #555;
            background: #1a1a1a;
            color: inherit;
            font-size: 1em;
        }
        .successMsg {
            margin-top: 10px;
            color: #1AD063;
            display: none;
            font-size: 0.95em;
        }
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
        <div id="devConsole">
            <h1>🛠️ Dev Console</h1>

            <!-- Make Random Posts -->
            <div class="devSection">
                <h2>🤖 Make Random Posts</h2>
                <p class="subText" style="margin-bottom:12px;">Posts from bot accounts using random topics &amp; templates. Scattered over the past 7 days so trends pick them up.</p>
                <div class="countRow">
                    <label for="postCount">Number of posts:</label>
                    <input type="number" id="postCount" value="50" min="1" max="500">
                    <button id="dummyBtn" type="button" class="devBtn devBtn-green">Make Random Posts</button>
                </div>
                <div class="progressArea" id="dummyProgressArea">
                    <p class="statusText" id="dummyStatus">Starting...</p>
                    <progress class="progressBar" id="dummyBar" value="0" max="100"></progress>
                    <div class="logDiv" id="dummyLog"></div>
                </div>
                <p class="successMsg" id="dummySuccess"></p>
                <p class="errorMsg" id="dummyError"></p>
            </div>

            <!-- Rescan Trends -->
            <div class="devSection">
                <h2>📊 Rescan Trends</h2>
                <p class="subText" style="margin-bottom:12px;">Re-run the Ollama embedding pipeline to recompute trends from all chirps in the past 7 days.</p>
                <button id="rescanBtn" type="button" class="devBtn devBtn-yellow">Rescan Trends</button>

                <div class="progressArea" id="progressArea">
                    <p class="statusText" id="statusText">Starting...</p>
                    <progress class="progressBar" id="progressBar" value="0" max="100"></progress>
                    <div class="logDiv" id="logDiv"></div>
                </div>

                <div id="trendsResult">
                    <h2 style="font-size:1.05em;margin-top:16px;margin-bottom:10px;">Trends found</h2>
                    <table>
                        <thead><tr><th>Word</th><th>Chirp count</th></tr></thead>
                        <tbody id="trendsBody"></tbody>
                    </table>
                </div>

                <p class="errorMsg" id="errorMsg"></p>
            </div>
        </div>
    </div>
</main>

<aside id="sideBar">
    <?php include '../include/sideBar.php'; ?>
</aside>

<footer>
    <div class="mobileCompose">
        <?php if (isset($_SESSION['username'])): ?>
            <button class="newchirpmobile" onclick="openNewChirpModal()">Chirp</button>
        <?php endif; ?>
    </div>
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
(function () {
    // ── Dummy Posts ──────────────────────────────────────────────────────────
    const dummyBtn    = document.getElementById('dummyBtn');
    const dummyArea   = document.getElementById('dummyProgressArea');
    const dummyStatus = document.getElementById('dummyStatus');
    const dummyBar    = document.getElementById('dummyBar');
    const dummyLog    = document.getElementById('dummyLog');
    const dummyOk     = document.getElementById('dummySuccess');
    const dummyErr    = document.getElementById('dummyError');
    const postCount   = document.getElementById('postCount');

    function appendDummyLog(msg) {
        dummyLog.textContent += msg + '\n';
        dummyLog.scrollTop = dummyLog.scrollHeight;
    }

    dummyBtn.addEventListener('click', function () {
        const n = Math.max(1, Math.min(500, parseInt(postCount.value, 10) || 50));
        dummyBtn.disabled  = true;
        dummyArea.style.display = 'block';
        dummyOk.style.display   = 'none';
        dummyErr.style.display  = 'none';
        dummyLog.textContent    = '';
        dummyStatus.textContent = 'Connecting...';
        dummyBar.value          = 0;

        const es = new EventSource('/dev/dummy_posts.php?count=' + n);

        es.addEventListener('progress', function (e) {
            const d = JSON.parse(e.data);
            dummyStatus.textContent = d.message || '';
            dummyBar.value = d.progress || 0;
            appendDummyLog(d.message || '');
        });

        es.addEventListener('done', function (e) {
            es.close();
            const d = JSON.parse(e.data);
            dummyBar.value = 100;
            dummyStatus.textContent = 'Done!';
            appendDummyLog(d.message || '');
            dummyOk.textContent     = '✓ ' + (d.message || 'Done!');
            dummyOk.style.display   = 'block';
            dummyBtn.disabled       = false;
        });

        es.addEventListener('error', function (e) {
            try { const d = JSON.parse(e.data); dummyErr.textContent = d.message || 'Unknown error'; }
            catch (_) { dummyErr.textContent = 'Connection error'; }
            dummyErr.style.display = 'block';
            es.close();
            dummyBtn.disabled = false;
        });

        es.onerror = function () {
            if (es.readyState === EventSource.CLOSED) return;
            es.close();
            dummyErr.textContent   = 'Error: connection to dummy_posts endpoint failed.';
            dummyErr.style.display = 'block';
            dummyBtn.disabled      = false;
        };
    });

    // ── Rescan Trends ────────────────────────────────────────────────────────
    const btn         = document.getElementById('rescanBtn');
    const progressArea= document.getElementById('progressArea');
    const statusText  = document.getElementById('statusText');
    const progressBar = document.getElementById('progressBar');
    const logDiv      = document.getElementById('logDiv');
    const trendsResult= document.getElementById('trendsResult');
    const trendsBody  = document.getElementById('trendsBody');
    const errorMsg    = document.getElementById('errorMsg');

    function appendLog(msg) {
        logDiv.textContent += msg + '\n';
        logDiv.scrollTop = logDiv.scrollHeight;
    }

    btn.addEventListener('click', function () {
        btn.disabled = true;
        progressArea.style.display = 'block';
        trendsResult.style.display = 'none';
        errorMsg.style.display     = 'none';
        logDiv.textContent         = '';
        statusText.textContent     = 'Connecting...';
        progressBar.value          = 0;
        trendsBody.innerHTML       = '';

        const es = new EventSource('/dev/rescan.php');

        es.addEventListener('progress', function (e) {
            const data = JSON.parse(e.data);
            statusText.textContent = data.message || '';
            progressBar.value      = data.progress || 0;
            appendLog('[' + (data.step || '') + '] ' + (data.message || ''));
        });

        es.addEventListener('done', function (e) {
            es.close();
            const data = JSON.parse(e.data);
            statusText.textContent = data.message || 'Done!';
            progressBar.value = 100;
            appendLog('[done] ' + (data.message || ''));

            if (data.trends && data.trends.length > 0) {
                trendsBody.innerHTML = '';
                data.trends.forEach(function (t) {
                    const tr   = document.createElement('tr');
                    const tdW  = document.createElement('td'); tdW.textContent = t.word;
                    const tdC  = document.createElement('td'); tdC.textContent = t.count;
                    tr.appendChild(tdW); tr.appendChild(tdC);
                    trendsBody.appendChild(tr);
                });
                trendsResult.style.display = 'block';
            }

            btn.disabled = false;
        });

        es.onerror = function () {
            es.close();
            errorMsg.textContent   = 'Error: connection to rescan endpoint failed.';
            errorMsg.style.display = 'block';
            btn.disabled = false;
        };
    });
})();
</script>

</body>
</html>
