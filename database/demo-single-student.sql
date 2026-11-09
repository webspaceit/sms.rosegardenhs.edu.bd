-- Add one complete, clearly labeled demo student to the active campus.
-- Run this in phpMyAdmin after selecting the school database.
-- Safe to repeat: the SAMPLE-...-FULL-001 student and guardian are not duplicated.
-- All person details below are fictional. Photo uploads are intentionally omitted.

SET @sample_branch_id = (
    SELECT id FROM branches
    WHERE status = 'active'
    ORDER BY is_main DESC, id ASC
    LIMIT 1
);
SET @sample_year_id = (
    SELECT id FROM academic_years
    ORDER BY is_current DESC, start_date DESC, id DESC
    LIMIT 1
);
SET @sample_year_name = (
    SELECT name FROM academic_years WHERE id = @sample_year_id LIMIT 1
);
SET @sample_class_id = (
    SELECT id FROM classes
    WHERE numeric_level = 1 AND branch_id = @sample_branch_id
    ORDER BY id LIMIT 1
);
SET @sample_section_id = (
    SELECT id FROM sections
    WHERE class_id = @sample_class_id AND name = 'A' AND branch_id = @sample_branch_id
    ORDER BY id LIMIT 1
);
SET @sample_student_code = CONCAT('SAMPLE-', @sample_year_name, '-FULL-001');
SET @sample_guardian_email = CONCAT('guardian.', @sample_year_name, '.full001@example.invalid');

-- Add Class 1 / Section A only if the selected campus has those placements.
INSERT INTO students (
    student_id, name, class_id, section_id, shift, academic_year_id, roll_no,
    gender, date_of_birth, birth_registration_no, admission_date, previous_school,
    address, religion, caste, mobile_number, email, blood_group, height, weight,
    measurement_date, medical_history, current_address, permanent_address,
    national_id, local_id, note, father_name, father_phone, father_occupation,
    mother_name, mother_phone, mother_occupation, status, branch_id
)
SELECT
    @sample_student_code, 'Demo Student Full Profile', @sample_class_id, @sample_section_id,
    'Day', @sample_year_id,
    COALESCE((
        SELECT MAX(s.roll_no) + 1 FROM students s
        WHERE s.class_id = @sample_class_id AND s.section_id = @sample_section_id
          AND s.academic_year_id = @sample_year_id AND s.branch_id = @sample_branch_id
    ), 1),
    'Female', DATE_SUB(CURDATE(), INTERVAL 7 YEAR), 'DEMO-BR-FULL-001',
    COALESCE((SELECT start_date FROM academic_years WHERE id = @sample_year_id), CURDATE()),
    'Demo Primary School (sample)',
    'House 12, Demo Road, Dhaka (sample only)', 'Demo value', 'Demo value',
    '01000000001', CONCAT('student.', @sample_year_name, '.full001@example.invalid'),
    'O+', 125.00, 27.00, CURDATE(), 'Demo: no known medical conditions',
    'House 12, Demo Road, Dhaka (sample only)', 'Village Demo, Dhaka (sample only)',
    'DEMO-NID-FULL-001', 'DEMO-LOCAL-FULL-001',
    'Fictional sample record. Replace with verified information before real use.',
    'Demo Father Full Profile', '01000000002', 'Demo occupation',
    'Demo Mother Full Profile', '01000000003', 'Demo occupation',
    'active', @sample_branch_id
WHERE @sample_branch_id IS NOT NULL
  AND @sample_year_id IS NOT NULL
  AND @sample_class_id IS NOT NULL
  AND @sample_section_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM students s
      WHERE s.student_id = @sample_student_code AND s.branch_id = @sample_branch_id
  );

-- Add a linked primary father/guardian so guardian profile fields are populated too.
INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at, branch_id)
SELECT 'Demo Father Full Profile', '01000000002', @sample_guardian_email,
       'Demo occupation', 'Father', 'House 12, Demo Road, Dhaka (sample only)',
       'Dhaka', NOW(), @sample_branch_id
WHERE @sample_branch_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM guardians g
      WHERE g.email = @sample_guardian_email AND g.branch_id = @sample_branch_id
  );

INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at, branch_id)
SELECT s.id, g.id, 1, NOW(), @sample_branch_id
FROM students s
JOIN guardians g ON g.email = @sample_guardian_email AND g.branch_id = @sample_branch_id
WHERE s.student_id = @sample_student_code
  AND s.branch_id = @sample_branch_id
  AND NOT EXISTS (
      SELECT 1 FROM student_guardian sg
      WHERE sg.student_id = s.id AND sg.guardian_id = g.id AND sg.branch_id = @sample_branch_id
  );

SELECT id, student_id, name, class_id, section_id, academic_year_id, roll_no
FROM students
WHERE student_id = @sample_student_code AND branch_id = @sample_branch_id;
