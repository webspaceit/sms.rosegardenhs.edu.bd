<?php
/**
 * The teacher allocation matrix.
 *
 * @var array<int, array<string, mixed>> $rows
 * @var array<int, string> $classes
 * @var array<int, string> $teachers
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-4xl">
    <h1 class="text-3xl font-semibold">Allocation matrix</h1>
    <p class="mt-1 text-sm text-white">Which teacher takes which subject in this class.</p>

    <form method="get" action="<?= Url::to('/allocation-matrix') ?>" class="mt-7 flex flex-wrap items-end gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4">
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Class</span>
            <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                <?php foreach ($classes as $id => $name): ?>
                    <option value="<?= (int) $id ?>"<?= $classId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <form method="post" action="<?= Url::to('/allocation-matrix/update') ?>" class="mt-5">
        <?= Csrf::field() ?>
        <input type="hidden" name="class_id" value="<?= (int) $classId ?>">

        <section class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
            <div class="border-b border-green-900/60 px-5 py-4">
                <h2 class="font-semibold"><?= count($rows) ?> subject(s)</h2>
                <p class="mt-1 text-xs text-slate-500">Leave a subject unassigned to remove it from the class.</p>
            </div>
            <div class="divide-y divide-green-900/50">
            <?php if ($rows === []): ?>
                <p class="px-5 py-10 text-center text-sm text-slate-500">No subjects have been mapped to this class yet.</p>
            <?php else: foreach ($rows as $row): ?>
                <div class="flex flex-wrap items-center gap-3 px-5 py-3">
                    <span class="w-40 shrink-0">
                        <span class="block font-medium"><?= View::escape((string) $row['subject_name']) ?></span>
                        <span class="font-mono text-[10px] text-slate-500"><?= View::escape((string) ($row['code'] ?? '')) ?></span>
                    </span>
                    <label class="sr-only" for="teacher-<?= (int) $row['subject_id'] ?>">Teacher for <?= View::escape((string) $row['subject_name']) ?></label>
                    <select id="teacher-<?= (int) $row['subject_id'] ?>" name="subjects[<?= (int) $row['subject_id'] ?>]" class="flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                        <option value="0">Unassigned</option>
                        <?php foreach ($teachers as $id => $name): ?>
                            <option value="<?= (int) $id ?>"<?= (int) ($row['teacher_id'] ?? 0) === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ((int) ($row['is_class_teacher'] ?? 0) === 1): ?>
                        <span class="rounded-full bg-teal-400/10 px-2.5 py-1 text-xs text-teal-200">Class teacher</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
            </div>
            <div class="flex justify-end border-t border-slate-800 px-5 py-4">
                <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save allocation</button>
            </div>
        </section>
    </form>
</div>
