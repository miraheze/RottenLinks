BEGIN;

CREATE INDEX /*i*/rl_externallink ON /*_*/rottenlinks (rl_externallink(50));
CREATE INDEX /*i*/rl_respcode ON /*_*/rottenlinks (rl_respcode);

COMMIT;
