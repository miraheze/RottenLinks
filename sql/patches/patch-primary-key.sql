 ALTER TABLE /*$wgDBprefix*/rottenlinks
   MODIFY COLUMN rl_externallink VARCHAR(2048) NOT NULL PRIMARY KEY;