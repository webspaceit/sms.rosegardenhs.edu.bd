-- One-time migration for existing installations.
-- Roll numbers become unique within class + section + academic year + shift.
ALTER TABLE students
    DROP INDEX students_roll_unique,
    ADD COLUMN roll_shift_key VARCHAR(10)
        GENERATED ALWAYS AS (COALESCE(shift, '')) STORED AFTER shift,
    ADD UNIQUE KEY students_roll_unique
        (class_id, section_id, academic_year_id, roll_shift_key, roll_no);
