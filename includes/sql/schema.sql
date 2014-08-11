/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Timecard database
 */


-- -----------------------------------------------------
-- Table `roles` 
-- -----------------------------------------------------

DROP TABLE IF EXISTS `roles` ;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `sortorder`   INT(11) NOT NULL DEFAULT 0 ,
  `name`  VARCHAR(255) NOT NULL ,
  `desc` TEXT NULL ,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name` ASC)
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `groups` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `groups` ;

CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL,
  `desc` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `users` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;

CREATE TABLE IF NOT EXISTS `users` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `login`  VARCHAR(40) NOT NULL ,
  `firstname`  VARCHAR(100) NOT NULL ,
  `lastname`  VARCHAR(100) NOT NULL ,
  `email`  VARCHAR(255) NOT NULL ,
  `desc`  TEXT NULL ,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`),
  UNIQUE KEY (`email`)
)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `group_hierarchy`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `group_hierarchy` ;

CREATE TABLE IF NOT EXISTS `group_hierarchy` (
  `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `group_id`  INT UNSIGNED NOT NULL,
  PRIMARY KEY (`group_id`) ,
  FOREIGN KEY (`parent_id`)
    REFERENCES `groups` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY (`group_id`)
    REFERENCES `groups` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `user_groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user_groups` ;

CREATE TABLE IF NOT EXISTS `user_groups` (
  `user_id` INT UNSIGNED NOT NULL ,
  `group_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`group_id`, `user_id`) ,
  FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  FOREIGN KEY (`group_id`) 
    REFERENCES `groups` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_roles` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user_roles` ;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`role_id`, `user_id`) ,
  FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  FOREIGN KEY (`role_id`) 
    REFERENCES `roles` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `passwords` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `passwords` ;

CREATE TABLE IF NOT EXISTS `passwords` (
  `user_id`  INT UNSIGNED NOT NULL ,
  `valid_date` DATETIME NOT NULL ,
  `expire_date` DATETIME NULL ,
  `reset` TINYINT NOT NULL DEFAULT 0,
  `reset_code` VARCHAR(255) NULL ,
  `hash` VARCHAR(255) NOT NULL ,
  `key` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`user_id`, `valid_date`) ,
  FOREIGN KEY (`user_id` )
    REFERENCES `users` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tasks` 
-- -----------------------------------------------------

DROP TABLE IF EXISTS `tasks` ;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `parent_id` INT UNSIGNED DEFAULT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `title` VARCHAR(255) NOT NULL ,
  `desc` TEXT ,
  `budget` TIME ,
  `progress` INT ,
  PRIMARY KEY (`id`) ,
  FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE ,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `timeslots` 
-- -----------------------------------------------------

DROP TABLE IF EXISTS `timeslots` ;

CREATE TABLE IF NOT EXISTS `timeslots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `task_id` INT UNSIGNED NOT NULL ,
  `begin` DATETIME NOT NULL ,
  `end` DATETIME ,
  `desc` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) ,
  FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
