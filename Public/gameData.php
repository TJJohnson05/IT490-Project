
<?php
// Temporary game using same sample games as whats in browse.php
function getSampleGames(): array
{
    return [
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
            "description" => "Build, explore, and survive in a world made of blocks.",
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
            "description" => "Build your dream farm, meet villagers, and explore the countryside.",
            "cover" => ""
        ],
        [
            "id" => 5,
            "name" => "Hollow Knight",
            "genre" => "Platformer",
            "release_date" => "2017-02-24",
            "description" => "Explore an underground kingdom filled with mysterious creatures.",
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
}
function escapeHTML($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
Replace getSampleGames() with a function that sends a request to backend through RabbitMQ. Still using testQueue, Jaiden should use backend listener we had before 
*/
