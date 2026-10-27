<?php
/**
 * Set up a result screen.
 *
 * @var array<string, mixed>|null $config
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array<int, string> $exams
 * @var array<int, string> $years
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-3xl">
    <a href="<?= Url::to('/reports') ?>" class="text-sm text-teal-300 hover:text-teal-200">← Result screens</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $config === null ? 'New' : 'Edit' ?> <?= View::escape($typeLabel) ?> setup</h1>
    <p class="mt-1 text-sm text-white">Choose the class, section, exam and academic year this screen covers.</p>

    <form method="post" action="<?= Url::to('/reports/config/save') ?>" class="mt-7 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-6">
        <?= Csrf::field() ?>
        <input type="hidden" name="type" value="<?= View::escape($type) ?>">
        <input type="hidden" name="id" value="<?= (int) ($config['id'] ?? 0) ?>">

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Class <span class="text-rose-300">*</span></span>
                <select name="class_name" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <option value="">Select a class</option>
                    <?php foreach ($classes as $name): ?>
                        <option value="<?= View::escape($name) ?>"<?= ($config['class_name'] ?? '') === $name ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Section</span>
                <select name="section" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <option value="">All sections</option>
                    <?php foreach ($sections as $name): ?>
                        <option value="<?= View::escape($name) ?>"<?= ($config['section'] ?? '') === $name ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Exam <span class="text-rose-300">*</span></span>
                <input name="exam_name" list="result-exams" value="<?= View::escape((string) ($config['exam_name'] ?? '')) ?>" required placeholder="Half Yearly Examination 2026" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                <datalist id="result-exams">
                    <?php foreach ($exams as $name): ?><option value="<?= View::escape($name) ?>"><?php endforeach; ?>
                </datalist>
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Academic year <span class="text-rose-300">*</span></span>
                <input name="academic_year" list="result-years" value="<?= View::escape((string) ($config['academic_year'] ?? '')) ?>" required placeholder="2026" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                <datalist id="result-years">
                    <?php foreach ($years as $name): ?><option value="<?= View::escape($name) ?>"><?php endforeach; ?>
                </datalist>
            </label>
        </div>

        <label class="mt-5 block text-sm">
            <span class="mb-1.5 block text-slate-300">Notes</span>
            <textarea name="config" rows="3" placeholder="Anything this screen should remember, for example the exam centre." class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?= View::escape((string) ($config['config'] ?? '')) ?></textarea>
        </label>

        <div class="mt-6 flex justify-end gap-3">
            <a href="<?= Url::to('/reports') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save setup</button>
        </div>
    </form>
</div>
