<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BranchContext;
use App\Core\Database;

/** The configurable types available to the subject register. */
final class SubjectType
{
    public static function module(): array
    {
        return [
            'table' => 'subject_types',
            'route' => '/subject-types',
            'title' => 'Subject Types',
            'singular' => 'subject type',
            'permission' => 'subjects.manage',
            'managePermission' => 'subjects.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Type'],
                ['name' => 'usage_count', 'label' => 'Subjects using this type', 'format' => 'muted'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Type name', 'type' => 'text', 'required' => true, 'max' => 100],
            ],
            'from' => 'subject_types st',
            'select' => 'st.*, (SELECT COUNT(*) FROM subjects s WHERE s.type_id=st.id AND s.branch_id=st.branch_id) AS usage_count',
            'search' => ['st.name'],
            'order' => 'st.name ASC',
            'deleteGuard' => static function (int $id): ?string {
                $statement = Database::connection()->prepare('SELECT COUNT(*) FROM subjects WHERE type_id=:id AND branch_id=:branch');
                $statement->execute(['id' => $id, 'branch' => BranchContext::id()]);
                return (int) $statement->fetchColumn() > 0 ? 'This type is in use by subjects and cannot be deleted.' : null;
            },
        ];
    }
}
