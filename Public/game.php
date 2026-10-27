
<?php
session_start();

require_once __DIR__ . "/gameData.php";


if (empty($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}

// Get game id 
$gameId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

// Find  game
$game = null;

foreach (getSampleGames() as $item) {
    if ($gameId !== false &&
        $gameId !== null &&
        $item["id"] === $gameId) {
        $game = $item;
        break;
    }
}

// if game no exist
if ($game === null) {
    http_response_code(404);
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

    <link rel="stylesheet" href="styles.css">

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
            cursor: not-allowed;
            opacity: 0.65;
        }

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
            <h1>Game Not Found</h1>
            <p>The requested game does not exist.</p>
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

            <p class="placeholder-text">
                Review STUFF
            </p>

            <!--
            FUTURE RATING/REVIEW STUFF
            -->

            <form class="review-form">

                <label for="rating">
                    Your Rating
                </label>

                <select id="rating" name="rating" disabled>
                    <option value="">Select a rating</option>
                    <option value="5">5 Stars - Excellent</option>
                    <option value="4">4 Stars - Great</option>
                    <option value="3">3 Stars - Good</option>
                    <option value="2">2 Stars - Fair</option>
                    <option value="1">1 Star - Poor</option>
                </select>

                <label for="review">
                    Write Your Review
                </label>

                <textarea
                    id="review"
                    name="review"
                    placeholder="What did you think of this game?"
                    disabled
                ></textarea>

                <button
                    type="button"
                    class="submit-review"
                    disabled
                >
                    Submit Review
                </button>

            </form>

        </section>

        <!-- Community reviews -->
        <section class="section-card">

            <h2>Community Reviews</h2>

            <!--
            MORE REVIEW STUFF
            -->

            <p class="placeholder-text">
                No community reviews are available yet.
            </p>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
