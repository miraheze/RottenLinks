CREATE TABLE /*_*/rottenlinks (
  `rl_externallink` BLOB NOT NULL,
  `rl_respcode` INT UNSIGNED NOT NULL PRIMARY KEY,
  `rl_pageusage` LONGTEXT NOT NULL
) /*$wgDBTableOptions*/;
