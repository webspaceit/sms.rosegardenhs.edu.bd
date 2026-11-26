-- Add the optional National ID field to teacher records.
-- Run once in phpMyAdmin. It needs ALTER permission on the teachers table,
-- but does not query information_schema or prepare a dynamic statement.
ALTER TABLE teachers ADD COLUMN nid VARCHAR(30) NULL AFTER name;
