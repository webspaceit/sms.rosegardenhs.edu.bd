<?php
/**
 * Report cards for a class.
 *
 * @var array<int, array<string, mixed>> $results
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array<int, string> $exams
 * @var array<int, string> $years
 */
use App\Core\Url;
use App\Core\View;
?>
<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">Report cards</h1>
            <p class="mt-1 text-sm text-white">Every student's result for one exam, with the grade points worked out.</p>
        </div>
        <a href="<?= Url::to('/reports/config?type=report') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Set up a saved view</a>
    </div>

    <form method="get" action="<?= Url::to('/reports') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-5">
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
            <select name="section" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                <option value="">All sections</option>
                <?php foreach ($sections as $id => $name): ?>
                    <option value="<?= View::escape($name) ?>"<?= $section === $name ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Exam</span>
            <input name="exam" list="exam-names" value="<?= View::escape($examName) ?>" placeholder="Half Yearly Examination 2026" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <datalist id="exam-names">
                <?php foreach ($exams as $name): ?><option value="<?= View::escape($name) ?>"><?php endforeach; ?>
            </datalist>
        </label>
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Academic year</span>
            <input name="year" list="year-names" value="<?= View::escape($academicYear) ?>" placeholder="2026" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <datalist id="year-names">
                <?php foreach ($years as $name): ?><option value="<?= View::escape($name) ?>"><?php endforeach; ?>
            </datalist>
        </label>
        <div class="flex items-end">
            <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Show results</button>
        </div>
    </form>

    <?php if ($classId > 0 && $examName !== '' && $academicYear !== ''): ?>
        <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
            <div class="border-b border-green-900/60 px-5 py-4">
                <h2 class="font-semibold"><?= count($results) ?> result(s)</h2>
                <p class="mt-1 text-xs text-slate-500">A student with any subject below 33 has failed and their grade point is zero.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="admit-list-table w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Position</th>
                            <th class="px-5 py-3 font-medium">Roll</th>
                            <th class="px-5 py-3 font-medium">Student</th>
                            <th class="px-5 py-3 text-right font-medium">Total</th>
                            <th class="px-5 py-3 text-right font-medium">GPA</th>
                            <th class="px-5 py-3 font-medium">Grade</th>
                            <th class="px-5 py-3 text-center font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-900/50">
                    <?php if ($results === []): ?>
                        <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No report cards have been entered for this exam yet.</td></tr>
                    <?php else: foreach ($results as $row): ?>
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-5 py-2 text-white"><?= (int) $row['position'] ?></td>
                            <td class="px-5 py-2 text-white"><?= (int) $row['roll_no'] ?></td>
                            <td class="px-5 py-2">
                                <p class="font-medium"><?= View::escape((string) $row['student_name']) ?></p>
                                <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) $row['student_code']) ?></p>
                            </td>
                            <td class="px-5 py-2 text-right font-medium"><?= (int) $row['total'] ?></td>
                            <td class="px-5 py-2 text-right text-slate-300"><?= number_format((float) $row['gpa'], 2) ?></td>
                            <td class="px-5 py-2">
                                <span class="rounded-full px-2.5 py-1 text-xs <?= $row['has_failed'] ? 'bg-rose-400/10 text-rose-200' : 'bg-teal-400/10 text-teal-200' ?>"><?= View::escape((string) $row['grade']) ?></span>
                            </td>
                            <td class="px-5 py-2 text-center">
                                <details class="student-actions group inline-flex items-center justify-center" aria-label="Actions">
                                    <summary class="grid h-9 w-9 place-items-center rounded-lg border border-green-100/20 text-green-100/75 transition hover:bg-green-100/10 hover:text-white" aria-label="Show actions" title="Actions">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"></circle><circle cx="12" cy="12" r="1.7"></circle><circle cx="19" cy="12" r="1.7"></circle></svg>
                                    </summary>
                                    <div class="student-actions-controls hidden flex items-center gap-2">
                                        <a href="<?= Url::to('/reports/form?student_id=' . (int) $row['student_id'] . '&exam=' . urlencode($examName) . '&year=' . urlencode($academicYear) . '&class_id=' . $classId) ?>" class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs hover:border-green-300">Edit marks</a>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
