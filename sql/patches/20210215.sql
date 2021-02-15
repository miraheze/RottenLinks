BEGIN;

ALTER TABLE /*$wgDBprefix*/rottenlinks
    MODIFY COLUMN rl_externallink BLOB NOT NULL;

CREATE INDEX /*i*/rl_externallink ON /*_*/rottenlinks (rl_externallink);
CREATE INDEX /*i*/rl_respcode ON /*_*/rottenlinks (rl_externallink);
CREATE INDEX /*i*/rl_pageusage ON /*_*/rottenlinks (rl_externallink);

COMMIT;
