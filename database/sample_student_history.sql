-- Clearly labeled sample values for blank STU-, DEMO- and SAMPLE- student records.
-- Select the school database in phpMyAdmin, then import/run this file.
-- Re-runnable: sample students and related records are not duplicated.

SET @has_student_behaviour = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'student_behaviour'
);
SET @student_behaviour_ddl = IF(
    @has_student_behaviour = 0,
    'ALTER TABLE students ADD COLUMN student_behaviour TEXT NULL AFTER medical_history',
    'SELECT 1'
);
PREPARE student_behaviour_stmt FROM @student_behaviour_ddl;
EXECUTE student_behaviour_stmt;
DEALLOCATE PREPARE student_behaviour_stmt;

START TRANSACTION;

-- Ensure the requested 2025 academic year exists for each active campus.
-- This does not change which year is marked current.
INSERT INTO academic_years (name, start_date, end_date, is_current, branch_id)
SELECT '2025', '2025-01-01', '2025-12-31', 0, b.id
FROM branches b
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM academic_years ay WHERE ay.branch_id = b.id AND ay.name = '2025');

-- Add one fictional, fully populated student in every existing section for
-- classes 1-9. IDs include campus, class and section so they remain unique.
INSERT INTO students (
    student_id, name, class_id, section_id, shift, academic_year_id, roll_no, gender,
    date_of_birth, birth_registration_no, admission_date, previous_school,
    religion, caste, mobile_number, email, blood_group, height, weight, measurement_date,
    medical_history, student_behaviour, current_address, permanent_address, national_id,
    local_id, note, father_name, father_phone, father_occupation, mother_name, mother_phone,
    mother_occupation, status, branch_id
)
SELECT CONCAT('SAMPLE-2025-B', c.branch_id, '-C', c.numeric_level, '-S', sec.id),
       CONCAT('Sample 2025 Class ', c.numeric_level, ' Section ', sec.name),
       c.id, sec.id, c.shift, ay.id,
       COALESCE((SELECT MAX(s2.roll_no) FROM students s2 WHERE s2.class_id = c.id AND s2.section_id = sec.id AND s2.academic_year_id = ay.id), 0) + 1,
       CASE WHEN MOD(c.numeric_level + sec.id, 2) = 0 THEN 'Female' ELSE 'Male' END,
       DATE_SUB('2019-01-01', INTERVAL (c.numeric_level - 1) YEAR),
       CONCAT('SAMPLE-2025-BR-', sec.id), '2025-01-02', 'Sample previous school',
       'Sample', 'Sample',
       CONCAT('019', LPAD(sec.id, 8, '0')),
       CONCAT('student.sample.2025.', c.branch_id, '.', sec.id, '@example.invalid'),
       'O+', 120 + c.numeric_level, 25 + c.numeric_level, '2025-01-02',
       'Sample record: no verified medical information.',
       'Sample note: demonstrates positive classroom participation.',
       CONCAT('Sample current address, ', sec.room_no), 'Sample permanent address, Dhaka',
       CONCAT('SAMPLE-NID-', sec.id), CONCAT('SAMPLE-LID-', sec.id),
       'Fictional sample record for 2025; replace before real use.',
       CONCAT('Sample father ', sec.id), CONCAT('013', LPAD(sec.id, 8, '0')), 'Sample occupation',
       CONCAT('Sample mother ', sec.id), CONCAT('014', LPAD(sec.id, 8, '0')), 'Sample occupation',
       'active', c.branch_id
FROM classes c
JOIN sections sec ON sec.class_id = c.id AND sec.branch_id = c.branch_id
JOIN academic_years ay ON ay.branch_id = c.branch_id AND ay.name = '2025'
JOIN branches b ON b.id = c.branch_id AND b.status = 'active'
WHERE c.numeric_level BETWEEN 1 AND 9
  AND NOT EXISTS (
      SELECT 1 FROM students existing
      WHERE existing.student_id = CONCAT('SAMPLE-2025-B', c.branch_id, '-C', c.numeric_level, '-S', sec.id)
  );

-- Give each new sample student a linked sample guardian.
INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at, branch_id)
SELECT CONCAT('Sample guardian ', s.id), CONCAT('018', LPAD(s.id, 8, '0')),
       CONCAT('guardian.sample.2025.', s.id, '@example.invalid'), 'Sample occupation', 'Father',
       s.current_address, 'Dhaka', NOW(), s.branch_id
FROM students s
WHERE s.student_id LIKE 'SAMPLE-2025-%'
  AND NOT EXISTS (
      SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.branch_id = s.branch_id
  );

INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)
SELECT s.id, g.id, 1, NOW(), s.branch_id
FROM students s
JOIN guardians g ON g.branch_id = s.branch_id AND g.email = CONCAT('guardian.sample.2025.', s.id, '@example.invalid')
WHERE s.student_id LIKE 'SAMPLE-2025-%'
  AND NOT EXISTS (SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.guardian_id = g.id);

-- Add the same fictional Class 1-9 / every existing section roster for 2024.
INSERT INTO academic_years (name, start_date, end_date, is_current, branch_id)
SELECT '2024', '2024-01-01', '2024-12-31', 0, b.id
FROM branches b
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM academic_years ay WHERE ay.branch_id = b.id AND ay.name = '2024');

INSERT INTO students (
    student_id, name, class_id, section_id, shift, academic_year_id, roll_no, gender,
    date_of_birth, birth_registration_no, admission_date, previous_school,
    religion, caste, mobile_number, email, blood_group, height, weight, measurement_date,
    medical_history, student_behaviour, current_address, permanent_address, national_id,
    local_id, note, father_name, father_phone, father_occupation, mother_name, mother_phone,
    mother_occupation, status, branch_id
)
SELECT CONCAT('SAMPLE-2024-B', c.branch_id, '-C', c.numeric_level, '-S', sec.id),
       CONCAT('Sample 2024 Class ', c.numeric_level, ' Section ', sec.name),
       c.id, sec.id, c.shift, ay.id,
       COALESCE((SELECT MAX(s2.roll_no) FROM students s2 WHERE s2.class_id = c.id AND s2.section_id = sec.id AND s2.academic_year_id = ay.id), 0) + 1,
       CASE WHEN MOD(c.numeric_level + sec.id, 2) = 0 THEN 'Female' ELSE 'Male' END,
       DATE_SUB('2018-01-01', INTERVAL (c.numeric_level - 1) YEAR),
       CONCAT('SAMPLE-2024-BR-', sec.id), '2024-01-02', 'Sample previous school',
       'Sample', 'Sample',
       CONCAT('017', LPAD(sec.id, 8, '0')),
       CONCAT('student.sample.2024.', c.branch_id, '.', sec.id, '@example.invalid'),
       'O+', 119 + c.numeric_level, 24 + c.numeric_level, '2024-01-02',
       'Sample record: no verified medical information.',
       'Sample note: demonstrates positive classroom participation.',
       CONCAT('Sample current address, ', sec.room_no), 'Sample permanent address, Dhaka',
       CONCAT('SAMPLE-2024-NID-', sec.id), CONCAT('SAMPLE-2024-LID-', sec.id),
       'Fictional sample record for 2024; replace before real use.',
       CONCAT('Sample father 2024 ', sec.id), CONCAT('015', LPAD(sec.id, 8, '0')), 'Sample occupation',
       CONCAT('Sample mother 2024 ', sec.id), CONCAT('016', LPAD(sec.id, 8, '0')), 'Sample occupation',
       'active', c.branch_id
FROM classes c
JOIN sections sec ON sec.class_id = c.id AND sec.branch_id = c.branch_id
JOIN academic_years ay ON ay.branch_id = c.branch_id AND ay.name = '2024'
JOIN branches b ON b.id = c.branch_id AND b.status = 'active'
WHERE c.numeric_level BETWEEN 1 AND 9
  AND NOT EXISTS (
      SELECT 1 FROM students existing
      WHERE existing.student_id = CONCAT('SAMPLE-2024-B', c.branch_id, '-C', c.numeric_level, '-S', sec.id)
  );

INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at, branch_id)
SELECT CONCAT('Sample guardian 2024 ', s.id), CONCAT('018', LPAD(s.id, 8, '0')),
       CONCAT('guardian.sample.2024.', s.id, '@example.invalid'), 'Sample occupation', 'Father',
       s.current_address, 'Dhaka', NOW(), s.branch_id
FROM students s
WHERE s.student_id LIKE 'SAMPLE-2024-%'
  AND NOT EXISTS (
      SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.branch_id = s.branch_id
  );

INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)
SELECT s.id, g.id, 1, NOW(), s.branch_id
FROM students s
JOIN guardians g ON g.branch_id = s.branch_id AND g.email = CONCAT('guardian.sample.2024.', s.id, '@example.invalid')
WHERE s.student_id LIKE 'SAMPLE-2024-%'
  AND NOT EXISTS (SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.guardian_id = g.id);

-- Add clearly labeled 2026 demo students in every existing section for Classes 1-9.
-- Existing academic-year flags are left as they are.
INSERT INTO academic_years (name, start_date, end_date, is_current, branch_id)
SELECT '2026', '2026-01-01', '2026-12-31', 0, b.id
FROM branches b
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM academic_years ay WHERE ay.branch_id = b.id AND ay.name = '2026');

INSERT INTO students (
    student_id, name, class_id, section_id, shift, academic_year_id, roll_no, gender,
    date_of_birth, birth_registration_no, admission_date, previous_school,
    religion, caste, mobile_number, email, blood_group, height, weight, measurement_date,
    medical_history, student_behaviour, current_address, permanent_address, national_id,
    local_id, note, father_name, father_phone, father_occupation, mother_name, mother_phone,
    mother_occupation, status, branch_id
)
SELECT CONCAT('SAMPLE-2026-B', c.branch_id, '-C', c.numeric_level, '-S', sec.id),
       CONCAT('Sample 2026 Class ', c.numeric_level, ' Section ', sec.name),
       c.id, sec.id, c.shift, ay.id,
       COALESCE((SELECT MAX(s2.roll_no) FROM students s2 WHERE s2.class_id = c.id AND s2.section_id = sec.id AND s2.academic_year_id = ay.id), 0) + 1,
       CASE WHEN MOD(c.numeric_level + sec.id + 1, 2) = 0 THEN 'Female' ELSE 'Male' END,
       DATE_SUB('2020-01-01', INTERVAL (c.numeric_level - 1) YEAR),
       CONCAT('SAMPLE-2026-BR-', sec.id), '2026-01-02', 'Sample previous school',
       'Sample', 'Sample',
       CONCAT('016', LPAD(sec.id, 8, '0')),
       CONCAT('student.sample.2026.', c.branch_id, '.', sec.id, '@example.invalid'),
       'A+', 121 + c.numeric_level, 26 + c.numeric_level, '2026-01-02',
       'Sample record: no verified medical information.',
       'Sample note: demonstrates positive classroom participation.',
       CONCAT('Sample current address, ', sec.room_no), 'Sample permanent address, Dhaka',
       CONCAT('SAMPLE-2026-NID-', sec.id), CONCAT('SAMPLE-2026-LID-', sec.id),
       'Fictional sample record for 2026; replace before real use.',
       CONCAT('Sample father 2026 ', sec.id), CONCAT('012', LPAD(sec.id, 8, '0')), 'Sample occupation',
       CONCAT('Sample mother 2026 ', sec.id), CONCAT('011', LPAD(sec.id, 8, '0')), 'Sample occupation',
       'active', c.branch_id
FROM classes c
JOIN sections sec ON sec.class_id = c.id AND sec.branch_id = c.branch_id
JOIN academic_years ay ON ay.branch_id = c.branch_id AND ay.name = '2026'
JOIN branches b ON b.id = c.branch_id AND b.status = 'active'
WHERE c.numeric_level BETWEEN 1 AND 9
  AND NOT EXISTS (
      SELECT 1 FROM students existing
      WHERE existing.student_id = CONCAT('SAMPLE-2026-B', c.branch_id, '-C', c.numeric_level, '-S', sec.id)
  );

INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at, branch_id)
SELECT CONCAT('Sample guardian 2026 ', s.id), CONCAT('018', LPAD(s.id, 8, '0')),
       CONCAT('guardian.sample.2026.', s.id, '@example.invalid'), 'Sample occupation', 'Father',
       s.current_address, 'Dhaka', NOW(), s.branch_id
FROM students s
WHERE s.student_id LIKE 'SAMPLE-2026-%'
  AND NOT EXISTS (
      SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.branch_id = s.branch_id
  );

INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)
SELECT s.id, g.id, 1, NOW(), s.branch_id
FROM students s
JOIN guardians g ON g.branch_id = s.branch_id AND g.email = CONCAT('guardian.sample.2026.', s.id, '@example.invalid')
WHERE s.student_id LIKE 'SAMPLE-2026-%'
  AND NOT EXISTS (SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.guardian_id = g.id);

-- Fill only blank profile fields for every STU-, DEMO- and SAMPLE- student.
-- All inserted text is visibly labeled as sample/demo information.
UPDATE students s
JOIN classes c ON c.id = s.class_id AND c.branch_id = s.branch_id
LEFT JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id = s.branch_id
SET s.date_of_birth = COALESCE(s.date_of_birth, DATE_SUB(CURDATE(), INTERVAL (6 + c.numeric_level) YEAR)),
    s.birth_registration_no = COALESCE(NULLIF(s.birth_registration_no, ''), CONCAT('DEMO-BR-', LPAD(s.id, 8, '0'))),
    s.admission_date = COALESCE(s.admission_date, ay.start_date),
    s.religion = COALESCE(NULLIF(s.religion, ''), 'Demo value (sample only)'),
    s.caste = COALESCE(NULLIF(s.caste, ''), 'Demo value (sample only)'),
    s.mobile_number = COALESCE(NULLIF(s.mobile_number, ''), CONCAT('010', LPAD(s.id, 8, '0'))),
    s.email = COALESCE(NULLIF(s.email, ''), CONCAT('student.', s.id, '@example.invalid')),
    s.blood_group = COALESCE(NULLIF(s.blood_group, ''), 'O+'),
    s.height = COALESCE(s.height, 120 + c.numeric_level),
    s.weight = COALESCE(s.weight, 25 + c.numeric_level),
    s.measurement_date = COALESCE(s.measurement_date, COALESCE(ay.start_date, CURDATE())),
    s.medical_history = COALESCE(NULLIF(s.medical_history, ''), 'Demo: no known medical conditions (sample only)'),
    s.student_behaviour = COALESCE(NULLIF(s.student_behaviour, ''), 'Sample note: demonstrates positive classroom participation. Replace with verified information.'),
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

UPDATE guardians g
JOIN student_guardian sg ON sg.guardian_id = g.id AND sg.branch_id = g.branch_id
JOIN students s ON s.id = sg.student_id AND s.branch_id = sg.branch_id
SET g.email = COALESCE(NULLIF(g.email, ''), CONCAT('guardian.sample.', g.id, '@example.invalid')),
    g.occupation = COALESCE(NULLIF(g.occupation, ''), 'Demo occupation (sample)'),
    g.address = COALESCE(NULLIF(g.address, ''), 'Demo address, Dhaka (sample only)'),
    g.district = COALESCE(NULLIF(g.district, ''), 'Dhaka (sample)')
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%');

-- Add a fictional linked guardian only for students who currently have none.
INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at, branch_id)
SELECT CONCAT('Sample guardian for student ', s.id), CONCAT('011', LPAD(s.id, 8, '0')),
       CONCAT('sample.guardian.student.', s.id, '@example.invalid'), 'Demo occupation (sample)', 'Guardian',
       COALESCE(NULLIF(s.current_address, ''), 'Demo address, Dhaka (sample only)'), 'Dhaka (sample)', NOW(), s.branch_id
FROM students s
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.branch_id = s.branch_id);

INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)
SELECT s.id, g.id, 1, NOW(), s.branch_id
FROM students s
JOIN guardians g ON g.branch_id = s.branch_id
                 AND g.email = CONCAT('sample.guardian.student.', s.id, '@example.invalid')
WHERE s.branch_id IS NOT NULL
  AND (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM student_guardian sg WHERE sg.student_id = s.id AND sg.guardian_id = g.id);

UPDATE students
SET student_behaviour = 'Sample note: demonstrates positive classroom participation. Replace with verified information.'
WHERE (student_id LIKE 'STU-%' OR student_id LIKE 'DEMO-%' OR student_id LIKE 'SAMPLE-%')
  AND (student_behaviour IS NULL OR student_behaviour = '');

-- Add the year before each sample student's enrollment year for their profile history.
INSERT INTO academic_years (name, start_date, end_date, is_current, branch_id)
SELECT DISTINCT CAST(CAST(ay.name AS UNSIGNED) - 1 AS CHAR),
       STR_TO_DATE(CONCAT(CAST(ay.name AS UNSIGNED) - 1, '-01-01'), '%Y-%m-%d'),
       STR_TO_DATE(CONCAT(CAST(ay.name AS UNSIGNED) - 1, '-12-31'), '%Y-%m-%d'), 0, ay.branch_id
FROM academic_years ay
JOIN students s ON s.academic_year_id = ay.id AND s.branch_id = ay.branch_id
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND ay.name REGEXP '^[0-9]{4}$'
  AND NOT EXISTS (
      SELECT 1 FROM academic_years old_year
      WHERE old_year.branch_id = ay.branch_id AND old_year.name = CAST(CAST(ay.name AS UNSIGNED) - 1 AS CHAR)
  );

INSERT INTO fee_heads (name, frequency, is_optional, sort_order, created_at, branch_id)
SELECT 'Demo tuition (sample)', 'monthly', 0, 0, NOW(), b.id
FROM branches b
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM fee_heads fh WHERE fh.branch_id = b.id AND fh.name = 'Demo tuition (sample)');

INSERT INTO subjects (name, type, created_at, branch_id)
SELECT 'Demo Mathematics (sample)', 'core', NOW(), b.id
FROM branches b
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM subjects s WHERE s.branch_id = b.id AND s.name = 'Demo Mathematics (sample)');

-- Attendance: five illustrative days for DEMO-/SAMPLE- records only.
INSERT INTO attendances (student_id, date, status, remarks, created_at, branch_id)
SELECT s.id, DATE_SUB(LEAST(STR_TO_DATE(CONCAT(ay.name, '-12-31'), '%Y-%m-%d'), CURDATE()), INTERVAL d.day_offset DAY),
       CASE WHEN d.day_offset = 3 THEN 'late' ELSE 'present' END,
       'Sample attendance record', NOW(), s.branch_id
FROM students s
JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id = s.branch_id
CROSS JOIN (
    SELECT 1 AS day_offset UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) d
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND ay.name REGEXP '^[0-9]{4}$'
  AND NOT EXISTS (SELECT 1 FROM attendances a WHERE a.student_id = s.id AND a.date = DATE_SUB(LEAST(STR_TO_DATE(CONCAT(ay.name, '-12-31'), '%Y-%m-%d'), CURDATE()), INTERVAL d.day_offset DAY));

-- Fee invoices, tied to the sample fee head and each student's academic year.
INSERT INTO fee_collections (student_id, fee_head_id, academic_year_id, invoice_no, month, amount, status, due_date, paid_on, payment_method, remarks, created_at, branch_id)
SELECT s.id, fh.id, s.academic_year_id, CONCAT('SAMPLE-', s.id, '-TUITION-01'), CONCAT(ay.name, '-11'),
       1500.00, 'paid', CONCAT(ay.name, '-11-20'), CONCAT(ay.name, '-11-18'), 'Cash', 'Sample invoice', NOW(), s.branch_id
FROM students s
JOIN fee_heads fh ON fh.branch_id = s.branch_id AND fh.name = 'Demo tuition (sample)'
JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id = s.branch_id
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND ay.name REGEXP '^[0-9]{4}$'
  AND NOT EXISTS (SELECT 1 FROM fee_collections fc WHERE fc.student_id = s.id AND fc.invoice_no = CONCAT('SAMPLE-', s.id, '-TUITION-01'));

-- Previous enrollment example; class/section/roll are retained as sample history.
INSERT INTO student_enrollment_history (student_id, academic_year_id, class_id, section_id, roll_no, status, recorded_at, branch_id)
SELECT s.id, old_year.id, s.class_id, s.section_id, s.roll_no, 'active', CONCAT(old_year.name, '-01-01 09:00:00'), s.branch_id
FROM students s
JOIN academic_years current_year ON current_year.id = s.academic_year_id AND current_year.branch_id = s.branch_id
JOIN academic_years old_year ON old_year.branch_id = s.branch_id AND old_year.name = CAST(CAST(current_year.name AS UNSIGNED) - 1 AS CHAR)
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND current_year.name REGEXP '^[0-9]{4}$'
  AND NOT EXISTS (SELECT 1 FROM student_enrollment_history h WHERE h.student_id = s.id AND h.academic_year_id = old_year.id);

-- Sample examination reports and a subject mark.
INSERT INTO exams (name, type, academic_year_id, start_date, end_date, description, created_at, branch_id)
SELECT DISTINCT 'Demo Half-Yearly Examination (sample)', 'half-yearly', ay.id,
       STR_TO_DATE(CONCAT(ay.name, '-08-01'), '%Y-%m-%d'),
       STR_TO_DATE(CONCAT(ay.name, '-08-05'), '%Y-%m-%d'), 'Sample examination data', NOW(), ay.branch_id
FROM students s
JOIN academic_years ay ON ay.id = s.academic_year_id AND ay.branch_id = s.branch_id
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND ay.name REGEXP '^[0-9]{4}$'
  AND NOT EXISTS (SELECT 1 FROM exams e WHERE e.branch_id = ay.branch_id AND e.academic_year_id = ay.id AND e.name = 'Demo Half-Yearly Examination (sample)');

INSERT INTO academic_reports (student_id, exam_id, class_position, total_marks, gpa, overall_grade, has_failed, working_days, present_days, absent_days, comments, created_at, branch_id)
SELECT s.id, e.id, s.roll_no, 85, 4.50, 'A', 0, 20, 19, 1, 'Sample academic report', NOW(), s.branch_id
FROM students s
JOIN exams e ON e.branch_id = s.branch_id AND e.academic_year_id = s.academic_year_id AND e.name = 'Demo Half-Yearly Examination (sample)'
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM academic_reports ar WHERE ar.student_id = s.id AND ar.exam_id = e.id);

INSERT INTO academic_report_marks (academic_report_id, subject_id, full_marks, highest_marks, written_marks, obtained, created_at, branch_id)
SELECT ar.id, sub.id, 100, 95, 85, 85, NOW(), ar.branch_id
FROM academic_reports ar
JOIN students s ON s.id = ar.student_id
JOIN exams e ON e.id = ar.exam_id AND e.name = 'Demo Half-Yearly Examination (sample)'
JOIN subjects sub ON sub.branch_id = ar.branch_id AND sub.name = 'Demo Mathematics (sample)'
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM academic_report_marks m WHERE m.academic_report_id = ar.id AND m.subject_id = sub.id);

-- The profile displays the file name, so no real upload is needed for sample rows.
INSERT INTO student_documents (student_id, title, file_path, created_at, branch_id)
SELECT s.id, 'Sample birth certificate', CONCAT('sample-data/student-', s.id, '-birth-certificate.pdf'), NOW(), s.branch_id
FROM students s
WHERE (s.student_id LIKE 'STU-%' OR s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM student_documents d WHERE d.student_id = s.id AND d.title = 'Sample birth certificate');

COMMIT;
