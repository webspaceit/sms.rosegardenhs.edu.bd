<?php
/**
 * Bulk student import.
 *
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array{imported: int, skipped: int, problems: array<int, string>}|null $report
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-3xl">
    <a href="<?= Url::to('/students') ?>" class="text-sm text-teal-300 hover:text-teal-200">← Student register</a>
    <h1 class="mt-2 text-3xl font-semibold">Import students</h1>
    <p class="mt-1 text-sm text-white">Upload a CSV file with one student per row.</p>

    <section class="mt-7 rounded-2xl border border-green-900/60 dashboard-gradient p-5">
        <h2 class="font-semibold">File format</h2>
        <p class="mt-1 text-sm text-white">The first row must hold the column names. These columns are read:</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach (['name', 'name_bn', 'student_id', 'class', 'section', 'roll_no', 'gender', 'status'] as $column): ?>
                <code class="rounded-lg border border-green-100/15 bg-emerald-950/35 px-2.5 py-1 font-mono text-xs text-green-100"><?= $column ?></code>
            <?php endforeach; ?>
        </div>
        <p class="mt-3 text-xs text-slate-500">The class and section must already exist. When student_id is left empty one is generated.</p>
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
