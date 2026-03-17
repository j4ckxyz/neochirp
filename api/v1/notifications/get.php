<?php

 }

require_once __DIR__ . '/../auth.php';
$apiUser = chirp_api_auth();
$db = chirp_api_db();

$userId = (int)$apiUser['user_id'];
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit = 20;

$stmt = $db->prepare("
    SELECT
        n.type,
        n.chirp_id,
        COUNT(DISTINCT n.actor_id) as actor_count,
        MAX(n.timestamp) as latest_ts,
        SUM(CASE WHEN n.read = 0 THEN 1 ELSE 0 END) as unread_count,
        c.chirp as chirp_text,
        c.type as chirp_type
    FROM notifications n
    JOIN chirps c ON c.id = n.chirp_id
    WHERE n.user_id = :uid
    GROUP BY n.type, n.chirp_id
    ORDER BY latest_ts DESC
    LIMIT :lim OFFSET :off
");
$stmt->execute([':uid' => $userId, ':lim' => $limit, ':off' => $offset]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($groups as $g) {
    $aStmt = $db->prepare("
        SELECT u.username, u.name, u.profilePic, u.isVerified
        FROM notifications n
        JOIN users u ON u.id = n.actor_id
        WHERE n.user_id = :uid AND n.type = :type AND n.chirp_id = :cid
        ORDER BY n.timestamp DESC
        LIMIT 2
    ");
    $aStmt->execute([':uid' => $userId, ':type' => $g['type'], ':cid' => $g['chirp_id']]);
    $actors = $aStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($actors as &$a) {
        $a['isVerified'] = strtolower((string)($a['isVerified'] ?? '')) === 'yes';
        $a['profilePic'] = htmlspecialchars_decode((string)($a['profilePic'] ?? ''));
    }
    unset($a);

    $replyData = null;
    if ($g['type'] === 'reply') {
        $rStmt = $db->prepare("
            SELECT ch.id, ch.chirp, ch.timestamp,
                   u.username, u.name, u.profilePic, u.isVerified,
                   (SELECT COUNT(*) FROM likes WHERE chirp_id = ch.id) as like_count,
                   (SELECT COUNT(*) FROM rechirps WHERE chirp_id = ch.id) as rechirp_count,
                   (SELECT COUNT(*) FROM chirps r WHERE r.parent = ch.id AND r.type = 'reply') as reply_count,
                   (SELECT COUNT(*) FROM likes WHERE chirp_id = ch.id AND user_id = :uid2) as liked_by_me,
                   (SELECT COUNT(*) FROM rechirps WHERE chirp_id = ch.id AND user_id = :uid2) as rechirped_by_me
            FROM notifications n
            JOIN chirps ch ON ch.id = n.chirp_id
            JOIN users u ON u.id = n.actor_id
            WHERE n.user_id = :uid AND n.type = 'reply' AND n.chirp_id = :cid
            ORDER BY n.timestamp DESC LIMIT 1
        ");
        $rStmt->execute([':uid' => $userId, ':uid2' => $userId, ':cid' => $g['chirp_id']]);
        $replyData = $rStmt->fetch(PDO::FETCH_ASSOC);
        if ($replyData) {
            $replyData['isVerified'] = strtolower((string)($replyData['isVerified'] ?? '')) === 'yes';
            $replyData['profilePic'] = htmlspecialchars_decode((string)($replyData['profilePic'] ?? ''));
            $replyData['liked_by_me'] = (bool)$replyData['liked_by_me'];
            $replyData['rechirped_by_me'] = (bool)$replyData['rechirped_by_me'];
        }
    }

    $chirpText = strip_tags($g['chirp_text']);
    if (strlen($chirpText) > 80) $chirpText = substr($chirpText, 0, 80) . '…';

    $results[] = [
        'type'       => $g['type'],
        'chirp_id'   => (int)$g['chirp_id'],
        'chirp_text' => $chirpText,
        'actor_count'=> (int)$g['actor_count'],
        'unread'     => (int)$g['unread_count'] > 0,
        'latest_ts'  => (int)$g['latest_ts'],
        'actors'     => $actors,
        'reply'      => $replyData,
    ];
}

echo json_encode($results);
