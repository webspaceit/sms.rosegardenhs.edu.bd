-- Add the optional National ID field to teacher records.
-- Run once in phpMyAdmin. This does not query information_schema.
ALTER TABLE teachers ADD COLUMN nid VARCHAR(30) NULL AFTER name;
