CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE `games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `igdb_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `summary` text,
  `cover` varchar(500) DEFAULT NULL,
  `rating` decimal(5,2) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `first_release_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `igdb_id` (`igdb_id`)
);

CREATE TABLE `release_dates` (
  `game_release_id` int NOT NULL AUTO_INCREMENT,
  `game_id` int DEFAULT NULL,
  `release_date` datetime DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`game_release_id`),
  KEY `fk_game_id` (`game_id`),
  CONSTRAINT `fk_game_id` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`)
);

CREATE TABLE `game_ratings` (
  `rating_id` int NOT NULL AUTO_INCREMENT,
  `game_id` int DEFAULT NULL,
  `username` varchar(30) DEFAULT NULL,
  `rating` decimal(5,2) DEFAULT NULL,
  `review` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rating_id`),
  KEY `fk_rating_game_id` (`game_id`),
  KEY `fk_rating_username` (`username`),
  CONSTRAINT `fk_rating_game_id` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  CONSTRAINT `fk_rating_username` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
);
