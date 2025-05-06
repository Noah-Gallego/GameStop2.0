-- stored_procedures.sql

-- Drop stored procedures if they exist to prevent duplicate definitions
DROP PROCEDURE IF EXISTS GetUserTradeHistory;
DROP PROCEDURE IF EXISTS InsertUser;
DROP PROCEDURE IF EXISTS DeleteUser;
DROP PROCEDURE IF EXISTS UpdateUser;
DROP PROCEDURE IF EXISTS GetMonthlyTradeStats;

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
-- Changed on 4/30/2025

-- 2. Insert New User Procedure
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

-- 3. Delete User Procedure
CREATE PROCEDURE DeleteUser(
    IN p_UID INT
)
BEGIN
    DELETE FROM Users WHERE UID = p_UID;
END //

-- 4. Update User Procedure
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

-- 5. Retrieve Monthly Trade Statistics Procedure
CREATE PROCEDURE GetMonthlyTradeStats(
    IN p_StartDate DATE,
    IN p_EndDate DATE
)
BEGIN
    SELECT COUNT(*) AS TotalTrades,
           AVG(DATEDIFF(NOW(), Timestamp)) AS AvgDaysSinceTrade
    FROM Trades
    WHERE Timestamp BETWEEN p_StartDate AND p_EndDate;
END //

DELIMITER ;