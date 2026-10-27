<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use PDOException;

/**
 * A reusable list/create/edit/delete controller.
 *
 * Most of the modules in this system are flat records: a table, a handful of
 * fields, and a list to browse. Rather than repeating the same controller
 * for each one, a module declares its table and fields once and this class
 * handles listing with search, the add and edit form, validation and
 * persistence.
 *
 * A module definition looks like this:
 *
 *     [
 *         'table'    => 'notices',
 *         'route'    => '/notices',
 *         'title'    => 'Notice Board',
 *         'singular' => 'notice',
 *         'columns'  => [['name' => 'title', 'label' => 'Title']],
 *         'fields'   => [['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true]],
 *         'search'   => ['title', 'content'],
 *         'order'    => 'published_date DESC, id DESC',
 *     ]
 */
final class Crud
{
    public function __construct(private array $module) {}

    public function index(Request $request): void
    {
        $this->guardView();

        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) ($this->module['perPage'] ?? 25);

        $from = (string) ($this->module['from'] ?? $this->table());
        $select = (string) ($this->module['select'] ?? '*');
        [$where, $bindings] = $this->searchClause($search);
        $branch = $this->branchScope($from);
        if ($branch !== null) {
            $branchClause = $branch['alias'] . '.branch_id = :__branch_id';
            $where = $where === '' ? ' WHERE ' . $branchClause : $where . ' AND ' . $branchClause;
            $bindings['__branch_id'] = $branch['id'];
        }

        $countStatement = Database::read()->prepare('SELECT COUNT(*) FROM ' . $from . $where);
        $countStatement->execute($bindings);
        $total = (int) $countStatement->fetchColumn();

        $stmt = Database::read()->prepare(
            'SELECT ' . $select . ' FROM ' . $from . $where . ' ORDER BY ' . ($this->module['order'] ?? 'id DESC') . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
        );
        $stmt->execute($bindings);
        $records = $stmt->fetchAll();

        View::render('crud/index', [
            'title' => $this->module['title'],
            'user' => Auth::user(),
            'module' => $this->module,
            'records' => $records,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function create(Request $request): void
    {
        $this->guardManage();
        View::render('crud/form', [
            'title' => 'New ' . $this->module['singular'],
            'user' => Auth::user(),
            'module' => $this->module,
            'record' => null,
        ]);
    }

    public function store(Request $request): void
    {
        $this->guardManage();
        $this->verifyCsrf($request);

        $data = $this->validated($request, null);
        if ($data === null) {
            header('Location: ' . Url::to($this->route() . '/new'));
            exit;
        }

        try {
            $branch = $this->branchScope((string) ($this->module['from'] ?? $this->table()));
            $this->insert($data + $this->extraDefaults('insertDefaults') + ($branch === null ? [] : ['branch_id' => $branch['id']]));
        } catch (PDOException $error) {
            error_log((string) $error);
            ErrorLog::write($error);
            Toast::flash(self::failureMessage($error), 'error');
            header('Location: ' . Url::to($this->route() . '/new'));
            exit;
        }

        $id = (int) Database::connection()->lastInsertId();
        AuditLog::record('record.created', $this->table(), $id, ['route' => $this->route()]);
        $this->afterSave($id, $data, $request);

        Toast::flash(ucfirst($this->module['singular']) . ' saved successfully.');
        header('Location: ' . Url::to($this->route()));
        exit;
    }

    public function edit(Request $request): void
    {
        $this->guardManage();
        $record = $this->find((int) $request->input('id', 0));
        if ($record === null) {
            Toast::flash(ucfirst($this->module['singular']) . ' not found.', 'error');
            header('Location: ' . Url::to($this->route()));
            exit;
        }
        $recordForEdit = $this->module['recordForEdit'] ?? null;
        if (is_callable($recordForEdit)) {
            $record = $recordForEdit($record) ?? $record;
        }
        View::render('crud/form', [
            'title' => 'Edit ' . $this->module['singular'],
            'user' => Auth::user(),
            'module' => $this->module,
            'record' => $record,
        ]);
    }

    public function update(Request $request): void
    {
        $this->guardManage();
        $this->verifyCsrf($request);

        $id = (int) $request->input('id', 0);
        if ($this->find($id) === null) {
            Toast::flash(ucfirst($this->module['singular']) . ' not found.', 'error');
            header('Location: ' . Url::to($this->route()));
            exit;
        }

        $data = $this->validated($request, $this->find($id));
        if ($data === null) {
            header('Location: ' . Url::to($this->route() . '/edit?id=' . $id));
            exit;
        }

        try {
            $this->updateRow($id, $data + $this->extraDefaults('updateDefaults'));
        } catch (PDOException $error) {
            error_log((string) $error);
            ErrorLog::write($error);
            Toast::flash(self::failureMessage($error), 'error');
            header('Location: ' . Url::to($this->route() . '/edit?id=' . $id));
            exit;
        }

        AuditLog::record('record.updated', $this->table(), $id, ['route' => $this->route()]);
        $this->afterSave($id, $data, $request);

        Toast::flash(ucfirst($this->module['singular']) . ' updated.');
        header('Location: ' . Url::to($this->route()));
        exit;
    }

    public function destroy(Request $request): void
    {
        $this->guardManage();
        $this->verifyCsrf($request);

        $id = (int) $request->input('id', 0);
        if ($this->find($id) !== null) {
            $branch = $this->branchScope($this->table());
            $sql = 'DELETE FROM ' . $this->table() . ' WHERE id = :id' . ($branch === null ? '' : ' AND branch_id = :__branch_id');
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute(['id' => $id] + ($branch === null ? [] : ['__branch_id' => $branch['id']]));
            AuditLog::record('record.deleted', $this->table(), $id, ['route' => $this->route()]);
            Toast::flash(ucfirst($this->module['singular']) . ' deleted.');
        }

        header('Location: ' . Url::to($this->route()));
        exit;
    }

    /* ---------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------ */

    private function table(): string
    {
        return (string) $this->module['table'];
    }

    /**
     * Check the permission needed to see the module listing.
     */
    private function guardView(): void
    {
        $slug = $this->module['permission'] ?? null;
        if (is_string($slug) && $slug !== '') {
            Guard::requirePermission($slug);
            return;
        }
        Guard::requireAdmin();
    }

    /**
     * Check the permission needed to add, change or remove a record.
     */
    private function guardManage(): void
    {
        $slug = $this->module['managePermission'] ?? null;
        if (is_string($slug) && $slug !== '') {
            Guard::requirePermission($slug);
            return;
        }
        Guard::requireAdmin();
    }

    private function route(): string
    {
        return (string) $this->module['route'];
    }

    /** The whitelisted, always safe to interpolate table and column names. */
    private function find(int $id): ?array
    {
        $branch = $this->branchScope($this->table());
        $sql = 'SELECT * FROM ' . $this->table() . ' WHERE id = :id' . ($branch === null ? '' : ' AND branch_id = :__branch_id') . ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id] + ($branch === null ? [] : ['__branch_id' => $branch['id']]));
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function searchClause(string $search): array
    {
        $columns = $this->module['search'] ?? [];
        if ($search === '' || $columns === []) {
            return ['', []];
        }

        $parts = [];
        $bindings = [];
        foreach ($columns as $index => $column) {
            $key = 's' . $index;
            $parts[] = $column . ' LIKE :' . $key;
            $bindings[$key] = '%' . $search . '%';
        }

        return [' WHERE (' . implode(' OR ', $parts) . ')', $bindings];
    }

    /**
     * The search terms a module allows, for use in the search placeholder.
     *
     * @return array<int, string>
     */
    public function searchLabels(): array
    {
        return $this->module['search'] ?? [];
    }

    /**
     * Validate the request against the module field definitions.
     *
     * @return array<string, mixed>|null Null when validation failed.
     */
    private function validated(Request $request, ?array $existing): ?array
    {
        $data = [];
        $errors = [];

        foreach ($this->module['fields'] as $field) {
            if (($field['virtual'] ?? false) === true) {
                continue;
            }

            $name = (string) $field['name'];
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);
            $raw = $request->input($name, null);

            if ($type === 'file') {
                try {
                    $uploaded = Upload::store($name, (string) ($field['directory'] ?? 'documents'), $field['extensions'] ?? ['pdf'], (int) ($field['maxBytes'] ?? 10485760));
                } catch (\RuntimeException $error) {
                    Toast::flash($error->getMessage(), 'error');
                    return null;
                }
                $data[$name] = $uploaded ?? ($existing[$name] ?? null);
                if ($required && !$data[$name]) {
                    Toast::flash($field['label'] . ' is required.', 'error');
                    return null;
                }
                continue;
            }

            if ($type === 'checkbox') {
                $data[$name] = $this->checked($raw) ? 1 : 0;
                continue;
            }

            if ($type === 'multiselect') {
                $values = is_array($raw) ? array_values($raw) : [];
                $options = $this->optionsFor($field);
                foreach ($values as $value) {
                    if (!array_key_exists((string) $value, $options)) {
                        $errors[] = 'One of the selected ' . $field['label'] . ' values is not valid.';
                    }
                }
                $data[$name] = implode(',', array_map('strval', $values));
                continue;
            }

            $value = is_string($raw) ? trim($raw) : $raw;

            if ($value === null || $value === '') {
                if ($required) {
                    $errors[] = $field['label'] . ' is required.';
                }
                $data[$name] = $field['default'] ?? null;
                continue;
            }

            switch ($type) {
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[] = $field['label'] . ' must be a number.';
                    }
                    $data[$name] = $field['integer'] ?? true ? (int) $value : (float) $value;
                    break;

                case 'date':
                    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        $errors[] = $field['label'] . ' must be a valid date.';
                    }
                    $data[$name] = (string) $value;
                    break;

                case 'select':
                    $options = $this->optionsFor($field);
                    if (!array_key_exists((string) $value, $options)) {
                        $errors[] = $field['label'] . ' is not a valid choice.';
                    }
                    $data[$name] = (string) $value;
                    break;

                default:
                    $string = (string) $value;
                    $max = (int) ($field['max'] ?? 0);
                    preg_match_all('/./us', $string, $characters);
                    $length = isset($characters[0]) ? count($characters[0]) : strlen($string);
                    if ($max > 0 && $length > $max) {
                        $errors[] = $field['label'] . ' must be ' . $max . ' characters or fewer.';
                    }
                    $data[$name] = $string;
                    break;
            }
        }

        if ($errors !== []) {
            Toast::flash(implode(' ', $errors), 'error');
            return null;
        }

        $beforeSave = $this->module['beforeSave'] ?? null;
        if (is_callable($beforeSave)) {
            try {
                $data = $beforeSave($data, $existing) ?? $data;
            } catch (\RuntimeException $error) {
                Toast::flash($error->getMessage(), 'error');
                return null;
            }
        }
        return $data;
    }

    /** @return array<string, string> */
    private function optionsFor(array $field): array
    {
        return self::fieldOptions($field);
    }

    /**
     * Resolve the choices for a select field.
     *
     * A field may declare its options inline or as a callable, which lets a
     * module pull live values such as the current class list.
     *
     * @return array<string, string>
     */
    public static function fieldOptions(array $field): array
    {
        $options = $field['options'] ?? [];
        if (is_callable($options)) {
            $options = $options();
        }
        return is_array($options) ? $options : [];
    }

    private function checked(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array((string) $value, ['1', 'on', 'true', 'yes'], true);
    }

    /**
     * A message that tells the administrator what actually went wrong.
     */
    private static function failureMessage(PDOException $error): string
    {
        $state = (string) ($error->errorInfo[0] ?? '');
        $driverCode = (int) ($error->errorInfo[1] ?? 0);

        if ($state === '23000' && $driverCode === 1452) {
            return 'That record refers to something that no longer exists. Pick the linked record again.';
        }
        if ($state === '23000' && $driverCode === 1451) {
            return 'This record is still being used somewhere else and cannot be removed.';
        }
        if ($state === '23000') {
            return 'That value is already used by another record.';
        }
        if ($state === '22001') {
            return 'That value is too long for the field.';
        }

        return 'That record could not be saved. Check the values and try again.';
    }

    /** @return array<string, mixed> */
    private function extraDefaults(string $key): array
    {
        $defaults = $this->module[$key] ?? null;
        if (!is_callable($defaults)) {
            return [];
        }
        $values = $defaults();
        return is_array($values) ? $values : [];
    }

    /**
     * Run a module hook after a record is created or updated, so modules can
     * write related rows such as junction tables.
     *
     * @param array<string, mixed> $data
     */
    private function afterSave(int $id, array $data, Request $request): void
    {
        $hook = $this->module['afterSave'] ?? null;
        if (is_callable($hook)) {
            $hook($id, $data, $request);
        }
    }

    /** @param array<string, mixed> $data */
    private function insert(array $data): void
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));

        $stmt = Database::connection()->prepare(
            'INSERT INTO ' . $this->table() . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
        );
        $stmt->execute($data);
    }

    /** @param array<string, mixed> $data */
    private function updateRow(int $id, array $data): void
    {
        $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = :' . $column, array_keys($data)));

        $stmt = Database::connection()->prepare(
            'UPDATE ' . $this->table() . ' SET ' . $assignments . ' WHERE id = :id'
                . (($branch = $this->branchScope($this->table())) === null ? '' : ' AND branch_id = :__branch_id')
        );
        $stmt->execute($data + ['id' => $id] + ($branch === null ? [] : ['__branch_id' => $branch['id']]));
    }

    /** @return array{alias:string,id:int}|null */
    private function branchScope(string $from): ?array
    {
        if (($this->module['branchScoped'] ?? true) === false || $this->table() === 'branches') return null;
        preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*(?:AS\s+)?([A-Za-z_][A-Za-z0-9_]*)?/i', $from, $matches);
        $table = (string) ($matches[1] ?? $this->table());
        $alias = (string) ($matches[2] ?? $table);
        if (in_array(strtoupper($alias), ['JOIN','LEFT','RIGHT','INNER','OUTER','CROSS','WHERE'], true)) $alias = $table;
        return ['alias' => $alias, 'id' => BranchContext::id()];
    }

    private function verifyCsrf(Request $request): void
    {
        if (Csrf::verify($request)) {
            return;
        }
        http_response_code(419);
        Toast::flash('Your session expired. Please try again.', 'error');
        header('Location: ' . Url::to($this->route()), true, 419);
        exit;
    }
}
