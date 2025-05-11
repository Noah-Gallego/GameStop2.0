/* -------------------------------------------------------------
   views.sql   – GameStop 2.0 analytic helpers
   ------------------------------------------------------------- */

/* drop first to avoid “already exists” errors */
DROP VIEW IF EXISTS
    AdminTradeDetails,
    v_user_has_counts,
    v_user_trade_direction,
    v_user_trade_states;
DROP TABLE IF EXISTS TradeHistory;

/* -------------------------------------------------------------
   REPLACEMENT  ·  TradeHistory + AdminTradeDetails
   -------------------------------------------------------------
   1) TradeHistory  – shadow table populated by trg_log_trade_history
   2) AdminTradeDetails – admin‑only view built *from* TradeHistory
------------------------------------------------------------- */

/* ----------------------------------------------------------------
   1) Shadow TABLE : TradeHistory
   ----------------------------------------------------------------
   Holds a one‑time snapshot of every trade at completion.
   Licence codes are stored here (sensitive) and kept out of the
   public-facing TradesDetails view.
-----------------------------------------------------------------*/

-- 2) JUSTIFICATION & USAGE
--    -----------------------
-- • **Why created:**  
--   - Administrators need an immutable, searchable ledger of every completed trade, including the actual licence codes exchanged.  
--   - By sourcing from `TradeHistory` (which only captures trades at completion), we prevent exposing licence data in user-facing views.  

-- • **Where it's used in the project:**  
--   - It is used to get the general data for AdminTradeDetails 
--   - Only users with the “admin” role have permission to query this view.

-- 3) SAMPLE QUERY & RESULTS
--    ------------------------
-- -- Query to fetch the five most recent completed trades:
-- SELECT *
-- FROM AdminTradeDetails
-- ORDER BY CompletedAt DESC;


CREATE TABLE TradeHistory (
    HistoryID  INT AUTO_INCREMENT PRIMARY KEY,
    TradeID    INT         NOT NULL,
    SenderID     INT         NOT NULL,
    ReceiverID   INT         NOT NULL,
    GID1       INT,
    GID2       INT,
    License1   VARCHAR(50),
    License2   VARCHAR(50),
    EventTime  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (TradeID)  REFERENCES Trades(TID),
    FOREIGN KEY (SenderID)   REFERENCES Users(UID),
    FOREIGN KEY (ReceiverID) REFERENCES Users(UID),
    FOREIGN KEY (GID1)     REFERENCES Games(GID),
    FOREIGN KEY (GID2)     REFERENCES Games(GID)
);

/* ----------------------------------------------------------------
   2) VIEW : AdminTradeDetails  (admin‑only)
   ----------------------------------------------------------------
   Exposes the snapshot with human‑readable usernames and titles.

   JUSTIFICATION & USAGE
      --------------------------------
      • Justification:
        - We need an admin‐only ledger of *completed* trades that
          includes the actual licence codes transferred.  
        - By sourcing from the immutable `TradeHistory` table, we prevent 
          public or user-role code from seeing license data.  
      • Where it’s used:
        - Displayed in the admin dashboard at `/admin/trades.php`.  
        - Powers CSV export, search/filter by date, user, or game.  
        - Only accounts with the “admin” role have permission to query this view.
-----------------------------------------------------------------*/
CREATE OR REPLACE VIEW AdminTradeDetails AS
SELECT
    th.HistoryID,
    th.TradeID,
    SUBSTRING_INDEX(u1.Email,'@',1) AS Sender,
    SUBSTRING_INDEX(u2.Email,'@',1) AS Receiver,
    g1.Title                        AS SenderGame,
    g2.Title                        AS ReceiverGame,
    th.License1,
    th.License2,
    th.EventTime                    AS CompletedAt
FROM TradeHistory th
JOIN Users u1  ON u1.UID = th.SenderID
JOIN Users u2  ON u2.UID = th.ReceiverID
LEFT JOIN Games g1 ON g1.GID = th.GID1
LEFT JOIN Games g2 ON g2.GID = th.GID2;

/* -------------------------------------------------------------
   2) Inventory counts per user
   Counts how many copies of each game each user owns.
------------------------------------------------------------- */
CREATE VIEW v_user_has_counts AS
SELECT
    H.UID,
    H.GID,
    G.Title,
    COUNT(*) AS cnt   -- Number of copies owned by the user
FROM Has H
    JOIN Games G 
      ON G.GID = H.GID  -- Associate inventory records with game details
GROUP BY 
    H.UID,
    H.GID;

/* -------------------------------------------------------------
   3) Trade direction per user – Started vs Received
   Breaks down the number of trades each user has initiated
   versus received.
------------------------------------------------------------- */
CREATE VIEW v_user_trade_direction AS
-- Trades initiated by each user
SELECT 
    UID1 AS uid,     -- User identifier for trade initiator
    'Started'  AS label,  
    COUNT(*) AS cnt  -- Count of trades started by the user
FROM Trades
GROUP BY UID1

UNION ALL

-- Trades received by each user
SELECT 
    UID2 AS uid,     -- User identifier for trade recipient
    'Received' AS label,
    COUNT(*) AS cnt  -- Count of trades received by the user
FROM Trades
GROUP BY UID2;

/* -------------------------------------------------------------
   4) Trade-state counts per user
   Aggregates counts of trades by their state (e.g., Pending,
   Completed, Cancelled) for both initiators and recipients.
------------------------------------------------------------- */
CREATE VIEW v_user_trade_states AS
SELECT
    uid,             -- User identifier (either initiator or recipient)
    State AS label,  -- Trade state label
    COUNT(*) AS cnt  -- Number of trades in this state for the user
FROM (
    -- Include all initiator states
    SELECT UID1 AS uid, State 
    FROM Trades
    UNION ALL
    -- Include all recipient states
    SELECT UID2 AS uid, State 
    FROM Trades
) AS x
GROUP BY 
    uid,
    State;
