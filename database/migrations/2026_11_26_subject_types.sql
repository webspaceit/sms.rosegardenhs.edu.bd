-- Add branch-managed subject types and link existing subjects to them.
CREATE TABLE IF NOT EXISTS subject_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY subject_type_name_unique (branch_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE subjects
    ADD COLUMN type_id BIGINT UNSIGNED NULL AFTER type;

INSERT IGNORE INTO subject_types (name, branch_id, created_at)
SELECT DISTINCT
    CASE LOWER(TRIM(s.type))
        WHEN 'core' THEN 'Core'
        WHEN 'elective' THEN 'Elective'
        WHEN 'skill' THEN 'Skill based'
        WHEN 'extra' THEN 'Extra curriculum'
        ELSE COALESCE(NULLIF(TRIM(s.type), ''), 'Core')
    END,
    s.branch_id,
    NOW()
FROM subjects s;

UPDATE subjects s
JOIN subject_types st
  ON st.branch_id = s.branch_id
 AND st.name = CASE LOWER(TRIM(s.type))
        WHEN 'core' THEN 'Core'
        WHEN 'elective' THEN 'Elective'
        WHEN 'skill' THEN 'Skill based'
        WHEN 'extra' THEN 'Extra curriculum'
        ELSE COALESCE(NULLIF(TRIM(s.type), ''), 'Core')
    END
SET s.type_id = st.id
WHERE s.type_id IS NULL;

ALTER TABLE subjects
    ADD CONSTRAINT subjects_type_fk
        FOREIGN KEY (type_id) REFERENCES subject_types (id) ON DELETE RESTRICT;
