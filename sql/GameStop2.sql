-- Disable foreign key checks to avoid dependency conflicts during drops
SET foreign_key_checks = 0;

-- Drop tables in reverse order of dependency
DROP TABLE IF EXISTS Has;
DROP TABLE IF EXISTS Wants;
DROP TABLE IF EXISTS Trades;
DROP TABLE IF EXISTS Games;
DROP TABLE IF EXISTS Users;

-- Recreate Users table
CREATE TABLE Users (
    UID INT AUTO_INCREMENT,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    FirstName VARCHAR(50),
    LastName VARCHAR(50),
    PRIMARY KEY (UID)
);

-- Recreate Games table
CREATE TABLE Games (
    GID INT AUTO_INCREMENT,
    Title VARCHAR(100) NOT NULL,
    Image VARCHAR(255),
    PRIMARY KEY (GID)
);

CREATE TABLE HadGames (
    UID INT,
    GID INT,
    FOREIGN KEY (UID) REFERENCES Users(UID)
);

-- Recreate Trades table with State as ENUM
CREATE TABLE Trades (
    TID INT AUTO_INCREMENT,
    UID1 INT NOT NULL,
    UID2 INT NOT NULL,
    GID1 INT,
    GID2 INT,
    State ENUM('Pending', 'Completed', 'Cancelled') NOT NULL,
    Status1 ENUM('Accepted', 'Decline') NOT NULL,
    Status2 ENUM('Pending', 'Accepted', 'Declined') NOT NULL,
    Timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (TID),
    FOREIGN KEY (UID1) REFERENCES Users(UID),
    FOREIGN KEY (UID2) REFERENCES Users(UID),
    FOREIGN KEY (GID1) REFERENCES Games(GID),
    FOREIGN KEY (GID2) REFERENCES Games(GID)
);
-- Last modified 4/5/25 revision
-- enum
-- 1: pending
-- 2: completed (both players sent keys)
-- 3: cancelled (neither player sent a lisence, and a trade is declined)
-- 4: failed (only one of the players sent a lisence)

-- Recreate Wants table (M:N between Users and Games)
CREATE TABLE Wants (
    UID INT NOT NULL,
    GID INT NOT NULL,
    Date DATE,
    Priority INT,
    PRIMARY KEY (UID, GID),
    FOREIGN KEY (UID) REFERENCES Users(UID),
    FOREIGN KEY (GID) REFERENCES Games(GID)
);

-- Recreate Has table (M:N between Users and Games with License as an attribute)
CREATE TABLE Has (
    License VARCHAR(50) NOT NULL,
    UID INT NOT NULL,
    GID INT NOT NULL,
    PRIMARY KEY (License),
    FOREIGN KEY (UID) REFERENCES Users(UID),
    FOREIGN KEY (GID) REFERENCES Games(GID)
);
-- reorganized the inserts 4/30 (CR)
-- -- Insert sample data into User
insert into Users (UID, Email, Password, FirstName, LastName) values
(1, 'dragot0@tamu.edu', 'De Maria', 'Donelle', 'Ragot'),
(2, 'obernardez1@pagesperso-orange.fr', 'McNulty', 'Oberon', 'Bernardez'),
(3, 'flangan2@economist.com', 'Mirfield', 'Fran', 'Langan'),
(4, 'tstonard3@canalblog.com', 'Seiler', 'Tibold', 'Stonard'),
(5, 'dmacellar4@nymag.com', 'Craze', 'Dalia', 'Macellar'),
(6, 'gfurmedge5@cnet.com', 'Mainstone', 'Gavrielle', 'Furmedge'),
(7, 'rocorhane6@google.com', 'Handford', 'Ruthanne', 'Corhane'),
(8, 'wwhitlock7@epa.gov', 'Kerby', 'Willdon', 'Whitlock'),
(9, 'hsimoens8@army.mil', 'Daftor', 'Haskel', 'Simoens'),
(10, 'vbransgrove9@last.fm', 'Riggey', 'Vilma', 'Bransgrove'),
(11, 'olorentzena@ftc.gov', 'Pollastrone', 'Orella', 'Lorentzen'),
(12, 'ksomervilleb@etsy.com', 'Tiler', 'Kile', 'Somerville'),
(13, 'vdemougeotc@stanford.edu', 'Ashburner', 'Vonni', 'Demougeot'),
(14, 'rtayspelld@techcrunch.com', 'Blurton', 'Roger', 'Tayspell'),
(15, 'rpinare@twitpic.com', 'Duigenan', 'Rhodie', 'Pinar'),
(16, 'bmackartanf@hugedomains.com', 'Meininking', 'Bucky', 'MacKartan'),
(17, 'cgowanlockg@vimeo.com', 'Ambrogio', 'Carri', 'Gowanlock'),
(18, 'jtaillh@google.co.uk', 'Bend', 'Jared', 'Taill'),
(19, 'rmaplethorpi@quantcast.com', 'Damarell', 'Raf', 'Maplethorp'),
(20, 'elarnerj@tuttocitta.it', 'Labeuil', 'Edy', 'Larner');

-- -- Insert sample data into Game
insert into Games (GID, Title, Image) values 
(1, 'Andalax', NULL),
(2, 'Otcom', NULL),
(3, 'Overhold', NULL),
(4, 'Viva', NULL),
(5, 'Prodder', NULL),
(6, 'Greenlam', NULL),
(7, 'Y-Solowarm', NULL),
(8, 'Ventosanzap', NULL),
(9, 'Fixflex', NULL),
(10, 'Bytecard', NULL),
(11, 'Overhold', NULL),
(12, 'Mat Lam Tam', NULL),
(13, 'It', NULL),
(14, 'Prodder', NULL),
(15, 'Bitwolf', NULL),
(16, 'Span', NULL),
(17, 'Temp', NULL),
(18, 'Treeflex', NULL),
(19, 'Lotstring', NULL),
(20, 'Keylex', NULL);

-- -- Insert sample data into Trades
INSERT INTO Trades (UID1, UID2, GID1, GID2, State, Timestamp) VALUES
(14, 5, 14, 3, 'Completed', '2024-03-11 04:30:10'),
(9, 10, 9, 7, 'Completed', '2023-12-29 21:41:09'),
(18, 5, 17, 13, 'Cancelled', '2023-06-29 08:12:28'),
(19, 5, 3, 18, 'Completed', '2024-08-02 20:51:13'),
(15, 14, 13, 15, 'Completed', '2024-12-21 00:22:42'),
(16, 14, 8, 14, 'Pending', '2024-08-19 18:29:02'),
(8, 14, 6, 8, 'Pending', '2025-03-13 10:53:59'),
(17, 10, 15, 1, 'Completed', '2024-05-29 15:07:55'),
(8, 1, 20, 20, 'Pending', '2023-11-10 13:24:06'),
(19, 20, 2, 4, 'Completed', '2025-02-25 03:26:16'),
(16, 6, 1, 12, 'Failed', '2024-05-07 20:37:11'),
(11, 16, 16, 13, 'Pending', '2024-08-04 20:52:35'),
(11, 19, 3, 10, 'Completed', '2024-09-01 05:17:38'),
(11, 10, 16, 20, 'Pending', '2024-02-22 10:39:31'),
(20, 9, 16, 19, 'Pending', '2024-02-13 04:13:04'),
(16, 2, 17, 5, 'Pending', '2023-12-24 15:03:48'),
(2, 14, 1, 8, 'Completed', '2023-09-13 05:54:30'),
(7, 1, 1, 3, 'Cancelled', '2024-10-09 11:29:01'),
(10, 14, 5, 10, 'Completed', '2024-12-12 19:15:32'),
(3, 18, 14, 20, 'Cancelled', '2023-04-02 11:35:31');
-- -- Insert sample data into Wants
insert into Wants (UID, GID, Date, Priority) values 
(2, 2, '2023-09-22', 1),
(1, 5, '2023-09-09', 2),
(4, 13, '2024-03-20', 2),
(1, 6, '2024-12-12', 3),
(16, 20, '2024-04-05', 3),
(3, 4, '2025-02-24', 3),
(4, 15, '2024-04-22', 2),
(15, 10, '2024-09-18', 2),
(5, 7, '2024-05-14', 4),
(15, 13, '2024-12-04', 4),
(15, 10, '2024-05-03', 4),
(19, 2, '2024-12-02', 3),
(20, 7, '2024-02-28', 1),
(20, 5, '2024-03-31', 5),
(17, 12, '2023-08-30', 4),
(20, 9, '2024-03-07', 5),
(13, 19, '2024-10-27', 2),
(18, 8, '2025-02-09', 2),
(2, 6, '2024-02-07', 2),
(11, 8, '2024-02-08', 1);

-- -- Insert sample data into Has
insert into Has (License, UID, GID) values 
('2997856191', 13, 6),
('1897563841', 6, 15),
('4789062252', 17, 5),
('0428805884', 16, 5),
('4187858849', 13, 10),
('1964550963', 3, 3),
('8151428074', 12, 4),
('6391168334', 5, 12),
('5924862178', 9, 5),
('7501323003', 8, 15),
('2499781084', 5, 7),
('4683834324', 5, 6),
('4837401562', 1, 10),
('0958166560', 4, 3),
('9163467240', 12, 6),
('0615470750', 20, 15),
('2563469783', 15, 9),
('6157011645', 3, 15),
('1384326766', 11, 5),
('9639555088', 12, 5);

-- Re-enable foreign key checks
SET foreign_key_checks = 1;
