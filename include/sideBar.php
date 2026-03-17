<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/trends.php';

$trends = [];
try {
    $sidebarDb = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
    $sidebarDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $trends = compute_trends($sidebarDb, 5);
} catch (Exception $e) {
    // Non-critical; fail silently
}
?>

<?php if (DEV_MODE): ?>
<div style="position:fixed;bottom:16px;right:16px;z-index:9999;background:#f0c040;color:#1a1a1a;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;pointer-events:none;letter-spacing:0.05em;box-shadow:0 2px 8px rgba(0,0,0,0.4);width:max-content;display:inline-block;">⚠ DEV MODE</div>
<?php endif; ?>
<div id="trends">
    <p>Trends</p>
    <?php if (!empty($trends)): ?>
        <?php foreach ($trends as $trend): ?>
        <div>
            <a href="/discover/search?q=<?php echo urlencode($trend['word']); ?>">
                <?php echo htmlspecialchars($trend['word']); ?>
            </a>
            <p class="subText"><?php echo (int)$trend['count']; ?> chirp<?php echo $trend['count'] !== 1 ? 's' : ''; ?></p>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="subText">No trends yet this week.</p>
    <?php endif; ?>
</div>
<?php if (isset($_SESSION['username'])): ?>
<div id="whotfollow">
    <p>Suggested accounts</p>
    <div>
        <div>
            <img class="userPic" src="https://pbs.twimg.com/profile_images/1797665112440045568/305XgPDq_400x400.png"
                alt="Apple">
            <div>
                <p>Apple <img class="verified" src="/src/images/icons/verified.svg" alt="Verified"></p>
                <p class="subText">@apple</p>
            </div>
        </div>
        <a class="followButton">Follow</a>
    </div>
    <div>
        <div>
            <img class="userPic" src="https://pbs.twimg.com/profile_images/1881368435453542400/NnD56DYV_400x400.jpg"
                alt="President Trump">
            <div>
                <p>President Trump <img class="verified" src="/src/images/icons/verified.svg" alt="Verified"></p>
                <p class="subText">@POTUS</p>
            </div>
        </div>
        <a class="followButton">Follow</a>
    </div>
</div>
<?php endif; ?>
<div>
    <p class="subText"><a class="subText" href="/docs/">📖 API Docs</a></p>
    <p class="subText">NeoChirp is an experimental fork of <a class="subText" href="https://github.com/actuallyaridan/chirp" target="_blank" rel="noopener noreferrer">Chirp</a> (original by Adnan Bukvic), maintained by <a class="subText" href="https://github.com/j4ckxyz" target="_blank" rel="noopener noreferrer">Jack Gilbert</a>. New features built with Claude Code.</p>
    <p class="subText">Twemoji by Twitter/X Corp — CC-BY 4.0.</p>
</div>
