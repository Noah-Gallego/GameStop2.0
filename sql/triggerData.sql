SET foreign_key_checks = 0;

-- ───────────────────────────────────────────────────────
-- 1) Populate Has with 20 real GIDs and UIDs 1–20
-- ───────────────────────────────────────────────────────
INSERT INTO Has (License, UID, GID) VALUES
  ('LICKS-19735-ABCDE',  1, 1973530),
  ('LICKS-24420-BCDEF',   2, 244210),
  ('LICKS-24296-CDEFG',  3, 2426960),
  ('LICKS-61890-DEFGH',   4, 761890),
  ('LICKS-24680-EFGHI',  5, 2642680),
  ('LICKS-12250-FGHIJ',  6, 1422450),
  ('LICKS-25131-GHIJK',  7, 2531310),
  ('LICKS-21150-HIJKL',   8, 216150),
  ('LICKS-96090-IJKLM',   9, 960090),
  ('LICKS-22720-JKLMN', 10, 2427520),
  ('LICKS-07410-KLMNO',  11, 107410),
  ('LICKS-58010-LMNOP',  12, 582010),
  ('LICKS-23960-MNOPQ',  13, 238960),
  ('LICKS-15980-NOPQR', 14, 1569580),
  ('LICKS-16660-OPQRS', 15, 1466860),
  ('LICKS-27420-PQRST', 16, 2074920), -- change to 2670630
  ('LICKS-24710-QRSTU', 17, 2471100), -- change to 2835570
  ('LICKS-17950-RSTUV', 18, 1687950), -- change to 311210
  ('LICKS-76670-STUVW',  19, 1865060),
  ('LICKS-13030-TUVWX', 20, 3592280);

-- ───────────────────────────────────────────────────────
-- 2) Create 10 valid trades
--    • Trades  1–4, 9: Completed swaps (two-game swaps)
--    • Trade   5      : Completed gift (one game)
--    • Trades  6–8,10 : Pending or cancelled cases
-- ───────────────────────────────────────────────────────
INSERT INTO Trades
  (UID1, UID2, GID1,     GID2,     State,      Status1,   Status2) VALUES
  /* 1 */ ( 1,   2,   1973530,  244210,   'Pending', 'Accepted', 'Accepted'),
  /* 2 */ ( 3,   4,   2426960,  761890,   'Pending', 'Accepted', 'Accepted'),
  /* 3 */ ( 5,   6,   2642680,  1422450,  'Pending', 'Accepted', 'Accepted'),
  /* 4 */ ( 7,   8,   2531310,  216150,   'Pending', 'Accepted', 'Accepted'),
  /* 5 */ (11,  12,   107410,   NULL,     'Pending', 'Accepted', 'Accepted'),  -- gift from 11→12
  /* 6 */ (13,  14,   238960,   1569580,  'Pending',   'Accepted', 'Pending'),
  /* 7 */ (15,  16,   1466860,  NULL,     'Pending',   'Accepted', 'Pending'),   -- pending gift
  /* 8 */ (17,  18,   2471100,  1687950,  'Cancelled', 'Declined', 'Declined'),
  /* 9 */ (19,  20,   1865060,   3592280,  'Pending', 'Accepted', 'Accepted'),
  /*10 */ ( 9,  10,    960090,  2427520,  'Pending',   'Accepted', 'Pending');

SET foreign_key_checks = 1;
