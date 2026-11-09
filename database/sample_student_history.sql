-- Demo history for clearly identified sample students only.
-- Select the school database in phpMyAdmin, then import/run this file.
-- Re-runnable: attendance, invoices, reports, documents, and behaviour rows
-- are only inserted when an equivalent sample row is not already present.

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

UPDATE students
SET student_behaviour = 'Sample note: demonstrates positive classroom participation. Replace with verified information.'
WHERE (student_id LIKE 'DEMO-%' OR student_id LIKE 'SAMPLE-%')
  AND (student_behaviour IS NULL OR student_behaviour = '');

-- Add prior-year records so the profile's enrollment history has an example.
INSERT INTO academic_years (name, start_date, end_date, is_current, branch_id)
SELECT CAST(YEAR(CURDATE()) - 1 AS CHAR), STR_TO_DATE(CONCAT(YEAR(CURDATE()) - 1, '-01-01'), '%Y-%m-%d'),
       STR_TO_DATE(CONCAT(YEAR(CURDATE()) - 1, '-12-31'), '%Y-%m-%d'), 0, b.id
FROM branches b
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM academic_years ay WHERE ay.branch_id = b.id AND ay.name = CAST(YEAR(CURDATE()) - 1 AS CHAR));

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
SELECT s.id, DATE_SUB(CURDATE(), INTERVAL d.day_offset DAY),
       CASE WHEN d.day_offset = 3 THEN 'late' ELSE 'present' END,
       'Sample attendance record', NOW(), s.branch_id
FROM students s
CROSS JOIN (
    SELECT 1 AS day_offset UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) d
WHERE (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM attendances a WHERE a.student_id = s.id AND a.date = DATE_SUB(CURDATE(), INTERVAL d.day_offset DAY));

-- Fee invoices, tied to the sample fee head and each student's academic year.
INSERT INTO fee_collections (student_id, fee_head_id, academic_year_id, invoice_no, month, amount, status, due_date, paid_on, payment_method, remarks, created_at, branch_id)
SELECT s.id, fh.id, s.academic_year_id, CONCAT('SAMPLE-', s.id, '-TUITION-01'), DATE_FORMAT(CURDATE(), '%Y-%m'),
       1500.00, 'paid', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Cash', 'Sample invoice', NOW(), s.branch_id
FROM students s
JOIN fee_heads fh ON fh.branch_id = s.branch_id AND fh.name = 'Demo tuition (sample)'
WHERE (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM fee_collections fc WHERE fc.student_id = s.id AND fc.invoice_no = CONCAT('SAMPLE-', s.id, '-TUITION-01'));

-- Previous enrollment example; class/section/roll are retained as sample history.
INSERT INTO student_enrollment_history (student_id, academic_year_id, class_id, section_id, roll_no, status, recorded_at, branch_id)
SELECT s.id, old_year.id, s.class_id, s.section_id, s.roll_no, 'active', CONCAT(YEAR(CURDATE()) - 1, '-01-01 09:00:00'), s.branch_id
FROM students s
JOIN academic_years old_year ON old_year.branch_id = s.branch_id AND old_year.name = CAST(YEAR(CURDATE()) - 1 AS CHAR)
WHERE (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM student_enrollment_history h WHERE h.student_id = s.id AND h.academic_year_id = old_year.id);

-- Sample examination reports and a subject mark.
INSERT INTO exams (name, type, academic_year_id, start_date, end_date, description, created_at, branch_id)
SELECT 'Demo Half-Yearly Examination (sample)', 'half-yearly', ay.id, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Sample examination data', NOW(), b.id
FROM branches b
JOIN academic_years ay ON ay.branch_id = b.id AND ay.is_current = 1
WHERE b.status = 'active'
  AND NOT EXISTS (SELECT 1 FROM exams e WHERE e.branch_id = b.id AND e.academic_year_id = ay.id AND e.name = 'Demo Half-Yearly Examination (sample)');

INSERT INTO academic_reports (student_id, exam_id, class_position, total_marks, gpa, overall_grade, has_failed, working_days, present_days, absent_days, comments, created_at, branch_id)
SELECT s.id, e.id, s.roll_no, 85, 4.50, 'A', 0, 20, 19, 1, 'Sample academic report', NOW(), s.branch_id
FROM students s
JOIN exams e ON e.branch_id = s.branch_id AND e.academic_year_id = s.academic_year_id AND e.name = 'Demo Half-Yearly Examination (sample)'
WHERE (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM academic_reports ar WHERE ar.student_id = s.id AND ar.exam_id = e.id);

INSERT INTO academic_report_marks (academic_report_id, subject_id, full_marks, highest_marks, written_marks, obtained, created_at, branch_id)
SELECT ar.id, sub.id, 100, 95, 85, 85, NOW(), ar.branch_id
FROM academic_reports ar
JOIN students s ON s.id = ar.student_id
JOIN exams e ON e.id = ar.exam_id AND e.name = 'Demo Half-Yearly Examination (sample)'
JOIN subjects sub ON sub.branch_id = ar.branch_id AND sub.name = 'Demo Mathematics (sample)'
WHERE (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM academic_report_marks m WHERE m.academic_report_id = ar.id AND m.subject_id = sub.id);

-- The profile displays the file name, so no real upload is needed for sample rows.
INSERT INTO student_documents (student_id, title, file_path, created_at, branch_id)
SELECT s.id, 'Sample birth certificate', CONCAT('sample-data/student-', s.id, '-birth-certificate.pdf'), NOW(), s.branch_id
FROM students s
WHERE (s.student_id LIKE 'DEMO-%' OR s.student_id LIKE 'SAMPLE-%')
  AND NOT EXISTS (SELECT 1 FROM student_documents d WHERE d.student_id = s.id AND d.title = 'Sample birth certificate');

COMMIT;
