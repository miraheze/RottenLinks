CREATE TABLE /*_*/rottenlinks (
  `rl_externallink` VARCHAR(512) NOT NULL PRIMARY KEY,
  `rl_respcode` INT UNSIGNED NOT NULL,
  `rl_pageusage` LONGTEXT NOT NULL
) /*$wgDBTableOptions*/;
