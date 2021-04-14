BEGIN;

delimiter //

CREATE PROCEDURE removePrimaryKey() BEGIN
    IF EXISTS(
            select constraint_name
            from information_schema.table_constraints
            where table_name = REPLACE( '/*_*/rottenlinks', '`', '' )
              and table_schema = database()
              and constraint_name = 'PRIMARY'
        )
    THEN
        ALTER TABLE /*_*/rottenlinks DROP PRIMARY KEY;
    END IF;
END;
//

delimiter ;

CALL removePrimaryKey();
DROP PROCEDURE removePrimaryKey;

ALTER TABLE /*_*/rottenlinks
    MODIFY COLUMN rl_externallink BLOB NOT NULL;

ALTER TABLE /*_*/rottenlinks
    ADD COLUMN `rl_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST;

COMMIT;
