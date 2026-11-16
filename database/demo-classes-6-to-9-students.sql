-- Add clearly labeled sample students to Classes 6–9 for the active main campus.
-- Creates Section A only for a class that has no sections, then creates two
-- students for every section in the current academic year. Class 9 gets two
-- sample students per configured academic group and section. Safe to rerun;
-- existing students are not changed or duplicated. The IDs match the earlier
-- Class 6–8 and Class 9 demo scripts, so this can replace either one safely.

INSERT INTO sections (class_id, name, room_no, branch_id)
SELECT c.id, 'A', NULL, c.branch_id
FROM branches AS campus
JOIN classes AS c
  ON c.branch_id = campus.id
 AND c.numeric_level BETWEEN 6 AND 9
WHERE campus.id = (
    SELECT id
    FROM branches
    WHERE status = 'active'
    ORDER BY is_main DESC, id ASC
    LIMIT 1
)
AND NOT EXISTS (
    SELECT 1
    FROM sections AS existing_section
    WHERE existing_section.class_id = c.id
      AND existing_section.branch_id = c.branch_id
);

INSERT INTO students (
    student_id, name, class_id, section_id, academic_group, shift,
    academic_year_id, roll_no, gender, status, date_of_birth,
    birth_registration_no, admission_date, religion, caste, mobile_number,
    email, blood_group, height, weight, measurement_date, medical_history,
    student_behaviour, current_address, permanent_address, note,
    father_name, father_phone, father_occupation, mother_name, mother_phone,
    mother_occupation, branch_id
)
SELECT
    CASE WHEN c.numeric_level = 9
        THEN CONCAT('SAMPLE-', ay.name, '-C09-SEC', LPAD(sec.id, 4, '0'), '-G', LPAD(COALESCE(ag.id, 0), 4, '0'), '-', roster.slot)
        ELSE CONCAT('SAMPLE-', ay.name, '-C', LPAD(c.numeric_level, 2, '0'), '-SEC', LPAD(sec.id, 4, '0'), '-', roster.slot)
    END,
    CASE WHEN c.numeric_level = 9
        THEN CONCAT('Demo Class 9 ', COALESCE(ag.name, 'Student'), ' Section ', sec.name, ' ', roster.slot)
        ELSE CONCAT('Demo Class ', c.numeric_level, ' Section ', sec.name, ' Student ', roster.slot)
    END,
    c.id,
    sec.id,
    ag.name,
    CASE WHEN c.shift = 'Both' THEN 'Day' ELSE c.shift END,
    ay.id,
    COALESCE((
        SELECT MAX(existing.roll_no)
        FROM students AS existing
        WHERE existing.class_id = c.id
          AND existing.section_id = sec.id
          AND existing.academic_year_id = ay.id
          AND existing.branch_id = campus.id
    ), 0) + CASE WHEN c.numeric_level = 9 THEN (COALESCE(ag.id, 0) * 2) + roster.slot ELSE roster.slot END,
    CASE roster.slot WHEN 1 THEN 'Female' ELSE 'Male' END,
    'active',
    DATE_SUB(CURDATE(), INTERVAL (c.numeric_level + 6) YEAR),
    CONCAT('DEMO-BR-C', LPAD(c.numeric_level, 2, '0'), '-S', LPAD(sec.id, 4, '0'), '-G', LPAD(COALESCE(ag.id, 0), 4, '0'), '-', roster.slot),
    DATE_ADD(ay.start_date, INTERVAL 1 DAY),
    'Demo value (sample only)',
    'Demo value (sample only)',
    CONCAT('010', LPAD(c.numeric_level * 100000 + sec.id * 100 + COALESCE(ag.id, 0) * 2 + roster.slot, 8, '0')),
    CONCAT('sample.c', c.numeric_level, '.s', sec.id, '.g', COALESCE(ag.id, 0), '.', roster.slot, '@example.invalid'),
    'O+',
    120 + c.numeric_level * 3,
    25 + c.numeric_level * 2,
    DATE_ADD(ay.start_date, INTERVAL 1 DAY),
    'Demo: no known medical conditions',
    'Sample behaviour record; replace with verified information.',
    'Demo address, Dhaka (sample only)',
    'Demo address, Dhaka (sample only)',
    'Fictional sample record for testing only.',
    CONCAT('Demo father C', c.numeric_level, ' S', sec.id, ' G', COALESCE(ag.id, 0), '-', roster.slot),
    CONCAT('011', LPAD(c.numeric_level * 100000 + sec.id * 100 + COALESCE(ag.id, 0) * 2 + roster.slot, 8, '0')),
    'Demo occupation (sample)',
    CONCAT('Demo mother C', c.numeric_level, ' S', sec.id, ' G', COALESCE(ag.id, 0), '-', roster.slot),
    CONCAT('012', LPAD(c.numeric_level * 100000 + sec.id * 100 + COALESCE(ag.id, 0) * 2 + roster.slot, 8, '0')),
    'Demo occupation (sample)',
    campus.id
FROM branches AS campus
JOIN classes AS c
  ON c.branch_id = campus.id
 AND c.numeric_level BETWEEN 6 AND 9
JOIN sections AS sec
  ON sec.class_id = c.id
 AND sec.branch_id = campus.id
JOIN academic_years AS ay
  ON ay.branch_id = campus.id
 AND ay.is_current = 1
LEFT JOIN academic_groups AS ag
  ON c.numeric_level = 9
 AND ag.branch_id = campus.id
CROSS JOIN (
    SELECT 1 AS slot
    UNION ALL SELECT 2
) AS roster
WHERE campus.id = (
    SELECT id
    FROM branches
    WHERE status = 'active'
    ORDER BY is_main DESC, id ASC
    LIMIT 1
)
AND NOT EXISTS (
    SELECT 1
    FROM students AS existing
    WHERE existing.branch_id = campus.id
      AND existing.student_id = CASE WHEN c.numeric_level = 9
          THEN CONCAT('SAMPLE-', ay.name, '-C09-SEC', LPAD(sec.id, 4, '0'), '-G', LPAD(COALESCE(ag.id, 0), 4, '0'), '-', roster.slot)
          ELSE CONCAT('SAMPLE-', ay.name, '-C', LPAD(c.numeric_level, 2, '0'), '-SEC', LPAD(sec.id, 4, '0'), '-', roster.slot)
      END
);

-- Confirm current-session sample data by class, section, and academic group.
SELECT c.name AS class_name, sec.name AS section_name,
       COALESCE(s.academic_group, 'No group') AS academic_group,
       COUNT(s.id) AS sample_students
FROM branches AS campus
JOIN classes AS c
  ON c.branch_id = campus.id
 AND c.numeric_level BETWEEN 6 AND 9
JOIN sections AS sec
  ON sec.class_id = c.id
 AND sec.branch_id = campus.id
JOIN academic_years AS ay
  ON ay.branch_id = campus.id
 AND ay.is_current = 1
LEFT JOIN students AS s
  ON s.class_id = c.id
 AND s.section_id = sec.id
 AND s.academic_year_id = ay.id
 AND s.branch_id = campus.id
 AND s.student_id LIKE CONCAT('SAMPLE-', ay.name, '-C', LPAD(c.numeric_level, 2, '0'), '-%')
WHERE campus.id = (
    SELECT id
    FROM branches
    WHERE status = 'active'
    ORDER BY is_main DESC, id ASC
    LIMIT 1
)
GROUP BY c.id, c.name, c.numeric_level, sec.id, sec.name, s.academic_group
ORDER BY c.numeric_level, sec.name, academic_group;
