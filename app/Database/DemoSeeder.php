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
            foreach ([1, 2, 3, 4, 5] as $level) {
                $classStmt->execute(['name' => 'Class ' . $level, 'level' => $level, 'shift' => 'Morning']);
                $classId = (int) $database->lastInsertId();
                $classIdByLevel[$level] = $classId;
                $sectionStmt->execute(['class_id' => $classId, 'name' => 'A', 'room' => 'Room ' . (100 + $level)]);
            }

            $studentStmt = $database->prepare('INSERT INTO students (student_id, name, name_bn, class_id, section_id, academic_year_id, roll_no, gender) VALUES (:student_id, :name, :name_bn, :class_id, :section_id, :year_id, :roll_no, :gender)');
            $sectionLookup = $database->prepare('SELECT id FROM sections WHERE class_id = :class_id AND name = :name');
            $sectionIdByLevel = [];
            foreach ($classIdByLevel as $level => $classId) {
                $sectionLookup->execute(['class_id' => $classId, 'name' => 'A']);
                $sectionIdByLevel[$level] = (int) $sectionLookup->fetchColumn();
            }

            $students = [
                ['Ayesha Rahman', 'আয়েশা রহমান', 1, 1, 'Female'],
                ['Arif Hasan', 'আরিফ হাসান', 1, 2, 'Male'],
                ['Nusrat Jahan', 'নুসরাত জাহান', 2, 1, 'Female'],
                ['Fahim Ahmed', 'ফাহিম আহমেদ', 2, 2, 'Male'],
                ['Sumaiya Akter', 'সুমাইয়া আক্তার', 3, 1, 'Female'],
                ['Rakib Hossain', 'রাকিব হোসেন', 3, 2, 'Male'],
                ['Maliha Chowdhury', 'মালিহা চৌধুরী', 4, 1, 'Female'],
                ['Tanvir Islam', 'তানভীর ইসলাম', 4, 2, 'Male'],
                ['Jannatul Ferdous', 'জান্নাতুল ফেরদৌস', 5, 1, 'Female'],
                ['Sabbir Khan', 'সাব্বির খান', 5, 2, 'Male'],
            ];

            foreach ($students as $index => [$name, $nameBn, $level, $roll, $gender]) {
                $studentStmt->execute([
                    'student_id' => sprintf('DEMO-%s-%03d', $yearName, $index + 1),
                    'name' => $name,
                    'name_bn' => $nameBn,
                    'class_id' => $classIdByLevel[$level],
                    'section_id' => $sectionIdByLevel[$level],
                    'year_id' => $yearId,
                    'roll_no' => $roll,
                    'gender' => $gender,
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
