<?php

$allowedOrigins = [
    "https://beta.chirpsocial.net",
    "https://chirpsocial.net",
    "http://legacy.chirpsocial.net",
    "https://legacy.chirpsocial.net"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

function imageExists($url) {
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $headers = @get_headers($url, 1, $context);
    return $headers && strpos($headers[0], '200') !== false;
}

function makeLinksClickable($text) {
    $urlPattern = '/\b((https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,6}(\/[^\s]*)?(\?[^\s]*)?)/i';
    $text = preg_replace_callback($urlPattern, function($matches) {
        $url = $matches[1];
        $url = html_entity_decode($url);
        if (strpos($url, 'https://') !== 0 && strpos($url, 'http://') !== 0) {
            $url = 'http://' . $url;
        }
        $parsedUrl = parse_url($url);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
        foreach ($imageExtensions as $extension) {
            if (stripos($query, 'format=' . $extension) !== false) {
                $fileExtension = $extension;
                break;
            }
        }
        if (in_array(strtolower($fileExtension), $imageExtensions)) {
            try {
                if (imageExists($url)) {
                    return '<div class="chirpImageContainer"><img class="imageInChirp" src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="Photo"></div>';
                } else {
                    return '<div class="chirpsee">🧑‍⚖️ Media not displayed<p class="subText">This image cannot be displayed.</p><a class="subText" href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer">Learn more</a></div>';
                }
            } catch (Exception $e) {
                return '<div class="chirpsee">🧑‍⚖️ Media not displayed<p class="subText">This image cannot be displayed due to an error.</p></div>';
            }
        } else {
            return '<a class="linkInChirp" href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($url, ENT_QUOTES) . '</a>';
        }
    }, $text);

    $mentionPattern = '/(?<!\S)@([a-zA-Z0-9_]+)(?!\S)/';
    $text = preg_replace_callback($mentionPattern, function($matches) {
        $username = $matches[1];
        $profileUrl = '/user/?id=' . htmlspecialchars($username, ENT_QUOTES);
        return '<a class="linkInChirp" href="' . $profileUrl . '">@' . htmlspecialchars($username, ENT_QUOTES) . '</a>';
    }, $text);

    return $text;
}

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../../chirp.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $offset = isset($_GET['offset']) ? max((int)$_GET['offset'], 0) : 0;
    $pageSize = 12;
    $currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $now = time();
    $sevenDaysAgo = $now - (7 * 86400);

    // Fetch recent chirps with interaction counts
    $query = '
        SELECT chirps.*, users.username, users.name, users.profilePic, users.isVerified,
               (SELECT COUNT(*) FROM likes WHERE chirp_id = chirps.id) AS like_count,
               (SELECT COUNT(*) FROM rechirps WHERE chirp_id = chirps.id) AS rechirp_count,
               (SELECT COUNT(*) FROM chirps AS replies WHERE replies.parent = chirps.id AND replies.type = "reply") AS reply_count,
               (SELECT COUNT(*) FROM likes WHERE chirp_id = chirps.id AND user_id = :user_id) AS liked_by_current_user,
               (SELECT COUNT(*) FROM rechirps WHERE chirp_id = chirps.id AND user_id = :user_id) AS rechirped_by_current_user
        FROM chirps
        INNER JOIN users ON chirps.user = users.id
        WHERE chirps.type = "post"
          AND chirps.timestamp > :seven_days_ago
        ORDER BY chirps.timestamp DESC
        LIMIT 500';

    $stmt = $db->prepare($query);
    $stmt->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':seven_days_ago', $sevenDaysAgo, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current user's following list for network boost
    $followingIds = [];
    if ($currentUserId) {
        $followStmt = $db->prepare('SELECT following_id FROM following WHERE follower_id = :uid');
        $followStmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
        $followStmt->execute();
        while ($f = $followStmt->fetch(PDO::FETCH_ASSOC)) {
            $followingIds[$f['following_id']] = true;
        }
    }

    // Score each chirp using velocity algorithm:
    // score = (likes + rechirps*2 + replies) / (hours_since_post + 2)^1.5
    // Posts from followed users get a 1.5x network boost
    foreach ($rows as &$row) {
        $ageHours = ($now - (int)$row['timestamp']) / 3600.0;
        $interactions = (int)$row['like_count'] + (int)$row['rechirp_count'] * 2 + (int)$row['reply_count'];
        $decay = pow($ageHours + 2, 1.5);
        $score = $interactions / $decay;

        // Network boost for followed users
        if (isset($followingIds[(int)$row['user']])) {
            $score *= 1.5;
        }

        $row['_hot_score'] = $score;
    }
    unset($row);

    // Sort by score descending
    usort($rows, function($a, $b) {
        return $b['_hot_score'] <=> $a['_hot_score'];
    });

    // Paginate
    $page = array_slice($rows, $offset, $pageSize);

    $chirps = [];
    foreach ($page as $row) {
        unset($row['_hot_score']);
        $row['chirp'] = makeLinksClickable(htmlspecialchars($row['chirp']));
        $row['chirp'] = str_replace(["\r\n", "\r"], "\n", $row['chirp']);
        $row['chirp'] = preg_replace('/\n+/', "\n", $row['chirp']);
        $row['chirp'] = nl2br($row['chirp']);
        $row['username'] = htmlspecialchars($row['username']);
        $row['name'] = htmlspecialchars($row['name']);
        $row['profilePic'] = htmlspecialchars_decode((string)($row['profilePic'] ?? ''));
        $row['isVerified'] = (bool)$row['isVerified'];
        $row['liked_by_current_user'] = (bool)$row['liked_by_current_user'];
        $row['rechirped_by_current_user'] = (bool)$row['rechirped_by_current_user'];
        $chirps[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($chirps);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching chirps. Please try again later.']);
}
