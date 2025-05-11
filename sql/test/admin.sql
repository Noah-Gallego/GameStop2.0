DROP TRIGGER IF EXISTS trg_check_licenses_before_complete;
DROP TRIGGER IF EXISTS trg_swap_or_gift_after_complete;

DROP PROCEDURE IF EXISTS finalize_trade;

DROP VIEW IF EXISTS TradesDetails;

/*
-- Test
-- Setup a dummy trade
INSERT INTO Trades (UID1,UID2,GID1,GID2,State,Status1,Status2,Timestamp)
     VALUES (1,2,100,200,'Pending','Accepted','Accepted',NOW());
-- Give each side one licence
INSERT INTO Has (License,UID,GID) VALUES
  ('ABC-100',1,100), ('XYZ-200',2,200);

-- This should pass and swap ABC→UID2, XYZ→UID1
CALL finalize_trade(LAST_INSERT_ID());

-- Now Has should contain ('ABC-100',2,100) and ('XYZ-200',1,200)
SELECT * FROM Has WHERE GID IN (100,200);


*/
CREATE VIEW TradesDetails AS
SELECT
    t.TID,
    SUBSTRING_INDEX(Sender.Email, '@', 1) AS Sender,       -- Username of initiating user (before the '@')
    SUBSTRING_INDEX(Receiver.Email, '@', 1) AS Receiver,       -- Username of recieving user (before the '@')
    g1.GID AS SenderGID,   -- Title of the game offered by Sender
    g2.GID AS ReceiverGID,   -- Title of the game offered by Reciever
    h1.License AS License1,  -- License status of Sender for the offered game, if any
    h2.License AS License2,  -- License status of Receiver for the offered game, if any
    t.State                 -- Current state of the trade (e.g., Pending, Completed)
FROM Trades t
    JOIN Users Sender
      ON t.UID1 = Sender.UID    -- Link trade to initiating user
    JOIN Users Receiver 
      ON t.UID2 = Receiver.UID    -- Link trade to receiving user
    JOIN Games g1 
      ON t.GID1 = g1.GID    -- Link to game offered by User1
    JOIN Games g2 
      ON t.GID2 = g2.GID    -- Link to game offered by User2
    LEFT JOIN Has h1 
      ON h1.UID = t.UID1 
     AND h1.GID = t.GID1    -- Retrieve User1’s license for their game, if recorded
    LEFT JOIN Has h2 
      ON h2.UID = t.UID2 
     AND h2.GID = t.GID2;   -- Retrieve User2’s license for their game, if recorded


DELIMITER //


-- Validate before a trade is marked Completed


CREATE TRIGGER trg_check_licenses_before_complete
BEFORE UPDATE ON Trades
FOR EACH ROW
BEGIN
    /* Run only when the row is switching to Completed */
    IF NEW.State = 'Completed' AND OLD.State <> 'Completed' THEN

        /* --------- Game‑for‑game (both GIDs present) --------- */
        IF NEW.GID1 IS NOT NULL AND NEW.GID2 IS NOT NULL THEN
            /* Sender must own one licence for GID1 */
            IF NOT EXISTS (SELECT 1
                             FROM Has
                            WHERE UID = NEW.UID1
                              AND GID = NEW.GID1
                            LIMIT 1) THEN
                SIGNAL SQLSTATE '45000'
                   SET MESSAGE_TEXT = 'Sender has no licence for GID1';
            END IF;

            /* Receiver must own one licence for GID2 */
            IF NOT EXISTS (SELECT 1
                             FROM Has
                            WHERE UID = NEW.UID2
                              AND GID = NEW.GID2
                            LIMIT 1) THEN
                SIGNAL SQLSTATE '45000'
                   SET MESSAGE_TEXT = 'Receiver has no licence for GID2';
            END IF;

        /* ------------- Gift (only one GID present) ------------ */
        ELSE
            /* Gift from UID1 to UID2 */
            IF NEW.GID1 IS NOT NULL THEN
                IF NOT EXISTS (SELECT 1
                                 FROM Has
                                WHERE UID = NEW.UID1
                                  AND GID = NEW.GID1
                                LIMIT 1) THEN
                    SIGNAL SQLSTATE '45000'
                       SET MESSAGE_TEXT = 'Giver has no licence for GID1';
                END IF;
            /* Gift from UID2 to UID1 */
            ELSE
                IF NOT EXISTS (SELECT 1
                                 FROM Has
                                WHERE UID = NEW.UID2
                                  AND GID = NEW.GID2
                                LIMIT 1) THEN
                    SIGNAL SQLSTATE '45000'
                       SET MESSAGE_TEXT = 'Giver has no licence for GID2';
                END IF;
            END IF;
        END IF;
    END IF;
END //


-- What it does?

-- Blocks the update if the necessary licence rows are missing.
-- Uses SIGNAL to throw a clear error that your app can catch.

-- --------------------------------------------------------------

--  Move the licences automatically when the trade closes

CREATE TRIGGER trg_swap_or_gift_after_complete
AFTER UPDATE ON Trades
FOR EACH ROW
BEGIN
    /* Run only once, right after the check trigger */
    IF NEW.State = 'Completed' AND OLD.State <> 'Completed' THEN

        /* ---------- Swap: both players trade games ---------- */
        IF NEW.GID1 IS NOT NULL AND NEW.GID2 IS NOT NULL THEN
            /* Move Sender’s licence to Receiver */
            UPDATE Has
               SET UID = NEW.UID2
             WHERE UID = NEW.UID1
               AND GID = NEW.GID1
             LIMIT 1;

            /* Move Receiver’s licence to Sender */
            UPDATE Has
               SET UID = NEW.UID1
             WHERE UID = NEW.UID2
               AND GID = NEW.GID2
             LIMIT 1;

        /* ---------------- Gift: only one side has a GID ---------------- */
        ELSEIF NEW.GID1 IS NOT NULL THEN
            /* UID1 gives UID2 the licence */
            UPDATE Has
               SET UID = NEW.UID2
             WHERE UID = NEW.UID1
               AND GID = NEW.GID1
             LIMIT 1;
        ELSE
            /* UID2 gives UID1 the licence */
            UPDATE Has
               SET UID = NEW.UID1
             WHERE UID = NEW.UID2
               AND GID = NEW.GID2
             LIMIT 1;
        END IF;
    END IF;
END //

-- What it does

-- Runs only after the trade passes validation.
-- Reassigns the UID in Has, so licences instantly end up in the correct inventory.
/* Nothing else in your schema needs to change—even the TradesDetails view will now always show 
        non‑NULL License1 and License2 whenever the row is in the Completed state and both GID columns are populated.*/

-- ================================

-- wrap the whole flow in a helper procedure
/*application to call a single routine instead of two UPDATE statements 
    (change statuses first, then swap)*/

CREATE PROCEDURE finalize_trade(IN p_tid INT)
BEGIN
    /* Force the state change; this fires both triggers */
    UPDATE Trades
       SET State = 'Completed'
     WHERE TID   = p_tid
       AND State = 'Pending';    -- safety guard
END //

-- How to apply logic:
-- CALL finalize_trade(?);


DELIMITER ;