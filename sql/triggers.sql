-- triggers.sql

-- Drop the trigger if it exists to avoid duplicate definitions
DROP TRIGGER IF EXISTS AfterTradeInsert;

-- Create the TradeHistory table if it doesn't already exist.
-- This table is used to log actions on the Trades table.
CREATE TABLE IF NOT EXISTS TradeHistory (
    HistoryID INT AUTO_INCREMENT PRIMARY KEY,
    TradeID INT,
    Action VARCHAR(50),
    ActionTimestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

DELIMITER //
-- Create an AFTER INSERT trigger on the Trades table to log new trade entries
CREATE TRIGGER AfterTradeInsert
AFTER INSERT ON Trades
FOR EACH ROW
BEGIN
    INSERT INTO TradeHistory (TradeID, Action)
    VALUES (NEW.TID, 'INSERT');
END //

CREATE TRIGGER wants_priority_update
BEFORE UPDATE ON Wants
FOR EACH ROW
BEGIN
    IF NEW.Priority <> OLD.Priority THEN
        SET NEW.Date = NOW();
    END IF;
END//

DELIMITER ;