CREATE TABLE /*_*/rottenlinks (
  `rl_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rl_externallink` BLOB NOT NULL,
  `rl_respcode` INT UNSIGNED NOT NULL,
  `rl_pageusage` LONGTEXT NOT NULL
) /*$wgDBTableOptions*/;
