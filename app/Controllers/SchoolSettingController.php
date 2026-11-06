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
use App\Core\View;

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
                    'school_established' => ['label' => 'Established', 'type' => 'text', 'max' => 20],
                ],
            ],
            'contact' => [
                'title' => 'Contact details',
                'hint' => 'Where parents and visitors can reach the school.',
                'fields' => [
                    'school_address' => ['label' => 'Address', 'type' => 'text', 'max' => 255],
                    'school_address_bn' => ['label' => 'Address in Bangla', 'type' => 'text', 'max' => 255],
                    'school_phone' => ['label' => 'Phone', 'type' => 'text', 'max' => 30],
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

        $database = Database::connection();
        $findSetting = $database->prepare(
            'SELECT id FROM school_settings WHERE `key` = :key AND branch_id = :branch ORDER BY id DESC LIMIT 1'
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
                if (($field['type'] ?? 'text') === 'checkbox') {
                    $value = $request->input($key) === '1' ? '1' : '0';
                } else {
                    $value = trim((string) $request->input($key, ''));
                    if ($value === '') {
                        continue;
                    }
                }
                $branchId = BranchContext::id();
                $findSetting->execute(['key' => $key, 'branch' => $branchId]);
                $settingId = $findSetting->fetchColumn();
                if ($settingId !== false) {
                    $updateSetting->execute(['id' => (int) $settingId, 'value' => $value]);
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
