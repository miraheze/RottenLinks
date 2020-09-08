 ALTER TABLE /*$wgDBprefix*/rottenlinks
   MODIFY COLUMN rl_externallink VARCHAR(512) NOT NULL PRIMARY KEY;