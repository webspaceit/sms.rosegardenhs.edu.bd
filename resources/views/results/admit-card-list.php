<?php
use App\Core\Url;
use App\Core\View;

require BASE_PATH . '/resources/views/results/partials/class-name-word.php';
$pageUrl = static fn (int $targetPage): string => Url::to('/admit-cards') . '?' . http_build_query([
    'year_id' => $yearId,
    'class_id' => $classId,
    'q' => $search,
    'format' => $format,
    'per_page' => $perPage,
    'page' => $targetPage,
]);
$first = $total === 0 ? 0 : (($page - 1) * $perPage + 1);
$last = min($page * $perPage, $total);
$schoolName = trim((string) ($schoolSettings['school_name'] ?? '')) ?: 'WSIT School';
?>
<section class="admit-list-shell">
    <header class="admit-list-heading">
        <div>
            <p class="admit-list-eyebrow">EXAMINATIONS</p>
            <h1>Admit Cards</h1>
            <p>Find students and print their examination admit cards.</p>
        </div>
        <div class="admit-list-total"><strong><?= number_format($total) ?></strong><span>eligible students</span></div>
    </header>

    <form method="get" action="<?= Url::to('/admit-cards') ?>" class="admit-list-filters">
        <label><span>Exam session</span>
            <select name="year_id">
                <option value="0">All sessions</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= (int) $year['id'] ?>" <?= $yearId === (int) $year['id'] ? 'selected' : '' ?>><?= View::escape((string) $year['name']) ?><?= (int) $year['is_current'] === 1 ? ' (current)' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Class</span>
            <select name="class_id">
                <option value="0">All classes</option>
                <?php foreach ($classes as $id => $name): ?>
                    <option value="<?= (int) $id ?>" <?= $classId === (int) $id ? 'selected' : '' ?>><?= View::escape((string) $name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="admit-list-search"><span>Search</span>
            <input type="search" name="q" value="<?= View::escape($search) ?>" placeholder="Name, registration no., or roll…">
        </label>
        <label><span>Card format</span>
            <select name="format">
                <option value="existing" <?= $format === 'existing' ? 'selected' : '' ?>>Existing format</option>
                <option value="new" <?= $format === 'new' ? 'selected' : '' ?>>New format (sample)</option>
            </select>
        </label>
        <label class="admit-list-per-page"><span>Per page</span>
            <select name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="admit-list-filter-button">Filter</button>
        <a class="admit-list-clear" href="<?= Url::to('/admit-cards') ?>">Clear</a>
    </form>

    <form id="admit-bulk-print" method="get" action="<?= Url::to('/admit-cards/bulk-print') ?>" data-endpoint="<?= View::escape(Url::to('/admit-cards/bulk-print')) ?>" class="admit-bulk-actions border-b border-green-900/40 bg-emerald-950/20 px-4 py-3">
        <input type="hidden" name="format" value="<?= View::escape($format) ?>">
        <p class="m-0 text-sm text-green-100/75"><span id="admit-selected-count">0</span> selected · up to 100 cards at a time</p>
        <button id="admit-bulk-print-button" type="submit" class="admit-list-filter-button">Print selected</button>

    <div class="admit-list-table-wrap">
        <table class="admit-list-table">
            <thead><tr>
                <th><input id="admit-select-all" type="checkbox" aria-label="Select all students on this page"></th><th>SL.</th><th>Registration</th><th>Roll</th><th>Name</th><th>Father</th><th>Mother</th><th>Class</th><th>School</th><th>Action</th>
            </tr></thead>
            <tbody>
            <?php if ($students === []): ?>
                <tr><td colspan="10" class="admit-list-empty">No active students match these filters.</td></tr>
            <?php else: foreach ($students as $index => $student): ?>
                <tr>
                    <td><input type="checkbox" name="student_ids[]" value="<?= (int) $student['id'] ?>" data-admit-select aria-label="Select <?= View::escape((string) $student['name']) ?>"></td>
                    <td><?= $first + $index ?></td>
                    <td class="admit-list-registration"><?= View::escape((string) $student['student_id']) ?></td>
                    <td><?= (int) $student['roll_no'] ?></td>
                    <td class="admit-list-name"><?= View::escape((string) $student['name']) ?></td>
                    <td><?= View::escape((string) ($student['father_name'] ?? '—') ?: '—') ?></td>
                    <td><?= View::escape((string) ($student['mother_name'] ?? '—') ?: '—') ?></td>
                    <td><?= View::escape($formatClassName($student['class_name'])) ?></td>
                    <td><?= View::escape($schoolName) ?></td>
                    <td><a class="admit-list-action" href="<?= Url::to('/admit-cards/print?id=' . (int) $student['id'] . '&format=' . rawurlencode($format)) ?>" target="_blank" rel="noopener">View / Print</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    </form>

    <footer class="admit-list-footer">
        <p>Showing <?= number_format($first) ?>–<?= number_format($last) ?> of <?= number_format($total) ?></p>
        <nav aria-label="Admit card pages">
            <?php if ($page > 1): ?><a href="<?= View::escape($pageUrl($page - 1)) ?>">‹ Previous</a><?php else: ?><span class="disabled">‹ Previous</span><?php endif; ?>
            <?php for ($number = max(1, $page - 2); $number <= min($totalPages, $page + 2); $number++): ?>
                <?php if ($number === $page): ?><span class="current" aria-current="page"><?= $number ?></span><?php else: ?><a href="<?= View::escape($pageUrl($number)) ?>"><?= $number ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?= View::escape($pageUrl($page + 1)) ?>">Next ›</a><?php else: ?><span class="disabled">Next ›</span><?php endif; ?>
        </nav>
    </footer>
</section>
<script>
(() => {
    const selectAll = document.getElementById('admit-select-all');
    const selected = [...document.querySelectorAll('[data-admit-select]')];
    const count = document.getElementById('admit-selected-count');
    const button = document.getElementById('admit-bulk-print-button');
    const form = document.getElementById('admit-bulk-print');
    const update = () => {
        const total = selected.filter((box) => box.checked).length;
        if (count) count.textContent = String(total);
        if (button) button.disabled = total === 0;
        if (selectAll) selectAll.checked = selected.length > 0 && total === selected.length;
    };
    selectAll?.addEventListener('change', () => {
        selected.forEach((box) => { box.checked = selectAll.checked; });
        update();
    });
    selected.forEach((box) => box.addEventListener('change', update));
    form?.addEventListener('submit', (event) => {
        const ids = selected.filter((box) => box.checked).map((box) => box.value);
        if (!ids.length) return;
        event.preventDefault();
        const target = new URL(form.dataset.endpoint || form.action, window.location.href);
        target.searchParams.set('format', form.querySelector('[name="format"]')?.value || 'existing');
        ids.forEach((id) => target.searchParams.append('student_ids[]', id));
        window.location.assign(target.toString());
    });
    update();
})();
</script>
