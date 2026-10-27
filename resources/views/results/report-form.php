<?php
/**
 * Edit one student's report card and its subject marks.
 *
 * @var array<string, mixed> $student
 * @var array<string, mixed>|null $report
 * @var array<int, array<string, mixed>> $marks
 * @var array<int, array<string, mixed>> $subjects
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$markBySubject = [];
foreach ($marks as $mark) {
    $markBySubject[(string) $mark['subject_name']] = $mark;
}
?>
<div class="mx-auto max-w-5xl">
    <a href="<?= Url::to('/reports?exam=' . urlencode($examName) . '&year=' . urlencode($academicYear)) ?>" class="text-sm text-teal-300 hover:text-teal-200">← Report cards</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $student['name']) ?></h1>
    <p class="mt-1 text-sm text-white">
        <?= View::escape((string) $student['student_id']) ?> ·
        <?= View::escape((string) $student['class_name']) ?> · <?= View::escape((string) $student['section_name']) ?> ·
        <?= View::escape($examName) ?> (<?= View::escape($academicYear) ?>)
    </p>

    <form method="post" action="<?= Url::to('/reports/save') ?>" class="mt-7 space-y-5">
        <?= Csrf::field() ?>
        <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
        <input type="hidden" name="exam" value="<?= View::escape($examName) ?>">
        <input type="hidden" name="year" value="<?= View::escape($academicYear) ?>">

        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
            <h2 class="font-semibold">Subject marks</h2>
            <p class="mt-1 text-xs text-slate-500">A subject below 33 is a fail. The grade point and the overall grade are worked out for you.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="admit-list-table w-full min-w-[720px] text-left text-sm">
                    <thead class="text-sm uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-medium">Subject</th>
                            <th class="px-3 py-2 font-medium">Full</th>
                            <th class="px-3 py-2 font-medium">Written</th>
                            <th class="px-3 py-2 font-medium">MCQ</th>
                            <th class="px-3 py-2 font-medium">Practical</th>
                            <th class="px-3 py-2 font-medium">CA</th>
                            <th class="px-3 py-2 font-medium">Highest</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-900/50">
                    <?php if ($subjects === []): ?>
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No subjects have been mapped to this class yet.</td></tr>
                    <?php else: foreach ($subjects as $subject): ?>
                        <?php $existing = $markBySubject[(string) $subject['name']] ?? null; ?>
                        <tr>
                            <td class="px-3 py-2 font-medium"><?= View::escape((string) $subject['name']) ?></td>
                            <td class="px-3 py-2">
                                <input type="number" name="marks[<?= View::escape((string) $subject['name']) ?>][full_marks]" value="<?= (int) ($existing['full_marks'] ?? $subject['full_marks'] ?? 100) ?>" class="w-20 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="marks[<?= View::escape((string) $subject['name']) ?>][written_marks]" value="<?= (int) ($existing['written_marks'] ?? 0) ?>" class="w-20 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="marks[<?= View::escape((string) $subject['name']) ?>][mcq_marks]" value="<?= (int) ($existing['mcq_marks'] ?? 0) ?>" class="w-20 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="marks[<?= View::escape((string) $subject['name']) ?>][practical_marks]" value="<?= (int) ($existing['practical_marks'] ?? 0) ?>" class="w-20 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="marks[<?= View::escape((string) $subject['name']) ?>][ca_marks]" value="<?= (int) ($existing['ca_marks'] ?? 0) ?>" class="w-20 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" name="marks[<?= View::escape((string) $subject['name']) ?>][highest_marks]" value="<?= (int) ($existing['highest_marks'] ?? 0) ?>" class="w-20 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm">
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
            <h2 class="font-semibold">Attendance and remarks</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Working days</span>
                    <input type="number" name="working_days" value="<?= (int) ($report['working_days'] ?? 0) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Present days</span>
                    <input type="number" name="present_days" value="<?= (int) ($report['present_days'] ?? 0) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Absent days</span>
                    <input type="number" name="absent_days" value="<?= (int) ($report['absent_days'] ?? 0) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Moral evaluation</span>
                    <input name="moral_evaluation" value="<?= View::escape((string) ($report['moral_evaluation'] ?? '')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Co-curricular activities</span>
                    <input name="co_curricular" value="<?= View::escape((string) ($report['co_curricular'] ?? '')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
            </div>
            <label class="mt-4 block text-sm">
                <span class="mb-1.5 block text-slate-300">Comments</span>
                <textarea name="comments" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?= View::escape((string) ($report['comments'] ?? '')) ?></textarea>
            </label>
        </section>

        <div class="flex justify-end gap-3">
            <a href="<?= Url::to('/reports?exam=' . urlencode($examName) . '&year=' . urlencode($academicYear)) ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save report card</button>
        </div>
    </form>
</div>
