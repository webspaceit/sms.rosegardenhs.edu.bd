<?php
/**
 * Generic module listing.
 *
 * @var array<string, mixed> $module
 * @var array<int, array<string, mixed>> $records
 */
use App\Core\Url;
use App\Core\View;

$columns = $module['columns'] ?? [];
$hasSerialColumn = count(array_filter($columns, static fn (array $column): bool => ($column['format'] ?? '') === 'serial' || in_array(strtolower(trim((string) ($column['label'] ?? ''))), ['sl', 'sl.', 's.l.', 'serial', 'serial no.', 'no.', '#'], true))) > 0;
if (!$hasSerialColumn) {
    array_unshift($columns, ['name' => '__generated_serial', 'label' => 'Sl.', 'format' => 'serial']);
}
$searchable = ($module['search'] ?? []) !== [];
$filters = $filters ?? [];
$selectedFilters = $selectedFilters ?? [];
$perPageOptions = $perPageOptions ?? [];
$canSearch = $searchable || $filters !== [] || $perPageOptions !== [];
$hasActiveFilters = $selectedFilters !== [];
$hasCustomPerPage = $perPageOptions !== [] && $perPage !== (int) ($module['perPage'] ?? 25);
$selectableRows = !empty($module['selectableRows']);
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold"><?= View::escape($module['title']) ?></h1>
        <p class="mt-1 text-sm text-white"><?= (int) $total ?> record<?= $total === 1 ? '' : 's' ?> in total</p>
    </div>
    <div class="flex flex-wrap gap-2"><?php if (!empty($module['secondaryHeaderAction'])): ?><a href="<?= Url::to($module['secondaryHeaderAction']['url']) ?>" class="form-action-button"><?= View::escape($module['secondaryHeaderAction']['label']) ?></a><?php endif; ?><?php if (!empty($module['headerAction'])): ?><a href="<?= Url::to($module['headerAction']['url']) ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm"><?= View::escape($module['headerAction']['label']) ?></a><?php endif; ?><?php if (empty($module['hideCreate'])): ?><a href="<?= Url::to($module['route'] . '/new') ?>" class="form-action-button"><?= View::escape($module['createLabel'] ?? ('New ' . $module['singular'])) ?></a><?php endif; ?></div>
</div>

<?php if ($canSearch): ?>
<form method="get" action="<?= Url::to($module['route']) ?>" class="mt-7 flex flex-wrap items-center gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4">
    <?php if ($searchable): ?>
        <label class="sr-only" for="module-search">Search <?= View::escape($module['singular']) ?> records</label>
        <input id="module-search" name="q" value="<?= View::escape($search) ?>" placeholder="Search <?= View::escape($module['singular']) ?> records" class="min-w-[220px] flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400">
    <?php endif; ?>
    <?php foreach ($filters as $filter): $filterName = (string) $filter['name']; ?>
        <label class="sr-only" for="filter-<?= View::escape($filterName) ?>"><?= View::escape($filter['label']) ?></label>
        <select id="filter-<?= View::escape($filterName) ?>" name="<?= View::escape($filterName) ?>"<?= !empty($filter['dependsOn']) ? ' data-filter-depends-on="' . View::escape((string) $filter['dependsOn']) . '"' : '' ?> class="min-w-[150px] rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400 disabled:cursor-not-allowed disabled:opacity-50">
            <option value=""><?= View::escape($filter['allLabel'] ?? ('All ' . $filter['label'] . 's')) ?></option>
            <?php foreach ($filter['options'] as $optionValue => $optionLabel): ?>
                <option value="<?= View::escape((string) $optionValue) ?>"<?= isset($filter['optionClassIds'][$optionValue]) ? ' data-class-id="' . (int) $filter['optionClassIds'][$optionValue] . '"' : '' ?><?= (string) ($selectedFilters[$filterName] ?? '') === (string) $optionValue ? ' selected' : '' ?>><?= View::escape((string) $optionLabel) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endforeach; ?>
    <?php if ($perPageOptions !== []): ?>
        <label class="sr-only" for="filter-per-page">Rows per page</label>
        <select id="filter-per-page" name="per_page" class="min-w-[140px] rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400">
            <?php foreach ($perPageOptions as $option): ?>
                <option value="<?= (int) $option ?>"<?= $perPage === (int) $option ? ' selected' : '' ?>><?= (int) $option ?> per page</option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Filter</button>
    <?php if ($search !== '' || $hasActiveFilters || $hasCustomPerPage): ?><a href="<?= Url::to($module['route']) ?>" class="rounded-lg px-4 py-2.5 text-center text-sm text-white hover:text-white">Clear</a><?php endif; ?>
</form>
<?php endif; ?>

<?php if (array_filter($filters, static fn (array $filter): bool => !empty($filter['dependsOn'])) !== []): ?>
<script>
document.querySelectorAll('select[data-filter-depends-on]').forEach(sectionFilter => {
    const classFilter = document.getElementById(`filter-${sectionFilter.dataset.filterDependsOn}`);
    if (!classFilter) return;
    const updateSections = reset => {
        const classId = classFilter.value;
        sectionFilter.disabled = classId === '';
        for (const option of sectionFilter.options) {
            if (!option.value) continue;
            option.hidden = classId === '' || option.dataset.classId !== classId;
        }
        if (reset || sectionFilter.selectedOptions[0]?.hidden) sectionFilter.value = '';
    };
    classFilter.addEventListener('change', () => updateSections(true));
    updateSections(false);
});
</script>
<?php endif; ?>

<?php if ($selectableRows): ?>
<form id="class-routine-bulk-delete" method="post" action="<?= Url::to($module['route'] . '/delete-selected') ?>" data-no-ajax data-confirm="Delete the selected class routines? This cannot be undone." class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-green-900/60 bg-[#0b3425]/80 px-4 py-3">
    <?= \App\Core\Csrf::field() ?>
    <?php foreach (['q', 'class_id', 'section_id', 'per_page', 'page'] as $queryKey): $queryValue = $queryKey === 'q' ? $search : ($queryKey === 'per_page' ? ($perPageOptions !== [] ? $perPage : null) : ($queryKey === 'page' ? $page : ($selectedFilters[$queryKey] ?? null))); ?>
        <?php if ($queryValue !== null && $queryValue !== ''): ?><input type="hidden" name="<?= View::escape($queryKey) ?>" value="<?= View::escape((string) $queryValue) ?>"><?php endif; ?>
    <?php endforeach; ?>
    <span data-selection-count class="text-sm text-slate-300" aria-live="polite">0 selected</span>
    <button type="submit" data-delete-selected disabled class="rounded-lg border border-red-300/30 px-4 py-2 text-sm font-semibold text-red-100 transition hover:border-red-300 hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-40">Delete selected</button>
</form>
<?php endif; ?>

<section class="mt-5 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
    <div class="overflow-x-auto">
        <table class="admit-list-table w-full min-w-[640px] text-left text-sm">
            <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-green-100/60">
                <tr>
                    <?php if ($selectableRows): ?><th class="w-px px-3 py-3 text-center font-medium"><input type="checkbox" data-select-all-rows aria-label="Select all rows on this page" class="h-4 w-4 accent-emerald-400"></th><?php endif; ?>
                    <?php foreach ($columns as $column): ?>
                        <th class="px-5 py-3 font-medium<?= !empty($column['nowrap']) ? ' w-px whitespace-nowrap' : '' ?>"><?= View::escape($column['label']) ?></th>
                    <?php endforeach; ?>
                    <th class="px-5 py-3 text-center font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-900/50">
            <?php if ($records === []): ?>
                <tr><td colspan="<?= count($columns) + 1 + ($selectableRows ? 1 : 0) ?>" class="px-5 py-12 text-center">
                    <p class="font-medium text-slate-300">No records found</p>
                    <p class="mt-1 text-sm text-slate-500"><?= ($search !== '' || $hasActiveFilters) ? 'Try changing the search or filters.' : 'Add the first ' . View::escape($module['singular']) . ' to get started.' ?></p>
                </td></tr>
            <?php else: foreach ($records as $recordIndex => $record): ?>
                <tr class="hover:bg-green-400/[0.06]">
                <?php if ($selectableRows): ?><td class="px-3 py-2 text-center"><input type="checkbox" form="class-routine-bulk-delete" name="routine_ids[]" value="<?= (int) ($record['id'] ?? 0) ?>" data-row-select class="h-4 w-4 accent-emerald-400" aria-label="Select routine <?= (int) ($record['id'] ?? 0) ?>"></td><?php endif; ?>
                <?php foreach ($columns as $column): ?>
                    <?php
                    $value = $record[$column['name']] ?? null;
                    $format = $column['format'] ?? 'text';
                    $ageText = null;
                    if ($format === 'date_age' && $value !== null && $value !== '') {
                        try {
                            $dateOfBirth = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
                            if ($dateOfBirth !== false) {
                                $today = new DateTimeImmutable('today');
                                $value = $dateOfBirth->format('d-F-Y');
                                if ($dateOfBirth <= $today) {
                                    $age = $dateOfBirth->diff($today);
                                    $parts = [];
                                    foreach ([[$age->y, 'year'], [$age->m, 'month'], [$age->d, 'day']] as [$count, $unit]) {
                                        if ($count > 0) $parts[] = $count . ' ' . $unit . ($count === 1 ? '' : 's');
                                    }
                                    $ageText = $parts !== [] ? 'Age: ' . implode(', ', $parts) : 'Age: 0 days';
                                }
                            }
                        } catch (Throwable) {
                            // Keep the stored date visible when it cannot be parsed.
                        }
                    } elseif ($value !== null && $value !== '' && isset($column['dateFormat'])) {
                        try {
                            $value = (new DateTimeImmutable((string) $value))->format((string) $column['dateFormat']);
                        } catch (Throwable) {
                            // Keep the stored value visible if it is not a valid date.
                        }
                    }
                    ?>
                    <td class="px-5 py-2<?= !empty($column['nowrap']) ? ' w-px whitespace-nowrap' : '' ?><?= !empty($column['wrap']) ? ' w-[100px] max-w-[100px] whitespace-normal' : '' ?>">
                    <?php if ($format === 'serial'): ?>
                        <span class="text-slate-300"><?= (int) (($page - 1) * $perPage + $recordIndex + 1) ?></span>
                    <?php elseif ($format === 'bool'): ?>
                        <span class="rounded-full px-2.5 py-1 text-xs <?= (int) $value === 1 ? 'bg-teal-400/10 text-teal-200' : 'bg-slate-700 text-slate-300' ?>"><?= (int) $value === 1 ? ($column['on'] ?? 'Yes') : ($column['off'] ?? 'No') ?></span>
                    <?php elseif ($format === 'badge'): ?>
                        <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-300"><?= View::escape((string) ($value ?? '—')) ?></span>
                    <?php elseif ($format === 'image'): ?>
                        <?php if ($value): ?><a href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $value)) ?>" target="_blank" title="View photo"><img src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $value)) ?>" alt="<?= View::escape($module['singular'] . ' photo') ?>" loading="lazy" class="h-10 w-10 rounded-lg border border-green-100/20 object-cover"></a>
                        <?php elseif (!empty($column['avatarFallback'])): $avatarGender = (string) ($record['gender'] ?? ''); $avatarName = (string) ($record['name'] ?? ucfirst($module['singular'])); $avatarClass = 'h-10 w-10 rounded-lg border border-green-100/20'; require BASE_PATH . '/resources/views/results/partials/student-avatar.php'; ?>
                        <?php else: ?><span class="text-slate-500">—</span><?php endif; ?>
                    <?php elseif ($format === 'date_age'): ?>
                        <span class="block text-slate-200"><?= View::escape((string) ($value ?? '—')) ?></span>
                        <?php if ($ageText !== null): ?><span class="mt-0.5 block text-xs text-slate-400"><?= View::escape($ageText) ?></span><?php endif; ?>
                    <?php else: ?>
                        <span<?= !empty($column['truncate']) ? ' class="block max-w-[220px] truncate ' . ($format === 'muted' ? 'text-white' : 'text-slate-200') . '"' : ' class="' . (!empty($column['wrap']) ? 'block whitespace-normal leading-snug ' : '') . ($format === 'muted' ? 'text-white' : 'text-slate-200') . '"' ?><?= !empty($column['truncate']) ? ' title="' . View::escape((string) ($value ?? '—')) . '"' : '' ?>><?= View::escape((string) ($value ?? '—')) ?></span>
                    <?php endif; ?>
                    </td>
                <?php endforeach; ?>
                    <td class="px-5 py-2 text-center">
                        <details class="student-actions group inline-flex items-center justify-center" aria-label="Actions">
                            <summary class="grid h-9 w-9 place-items-center rounded-lg border border-green-100/20 text-green-100/75 transition hover:bg-green-100/10 hover:text-white" aria-label="Show actions" title="Actions">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"></circle><circle cx="12" cy="12" r="1.7"></circle><circle cx="19" cy="12" r="1.7"></circle></svg>
                            </summary>
                            <div class="student-actions-controls hidden flex items-center gap-2">
                                <?php if (!empty($module['fileField']) && !empty($record[$module['fileField']])): ?><a target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $record[$module['fileField']])) ?>" class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs hover:border-green-300">Open file</a><?php endif; ?>
                                <a href="<?= Url::to($module['route'] . '/edit?id=' . (int) $record['id']) ?>" class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs hover:border-green-300">Edit</a>
                                <?php if (empty($module['hideDelete'])): ?>
                                    <form method="post" action="<?= Url::to($module['route'] . '/delete') ?>" data-confirm="Delete this <?= View::escape($module['singular']) ?>?" data-no-ajax>
                                        <?= \App\Core\Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                        <button class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs text-rose-200 hover:border-rose-400">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($selectableRows): ?>
<script>
(() => {
    const selectAll = document.querySelector('[data-select-all-rows]');
    const rowCheckboxes = Array.from(document.querySelectorAll('[data-row-select]'));
    const deleteButton = document.querySelector('[data-delete-selected]');
    const selectionCount = document.querySelector('[data-selection-count]');
    if (!selectAll || rowCheckboxes.length === 0) return;
    const syncSelection = () => {
        const selectedCount = rowCheckboxes.filter(checkbox => checkbox.checked).length;
        selectAll.checked = selectedCount === rowCheckboxes.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < rowCheckboxes.length;
        if (deleteButton) deleteButton.disabled = selectedCount === 0;
        if (selectionCount) selectionCount.textContent = `${selectedCount} selected`;
    };
    selectAll.addEventListener('change', () => {
        rowCheckboxes.forEach(checkbox => { checkbox.checked = selectAll.checked; });
        syncSelection();
    });
    rowCheckboxes.forEach(checkbox => checkbox.addEventListener('change', syncSelection));
    syncSelection();
})();
</script>
<?php endif; ?>

<?php if ($pages > 1): ?>
<?php $paginationQuery = http_build_query(['q' => $search] + $selectedFilters + ($perPageOptions !== [] ? ['per_page' => $perPage] : [])); ?>
<nav class="mt-5 flex items-center justify-between text-sm" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= Url::to($module['route'] . '?' . $paginationQuery . '&page=' . ($page - 1)) ?>" class="rounded-lg border border-slate-700 px-4 py-2 hover:border-teal-400">← Previous</a>
    <?php else: ?><span></span><?php endif; ?>
    <span class="text-slate-500">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
    <?php if ($page < $pages): ?>
        <a href="<?= Url::to($module['route'] . '?' . $paginationQuery . '&page=' . ($page + 1)) ?>" class="rounded-lg border border-slate-700 px-4 py-2 hover:border-teal-400">Next →</a>
    <?php else: ?><span></span><?php endif; ?>
</nav>
<?php endif; ?>
