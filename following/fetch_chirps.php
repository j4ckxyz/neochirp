<?php

// Allow same-origin requests only
if (defined('APP_DOMAIN')) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = ['https://' . APP_DOMAIN, 'http://' . APP_DOMAIN];
    if ($origin && in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
    }
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'not_signed_in']);
    exit;
}

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
    $limit = 12;
    $currentUserId = (int)$_SESSION['user_id'];

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
          AND chirps.user IN (
              SELECT following_id FROM following WHERE follower_id = :current_user_id
          )
        ORDER BY chirps.timestamp DESC
        LIMIT :limit OFFSET :offset';

    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();

    $chirps = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
