<?php
session_start();
if (($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

// RabbitMQ client files must be available on the frontend VM.
require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';

require_once __DIR__ . "/gameData.php";

function escapeHTML($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

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
            // Must point to the same broker/queue as your working testIGDBRabbitMQ.php.
            $client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
            $response = $client->send_request([
                'type' => 'searchGames',
                'gameName' => $search
            ]);

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

                $games[] = [
                    'id' => (int)$item['id'],
                    'name' => (string)$item['name'],
                    'genre' => 'Genre unavailable', // Add genres to backend IGDB fields later.
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
$filteredGames = $games;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Browse Games</title>

    <link rel="stylesheet" href="css/styles.css">

    <style>
        /* Css styling, for the browser page specifically, regular stylesheet included above*/ 
        /* Some of the most annoying testing of all time */
        /* May god help us all, this is annoying v2*/

        .browse-container {
            max-width: 1250px;
            margin: 40px auto;
            padding: 0 24px 50px;
            color: #dbeafe;
        }

        .browse-container,
        .browse-container * {
            box-sizing: border-box;
        }

        .browse-header h1 {
            color: #f1f5f9;
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 800;
        }

        .browse-header p,
        .game-count {
            color: #b6c6db;
        }

        
        .browse-filters {
            display: grid;
            grid-template-columns: minmax(180px, 2fr) 1fr 1fr auto;
            width: 100%;
            max-width: none;
            margin: 0 0 28px;
            padding: 20px;
            gap: 12px;
            background: #142237;
            border: 1px solid #34465e;
            border-radius: 16px;
            box-shadow: none;
        }

        .browse-filters input,
        .browse-filters select {
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 12px 14px;
            background: #f8fafc;
            color: #172554;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font: inherit;
        }

        .browse-filters input::placeholder {
            color: #64748b;
        }

        .browse-filters button {
            width: auto;
            margin: 0;
            padding: 12px 24px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 9px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .browse-filters button:hover {
            background: #1d4ed8;
        }

        /* Game cards */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            align-items: stretch;
        }

        .game-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #f8fafc;
            color: #172554;
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.28);
        }

        .game-cover {
            height: 190px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dbeafe, #c7d2fe);
            color: #172554;
            border-bottom: 1px solid #bdcdee;
            font-size: 27px;
            font-weight: 800;
            line-height: 1.25;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .game-cover:has(img) {
            padding: 0;
        }

        .game-cover img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Details below the cover */
        .game-info {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            padding: 22px;
            background: #f8fafc;
        }

        .game-info h3 {
            margin: 0 0 12px;
            color: black;
            font-size: 23px;
            font-weight: 800;
            line-height: 1.3;
            text-shadow: none;
        }

        .game-genre {
            align-self: flex-start;
            margin-bottom: 16px;
            padding: 5px 11px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .game-description {
            flex-grow: 1;
            margin: 0 0 18px;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
        }

        .game-release {
            margin: 0 0 18px;
            color: #526176;
            font-size: 12px;
        }

        .game-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .game-actions a,
        .game-actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-width: 0;
            margin: 0;
            padding: 11px 8px;
            border-radius: 9px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
        }

        .details-btn {
            background: #2563eb;
            color: #fff;
        }

        .details-btn:hover {
            background: #1d4ed8;
        }

        .watchlist-btn:disabled {
            background: #e2e8f0;
            color: #526176;
            border: 1px solid #cbd5e1;
            opacity: 1;
            cursor: not-allowed;
        }

        .no-results,
        .no-results h2,
        .no-results p {
            color: #cbd5e1;
        }

        .browse-container:is(input, select, button, a):focus-visible {
            outline: 3px solid #60a5fa;
            outline-offset: 3px;
        }
        /* Clickable game titles */
        .game-title a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 700;
        }

        .game-title a:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        @media (max-width: 900px) {
            .browse-container .browse-filters {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .browse-filters button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .browse-container .game-card {
                transition: none;
            }

            .game-card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>

<?php
    require __DIR__ . "/../Partials/Navigation.php"; 
?>

<main class="browse-container">
    <div class="browse-header">
        <h1>Browse Games</h1>
        <p>Discover new games, explore genres, and build your watchlist.</p>
    </div>
    <!-- Search stuff -->
    <form class="browse-filters" method="GET" action="browse.php">
        <input
            type="search" name="search" placeholder="Search for a game..." value="<?php echo escapeHTML($search); ?>">

        <!-- Genre filter will be enabled when backend IGDB queries include genres. -->
        <select name="genre" disabled title="Genre filtering coming soon">
            <option>All Genres (coming soon)</option>
        </select>
            <!-- Sorting stuff -->
        <select name="sort">
            <option value="name" <?php echo $sort === "name" ? "selected" : ""; ?>>
                Name (A-Z)
            </option>
            <option value="newest" <?php echo $sort === "newest" ? "selected" : ""; ?>>
                Newest First
            </option>
            <option value="oldest" <?php echo $sort === "oldest" ? "selected" : ""; ?>>
                Oldest First
            </option>
        </select>
        <button type="submit">Search</button>
    </form>

    <p class="game-count">
        <?php echo $searched ? 'Showing ' . count($filteredGames) . ' games' : 'Search for a game to get started'; ?>
    </p>
    <?php if ($errorMessage !== ''): ?>
        <div class="no-results" role="alert"><p><?php echo escapeHTML($errorMessage); ?></p></div>
    <?php endif; ?>

    <!-- Game cards -->
    <?php if (!empty($filteredGames)): ?>
        <div class="games-grid">
            <?php foreach ($filteredGames as $game): ?>
                <article class="game-card">
                    <div class="game-cover">
                        <?php if (!empty($game["cover"])): ?>
                            <img
                                src="<?php echo escapeHTML($game["cover"]); ?>"
                                alt="<?php echo escapeHTML($game["name"]); ?> cover"
                                loading="lazy">
                        <?php else: ?>
                            <?php echo escapeHTML($game["name"]); ?>
                        <?php endif; ?>
                    </div>
                    <div class="game-info">
                                                
                        <h3 class="game-title">
                            <a href="game.php?id=<?php echo urlencode((string)$game["id"]); ?>">
                                <?php echo escapeHTML($game["name"]); ?>
                            </a>
                        </h3>

                        <div class="game-genre">
                            <?php echo escapeHTML($game["genre"]); ?>
                        </div>
                        <p class="game-description">
                            <?php echo escapeHTML($game["description"]); ?>
                        </p>
                        <p class="game-release">
                            IGDB Rating: <?php echo escapeHTML($game["rating"]); ?>
                        </p>
                        <p class="game-release">
                            Release Date:
                            <?php echo escapeHTML($game["release_date"]); ?>
                        </p>
                        <div class="game-actions">
                            <!--
                            Gonna be a game page that shows the specific games stuff from the api
                            hopefully
                            -->
                            <a
                                class="details-btn"
                                href="game.php?id=<?php echo urlencode((string)$game["id"]); ?>"
                            >
                                View Details
                            </a>
                            <!--
                            Should be where watchlist stuff goes,
                            hopefully
                            -->
                            <button
                                class="watchlist-btn"
                                type="button"
                                disabled
                                title="Watchlist function coming soon"
                            >
                                + Watchlist
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php elseif ($errorMessage === ''): ?>
        <div class="no-results">
            <h2><?php echo $searched ? 'No games found' : 'Find your next game'; ?></h2>
            <p><?php echo $searched ? 'Try another game name.' : 'Enter a game title in the search box above.'; ?></p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
