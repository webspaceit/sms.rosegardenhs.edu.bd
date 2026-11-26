-- Store a signature image path on the class teacher assignment.
ALTER TABLE teacher_subject
    ADD COLUMN class_teacher_signature VARCHAR(255) NULL AFTER is_class_teacher;
