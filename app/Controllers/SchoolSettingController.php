<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\Upload;
use App\Core\View;
use App\Core\PhoneNumber;

/**
 * School settings.
 *
 * The settings live in a simple key/value table. This controller renders one
 * form per group of related keys and writes whatever was submitted back,
 * creating the row when a key is seen for the first time.
 */
final class SchoolSettingController
{
    /**
     * The editable settings, grouped for the form.
     *
     * @return array<string, array{title: string, hint: string, fields: array<string, array{label: string, type: string, max: int}>}>
     */
    public static function groups(): array
    {
        return [
            'identity' => [
                'title' => 'School identity',
                'hint' => 'Shown on the notice board, printed documents and the public header.',
                'fields' => [
                    'school_name' => ['label' => 'School name', 'type' => 'text', 'max' => 150],
                    'school_name_bn' => ['label' => 'School name in Bangla', 'type' => 'text', 'max' => 150],
                    'school_eiin' => ['label' => 'EIIN code', 'type' => 'text', 'max' => 50],
                    'school_code' => ['label' => 'School Code', 'type' => 'text', 'max' => 50],
                    'school_primary_code' => ['label' => 'Primary Code', 'type' => 'text', 'max' => 50],
                    'school_vocational_code' => ['label' => 'Vocational Code', 'type' => 'text', 'max' => 50],
                    'school_established' => ['label' => 'Established', 'type' => 'text', 'max' => 20],
                ],
            ],
            'contact' => [
                'title' => 'Contact details',
                'hint' => 'Where parents and visitors can reach the school.',
                'fields' => [
                    'school_address' => ['label' => 'Address', 'type' => 'text', 'max' => 255],
                    'school_address_bn' => ['label' => 'Address in Bangla', 'type' => 'text', 'max' => 255],
                    'school_phone' => ['label' => 'Phone', 'type' => 'tel', 'max' => 14],
                    'school_email' => ['label' => 'Email', 'type' => 'text', 'max' => 190],
                ],
            ],
            'academic' => [
                'title' => 'Academic and finance',
                'hint' => 'Defaults used when admitting students and recording fees.',
                'fields' => [
                    'admission_prefix' => ['label' => 'Student ID prefix', 'type' => 'text', 'max' => 20],
                    'currency' => ['label' => 'Currency code', 'type' => 'text', 'max' => 10],
                    'school_footer' => ['label' => 'Footer text', 'type' => 'text', 'max' => 255],
                    'school_footer_url' => ['label' => 'Footer link', 'type' => 'text', 'max' => 255],
                ],
            ],
            'display' => [
                'title' => 'Display preferences',
                'hint' => 'Choose how calendar dates appear across the school system.',
                'fields' => [
                    'date_format' => [
                        'label' => 'Date format',
                        'type' => 'select',
                        'options' => [
                            'd-m-Y' => 'DD-MM-YYYY (13-11-2026)',
                            'd/m/Y' => 'DD/MM/YYYY (13/11/2026)',
                            'Y-m-d' => 'YYYY-MM-DD (2026-11-13)',
                            'M j, Y' => 'Month day, year (Nov 13, 2026)',
                        ],
                    ],
                ],
            ],
            'print_signatures' => [
                'title' => 'Print signatures',
                'hint' => 'Upload JPG, PNG, or WebP signature images. They will appear on admit cards and printable result sheets.',
                'fields' => [
                    'class_teacher_signature' => ['label' => 'Class teacher signature', 'type' => 'file', 'max' => 2097152],
                    'invigilator_signature' => ['label' => 'Invigilator signature', 'type' => 'file', 'max' => 2097152],
                    'exam_controller_signature' => ['label' => 'Exam controller signature', 'type' => 'file', 'max' => 2097152],
                    'head_teacher_signature' => ['label' => 'Head teacher signature', 'type' => 'file', 'max' => 2097152],
                    'principal_signature' => ['label' => 'Principal signature', 'type' => 'file', 'max' => 2097152],
                ],
            ],
            'modules' => [
                'title' => 'Admission form modules',
                'hint' => 'Toggle the optional sections shown on the student admission form.',
                'fields' => [
                    'enable_transport_details' => ['label' => 'Transport Details', 'type' => 'checkbox'],
                    'enable_hostel_details' => ['label' => 'Hostel Details', 'type' => 'checkbox'],
                ],
            ],
        ];
    }

    public function index(Request $request): void
    {
        Guard::requireAdmin();

        View::render('settings/school', [
            'title' => 'School settings',
            'user' => Auth::user(),
            'groups' => self::groups(),
            'values' => $this->values(),
        ]);
    }

    public function update(Request $request): void
    {
        Guard::requireAdmin();

        if (!Csrf::verify($request)) {
            http_response_code(419);
            Toast::flash('Your session expired. Please try again.', 'error');
            header('Location: ' . Url::to('/settings/school'), true, 419);
            exit;
        }

        $schoolPhone = trim((string) $request->input('school_phone', ''));
        if ($schoolPhone !== '' && !PhoneNumber::isBangladeshiMobile($schoolPhone)) {
            Toast::flash('School phone must be a valid Bangladeshi mobile number (01XXXXXXXXX or +8801XXXXXXXXX).', 'error');
            header('Location: ' . Url::to('/settings/school'));
            exit;
        }

        $identityCodes = [
            'school_code' => 'School Code',
            'school_primary_code' => 'Primary Code',
            'school_vocational_code' => 'Vocational Code',
        ];
        $findDuplicateCode = Database::read()->prepare(
            'SELECT 1 FROM school_settings WHERE `key` = :key AND value = :value AND branch_id <> :branch LIMIT 1'
        );
        foreach ($identityCodes as $key => $label) {
            $code = trim((string) $request->input($key, ''));
            if ($code === '') continue;
            $findDuplicateCode->execute(['key' => $key, 'value' => $code, 'branch' => BranchContext::id()]);
            if ($findDuplicateCode->fetchColumn() !== false) {
                Toast::flash($label . ' is already assigned to another campus. Enter a unique code.', 'error');
                header('Location: ' . Url::to('/settings/school'));
                exit;
            }
        }

        $database = Database::connection();
        $findSetting = $database->prepare(
            'SELECT id, value FROM school_settings WHERE `key` = :key AND branch_id = :branch ORDER BY id DESC LIMIT 1'
        );
        $updateSetting = $database->prepare(
            'UPDATE school_settings SET value = :value, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $insertSetting = $database->prepare(
            'INSERT INTO school_settings (`key`, value, branch_id) VALUES (:key, :value, :branch)'
        );

        $saved = 0;
        foreach (self::groups() as $group) {
            foreach ($group['fields'] as $key => $field) {
                $fieldType = (string) ($field['type'] ?? 'text');
                if ($fieldType === 'file') {
                    $file = $_FILES[$key] ?? [];
                    $hasUpload = is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    $removeUpload = $request->input('remove_' . $key) === '1';
                    if (!$hasUpload && !$removeUpload) continue;
                    $findSetting->execute(['key' => $key, 'branch' => BranchContext::id()]);
                    $existingSetting = $findSetting->fetch();
                    $settingId = $existingSetting ? (int) $existingSetting['id'] : false;
                    $oldPath = (string) ($existingSetting['value'] ?? '');
                    $value = '';
                    if ($hasUpload) {
                        try {
                            $value = Upload::store($key, 'school-signatures', ['jpg', 'jpeg', 'png', 'webp'], (int) $field['max']) ?? '';
                        } catch (\RuntimeException $exception) {
                            Toast::flash($exception->getMessage(), 'error');
                            header('Location: ' . Url::to('/settings/school'));
                            exit;
                        }
                        if ($value === '') continue;
                    }
                    if ($settingId !== false) {
                        $updateSetting->execute(['id' => $settingId, 'value' => $value]);
                    } else {
                        $insertSetting->execute(['key' => $key, 'value' => $value, 'branch' => BranchContext::id()]);
                    }
                    if ($oldPath !== '' && $oldPath !== $value) Upload::delete($oldPath);
                    $saved++;
                    continue;
                }
                if ($fieldType === 'checkbox') {
                    $value = $request->input($key) === '1' ? '1' : '0';
                } elseif ($fieldType === 'select') {
                    $value = (string) $request->input($key, '');
                    if (!array_key_exists($value, $field['options'] ?? [])) {
                        continue;
                    }
                } else {
                    $value = trim((string) $request->input($key, ''));
                    if ($value === '') {
                        continue;
                    }
                }
                $branchId = BranchContext::id();
                $findSetting->execute(['key' => $key, 'branch' => BranchContext::id()]);
                $existingSetting = $findSetting->fetch();
                if ($existingSetting !== false) {
                    $updateSetting->execute(['id' => (int) $existingSetting['id'], 'value' => $value]);
                } else {
                    $insertSetting->execute(['key' => $key, 'value' => $value, 'branch' => $branchId]);
                }
                $saved++;
            }
        }

        Toast::flash($saved > 0 ? 'School settings saved.' : 'No settings were changed.');
        header('Location: ' . Url::to('/settings/school'));
        exit;
    }

    /** @return array<string, string> */
    private function values(): array
    {
        $rows = Database::connection()->query('SELECT `key`, value FROM school_settings WHERE branch_id=' . BranchContext::id() . ' ORDER BY id ASC')->fetchAll();
        $values = [];
        foreach ($rows as $row) {
            $values[(string) $row['key']] = (string) $row['value'];
        }
        return $values;
    }

    /**
     * Whether a boolean setting is enabled for the current branch. Defaults to
     * enabled when the setting has never been saved.
     */
    public static function flag(string $key, bool $default = true): bool
    {
        $statement = Database::connection()->prepare('SELECT value FROM school_settings WHERE `key` = :key AND branch_id = :branch ORDER BY id DESC LIMIT 1');
        $statement->execute(['key' => $key, 'branch' => BranchContext::id()]);
        $value = $statement->fetchColumn();
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value === '1' || $value === 'on' || $value === 'true';
    }
}
