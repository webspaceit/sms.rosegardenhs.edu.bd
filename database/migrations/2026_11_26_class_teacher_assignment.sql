-- Store one class teacher and signature directly on each class.
ALTER TABLE classes
    ADD COLUMN class_teacher_id BIGINT UNSIGNED NULL AFTER shift,
    ADD COLUMN class_teacher_signature VARCHAR(255) NULL AFTER class_teacher_id;

-- Preserve each existing class teacher and signature from the subject assignment.
UPDATE classes c
JOIN (
    SELECT class_id, branch_id, MIN(id) AS assignment_id
    FROM teacher_subject
    WHERE is_class_teacher = 1
    GROUP BY class_id, branch_id
) chosen ON chosen.class_id = c.id AND chosen.branch_id = c.branch_id
JOIN teacher_subject ts ON ts.id = chosen.assignment_id AND ts.branch_id = chosen.branch_id
SET c.class_teacher_id = ts.teacher_id,
    c.class_teacher_signature = ts.class_teacher_signature
WHERE c.class_teacher_id IS NULL;
