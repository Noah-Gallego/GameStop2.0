/* -------------------------------------------------------------
   views.sql   – GameStop 2.0 analytic helpers
   ------------------------------------------------------------- */

/* drop first to avoid “already exists” errors */
DROP VIEW IF EXISTS
    AdminTradeDetails,
    v_user_has_counts,
    v_user_trade_direction,
    v_user_trade_states;

/* -------------------------------------------------------------
   1) Admin-level trade detail
   Provides a consolidated view of all trades with user identifiers,
   game titles, trade state, user licenses, and timestamp.
------------------------------------------------------------- */
CREATE VIEW AdminTradeDetails AS
SELECT 
    t.TID,
    SUBSTRING_INDEX(u1.Email, '@', 1) AS User1,       -- Username of initiating user (before the '@')
    SUBSTRING_INDEX(u2.Email, '@', 1) AS User2,       -- Username of receiving user (before the '@')
    g1.Title AS User1Game,   -- Title of the game offered by User1
    g2.Title AS User2Game,   -- Title of the game offered by User2
    t.State,                 -- Current state of the trade (e.g., Pending, Completed)
    h1.License AS User1License,  -- License status of User1 for the offered game, if any
    h2.License AS User2License,  -- License status of User2 for the offered game, if any
    t.Timestamp              -- Date and time when the trade was initiated
FROM Trades t
    JOIN Users u1 
      ON t.UID1 = u1.UID    -- Link trade to initiating user
    JOIN Users u2 
      ON t.UID2 = u2.UID    -- Link trade to receiving user
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
