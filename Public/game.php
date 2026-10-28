<?php
session_start();
require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';
require_once __DIR__ . "/gameData.php";

if (($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

// Read and validate IGDB game ID from URL
$gameId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

$game = null;
$errorMessage = "";
//All community and reveiw related stuff
$reviewMessage = "";
$reviewSuccess = false;
$ratings = [];
$ratingsError = "";
$submittedReview = "";
$submittedRating = "";

if (empty($_SESSION["review_csrf"])) {
    $_SESSION["review_csrf"] = bin2hex(random_bytes(32));
}

if ($gameId === false || $gameId === null) {
    $errorMessage = "Invalid game ID.";
    http_response_code(400);
} else {
    try {
       
        $client = new rabbitMQClient(
            __DIR__ . "/../testRabbitMQ.ini",
            "testServer"
        );

        $dbClient = new rabbitMQClient(
            __DIR__ . '/../testRabbitMQDatabase.ini',
            'databaseServer'
        );

        // Only submit a review when the user submits the form.
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $submittedRating = $_POST["rating"] ?? "";
            $submittedReview = $_POST["review"] ?? "";
            $rating = filter_var($submittedRating, FILTER_VALIDATE_INT, [
                "options" => ["min_range" => 1, "max_range" => 5]
            ]);
            $username = $_SESSION["username"] ?? $_SESSION["login_name"] ?? null;

            if (!hash_equals($_SESSION["review_csrf"], (string)($_POST["review_csrf"] ?? ""))) {
                $reviewMessage = "Invalid form token. Refresh the page and try again.";
            } elseif (!is_string($username) || !preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $username)) {
                $reviewMessage = "Please log in again to submit a review.";
            } elseif ($rating === false || $rating === null || !is_string($submittedReview)
                || trim($submittedReview) === "" || mb_strlen($submittedReview) > 5000) {
                $reviewMessage = "Choose a rating from 1 to 5 and enter a review (maximum 5000 characters).";
            } else {
                try {
                    $writeResponse = $dbClient->send_request([
                        "type" => "write_rating",
                        "game_id" => $gameId,
                        "username" => $username,
                        "rating" => $rating,
                        "review" => $submittedReview
                    ]);
                    if ($writeResponse ==true || $writeResponse == 1) {
                        $reviewSuccess = true;
                        $reviewMessage = "Your review was submitted successfully.";
                        $submittedReview = "";
                        $submittedRating = "";
                        $_SESSION["review_csrf"] = bin2hex(random_bytes(32));
                    } else {
                        $reviewMessage = "The database did not confirm that your review was saved.";
                    }
                } catch (Throwable $e) {
                    error_log("Review submission failed: " . $e->getMessage());
                    $reviewMessage = "Unable to submit your review right now.";
                }
            }
        }

        // Retrieve reviews for this game directly from the Database VM.
        try {
            $ratingsResponse = $dbClient->send_request([
                "type" => "get_ratings",
                "game_id" => $gameId
            ]);
            if (is_array($ratingsResponse) && ($ratingsResponse["success"] ?? false) === true
                && is_array($ratingsResponse["ratings"] ?? null)) {
                $ratings = $ratingsResponse["ratings"];
            } else {
                $ratingsError = "Community reviews are unavailable right now.";
            }
        } catch (Throwable $e) {
            error_log("Review lookup failed: " . $e->getMessage());
            $ratingsError = "Community reviews are unavailable right now.";
        }

        $response = $client->send_request([
            "type" => "getGameDetails",
            "gameId" => $gameId
        ]);

        if (!is_array($response)) {
            throw new RuntimeException("Invalid backend response.");
        }

        if (($response["success"] ?? false) !== true) {
            if (($response["message"] ?? "") === "Game not found.") {
                http_response_code(404);
                $errorMessage = "Game not found.";
            } else {
                throw new RuntimeException("Backend game lookup failed.");
            }
        } else {
            $data = $response["game"] ?? null;

            if (!is_array($data) || !isset($data["id"], $data["name"])) {
                throw new RuntimeException("Invalid game data.");
            }

            // IGDB cover image
            $imageId = $data["cover"]["image_id"] ?? "";

            $cover = "";

            if (is_string($imageId) &&
                preg_match('/^[a-zA-Z0-9_]+$/', $imageId)) {
                $cover = "https://images.igdb.com/igdb/image/upload/t_cover_big/"
                       . $imageId . ".jpg";
            }

            //  release date
            $releaseDate = "Unknown";

            if (isset($data["first_release_date"]) &&
                is_numeric($data["first_release_date"])) {
                $releaseDate = gmdate(
                    "Y-m-d",
                    (int)$data["first_release_date"]
                );
            }

            // genres
            $genres = [];

            foreach (($data["genres"] ?? []) as $genre) {
                if (is_array($genre) && isset($genre["name"])) {
                    $genres[] = (string)$genre["name"];
                }
            }

            $genreText = !empty($genres)
                ? implode(", ", $genres)
                : "Unknown";

            
            $game = [
                "id" => (int)$data["id"],
                "name" => (string)$data["name"],
                "genre" => $genreText,
                "release_date" => $releaseDate,
                "description" => (string)(
                    $data["summary"] ?? "No description available."
                ),
                "cover" => $cover,
                "rating" => isset($data["rating"]) &&
                            is_numeric($data["rating"])
                    ? number_format((float)$data["rating"], 1) . "/100"
                    : "Not rated"
            ];
        }

    } catch (Throwable $e) {
        error_log("Game details failed: " . $e->getMessage());

        $errorMessage = "Unable to load game details right now.";
        http_response_code(502);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $game
            ? escapeHTML($game["name"])
            : "Game Not Found"; ?>
        | 
    </title>

    <link rel="stylesheet" href="css/styles.css">

    <style>
        /* I hate it here */

        .game-page {
            max-width: 1150px;
            margin: 40px auto;
            padding: 0 20px 50px;
            color: #1e293b;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .game-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 35px;
            align-items: start;
        }

        .detail-cover {
            min-height: 400px;
            background: linear-gradient(
                135deg, #1e293b, #475569
            );
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
            color: white;
            font-size: 30px;
            font-weight: bold;
        }

        .detail-cover img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }

        .detail-info {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
        }

        .detail-info h1 {
            font-size: 36px;
            margin: 0 0 20px;
            color: #0f172a;
        }

        .genre-badge {
            display: inline-block;
            padding: 7px 14px;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .game-meta {
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .game-description {
            line-height: 1.8;
            color: #475569;
            margin-bottom: 25px;
        }

        .detail-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .detail-actions button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
        }

        .watchlist-button {
            background: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        .section-card {
            margin-top: 30px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
        }

        .section-card h2 {
            margin: 0 0 15px;
            color: #0f172a;
        }

        .review-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .review-form label {
            font-weight: 600;
        }

        .review-form select,
        .review-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            background: white;
            color: #0f172a;
        }

        .review-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-review {
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            align-self: flex-start;
            cursor: pointer;
            opacity: 1;
        }

        .community-review {
            padding: 20px;
            margin-top: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .community-review h3 { margin: 0 0 10px; color: #1d4ed8; }
        .community-review p { margin: 8px 0; color: #334155; line-height: 1.6; }

        .placeholder-text {
            color: #64748b;
            line-height: 1.6;
        }

        .not-found {
            text-align: center;
            padding: 70px 20px;
        }

        @media (max-width: 750px) {
            .game-layout {
                grid-template-columns: 1fr;
            }

            .detail-cover {
                min-height: 300px;
            }

            .detail-info h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<?php require __DIR__ . "/../Partials/Navigation.php"; ?>

<main class="game-page">

    <a class="back-link" href="browse.php">
        &larr; Back to Browse Games
    </a>

    <?php if ($game === null): ?>

        <div class="not-found">
            <h1>Game Unavailable</h1>
            <p>
                <?php echo escapeHTML(
                    $errorMessage ?: "The requested game does not exist."
                ); ?>
            </p>
        </div>

    <?php else: ?>

        <!-- Game info -->
        <div class="game-layout">

            <div class="detail-cover">
                <?php if (!empty($game["cover"])): ?>

                    <img
                        src="<?php echo escapeHTML($game["cover"]); ?>"
                        alt="<?php echo escapeHTML($game["name"]); ?> cover"
                    >

                <?php else: ?>

                    <?php echo escapeHTML($game["name"]); ?>

                <?php endif; ?>
            </div>

            <section class="detail-info">

                <h1>
                    <?php echo escapeHTML($game["name"]); ?>
                </h1>

                <span class="genre-badge">
                    <?php echo escapeHTML($game["genre"]); ?>
                </span>

                <div class="game-meta">
                    <p>
                        <strong>Release Date:</strong>
                        <?php echo escapeHTML($game["release_date"]); ?>
                    </p>

                    <p>
                        <strong>Genre:</strong>
                        <?php echo escapeHTML($game["genre"]); ?>
                    </p>

                    <p>
                        <strong>IGDB Rating:</strong>
                        <?php echo escapeHTML($game["rating"]); ?>
                    </p>
                </div>

                <h2>About This Game</h2>

                <p class="game-description">
                    <?php echo escapeHTML($game["description"]); ?>
                </p>

                <div class="detail-actions">

                    <!-- FUTURE WATCHLIST FEATURE -->
                    <button
                        class="watchlist-button"
                        type="button"
                        disabled
                    >
                        + Add to Watchlist
                    </button>

                </div>

            </section>

        </div>

        <!-- Ratings and reviews -->
        <section class="section-card">

            <h2>Rate &amp; Review This Game</h2>

            <?php if ($reviewMessage !== ""): ?>
                <p role="status" style="color: <?php echo $reviewSuccess ? '#166534' : '#b91c1c'; ?>;">
                    <?php echo escapeHTML($reviewMessage); ?>
                </p>
            <?php endif; ?>

            <form class="review-form" method="POST" action="game.php?id=<?php echo (int)$gameId; ?>">
                <input type="hidden" name="review_csrf"
                       value="<?php echo escapeHTML($_SESSION["review_csrf"]); ?>">

                <label for="rating">Your Rating</label>
                <select id="rating" name="rating" required>
                    <option value="">Select a rating</option>
                    <?php foreach ([5 => "Excellent", 4 => "Great", 3 => "Good", 2 => "Fair", 1 => "Poor"] as $stars => $label): ?>
                        <option value="<?php echo $stars; ?>" <?php echo (string)$submittedRating === (string)$stars ? 'selected' : ''; ?>>
                            <?php echo $stars . ' Stars - ' . $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="review">Write Your Review</label>
                <textarea id="review" name="review" maxlength="5000" required
                    placeholder="What did you think of this game?"><?php echo escapeHTML(is_string($submittedReview) ? $submittedReview : ''); ?></textarea>

                <button type="submit" class="submit-review">Submit Review</button>
            </form>

        </section>

        <!-- Community reviews -->
        <section class="section-card">

            <h2>Community Reviews</h2>

            <?php if ($ratingsError !== ""): ?>
                <p class="placeholder-text"><?php echo escapeHTML($ratingsError); ?></p>
            <?php elseif (empty($ratings)): ?>
                <p class="placeholder-text">No community reviews are available yet.</p>
            <?php else: ?>
                <?php foreach ($ratings as $rating_full): ?>
                    <?php if (!is_array($rating_full)) continue; ?>
                    <div class="community-review">
                        <h3><?php echo escapeHTML($rating_full["username"] ?? "Unknown user"); ?></h3>
                        <p><strong>Rating:</strong> <?php echo escapeHTML($rating_full["rating"] ?? "?"); ?>/5</p>
                        <p><?php echo nl2br(escapeHTML($rating_full["review"] ?? "")); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
