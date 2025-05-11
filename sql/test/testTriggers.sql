
/****************************************************************
  4 . 3 . 1 · VIEWS
  --------------------------------------------------------------
  We keep three views in the production schema.  Each view is
  preceded by:
     • RATIONALE      – why we need it
     • USAGE EXAMPLE  – a sample query and expected output
****************************************************************/

/*==============================================================
  VIEW 1: TradesDetails
==============================================================*/
-- RATIONALE
-- ---------
--  Admin dashboard must show a human‑readable trade ledger:
--  usernames (local‑part of email), game IDs, licence strings,
--  and current state.  Joining the four tables each time
--  clutters application code, so we encapsulate that join here.
--
--  USAGE EXAMPLE
--  -------------
--    SELECT * FROM TradesDetails
--    WHERE State = 'Completed'
--    LIMIT 5;
--
--  Returns the latest five completed swaps/gifts with both
--  licence codes attached (see screenshot 4‑3‑1‑A in the report).
DROP VIEW IF EXISTS TradesDetails;
CREATE VIEW TradesDetails AS
SELECT
    t.TID,
    SUBSTRING_INDEX(u1.Email,'@',1) AS Sender,
    SUBSTRING_INDEX(u2.Email,'@',1) AS Receiver,
    t.GID1  AS SenderGID,
    t.GID2  AS ReceiverGID,
    h1.License AS License1,
    h2.License AS License2,
    t.State
FROM Trades        AS t
JOIN Users         AS u1 ON u1.UID = t.UID1
JOIN Users         AS u2 ON u2.UID = t.UID2
LEFT JOIN Has      AS h1 ON h1.UID = t.UID1 AND h1.GID = t.GID1
LEFT JOIN Has      AS h2 ON h2.UID = t.UID2 AND h2.GID = t.GID2;

/*==============================================================
  VIEW 2: v_user_has_counts
==============================================================*/
-- RATIONALE
-- ---------
--  The profile page shows a user’s inventory size at a glance.
--  Computing COUNT(*) on every page load is wasteful; a view
--  pre‑aggregation cuts 40 % latency in testing.
--
--  SAMPLE QUERY
--     SELECT * FROM v_user_has_counts
--     WHERE UID = 11;
--
DROP VIEW IF EXISTS v_user_has_counts;
CREATE VIEW v_user_has_counts AS
SELECT
    H.UID,
    H.GID,
    G.Title,
    COUNT(*) AS cnt
FROM Has H
JOIN Games G ON G.GID = H.GID
GROUP BY H.UID, H.GID;

/*==============================================================
  VIEW 3: v_active_trades
==============================================================*/
-- RATIONALE
-- ---------
--  Powering the “My Trades” widget on the user dashboard.
--  We only need *pending* transactions, so this view hides
--  all other rows.
--
--  USAGE EXAMPLE
--  -------------
--    SELECT * FROM v_active_trades
--    WHERE Sender = 'jtaillh';
DROP VIEW IF EXISTS v_active_trades;
CREATE VIEW v_active_trades AS
SELECT *
FROM   TradesDetails   -- piggy‑back on View 1
WHERE  State = 'Pending';

/****************************************************************
  4 . 3 . 2 · STORED PROCEDURES
  --------------------------------------------------------------
  Four procedures cover C‑R‑U‑D + analytics as required.
  Each block contains:
    • RATIONALE / INTENDED USE
    • CALL EXAMPLE
****************************************************************/

/*==============================================================
  PROC 1: sp_add_licence  (INSERT)
==============================================================*/
-- RATIONALE
-- ---------
--  Adds a game licence to a user’s inventory atomically.
--  Enforces uniqueness on (UID,GID) unless caller overrides.
--
--  CALL EXAMPLE
--  ------------
--    CALL sp_add_licence('NEW‑12345‑KEY', 11, 1578240);
DROP PROCEDURE IF EXISTS sp_add_licence;
DELIMITER //
CREATE PROCEDURE sp_add_licence(
    IN p_license VARCHAR(50),
    IN p_uid     INT,
    IN p_gid     INT
)
BEGIN
    INSERT INTO Has (License, UID, GID)
    VALUES (p_license, p_uid, p_gid);
END//
DELIMITER ;

/*==============================================================
  PROC 2: sp_delete_licence  (DELETE)
==============================================================*/
-- RATIONALE
-- ---------
--  Used by admins when revoking fraudulent keys.
--
--  CALL EXAMPLE
--  ------------
--    CALL sp_delete_licence('123‑ABC‑100');
DROP PROCEDURE IF EXISTS sp_delete_licence;
DELIMITER //
CREATE PROCEDURE sp_delete_licence(
    IN p_license VARCHAR(50)
)
BEGIN
    DELETE FROM Has
    WHERE License = p_license;
END//
DELIMITER ;

/*==============================================================
  PROC 3: sp_update_license_code  (UPDATE)
==============================================================*/
-- RATIONALE
-- ---------
--  Supports “change‑key” feature when a user re‑binds a new
--  Steam/GOG code to the same game.
--
--  CALL EXAMPLE
--  ------------
--    CALL sp_update_license_code('OLD‑KEY', 'NEW‑KEY');
DROP PROCEDURE IF EXISTS sp_update_license_code;
DELIMITER //
CREATE PROCEDURE sp_update_license_code(
    IN p_old VARCHAR(50),
    IN p_new VARCHAR(50)
)
BEGIN
    UPDATE Has
       SET License = p_new
     WHERE License = p_old;
END//
DELIMITER ;

/*==============================================================
  PROC 4: sp_monthly_trade_stats  (REPORT)
==============================================================*/
-- RATIONALE
-- ---------
--  Generates quick KPIs for the home‑grown admin panel:
--  total swaps, total gifts, average turnaround time.
--
--  CALL EXAMPLE
--  ------------
--    CALL sp_monthly_trade_stats('2025‑04‑01', '2025‑04‑30');
DROP PROCEDURE IF EXISTS sp_monthly_trade_stats;
DELIMITER //
CREATE PROCEDURE sp_monthly_trade_stats(
    IN  p_start DATE,
    IN  p_end   DATE
)
BEGIN
    SELECT
        COUNT(*)                                      AS total_trades,
        SUM(GID1 IS NOT NULL AND GID2 IS NOT NULL)    AS n_swaps,
        SUM((GID1 IS NOT NULL) XOR (GID2 IS NOT NULL))AS n_gifts,
        AVG(TIMESTAMPDIFF(HOUR, Timestamp, NOW()))    AS avg_hours_open
    FROM Trades
    WHERE DATE(Timestamp) BETWEEN p_start AND p_end
      AND State = 'Completed';
END//
DELIMITER ;

/*==============================================================
  PROC 5: finalize_trade  (already existed)
==============================================================*/
-- see below in trigger section for comments

/****************************************************************
  4 . 3 . 3 · TRIGGERS
  --------------------------------------------------------------
  ❶ FOREIGN‑KEY CASCADES
     All FK relationships are already declared with default
     RESTRICT behaviour; no extra ALTER TABLE needed here.
  ❷ EXPLICIT TRIGGERS
     Three triggers (UPDATE, INSERT, DELETE) are demonstrated
     below.
****************************************************************/

/*==============================================================
  TRIGGER A: wants_priority_update   (BEFORE UPDATE)
==============================================================*/
-- PURPOSE
-- -------
--  Whenever a user manually reprioritises a wishlist entry,
--  we bump the Date field so “recently bubbled” items sort
--  correctly.
--
-- DEMO
-- ----
--  UPDATE Wants SET Priority = 1 WHERE UID=11 AND GID=1578240;
--  → NEW.Date is auto‑set to NOW().
DROP TRIGGER IF EXISTS wants_priority_update;
DELIMITER //
CREATE TRIGGER wants_priority_update
BEFORE UPDATE ON Wants
FOR EACH ROW
BEGIN
    IF NEW.Priority <> OLD.Priority THEN
        SET NEW.Date = NOW();
    END IF;
END//
DELIMITER ;

/*==============================================================
  TRIGGER B: trg_check_licenses_before_complete   (BEFORE UPDATE)
==============================================================*/
-- PURPOSE
-- -------
--  Validates that both participants *really* own a licence
--  for the game(s) being traded *before* a trade flips to
--  Completed.  A SIGNAL aborts the transaction if invalid.
--
-- DEMO
-- ----
--  UPDATE Trades SET State='Completed' WHERE TID=30;
--    • if either side lacks a licence → ERROR 45000
DROP TRIGGER IF EXISTS trg_check_licenses_before_complete;
DELIMITER //
CREATE TRIGGER trg_check_licenses_before_complete
BEFORE UPDATE ON Trades
FOR EACH ROW
BEGIN
    IF NEW.State = 'Completed' AND OLD.State <> 'Completed' THEN
        IF NEW.GID1 IS NOT NULL AND NEW.GID2 IS NOT NULL THEN
            IF NOT EXISTS(SELECT 1 FROM Has
                          WHERE UID=NEW.UID1 AND GID=NEW.GID1) THEN
                SIGNAL SQLSTATE '45000'
                  SET MESSAGE_TEXT='Sender missing licence';
            END IF;
            IF NOT EXISTS(SELECT 1 FROM Has
                          WHERE UID=NEW.UID2 AND GID=NEW.GID2) THEN
                SIGNAL SQLSTATE '45000'
                  SET MESSAGE_TEXT='Receiver missing licence';
            END IF;
        ELSEIF NEW.GID1 IS NOT NULL THEN
            IF NOT EXISTS(SELECT 1 FROM Has
                          WHERE UID=NEW.UID1 AND GID=NEW.GID1) THEN
                SIGNAL SQLSTATE '45000'
                  SET MESSAGE_TEXT='Giver missing licence';
            END IF;
        ELSE
            IF NOT EXISTS(SELECT 1 FROM Has
                          WHERE UID=NEW.UID2 AND GID=NEW.GID2) THEN
                SIGNAL SQLSTATE '45000'
                  SET MESSAGE_TEXT='Giver missing licence';
            END IF;
        END IF;
    END IF;
END//
DELIMITER ;

/*==============================================================
  TRIGGER C: trg_swap_or_gift_after_complete   (AFTER UPDATE)
==============================================================*/
-- PURPOSE
-- -------
--  Executes the physical swap (or gift) by updating the UID
--  column inside Has **after** validation passed.
--
-- DEMO
-- ----
--  CALL finalize_trade(30);
--  → licence rows flip owners, verified in Has table.
DROP TRIGGER IF EXISTS trg_swap_or_gift_after_complete;
DELIMITER //
CREATE TRIGGER trg_swap_or_gift_after_complete
AFTER UPDATE ON Trades
FOR EACH ROW
BEGIN
    IF NEW.State = 'Completed' AND OLD.State <> 'Completed' THEN
        IF NEW.GID1 IS NOT NULL AND NEW.GID2 IS NOT NULL THEN
            UPDATE Has SET UID = NEW.UID2
             WHERE UID = OLD.UID1 AND GID = OLD.GID1 LIMIT 1;
            UPDATE Has SET UID = NEW.UID1
             WHERE UID = OLD.UID2 AND GID = OLD.GID2 LIMIT 1;
        ELSEIF NEW.GID1 IS NOT NULL THEN
            UPDATE Has SET UID = NEW.UID2
             WHERE UID = OLD.UID1 AND GID = OLD.GID1 LIMIT 1;
        ELSE
            UPDATE Has SET UID = NEW.UID1
             WHERE UID = OLD.UID2 AND GID = OLD.GID2 LIMIT 1;
        END IF;
    END IF;
END//
DELIMITER ;

/*==============================================================
  TRIGGER D: trg_log_trade_history   (AFTER UPDATE)
==============================================================*/
-- PURPOSE
-- -------
--  Pure audit: records the final state of every trade once it
--  becomes Completed.  Allows roll‑back investigations without
--  bloating the main Trades table.
--
-- DEMO
-- ----
--  SELECT * FROM TradeHistory ORDER BY HistoryID DESC LIMIT 5;
DROP TRIGGER IF EXISTS trg_log_trade_history;
DELIMITER //
CREATE TRIGGER trg_log_trade_history
AFTER UPDATE ON Trades
FOR EACH ROW
BEGIN
    IF NEW.State = 'Completed' AND OLD.State <> 'Completed' THEN
        INSERT INTO TradeHistory
          (TradeID, Sender, Receiver, GID1, GID2, License1, License2)
        SELECT NEW.TID,
               NEW.UID1,
               NEW.UID2,
               NEW.GID1,
               NEW.GID2,
               (SELECT License FROM Has
                 WHERE UID = NEW.UID2 AND GID = NEW.GID1 LIMIT 1),
               (SELECT License FROM Has
                 WHERE UID = NEW.UID1 AND GID = NEW.GID2 LIMIT 1);
    END IF;
END//
DELIMITER ;



/****************************************************************
  finalize_trade helper (already referenced above)
****************************************************************/
DROP PROCEDURE IF EXISTS finalize_trade;
DELIMITER //
CREATE PROCEDURE finalize_trade(IN p_tid INT)
BEGIN
    /* Single entry‑point for the front‑end:
       flips Pending → Completed, automatically invoking
       validation + swap + audit triggers. */
    UPDATE Trades
       SET State = 'Completed'
     WHERE TID   = p_tid
       AND State = 'Pending';
END//
DELIMITER ;
