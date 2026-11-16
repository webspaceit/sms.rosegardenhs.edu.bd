-- Add missing Class 7, Class 8, and Class 9 records to the active main campus.
-- Also ensure each class has a Section A so it is ready for student admission.
-- Safe to run more than once. Does not create or alter student records.

INSERT INTO classes (name, numeric_level, shift, branch_id)
SELECT CONCAT('Class ', levels.numeric_level), levels.numeric_level, 'Day', campus.id
FROM branches AS campus
CROSS JOIN (
    SELECT 7 AS numeric_level
    UNION ALL SELECT 8
    UNION ALL SELECT 9
) AS levels
WHERE campus.id = (
    SELECT id
    FROM branches
    WHERE status = 'active'
    ORDER BY is_main DESC, id ASC
    LIMIT 1
)
AND NOT EXISTS (
    SELECT 1
    FROM classes AS existing_class
    WHERE existing_class.branch_id = campus.id
      AND existing_class.numeric_level = levels.numeric_level
);

INSERT INTO sections (class_id, name, room_no, branch_id)
SELECT class_record.id, 'A', NULL, class_record.branch_id
FROM classes AS class_record
WHERE class_record.numeric_level BETWEEN 7 AND 9
  AND class_record.branch_id = (
      SELECT id
      FROM branches
      WHERE status = 'active'
      ORDER BY is_main DESC, id ASC
      LIMIT 1
  )
  AND NOT EXISTS (
      SELECT 1
      FROM sections AS existing_section
      WHERE existing_section.class_id = class_record.id
        AND existing_section.branch_id = class_record.branch_id
        AND existing_section.name = 'A'
  );
