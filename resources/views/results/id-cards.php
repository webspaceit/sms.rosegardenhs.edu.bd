<?php
/**
 * Printable identity cards.
 *
 * @var array<int, array<string, mixed>> $students
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 */
use App\Core\Url;
use App\Core\View;
?>
<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">ID cards</h1>
            <p class="mt-1 text-sm text-white">Printable identity cards for the students of a class.</p>
        </div>
    </div>

    <form method="get" action="<?= Url::to('/id-cards') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Class</span>
            <select name="class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                <option value="0">Select a class</option>
                <?php foreach ($classes as $id => $name): ?>
                    <option value="<?= (int) $id ?>"<?= $classId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Section</span>
            <select name="section_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                <option value="0">All sections</option>
                <?php foreach ($sections as $id => $name): ?>
                    <option value="<?= (int) $id ?>"<?= $sectionId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="flex items-end sm:col-span-2">
            <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Show cards</button>
        </div>
    </form>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <?php if ($students === []): ?>
        <p class="rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Choose a class to print its identity cards.</p>
    <?php else: foreach ($students as $student): ?>
        <article class="overflow-hidden rounded-2xl border border-slate-800 bg-white text-slate-900 shadow-xl">
            <div class="h-20 bg-gradient-to-r from-teal-500 to-sky-500"></div>
            <div class="-mt-10 px-5 pb-5">
                <span class="grid h-20 w-20 place-items-center rounded-full border-4 border-white bg-slate-100 text-2xl font-bold text-slate-500">
                    <?= View::escape(strtoupper(substr((string) $student['name'], 0, 1))) ?>
                </span>
                <p class="mt-3 font-semibold"><?= View::escape((string) $student['name']) ?></p>
                <?php if ((string) $student['name_bn'] !== ''): ?>
                    <p class="text-sm text-slate-500"><?= View::escape((string) $student['name_bn']) ?></p>
                <?php endif; ?>
                <dl class="mt-3 space-y-1 text-xs text-slate-600">
                    <div class="flex justify-between"><dt>Student ID</dt><dd class="font-medium text-slate-900"><?= View::escape((string) $student['student_id']) ?></dd></div>
                    <div class="flex justify-between"><dt>Class</dt><dd class="font-medium text-slate-900"><?= View::escape((string) $student['class_name']) ?> · <?= View::escape((string) $student['section_name']) ?></dd></div>
                    <div class="flex justify-between"><dt>Roll</dt><dd class="font-medium text-slate-900"><?= (int) $student['roll_no'] ?></dd></div>
                    <?php if ((string) ($student['guardian_phone'] ?? '') !== ''): ?>
                        <div class="flex justify-between"><dt>Guardian</dt><dd class="font-medium text-slate-900"><?= View::escape((string) $student['guardian_phone']) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>
        </article>
    <?php endforeach; endif; ?>
    </div>
</div>
