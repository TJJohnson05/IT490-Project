
<?php
session_start();
// Only logged in users
if (($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}
/*
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

$request = [
    "type" => "get_games"
];

$response = $client->send_request($request);

$games = $response["games"] ?? [];
*/
// Temporary game data for testing
$games = [
    [
        "id" => 1,
        "name" => "Elden Ring",
        "genre" => "RPG",
        "release_date" => "2022-02-25",
        "description" => "Explore a vast fantasy world filled with challenging enemies, powerful bosses, and hidden secrets.",
        "cover" => ""
    ],
    [
        "id" => 2,
        "name" => "Minecraft",
        "genre" => "Sandbox",
        "release_date" => "2011-11-18",
        "description" => "Build, explore, and survive in an endless world made entirely of blocks.",
        "cover" => ""
    ],
    [
        "id" => 3,
        "name" => "Cyberpunk 2077",
        "genre" => "RPG",
        "release_date" => "2020-12-10",
        "description" => "Become a mercenary in Night City, a futuristic world driven by technology and power.",
        "cover" => ""
    ],
    [
        "id" => 4,
        "name" => "Stardew Valley",
        "genre" => "Simulation",
        "release_date" => "2016-02-26",
        "description" => "Build your dream farm, meet villagers, and explore a charming countryside.",
        "cover" => ""
    ],
    [
        "id" => 5,
        "name" => "Hollow Knight",
        "genre" => "Platformer",
        "release_date" => "2017-02-24",
        "description" => "Explore an interconnected underground kingdom filled with mysterious creatures.",
        "cover" => ""
    ],
    [
        "id" => 6,
        "name" => "Red Dead Redemption 2",
        "genre" => "Action",
        "release_date" => "2018-10-26",
        "description" => "Experience an epic western adventure in a massive open world.",
        "cover" => ""
    ],
    [
        "id" => 7,
        "name" => "Baldur's Gate 3",
        "genre" => "RPG",
        "release_date" => "2023-08-03",
        "description" => "Gather your party and embark on an adventure shaped by your choices.",
        "cover" => ""
    ],
    [
        "id" => 8,
        "name" => "Hades",
        "genre" => "Roguelike",
        "release_date" => "2020-09-17",
        "description" => "Battle your way out of the underworld in a fast-paced action adventure.",
        "cover" => ""
    ]
];

//function for displaying things as text
function escapeHTML($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Filter games using search input
$search = trim($_GET["search"] ?? "");
$genre = trim($_GET["genre"] ?? "");
$sort = $_GET["sort"] ?? "name";

$allowedSorts = ["name", "newest", "oldest"];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "name";
}

// Looking for available genres 
$genres = array_unique(array_column($games, "genre"));
sort($genres);

// Filters
$filteredGames = array_filter($games, function($game) use ($search, $genre) {

    $matchesSearch = $search === "" ||
        stripos($game["name"], $search) !== false;

    $matchesGenre = $genre === "" ||
        $game["genre"] === $genre;

    return $matchesSearch && $matchesGenre;
});

// Sorting specific results
usort($filteredGames, function($a, $b) use ($sort) {

    if ($sort === "newest") {
        return strcmp($b["release_date"], $a["release_date"]);
    }

    if ($sort === "oldest") {
        return strcmp($a["release_date"], $b["release_date"]);
    }

    return strcasecmp($a["name"], $b["name"]);
});

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

        .browse-container {
            max-width: 1250px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .browse-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .browse-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .browse-header p {
            color: #777;
            font-size: 16px;
        }

        .browse-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 10px;
        }

        .browse-filters input,
        .browse-filters select {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            flex: 1;
            min-width: 160px;
        }

        .browse-filters button {
            padding: 12px 25px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .browse-filters button:hover {
            background: #1d4ed8;
        }

        .game-count {
            margin-bottom: 20px;
            color: #666;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .game-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .game-card:hover {
            transform: translateY(-5px);
        }

        .game-cover {
            height: 220px;
            background: linear-gradient(135deg, #1e293b, #334155);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }

        .game-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .game-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .game-info h3 {
            margin: 0 0 10px;
            font-size: 21px;
        }

        .game-genre {
            color: #2563eb;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .game-description {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .game-release {
            font-size: 13px;
            color: #777;
            margin-bottom: 15px;
        }

        .game-actions {
            display: flex;
            gap: 10px;
        }

        .game-actions a,
        .game-actions button {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            text-align: center;
        }

        .details-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
        }

        .watchlist-btn {
            background: #e5e7eb;
            border: none;
            color: #777;
            cursor: not-allowed;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #777;
        }

        @media (max-width: 600px) {
            .browse-header h1 {
                font-size: 28px;
            }

            .browse-filters {
                flex-direction: column;
            }

            .browse-filters input,
            .browse-filters select,
            .browse-filters button {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php
// Load your existing navigation component
if (file_exists(__DIR__ . "/Navigation.php")) {
    include __DIR__ . "/Navigation.php";
}
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

        <select name="genre">
            <option value="">All Genres</option>
            <?php foreach ($genres as $gameGenre): ?>
                <option
                    value="<?php echo escapeHTML($gameGenre); ?>"
                    <?php echo $genre === $gameGenre ? "selected" : ""; ?>>
                    <?php echo escapeHTML($gameGenre); ?>
                </option>
            <?php endforeach; ?>
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
        Showing <?php echo count($filteredGames); ?> games
    </p>

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
                        <h3><?php echo escapeHTML($game["name"]); ?></h3>
                        <div class="game-genre">
                            <?php echo escapeHTML($game["genre"]); ?>
                        </div>
                        <p class="game-description">
                            <?php echo escapeHTML($game["description"]); ?>
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
    <?php else: ?>
        <div class="no-results">
            <h2>No games found</h2>
            <p>Try searching for another game or changing your filters.</p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
