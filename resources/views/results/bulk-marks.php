<?php
/**
 * Mark entry for a whole class.
 *
 * @var array<int, string> $classes
 * @var array<int, array<string, mixed>> $students
 * @var array<int, array<string, mixed>> $subjects
 * @var array<int, array<string, array<string, mixed>>> $marks
 * @var array<int, int> $totals
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold">Mark entry</h1>
        <p class="mt-1 text-sm text-white">The marks of a whole class on one page.</p>
    </div>
    <a href="<?= Url::to('/marks/sheet?class_id=' . (int) $classId . '&exam=' . urlencode($examName) . '&year=' . urlencode($academicYear)) ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Printable mark sheet →</a>
</div>

<form method="get" action="<?= Url::to('/marks') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-5">
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
        <span class="mb-1.5 block text-slate-300">Exam</span>
        <input name="exam" value="<?= View::escape($examName) ?>" placeholder="Half Yearly Examination 2026" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
    </label>
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Academic year</span>
        <input name="year" value="<?= View::escape($academicYear) ?>" placeholder="2026" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
    </label>
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Entry mode</span>
        <select name="mode" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <option value="total"<?= $mode === 'total' ? ' selected' : '' ?>>One total per subject</option>
            <option value="breakdown"<?= $mode === 'breakdown' ? ' selected' : '' ?>>Written, MCQ, practical, CA</option>
        </select>
    </label>
    <div class="flex items-end">
        <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Load the class</button>
    </div>
</form>

<?php if ($classId <= 0 || $examName === '' || $academicYear === ''): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">Choose a class, an exam and an academic year.</div>
<?php elseif ($students === []): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">This class has no students.</div>
<?php elseif ($subjects === []): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">No subjects have been mapped to this class yet.</div>
<?php else: ?>
    <form method="post" action="<?= Url::to('/marks/save') ?>" class="mt-6">
        <?= Csrf::field() ?>
        <input type="hidden" name="class_id" value="<?= (int) $classId ?>">
        <input type="hidden" name="exam" value="<?= View::escape($examName) ?>">
        <input type="hidden" name="year" value="<?= View::escape($academicYear) ?>">
        <input type="hidden" name="mode" value="<?= View::escape($mode) ?>">

        <section class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-green-900/60 px-5 py-4">
                <div>
                    <h2 class="font-semibold"><?= count($students) ?> student(s) · <?= count($subjects) ?> subject(s)</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        <?= $mode === 'total' ? 'Type the total marks obtained in each subject.' : 'Type the written, MCQ, practical and continuous assessment marks separately.' ?>
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admit-list-table w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-10 bg-slate-950/95 px-5 py-3 font-medium">Roll &amp; student</th>
                            <?php foreach ($subjects as $subject): ?>
                                <?php if ($mode === 'total'): ?>
                                    <th class="px-2 py-3 text-center font-medium"><?= View::escape((string) $subject['name']) ?><span class="block text-[9px] font-normal text-white">/ <?= (int) $subject['full_marks'] ?></span></th>
                                <?php else: ?>
                                    <th class="px-2 py-3 text-center font-medium" colspan="4"><?= View::escape((string) $subject['name']) ?><span class="block text-[9px] font-normal text-white">/ <?= (int) $subject['full_marks'] ?></span></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                        </tr>
                        <?php if ($mode === 'breakdown'): ?>
                            <tr class="bg-slate-950/40 text-[9px] uppercase tracking-widest text-white">
                                <th class="sticky left-0 z-10 bg-slate-950/95"></th>
                                <?php foreach ($subjects as $subject): ?>
                                    <th class="px-1 py-1 text-center font-normal">W</th>
                                    <th class="px-1 py-1 text-center font-normal">M</th>
                                    <th class="px-1 py-1 text-center font-normal">P</th>
                                    <th class="px-1 py-1 text-center font-normal">CA</th>
                                <?php endforeach; ?>
                                <th></th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody class="divide-y divide-green-900/50">
                    <?php foreach ($students as $student): ?>
                        <?php $studentId = (int) $student['id']; ?>
                        <tr class="hover:bg-slate-800/30">
                            <td class="sticky left-0 z-10 bg-slate-900/95 px-5 py-2">
                                <span class="block font-medium"><?= (int) $student['roll_no'] ?>. <?= View::escape((string) $student['name']) ?></span>
                                <span class="block text-[10px] text-slate-500"><?= View::escape((string) $student['student_id']) ?></span>
                            </td>
                            <?php foreach ($subjects as $subject): ?>
                                <?php
                                $subjectName = (string) $subject['name'];
                                $mark = $marks[$studentId][$subjectName] ?? null;
                                ?>
                                <?php if ($mode === 'total'): ?>
                                    <td class="px-2 py-2">
                                        <input type="number" min="0" max="<?= (int) $subject['full_marks'] ?>" name="marks[<?= $studentId ?>][<?= View::escape($subjectName) ?>][obtained]" value="<?= $mark === null ? '' : (int) ($mark['obtained'] ?? 0) + (int) $mark['written_marks'] + (int) $mark['mcq_marks'] + (int) $mark['practical_marks'] + (int) $mark['ca_marks'] ?>" class="w-16 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-center text-sm outline-none focus:border-teal-400">
                                    </td>
                                <?php else: ?>
                                    <td class="px-1 py-2"><input type="number" min="0" name="marks[<?= $studentId ?>][<?= View::escape($subjectName) ?>][written_marks]" value="<?= (int) ($mark['written_marks'] ?? 0) ?>" class="w-12 rounded border border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs outline-none focus:border-teal-400"></td>
                                    <td class="px-1 py-2"><input type="number" min="0" name="marks[<?= $studentId ?>][<?= View::escape($subjectName) ?>][mcq_marks]" value="<?= (int) ($mark['mcq_marks'] ?? 0) ?>" class="w-12 rounded border border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs outline-none focus:border-teal-400"></td>
                                    <td class="px-1 py-2"><input type="number" min="0" name="marks[<?= $studentId ?>][<?= View::escape($subjectName) ?>][practical_marks]" value="<?= (int) ($mark['practical_marks'] ?? 0) ?>" class="w-12 rounded border border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs outline-none focus:border-teal-400"></td>
                                    <td class="px-1 py-2"><input type="number" min="0" name="marks[<?= $studentId ?>][<?= View::escape($subjectName) ?>][ca_marks]" value="<?= (int) ($mark['ca_marks'] ?? 0) ?>" class="w-12 rounded border border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs outline-none focus:border-teal-400"></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td class="px-4 py-2 text-right font-medium text-white"><?= (int) ($totals[$studentId] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-slate-800 px-5 py-4">
                <button class="rounded-lg bg-teal-400 px-6 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save the marks</button>
            </div>
        </section>
    </form>
<?php endif; ?>
