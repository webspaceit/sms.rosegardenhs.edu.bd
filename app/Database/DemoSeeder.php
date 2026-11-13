<?php
declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use App\Models\User;
use RuntimeException;

final class DemoSeeder
{
    private const DEMO_EMAIL = 'demo.admin@wsit-school.local';

    public function seed(): array
    {
        $database = Database::connection();
        $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Unable to read database/schema.sql.');
        }
        foreach (explode(';', $schema) as $statement) {
            if (trim($statement) !== '') {
                $database->exec($statement);
            }
        }

        if (User::findByEmail(self::DEMO_EMAIL) !== null) {
            throw new RuntimeException('The demo account already exists. The seeder will not change its password.');
        }

        $password = 'Wsit!' . rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');
        $yearName = date('Y');
        $database->beginTransaction();
        try {
            User::createAdmin('Demo Administrator', self::DEMO_EMAIL, $password);

            $yearStmt = $database->prepare('INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES (:name, :start_date, :end_date, 1)');
            $yearStmt->execute([
                'name' => $yearName,
                'start_date' => $yearName . '-01-01',
                'end_date' => $yearName . '-12-31',
            ]);
            $yearId = (int) $database->lastInsertId();

            $classStmt = $database->prepare('INSERT INTO classes (name, numeric_level, shift) VALUES (:name, :level, :shift)');
            $sectionStmt = $database->prepare('INSERT INTO sections (class_id, name, room_no) VALUES (:class_id, :name, :room)');
            $classIdByLevel = [];
            foreach (range(1, 9) as $level) {
                $classStmt->execute(['name' => 'Class ' . $level, 'level' => $level, 'shift' => 'Morning']);
                $classId = (int) $database->lastInsertId();
                $classIdByLevel[$level] = $classId;
                $sectionStmt->execute(['class_id' => $classId, 'name' => 'A', 'room' => 'Room ' . (100 + $level)]);
            }

            $studentStmt = $database->prepare('INSERT INTO students (student_id, name, class_id, section_id, academic_year_id, roll_no, gender, shift, date_of_birth, birth_registration_no, admission_date, religion, caste, mobile_number, email, blood_group, height, weight, measurement_date, medical_history, current_address, permanent_address, note, father_name, father_phone, father_occupation, mother_name, mother_phone, mother_occupation) VALUES (:student_id, :name, :class_id, :section_id, :year_id, :roll_no, :gender, :shift, :date_of_birth, :birth_registration_no, :admission_date, :religion, :caste, :mobile_number, :email, :blood_group, :height, :weight, :measurement_date, :medical_history, :current_address, :permanent_address, :note, :father_name, :father_phone, :father_occupation, :mother_name, :mother_phone, :mother_occupation)');
            $sectionLookup = $database->prepare('SELECT id FROM sections WHERE class_id = :class_id AND name = :name');
            $sectionIdByLevel = [];
            foreach ($classIdByLevel as $level => $classId) {
                $sectionLookup->execute(['class_id' => $classId, 'name' => 'A']);
                $sectionIdByLevel[$level] = (int) $sectionLookup->fetchColumn();
            }

            $students = [];
            foreach (range(1, 9) as $level) {
                $students[] = ['Demo Class ' . $level . ' Student 01', $level, 1, 'Female'];
                $students[] = ['Demo Class ' . $level . ' Student 02', $level, 2, 'Male'];
            }

            foreach ($students as $index => [$name, $level, $roll, $gender]) {
                $studentCode = sprintf('DEMO-%s-%03d', $yearName, $index + 1);
                $fakePhone = sprintf('010%08d', $index + 1);
                $studentStmt->execute([
                    'student_id' => $studentCode,
                    'name' => $name,
                    'class_id' => $classIdByLevel[$level],
                    'section_id' => $sectionIdByLevel[$level],
                    'year_id' => $yearId,
                    'roll_no' => $roll,
                    'gender' => $gender,
                    'shift' => 'Morning',
                    'date_of_birth' => date('Y-m-d', strtotime('-' . (6 + $level) . ' years -2 months')),
                    'birth_registration_no' => sprintf('DEMO-BR-%05d', $index + 1),
                    'admission_date' => $yearName . '-01-02',
                    'religion' => 'Demo value',
                    'caste' => 'Demo value',
                    'mobile_number' => $fakePhone,
                    'email' => 'student.' . strtolower($studentCode) . '@example.invalid',
                    'blood_group' => 'O+',
                    'height' => 120 + $level,
                    'weight' => 25 + $level,
                    'measurement_date' => $yearName . '-01-02',
                    'medical_history' => 'Demo: no known medical conditions',
                    'current_address' => 'Demo address, Dhaka',
                    'permanent_address' => 'Demo address, Dhaka',
                    'note' => 'Sample data for demonstration only',
                    'father_name' => 'Demo father ' . ($index + 1),
                    'father_phone' => $fakePhone,
                    'father_occupation' => 'Demo occupation',
                    'mother_name' => 'Demo mother ' . ($index + 1),
                    'mother_phone' => sprintf('010%08d', $index + 1001),
                    'mother_occupation' => 'Demo occupation',
                ]);
                $studentRecordId = (int) $database->lastInsertId();
                $guardianPhone = sprintf('011%08d', $index + 1);
                $guardianStmt = $database->prepare('INSERT INTO guardians (name, phone, email, occupation, relation, address, district, created_at) VALUES (:name, :phone, :email, :occupation, :relation, :address, :district, NOW())');
                $guardianStmt->execute([
                    'name' => 'Demo guardian ' . ($index + 1),
                    'phone' => $guardianPhone,
                    'email' => 'guardian.' . strtolower($studentCode) . '@example.invalid',
                    'occupation' => 'Demo occupation',
                    'relation' => 'Guardian',
                    'address' => 'Demo address, Dhaka',
                    'district' => 'Dhaka',
                ]);
                $guardianId = (int) $database->lastInsertId();
                $linkStmt = $database->prepare('INSERT INTO student_guardian (student_id, guardian_id, is_primary, created_at) VALUES (:student_id, :guardian_id, 1, NOW())');
                $linkStmt->execute([
                    'student_id' => $studentRecordId,
                    'guardian_id' => $guardianId,
                ]);
            }

            $database->commit();
        } catch (\Throwable $error) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $error;
        }

        return [
            'email' => self::DEMO_EMAIL,
            'password' => $password,
            'year' => $yearName,
            'students' => count($students),
            'classes' => count($classIdByLevel),
        ];
    }
}
