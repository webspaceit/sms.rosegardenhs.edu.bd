-- Add clearly labeled, non-real sample student profiles for every section
-- in Classes 1–9 (two students per section).
-- Run this file in phpMyAdmin after selecting the school database.
-- Safe to repeat: it adds only missing SAMPLE records and fills only blank
-- profile fields for existing students. Existing nonblank values are preserved.

SET @demo_branch_id = (
    SELECT id FROM branches
    WHERE status = 'active'
    ORDER BY is_main DESC, id ASC
    LIMIT 1
);
SET @demo_year_id = (
    SELECT id FROM academic_years
    ORDER BY is_current DESC, start_date DESC, id DESC
    LIMIT 1
);
SET @demo_year_start = (
    SELECT start_date FROM academic_years WHERE id = @demo_year_id LIMIT 1
);
SET @demo_year_name = (
    SELECT name FROM academic_years WHERE id = @demo_year_id LIMIT 1
);

-- Ensure all nine class levels and at least a section A are available.
INSERT INTO classes (name, numeric_level, shift, branch_id)
SELECT CONCAT('Class ', levels.level), levels.level, 'Day', @demo_branch_id
FROM (
    SELECT 1 AS level UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
    SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
    SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
) AS levels
WHERE @demo_branch_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM classes c
      WHERE c.numeric_level = levels.level AND c.branch_id = @demo_branch_id
  );

INSERT INTO sections (class_id, name, room_no, branch_id)
SELECT c.id, 'A', NULL, @demo_branch_id
FROM classes c
WHERE c.numeric_level BETWEEN 1 AND 9
  AND c.branch_id = @demo_branch_id
  AND NOT EXISTS (
      SELECT 1 FROM sections sec
      WHERE sec.class_id = c.id AND sec.name = 'A' AND sec.branch_id = @demo_branch_id
  );

CREATE TEMPORARY TABLE demo_student_roster (
    class_level TINYINT UNSIGNED NOT NULL,
    slot TINYINT UNSIGNED NOT NULL,
    gender VARCHAR(20) NOT NULL,
    PRIMARY KEY (class_level, slot)
);
INSERT INTO demo_student_roster (class_level, slot, gender) VALUES
    (1,1,'Female'),(1,2,'Male'),(2,1,'Female'),(2,2,'Male'),
    (3,1,'Female'),(3,2,'Male'),(4,1,'Female'),(4,2,'Male'),
    (5,1,'Female'),(5,2,'Male'),(6,1,'Female'),(6,2,'Male'),
    (7,1,'Female'),(7,2,'Male'),(8,1,'Female'),(8,2,'Male'),
    (9,1,'Female'),(9,2,'Male');

-- Create two sample records for every existing section. Existing records and
-- roll numbers remain unchanged; sample roll numbers continue after the
-- current maximum in each section.
INSERT INTO students (
    student_id, name, class_id, section_id, shift, academic_year_id, roll_no,
    gender, date_of_birth, birth_registration_no, admission_date, address,
    religion, caste, mobile_number, email, blood_group, height, weight,
    measurement_date, medical_history, current_address, permanent_address,
    note, father_name, father_phone, father_occupation, mother_name,
    mother_phone, mother_occupation, branch_id
)
SELECT
    CASE WHEN sec.name = 'A'
        THEN CONCAT('SAMPLE-', @demo_year_name, '-C', LPAD(roster.class_level, 2, '0'), '-', LPAD(roster.slot, 2, '0'))
        ELSE CONCAT('SAMPLE-', @demo_year_name, '-C', LPAD(roster.class_level, 2, '0'), '-SEC', LPAD(sec.id, 4, '0'), '-', LPAD(roster.slot, 2, '0'))
    END,
    CONCAT('Demo Class ', roster.class_level, ' Section ', sec.name, ' Student ', LPAD(roster.slot, 2, '0')),
    c.id, sec.id, 'Day', @demo_year_id,
    COALESCE((
        SELECT MAX(existing.roll_no) FROM students existing
        WHERE existing.class_id = c.id AND existing.section_id = sec.id
          AND existing.academic_year_id = @demo_year_id
          AND existing.branch_id = @demo_branch_id
    ), 0) + roster.slot,
    roster.gender,
    DATE_SUB(CURDATE(), INTERVAL (6 + roster.class_level) YEAR),
    CONCAT('DEMO-BR-C', LPAD(roster.class_level, 2, '0'), '-S', LPAD(sec.id, 4, '0'), '-', LPAD(roster.slot, 2, '0')),
    DATE_ADD(@demo_year_start, INTERVAL 1 DAY),
    'Demo address, Dhaka', 'Demo value', 'Demo value',
    CONCAT('010', LPAD(sec.id * 100 + roster.slot, 8, '0')),
    CONCAT('student.c', roster.class_level, '.s', sec.id, '.', roster.slot, '@example.invalid'),
    'O+', 120 + roster.class_level, 25 + roster.class_level,
    DATE_ADD(@demo_year_start, INTERVAL 1 DAY),
    'Demo: no known medical conditions', 'Demo address, Dhaka', 'Demo address, Dhaka',
    'Sample data for demonstration only',
    CONCAT('Demo father C', roster.class_level, ' S', sec.id, '-', roster.slot),
    CONCAT('010', LPAD(100000 + sec.id * 10 + roster.slot, 8, '0')),
    'Demo occupation',
    CONCAT('Demo mother C', roster.class_level, ' S', sec.id, '-', roster.slot),
    CONCAT('010', LPAD(200000 + sec.id * 10 + roster.slot, 8, '0')),
    'Demo occupation', @demo_branch_id
FROM demo_student_roster roster
JOIN classes c ON c.numeric_level = roster.class_level AND c.branch_id = @demo_branch_id
JOIN sections sec ON sec.class_id = c.id AND sec.branch_id = @demo_branch_id
WHERE @demo_year_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM students existing
      WHERE existing.student_id = CASE WHEN sec.name = 'A'
          THEN CONCAT('SAMPLE-', @demo_year_name, '-C', LPAD(roster.class_level, 2, '0'), '-', LPAD(roster.slot, 2, '0'))
          ELSE CONCAT('SAMPLE-', @demo_year_name, '-C', LPAD(roster.class_level, 2, '0'), '-SEC', LPAD(sec.id, 4, '0'), '-', LPAD(roster.slot, 2, '0'))
      END
        AND existing.branch_id = @demo_branch_id
  );

-- Fill blank profile fields for every STU-, DEMO- and SAMPLE- student. Each generated value
-- is explicitly labeled as demo/sample information and should be replaced by
-- verified information. No existing nonblank value is overwritten.
UPDATE students s
JOIN classes c ON c.id = s.class_id AND c.branch_id = s.branch_id
SET s.shift = COALESCE(NULLIF(s.shift, ''), 'Day'),
    s.date_of_birth = COALESCE(s.date_of_birth, DATE_SUB(CURDATE(), INTERVAL (6 + c.numeric_level) YEAR)),
    s.birth_registration_no = COALESCE(NULLIF(s.birth_registration_no, ''), CONCAT('DEMO-BR-', LPAD(s.id, 8, '0'))),
    s.admission_date = COALESCE(s.admission_date, DATE_ADD(@demo_year_start, INTERVAL 1 DAY)),
    s.address = COALESCE(NULLIF(s.address, ''), 'Demo address, Dhaka (sample only)'),
    s.religion = COALESCE(NULLIF(s.religion, ''), 'Demo value (sample only)'),
    s.caste = COALESCE(NULLIF(s.caste, ''), 'Demo value (sample only)'),
    s.mobile_number = COALESCE(NULLIF(s.mobile_number, ''), CONCAT('010', LPAD(s.id, 8, '0'))),
    s.email = COALESCE(NULLIF(s.email, ''), CONCAT('student.', s.id, '@example.invalid')),
    s.blood_group = COALESCE(NULLIF(s.blood_group, ''), 'O+'),
    s.height = COALESCE(s.height, 120 + c.numeric_level),
    s.weight = COALESCE(s.weight, 25 + c.numeric_level),
    s.measurement_date = COALESCE(s.measurement_date, DATE_ADD(@demo_year_start, INTERVAL 1 DAY)),
    s.medical_history = COALESCE(NULLIF(s.medical_history, ''), 'Demo: no known medical conditions'),
    s.current_address = COALESCE(NULLIF(s.current_address, ''), 'Demo address, Dhaka (sample only)'),
    s.permanent_address = COALESCE(NULLIF(s.permanent_address, ''), 'Demo address, Dhaka (sample only)'),
    s.note = COALESCE(NULLIF(s.note, ''), 'Demo sample values were added for missing fields; replace with verified information.'),
    s.father_name = COALESCE(NULLIF(s.father_name, ''), CONCAT('Demo father (sample) for student ', s.id)),
    s.father_phone = COALESCE(NULLIF(s.father_phone, ''), CONCAT('010', LPAD(s.id, 8, '0'))),
    s.father_occupation = COALESCE(NULLIF(s.father_occupation, ''), 'Demo occupation (sample)'),
    s.mother_name = COALESCE(NULLIF(s.mother_name, ''), CONCAT('Demo mother (sample) for student ', s.id)),
    s.mother_phone = COALESCE(NULLIF(s.mother_phone, ''), CONCAT('010', LPAD(s.id + 100000, 8, '0'))),
    s.mother_occupation = COALESCE(NULLIF(s.mother_occupation, ''), 'Demo occupation (sample)')
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%');

-- Give demo records a linked primary guardian where no guardian is already
-- linked. example.invalid addresses and demo-labeled names are not real people.
UPDATE guardians g
JOIN student_guardian sg ON sg.guardian_id = g.id AND sg.branch_id = g.branch_id
JOIN students s ON s.id = sg.student_id AND s.branch_id = sg.branch_id
SET g.email = COALESCE(NULLIF(g.email, ''), CONCAT('guardian.', g.id, '@example.invalid')),
    g.occupation = COALESCE(NULLIF(g.occupation, ''), 'Demo occupation (sample)'),
    g.address = COALESCE(NULLIF(g.address, ''), 'Demo address, Dhaka (sample only)'),
    g.district = COALESCE(NULLIF(g.district, ''), 'Dhaka')
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%');

INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at, branch_id)
SELECT CONCAT('Demo guardian for student ', s.id),
       CONCAT('011', LPAD(s.id, 8, '0')),
       CONCAT('guardian.', s.id, '@example.invalid'),
       'Demo occupation', 'Guardian', 'Demo address, Dhaka', 'Dhaka', NOW(), s.branch_id
FROM students s
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (
      SELECT 1 FROM student_guardian sg
      WHERE sg.student_id = s.id AND sg.branch_id = s.branch_id
  );

INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)
SELECT s.id, g.id, 1, NOW(), s.branch_id
FROM students s
JOIN guardians g ON g.name = CONCAT('Demo guardian for student ', s.id)
                AND g.branch_id = s.branch_id
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (
      SELECT 1 FROM student_guardian sg
      WHERE sg.student_id = s.id AND sg.branch_id = s.branch_id
  );

DROP TEMPORARY TABLE demo_student_roster;

-- Confirm demo records exist for every class and section.
SELECT c.name AS class_name, sec.name AS section_name, COUNT(s.id) AS sample_students
FROM classes c
JOIN sections sec ON sec.class_id = c.id AND sec.branch_id = c.branch_id
LEFT JOIN students s ON s.class_id = c.id AND s.section_id = sec.id AND s.branch_id = c.branch_id
    AND (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
WHERE c.numeric_level BETWEEN 1 AND 9
  AND c.branch_id = @demo_branch_id
GROUP BY c.id, c.name, c.numeric_level, sec.id, sec.name
ORDER BY c.numeric_level, sec.name;
