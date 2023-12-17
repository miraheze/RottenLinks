BEGIN;

CREATE TABLE /*_*/rottenlinks (
  `rl_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `rl_externallink` BLOB NOT NULL,
  `rl_respcode` INT UNSIGNED NOT NULL
)/*$wgDBTableOptions*/;

CREATE INDEX /*i*/rl_externallink ON /*_*/rottenlinks (rl_externallink(50));
CREATE INDEX /*i*/rl_respcode ON /*_*/rottenlinks (rl_respcode);

COMMIT;
