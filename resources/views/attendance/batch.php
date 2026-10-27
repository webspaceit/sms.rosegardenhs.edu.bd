<?php
/**
 * Batch attendance marking.
 *
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array<int, array<string, mixed>> $students
 * @var array<int, array<string, mixed>> $existing
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$statuses = ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'half_day' => 'Half day', 'leave' => 'Leave'];
?>
<div class="mx-auto max-w-6xl">
    <h1 class="text-3xl font-semibold">Batch attendance</h1>
    <p class="mt-1 text-sm text-white">Pick a class and date, then mark every student in one go. Saving again for the same day updates the earlier marks.</p>

    <form method="get" action="<?= Url::to('/batch-attendance') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-4">
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
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Date</span>
            <input type="date" name="date" value="<?= View::escape($date) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
        </label>
        <div class="flex items-end">
            <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Load students</button>
        </div>
    </form>

    <?php if ($students === []): ?>
        <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">
            Choose a class to list its students.
        </div>
    <?php else: ?>
        <form method="post" action="<?= Url::to('/batch-attendance') ?>" class="mt-6">
            <?= Csrf::field() ?>
            <input type="hidden" name="class_id" value="<?= (int) $classId ?>">
            <input type="hidden" name="section_id" value="<?= (int) $sectionId ?>">
            <input type="hidden" name="date" value="<?= View::escape($date) ?>">

            <section class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
                <div class="flex items-center justify-between border-b border-green-900/60 px-5 py-4">
                    <div>
                        <h2 class="font-semibold"><?= count($students) ?> student(s)</h2>
                        <p class="mt-1 text-xs text-slate-500">Attendance for <?= View::escape($date) ?></p>
                    </div>
                    <button type="button" data-mark-all="present" class="rounded-lg border border-slate-700 px-3 py-2 text-xs hover:border-teal-400">Mark all present</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="admit-list-table w-full min-w-[720px] text-left text-sm">
                        <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Roll</th>
                                <th class="px-5 py-3 font-medium">Student</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-green-900/50">
                        <?php foreach ($students as $student): ?>
                            <?php
                            $studentId = (int) $student['id'];
                            $current = $existing[$studentId]['status'] ?? 'present';
                            ?>
                            <tr>
                                <td class="px-5 py-2 text-white"><?= (int) $student['roll_no'] ?></td>
                                <td class="px-5 py-2">
                                    <p class="font-medium"><?= View::escape($student['name']) ?></p>
                                    <p class="mt-0.5 text-xs text-slate-500"><?= View::escape($student['student_id']) ?></p>
                                </td>
                                <td class="px-5 py-2">
                                    <label class="sr-only" for="status-<?= $studentId ?>">Status for <?= View::escape($student['name']) ?></label>
                                    <select id="status-<?= $studentId ?>" name="status[<?= $studentId ?>]" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                                        <?php foreach ($statuses as $value => $label): ?>
                                            <option value="<?= $value ?>"<?= $current === $value ? ' selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-5 py-2">
                                    <label class="sr-only" for="remark-<?= $studentId ?>">Remarks</label>
                                    <input id="remark-<?= $studentId ?>" name="remarks[<?= $studentId ?>]" value="<?= View::escape((string) ($existing[$studentId]['remarks'] ?? '')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end border-t border-slate-800 px-5 py-4">
                    <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save attendance</button>
                </div>
            </section>
        </form>

        <script>
        (() => {
            const button = document.querySelector('[data-mark-all]');
            if (!button) return;
            button.addEventListener('click', () => {
                document.querySelectorAll('select[name^="status["]').forEach((select) => {
                    select.value = button.dataset.markAll;
                });
            });
        })();
        </script>
    <?php endif; ?>
</div>
