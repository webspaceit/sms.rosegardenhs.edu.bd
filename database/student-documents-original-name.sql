-- One-time migration for existing installations.
-- Preserve the uploaded PDF's client filename separately from its stored path.
ALTER TABLE student_documents
    ADD COLUMN original_name VARCHAR(255) NULL AFTER title;

UPDATE student_documents
SET original_name = CONCAT(title, '.', LOWER(SUBSTRING_INDEX(file_path, '.', -1)))
WHERE original_name IS NULL;
