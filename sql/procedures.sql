-- procedures.sql

-- Drop stored procedures if they exist to prevent duplicate definitions
DROP PROCEDURE IF EXISTS GetUserTradeHistory;
DROP PROCEDURE IF EXISTS InsertUser;  -- 1
DROP PROCEDURE IF EXISTS DeleteUser;
DROP PROCEDURE IF EXISTS UpdateUser;
DROP PROCEDURE IF EXISTS DeleteWant; -- 2
DROP PROCEDURE IF EXISTS GetMonthlyTradeStats; -- 4
DROP PROCEDURE IF EXISTS finalize_trade; -- 3

DELIMITER //
-- 1. Dynamic User Trade History Procedure
/* ------------------------------------------------------------------
 * GetUserTradeHistory
 * Inputs : p_UID  (session of the e-mail)
 * Output : SentGame | TradePartner | ReceivedGame | Status
 * -----------------------------------------------------------------*/
CREATE PROCEDURE GetUserTradeHistory (IN p_UID INT)
BEGIN
    SELECT  g1.Title AS SentGame,
            CASE
              WHEN t.UID1 = p_UID
                     THEN SUBSTRING_INDEX(u2.Email,'@',1)
              ELSE      SUBSTRING_INDEX(u1.Email,'@',1)
            END  AS TradePartner,
            g2.Title AS ReceivedGame,
            t.State  AS Status
    FROM Trades t
    JOIN Users  u1 ON t.UID1 = u1.UID
    JOIN Users  u2 ON t.UID2 = u2.UID
    JOIN Games  g1 ON t.GID1 = g1.GID
    JOIN Games  g2 ON t.GID2 = g2.GID
    WHERE t.UID1 = p_UID OR t.UID2 = p_UID;
END//
DELIMITER ;
-- Changed on 4/30/2025

/* =============================================================
   PROC 2 · InsertUser   (INSERT)
============================================================= */
-- >>> CATEGORY
--     “Insert new record” requirement
--
-- >>> RATIONALE
--     Centralises hashing rules and uniqueness checks (handled
--     by BEFORE INSERT triggers not shown here).  Front‑end
--     simply calls this procedure from the signup form.
--
-- >>> CALL EXAMPLE
--     CALL InsertUser('demo@demo.com','hashed‑pw','Demo','User');

-- 2. Insert New User Procedure
DELIMITER //
CREATE PROCEDURE InsertUser(
    IN p_Email VARCHAR(100),
    IN p_Password VARCHAR(255),
    IN p_FirstName VARCHAR(50),
    IN p_LastName VARCHAR(50)
)
BEGIN
    INSERT INTO Users (Email, Password, FirstName, LastName)
    VALUES (p_Email, p_Password, p_FirstName, p_LastName);
END //
DELIMITER ;

/* =========================================
   PROC 3 · DeleteWant
============================================ */
-- >>> CATEGORY
--     “Deleting an existing record based on the primary key” requirement
--
-- >>> RATIONALE
--     If you have an item in your wishlist that you don't want anymore, then
--     you want to be able to delete it
--
-- >>> CALL EXAMPLE
--     CALL DeleteWant(123, 456);
DELIMITER //
CREATE OR REPLACE PROCEDURE DeleteWant(IN i_uid INT, IN i_gid INT)
BEGIN
    DELETE FROM Wants WHERE UID = i_uid AND GID = i_gid;
END//
DELIMITER ;

/* =============================================================
   PROC 4 · UpdateUser   (UPDATE)
============================================================= */
-- >>> CATEGORY
--     “Update record” requirement
--
-- >>> RATIONALE
--     Re‑used by both the profile “edit” screen and an admin
--     bulk‑update CSV importer.
--
-- >>> CALL EXAMPLE
--     CALL UpdateUser(11,'demo@newmail.com','new‑hash','Demo','User');


-- 4. Update User Procedure
DELIMITER //
CREATE PROCEDURE UpdateUser(
    IN p_UID INT,
    IN p_Email VARCHAR(100),
    IN p_Password VARCHAR(255),
    IN p_FirstName VARCHAR(50),
    IN p_LastName VARCHAR(50)
)
BEGIN
    UPDATE Users
    SET Email = p_Email,
        Password = p_Password,
        FirstName = p_FirstName,
        LastName = p_LastName
    WHERE UID = p_UID;
END //
DELIMITER ;


/*==============================
  PROC 5 · finalize_trade
================================*/
-- >>> PURPOSE
--     Wraps the state change in one call so the front‑end does
--     not need to know about trigger cascade logic.
--
-- >>> DEMO
-- UPDATED PROCEDURE: 5/10/25
DELIMITER //
CREATE OR REPLACE PROCEDURE finalize_trade(IN p_tid INT)
BEGIN
    -- Only complete if both parties accepted and we're still pending
    UPDATE Trades
       SET State     = 'Completed'
     WHERE TID        = p_tid
       AND State      = 'Pending'
       AND Status1    = 'Accepted'
       AND Status2    = 'Accepted';
        -- trade_touch_timestamp will fire 
        -- and set Timestamp = NOW()
END//
DELIMITER ;

/* =========================================
   PROC 6 · GetMonthlyTradeStats   (STATS)
============================================ */
-- >>> CATEGORY
--     “Statistical metrics over a period” requirement
--
-- >>> RATIONALE
--     Powers the KPI card “Trades last 30 days” and feeds
--     Grafana via a MySQL data‑source.
--
-- >>> CALL EXAMPLE
--     CALL GetMonthlyTradeStats('2025‑04‑01','2025‑04‑30');

-- 5. Retrieve Monthly Trade Statistics Procedure
DELIMITER //
CREATE PROCEDURE GetMonthlyTradeStats(
    IN p_StartDate DATE,
    IN p_EndDate DATE
)
BEGIN
    SELECT COUNT(*) AS TotalTrades,
           AVG(DATEDIFF(NOW(), Timestamp)) AS AvgDaysSinceTrade
    FROM Trades
    WHERE Timestamp BETWEEN p_StartDate AND p_EndDate;
END//
DELIMITER ;