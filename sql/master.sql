-- phase4.sql
-- This master SQL file sequentially calls the views, stored procedures, and triggers files.
-- Ensure that views.sql, stored_procedures.sql, and triggers.sql are in the same directory,
-- or adjust the file paths accordingly.

/*To list all views in your current database*/
-- SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW';

/*To list all stored procedures in your current database, run:*/
-- SHOW PROCEDURE STATUS WHERE Db = DATABASE();

/*To list all triggers defined in your current database*/
-- SHOW TRIGGERS;

SOURCE GameStop2.sql;
SOURCE views.sql;
SOURCE procedures.sql;
SOURCE triggers.sql;