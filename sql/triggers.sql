-- triggers.sql

-- Drop the trigger if it exists to avoid duplicate definitions
DROP TRIGGER IF EXISTS wants_priority_update;
DROP TRIGGER IF EXISTS TradeCompletedTrigger;
DROP TRIGGER IF EXISTS trade_touch_timestamp;
DROP TRIGGER IF EXISTS game_deletion;

DELIMITER //

/*==============================================================
  TRIGGER A: wants_priority_update   (BEFORE UPDATE on Wishlist [Wants])
==============================================================*/
-- PURPOSE
-- -------
--  Whenever a user manually reprioritises a wishlist entry,
--  we bump the Date field so “recently bubbled” items sort
--  correctly.
--
-- DEMO
-- ----
--  UPDATE Wants SET Priority = 2 WHERE UID=11 AND GID=1473350;

CREATE TRIGGER wants_priority_update
BEFORE UPDATE ON Wants
FOR EACH ROW
BEGIN
    IF NEW.Priority <> OLD.Priority THEN
        SET NEW.Date = NOW();
    END IF;
END//

CREATE TRIGGER game_deletion
AFTER DELETE ON Has
FOR EACH ROW
BEGIN
    INSERT INTO HadGames (GID, UID) VALUES (OLD.GID, OLD.UID);
END//

/*-----------------------------------------------------------------
  TRIGGER : trade_touch_timestamp
  Keeps the “Timestamp” column up-to-date whenever either party
  changes Status1, Status2 or State.
-----------------------------------------------------------------*/
CREATE TRIGGER trade_touch_timestamp
BEFORE UPDATE ON Trades
FOR EACH ROW
BEGIN
    IF  NEW.Status1 <> OLD.Status1
     OR NEW.Status2 <> OLD.Status2
     OR NEW.State   <> OLD.State   THEN
        SET NEW.Timestamp = NOW();
    END IF;
END//

CREATE TRIGGER TradeCompletedTrigger
    AFTER UPDATE ON Trades
    FOR EACH ROW
BEGIN
    DECLARE license1 VARCHAR(50);
    DECLARE license2 VARCHAR(50);

    -- Make sure user cant accidentally retrade
    IF NEW.State = 'Completed' AND OLD.State != 'Completed' THEN

        -- Handle GID1 transfer from UID1 to UID2
        IF NEW.GID1 IS NOT NULL THEN
            -- Temporarily store an arbitrary license from uid1 into license1
            SELECT License INTO license1
            FROM Has
            WHERE UID = NEW.UID1
              AND GID = NEW.GID1
            LIMIT 1;

            -- remove license from uid1
            DELETE FROM Has
            WHERE UID = NEW.UID1 AND License = license1;

            -- give license to uid2
            INSERT INTO Has (License, UID, GID)
            VALUES (license1, NEW.UID2, NEW.GID1);
        END IF;

        -- Handle GID2 transfer from UID2 to UID1
        IF NEW.GID2 IS NOT NULL THEN
            -- Temporarily store an arbitrary license from uid2 into license2
            SELECT License INTO license2
            FROM Has
            WHERE UID = NEW.UID2
              AND GID = NEW.GID2
            LIMIT 1;

            -- remove license from uid1
            DELETE FROM Has
            WHERE UID = NEW.UID2 AND License = license2;

            -- give license to uid2
            INSERT INTO Has (License, UID, GID)
            VALUES (license2, NEW.UID1, NEW.GID2);
        END IF;

        INSERT INTO TradeHistory (TradeID, SenderID, GID1, License1, ReceiverID, GID2, License2, State)
        VALUES (New.TID, NEW.UID1, NEW.GID1, license1, NEW.UID2, NEW.GID2, license2, 'Completed');
    END IF;
END//


DELIMITER ;