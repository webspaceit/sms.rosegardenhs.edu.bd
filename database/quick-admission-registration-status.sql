-- One-time migration for existing installations.
-- Quick admissions remain pending until a staff member saves the full student form.
-- Existing students are treated as fully registered.
ALTER TABLE students
    ADD COLUMN registration_status ENUM('pending', 'completed') NOT NULL DEFAULT 'completed' AFTER status,
    ADD KEY students_registration_status_index (registration_status);
