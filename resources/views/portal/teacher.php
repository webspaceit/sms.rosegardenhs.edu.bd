<?php
/**
 * The teacher home.
 *
 * @var array<string, mixed> $teacher
 * @var array<int, array<string, mixed>> $classes
 * @var array<int, array<string, mixed>> $subjects
 * @var array<int, array<string, mixed>> $students
 * @var array<int, array<string, mixed>> $routine
 * @var array<int, array<string, mixed>> $leaves
 * @var array<int, array<string, mixed>> $notices
 */
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">Teacher portal</p>
        <h1 class="mt-2 text-3xl font-semibold">Welcome, <?= View::escape(explode(' ', (string) $teacher['name'])[0]) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60">
            <?= View::escape((string) ($teacher['designation'] ?? 'Teacher')) ?>
            <?php if (($teacher['department'] ?? '') !== ''): ?> · <?= View::escape((string) $teacher['department']) ?><?php endif; ?>
            · <?= View::escape((string) ($teacher['employee_id'] ?? '')) ?>
        </p>
    </div>
    <a href="<?= Url::to('/dashboard') ?>" class="rounded-lg border border-white/15 px-4 py-2.5 text-sm text-emerald-100/80 transition hover:border-green-300/40">Administration →</a>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-3">
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">My classes</p>
        <p class="mt-2 text-3xl font-semibold text-green-200"><?= count($classes) ?></p>
    </article>
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Students in class</p>
        <p class="mt-2 text-3xl font-semibold text-sky-200"><?= count($students) ?></p>
    </article>
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Leave requests waiting</p>
        <p class="mt-2 text-3xl font-semibold <?= count($leaves) > 0 ? 'text-amber-200' : 'text-emerald-200' ?>"><?= count($leaves) ?></p>
    </article>
</div>

<form method="get" action="<?= Url::to('/portal/teacher') ?>" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
    <label class="text-sm">
        <span class="mb-1.5 block text-emerald-100/60">Class</span>
        <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm">
            <?php foreach ($classes as $class): ?>
                <option value="<?= (int) $class['id'] ?>"<?= $classId === (int) $class['id'] ? ' selected' : '' ?>>
                    <?= View::escape((string) $class['name']) ?> (<?= (int) $class['student_count'] ?> students)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <h2 class="font-semibold">My subjects in this class</h2>
        <div class="mt-4 space-y-2">
            <?php if ($subjects === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No subject has been assigned to you in this class yet.</p>
            <?php else: foreach ($subjects as $subject): ?>
                <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                    <span class="min-w-0 flex-1 truncate text-sm font-medium"><?= View::escape((string) $subject['name']) ?></span>
                    <?php if ((int) $subject['is_class_teacher'] === 1): ?>
                        <span class="shrink-0 rounded-full bg-green-400/15 px-2.5 py-1 text-xs text-green-200">Class teacher</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <h2 class="font-semibold">Today's classes</h2>
        <div class="mt-4 space-y-2">
            <?php if ($routine === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No classes scheduled today.</p>
            <?php else: foreach ($routine as $slot): ?>
                <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                    <span class="w-24 shrink-0 font-mono text-xs text-emerald-100/60"><?= View::escape((string) $slot['start_time']) ?></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium"><?= View::escape((string) $slot['subject_name']) ?></span>
                        <span class="block text-xs text-emerald-100/40"><?= View::escape((string) $slot['class_name']) ?><?= $slot['room'] ? ' · ' . View::escape((string) $slot['room']) : '' ?></span>
                    </span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold">My students</h2>
            <a href="<?= Url::to('/batch-attendance') ?>" class="text-xs text-green-300 hover:text-green-200">Mark attendance →</a>
        </div>
        <div class="mt-4 max-h-80 overflow-y-auto">
            <table class="admit-list-table w-full text-left text-sm">
                <thead class="font-mono text-xs uppercase tracking-widest text-emerald-100/40">
                    <tr><th class="pb-2 font-normal">Roll</th><th class="pb-2 font-normal">Student</th><th class="pb-2 font-normal">Section</th></tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                <?php if ($students === []): ?>
                    <tr><td colspan="3" class="py-6 text-center text-emerald-100/40">No students in this class.</td></tr>
                <?php else: foreach ($students as $student): ?>
                    <tr>
                        <td class="py-2 text-emerald-100/50"><?= (int) $student['roll_no'] ?></td>
                        <td class="py-2">
                            <span class="block font-medium"><?= View::escape((string) $student['name']) ?></span>
                            <span class="block text-xs text-emerald-100/40"><?= View::escape((string) $student['student_id']) ?></span>
                        </td>
                        <td class="py-2 text-emerald-100/50"><?= View::escape((string) $student['section_name']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <h2 class="font-semibold">Notice board</h2>
        <div class="mt-4 space-y-2">
            <?php if ($notices === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">Nothing to read right now.</p>
            <?php else: foreach ($notices as $notice): ?>
                <article class="rounded-xl border border-white/5 bg-black/20 p-3">
                    <div class="flex items-center gap-2 text-xs text-emerald-100/40">
                        <span class="rounded-full border border-white/10 px-2 py-0.5"><?= View::escape((string) $notice['type']) ?></span>
                        <span><?= View::escape((string) $notice['published_date']) ?></span>
                    </div>
                    <h3 class="mt-2 text-sm font-semibold"><?= View::escape((string) $notice['title']) ?></h3>
                    <p class="mt-1 text-xs leading-5 text-emerald-100/60"><?= View::escape((string) $notice['content']) ?></p>
                </article>
            <?php endforeach; endif; ?>
        </div>
    </section>
</div>
