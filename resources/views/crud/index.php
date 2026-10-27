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
$searchable = ($module['search'] ?? []) !== [];
$canSearch = $searchable;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold"><?= View::escape($module['title']) ?></h1>
        <p class="mt-1 text-sm text-white"><?= (int) $total ?> record<?= $total === 1 ? '' : 's' ?> in total</p>
    </div>
    <div class="flex flex-wrap gap-2"><?php if (!empty($module['headerAction'])): ?><a href="<?= Url::to($module['headerAction']['url']) ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm"><?= View::escape($module['headerAction']['label']) ?></a><?php endif; ?><a href="<?= Url::to($module['route'] . '/new') ?>" class="form-action-button">New <?= View::escape($module['singular']) ?></a></div>
</div>

<?php if ($canSearch): ?>
<form method="get" action="<?= Url::to($module['route']) ?>" class="mt-7 flex flex-wrap items-center gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4">
    <label class="sr-only" for="module-search">Search <?= View::escape($module['singular']) ?> records</label>
    <input id="module-search" name="q" value="<?= View::escape($search) ?>" placeholder="Search <?= View::escape($module['singular']) ?> records" class="min-w-[220px] flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400">
    <button class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Search</button>
    <?php if ($search !== ''): ?><a href="<?= Url::to($module['route']) ?>" class="rounded-lg px-4 py-2.5 text-center text-sm text-white hover:text-white">Clear</a><?php endif; ?>
</form>
<?php endif; ?>

<section class="mt-5 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
    <div class="overflow-x-auto">
        <table class="admit-list-table w-full min-w-[640px] text-left text-sm">
            <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-green-100/60">
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <th class="px-5 py-3 font-medium"><?= View::escape($column['label']) ?></th>
                    <?php endforeach; ?>
                    <th class="px-5 py-3 text-center font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-900/50">
            <?php if ($records === []): ?>
                <tr><td colspan="<?= count($columns) + 1 ?>" class="px-5 py-12 text-center">
                    <p class="font-medium text-slate-300">No records found</p>
                    <p class="mt-1 text-sm text-slate-500"><?= $search !== '' ? 'Try a different search term.' : 'Add the first ' . View::escape($module['singular']) . ' to get started.' ?></p>
                </td></tr>
            <?php else: foreach ($records as $record): ?>
                <tr class="hover:bg-green-400/[0.06]">
                <?php foreach ($columns as $column): ?>
                    <?php
                    $value = $record[$column['name']] ?? null;
                    $format = $column['format'] ?? 'text';
                    ?>
                    <td class="px-5 py-2">
                    <?php if ($format === 'bool'): ?>
                        <span class="rounded-full px-2.5 py-1 text-xs <?= (int) $value === 1 ? 'bg-teal-400/10 text-teal-200' : 'bg-slate-700 text-slate-300' ?>"><?= (int) $value === 1 ? ($column['on'] ?? 'Yes') : ($column['off'] ?? 'No') ?></span>
                    <?php elseif ($format === 'badge'): ?>
                        <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-300"><?= View::escape((string) ($value ?? '—')) ?></span>
                    <?php else: ?>
                        <span class="<?= $format === 'muted' ? 'text-white' : 'text-slate-200' ?>"><?= View::escape((string) ($value ?? '—')) ?></span>
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
                                <form method="post" action="<?= Url::to($module['route'] . '/delete') ?>" onsubmit="return confirm('Delete this <?= View::escape($module['singular']) ?>?')">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                    <button class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs text-rose-200 hover:border-rose-400">Delete</button>
                                </form>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($pages > 1): ?>
<nav class="mt-5 flex items-center justify-between text-sm" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a href="<?= Url::to($module['route'] . '?q=' . urlencode($search) . '&page=' . ($page - 1)) ?>" class="rounded-lg border border-slate-700 px-4 py-2 hover:border-teal-400">← Previous</a>
    <?php else: ?><span></span><?php endif; ?>
    <span class="text-slate-500">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
    <?php if ($page < $pages): ?>
        <a href="<?= Url::to($module['route'] . '?q=' . urlencode($search) . '&page=' . ($page + 1)) ?>" class="rounded-lg border border-slate-700 px-4 py-2 hover:border-teal-400">Next →</a>
    <?php else: ?><span></span><?php endif; ?>
</nav>
<?php endif; ?>
