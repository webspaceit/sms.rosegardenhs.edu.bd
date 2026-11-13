-- Run once in phpMyAdmin after selecting the school's database.
-- Preserve legacy student.address values in current_address before dropping
-- the now-redundant students.address column. Guardian/branch address columns
-- are not touched.

UPDATE students
SET current_address = CASE
    WHEN current_address IS NULL OR TRIM(current_address) = '' THEN TRIM(address)
    WHEN TRIM(current_address) = TRIM(address) THEN current_address
    ELSE CONCAT(TRIM(current_address), '\n', TRIM(address))
END
WHERE address IS NOT NULL
  AND TRIM(address) <> '';

ALTER TABLE students DROP COLUMN address;
