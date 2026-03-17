<?php
 }

require_once __DIR__ . '/../../auth.php';
$apiUser = chirp_api_auth();
$db = chirp_api_db();

$chirpId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$chirpId) chirp_api_error(400, 'Missing id parameter');

$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit  = 20;

// Verify chirp exists
$check = $db->prepare('SELECT id FROM chirps WHERE id = :id');
$check->execute([':id' => $chirpId]);
if (!$check->fetch()) chirp_api_error(404, 'Chirp not found');

$stmt = $db->prepare('
    SELECT u.id, u.username, u.name, u.profilePic, u.bio, u.isVerified,
           (SELECT COUNT(*) FROM following WHERE following_id = u.id) AS follower_count
    FROM likes l
    JOIN users u ON u.id = l.user_id
    WHERE l.chirp_id = :cid
    ORDER BY l.timestamp DESC
    LIMIT :lim OFFSET :off
');
$stmt->execute([':cid' => $chirpId, ':lim' => $limit, ':off' => $offset]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($rows as $row) {
    $row['isVerified'] = (bool)$row['isVerified'];
    $row['profilePic'] = htmlspecialchars_decode((string)($row['profilePic'] ?? ''));
    $result[] = $row;
}

echo json_encode($result);
