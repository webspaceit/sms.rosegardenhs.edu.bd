-- Add an optional shift assignment to each teacher record.
ALTER TABLE teachers
    ADD COLUMN shift_id BIGINT UNSIGNED NULL AFTER department_id,
    ADD INDEX teachers_shift_index (shift_id),
    ADD CONSTRAINT teachers_shift_fk
        FOREIGN KEY (shift_id) REFERENCES shifts (id) ON DELETE SET NULL;
