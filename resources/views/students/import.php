<?php
/**
 * Bulk student import.
 *
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array{imported: int, skipped: int, roll_adjusted?: int, warnings?: array<int, string>, problems: array<int, string>}|null $report
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-3xl">
    <a href="<?= Url::to('/students') ?>" class="text-sm text-teal-300 hover:text-teal-200">← Student register</a>
    <h1 class="mt-2 text-3xl font-semibold">Import students</h1>
        <p class="mt-1 text-sm text-white">Download the blank template, add at least one student row, then upload the completed CSV.</p>
        <a href="<?= Url::to('/students/import/example') ?>" class="form-action-button mt-3 inline-flex">Download new-student CSV template</a>

    <section class="mt-7 rounded-2xl border border-green-900/60 dashboard-gradient p-5">
        <h2 class="font-semibold">File format</h2>
        <p class="mt-1 text-sm text-white">This CSV follows the Quick admit form. Enter dates as DD-MM-YYYY (for example, 05-03-2015):</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach (['Student name', 'Class', 'Academic group', 'Section', 'Shift', 'Roll number', 'Gender', 'Date of birth', 'Birth registration number', 'Admission date', 'Current Address', 'Guardian name', 'Relation', 'Guardian phone'] as $column): ?>
                <code class="rounded-lg border border-green-100/15 bg-emerald-950/35 px-2.5 py-1 font-mono text-xs text-green-100"><?= $column ?></code>
            <?php endforeach; ?>
        </div>
        <p class="mt-3 text-xs text-slate-500">This creates new Active student records for the current academic year; it does not update existing students. Student IDs are generated automatically. Use exact class and section names. Roll number is optional: blank rolls are assigned automatically, and duplicate rolls are changed to the next available unique roll with a notification. Shift may be Morning or Day and defaults to the class shift when blank. Guardian name and phone are required. Gender, dates, address, and academic group can be left blank; admission date defaults to today, and Class 9 or 10 requires an academic group.</p>
    </section>

    <form method="post" action="<?= Url::to('/students/import') ?>" enctype="multipart/form-data" class="mt-5 rounded-2xl border border-green-900/60 dashboard-gradient p-5">
        <?= Csrf::field() ?>
        <label class="block text-sm">
            <span class="mb-1.5 block text-slate-300">CSV file</span>
            <input type="file" name="csv" accept=".csv,text/csv" required class="w-full rounded-lg border border-green-100/15 bg-emerald-950/45 px-3 py-2.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-emerald-900 file:px-3 file:py-1.5 file:text-green-50">
        </label>
        <div class="mt-5 flex justify-end">
            <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Import students</button>
        </div>
    </form>

    <?php if ($report !== null): ?>
        <section class="mt-5 rounded-2xl border border-green-900/60 dashboard-gradient p-5">
            <h2 class="font-semibold">Last import</h2>
            <p class="mt-1 text-sm text-white">
                <?= (int) $report['imported'] ?> student(s) imported, <?= (int) $report['skipped'] ?> skipped.
            </p>
            <?php if (!empty($report['warnings'])): ?>
                <ul class="mt-3 space-y-1 text-xs text-amber-200">
                    <?php foreach ($report['warnings'] as $warning): ?>
                        <li><?= View::escape($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($report['problems'] !== []): ?>
                <ul class="mt-3 space-y-1 text-xs text-amber-200">
                    <?php foreach ($report['problems'] as $problem): ?>
                        <li><?= View::escape($problem) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
