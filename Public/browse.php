<?php
session_start();
//IT202 repo, youtube browse pg
//User loggedin
if (($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';

require_once __DIR__ . '/gameData.php';

$_SESSION['watchlist_csrf'] ??= bin2hex(random_bytes(32));
$watchlistNotice = $_SESSION['watchlist_notice'] ?? null;
unset($_SESSION['watchlist_notice']);

$search = trim((string)($_GET['search'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'name');

if (!in_array($sort, ['name', 'newest', 'oldest'], true)) {
    $sort = 'name';
}

$games = [];
$errorMessage = '';
$searched = $search !== '';

if ($searched) {
    if (mb_strlen($search) > 100) {
        $errorMessage = 'Please use a search term of 100 characters or fewer.';
    } else {
        try {
            $client = new rabbitMQClient(__DIR__ . '/../testRabbitMQ.ini', 'testServer');
            //DB Requst
            $response = $client->send_request([
                'type' => 'searchGames',
                'gameName' => $search
            ]);
            //Backend chekc
            if (!is_array($response) || ($response['success'] ?? false) !== true) {
                throw new RuntimeException(
                    is_array($response) ? (string)($response['message'] ?? 'Game search failed.') : 'Invalid backend response.'
                );
            }
            if (!isset($response['games']) || !is_array($response['games'])) {
                throw new RuntimeException('Backend returned an invalid games list.');
            }

            foreach ($response['games'] as $item) {
                if (!is_array($item) || !isset($item['id'], $item['name'])) {
                    continue;
                }
                $imageId = $item['cover']['image_id'] ?? '';
                $cover = is_string($imageId) && preg_match('/^[a-zA-Z0-9_]+$/', $imageId)
                    ? 'https://images.igdb.com/igdb/image/upload/t_cover_big/' . $imageId . '.jpg'
                    : '';
                $timestamp = $item['first_release_date'] ?? null;
                $date = is_numeric($timestamp) ? gmdate('Y-m-d', (int)$timestamp) : '';
                $rating = isset($item['rating']) && is_numeric($item['rating'])
                    ? number_format((float)$item['rating'], 1) . '/100'
                    : 'Not rated';

                //info stored 
                $games[] = [
                    'id' => (int)$item['id'],
                    'name' => (string)$item['name'],
                    'genre' => 'Genre unavailable',
                    'release_date' => $date ?: 'Unknown',
                    'description' => (string)($item['summary'] ?? 'No description available.'),
                    'cover' => $cover,
                    'rating' => $rating
                ];
            }
        } catch (Throwable $e) {
            error_log('Browse IGDB search failed: ' . $e->getMessage());
            $errorMessage = 'Unable to load games right now. Please try again later.';
        }
    }
}

usort($games, function ($a, $b) use ($sort) {
    if ($sort === 'newest') return strcmp($b['release_date'], $a['release_date']);
    if ($sort === 'oldest') return strcmp($a['release_date'], $b['release_date']);
    return strcasecmp($a['name'], $b['name']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Games</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/browse.css">
</head>
<body>
<?php require __DIR__ . '/../Partials/Navigation.php'; ?>
<main class="browse-container">
    <div class="browse-header">
        <h1>Browse Games</h1>
        <p>Discover new games, explore genres, and build your watchlist.</p>
    </div>

    <?php if (is_array($watchlistNotice)): ?>
        <div class="watchlist-notice<?= ($watchlistNotice['success'] ?? false) ? '' : ' error' ?>" role="status">
            <?= escapeHTML((string)($watchlistNotice['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <form class="browse-filters" method="GET" action="browse.php">
        <input type="search" name="search" placeholder="Search for a game..." value="<?= escapeHTML($search) ?>">
        <select name="genre" disabled title="Genre filtering coming soon">
            <option>All Genres (coming soon)</option>
        </select>

        <select name="sort">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <p class="game-count">
        <?= $searched ? 'Showing ' . count($games) . ' games' : 'Search for a game to get started' ?>
    </p>
    <?php if ($errorMessage !== ''): ?>
        <div class="no-results" role="alert"><p><?= escapeHTML($errorMessage) ?></p></div>
    <?php endif; ?>

    <?php if (!empty($games)): ?>
        <div class="games-grid">
            <?php foreach ($games as $game): ?>
                <article class="game-card">
                    <div class="game-cover">
                        <?php if (!empty($game['cover'])): ?>
                            <img src="<?= escapeHTML($game['cover']) ?>" alt="<?= escapeHTML($game['name']) ?> cover" loading="lazy">
                        <?php else: ?>
                            <?= escapeHTML($game['name']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">
                            <a href="game.php?id=<?= urlencode((string)$game['id']) ?>"><?= escapeHTML($game['name']) ?></a>
                        </h3>
                        <div class="game-genre"><?= escapeHTML($game['genre']) ?></div>
                        <p class="game-description"><?= escapeHTML($game['description']) ?></p>
                        <p class="game-release">IGDB Rating: <?= escapeHTML($game['rating']) ?></p>
                        <p class="game-release">Release Date: <?= escapeHTML($game['release_date']) ?></p>
                        <div class="game-actions">
                            <a class="details-btn" href="game.php?id=<?= urlencode((string)$game['id']) ?>">View Details</a>
                            <form method="POST" action="watchlist_action.php">
                                <input type="hidden" name="csrf_token" value="<?= escapeHTML($_SESSION['watchlist_csrf']) ?>">
                                <input type="hidden" name="game_igdb_id" value="<?= (int)$game['id'] ?>">
                                <input type="hidden" name="search" value="<?= escapeHTML($search) ?>">
                                <input type="hidden" name="sort" value="<?= escapeHTML($sort) ?>">
                                <button class="watchlist-btn" type="submit">+ Watchlist</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php elseif ($errorMessage === ''): ?>
        <div class="no-results">
            <h2><?= $searched ? 'No games found' : 'Find your next game' ?></h2>
            <p><?= $searched ? 'Try another game name.' : 'Enter a game title in the search box above.' ?></p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
