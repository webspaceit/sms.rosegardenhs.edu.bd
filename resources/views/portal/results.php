<?php
/**
 * A student's report cards.
 *
 * @var array<string, mixed> $student
 * @var array<int, array<string, mixed>> $reports
 * @var array<string, mixed>|null $selected
 * @var array<int, array<string, mixed>> $marks
 */
use App\Core\Url;
use App\Core\View;

$total = 0;
$points = 0.0;
$failed = [];
foreach ($marks as $mark) {
    $total += (int) $mark['total'];
    if ($mark['letter_grade'] === 'F') {
        $failed[] = (string) $mark['subject_name'];
    } else {
        $points += (float) $mark['grade_point'];
    }
}
$average = $marks === [] ? 0.0 : round($points / count($marks), 2);
$grade = $failed !== [] ? 'F' : match (true) {
    $average >= 5.0 => 'A+',
    $average >= 4.0 => 'A',
    $average >= 3.5 => 'A-',
    $average >= 3.0 => 'B',
    $average >= 2.0 => 'C',
    default => 'D',
};
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">My results</p>
        <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $student['name']) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60"><?= View::escape((string) $student['student_id']) ?> · <?= View::escape((string) $student['class_name']) ?> · Roll <?= (int) $student['roll_no'] ?></p>
    </div>
</div>

<?php if ($reports === []): ?>
    <div class="mt-7 rounded-2xl border border-dashed border-white/10 p-10 text-center text-sm text-emerald-100/40">No report card has been published yet.</div>
<?php else: ?>
    <form method="get" action="<?= Url::to('/portal/results') ?>" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
        <label class="text-sm">
            <span class="mb-1.5 block text-emerald-100/60">Examination</span>
            <select name="report" onchange="this.form.submit()" class="rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm">
                <?php foreach ($reports as $report): ?>
                    <option value="<?= (int) $report['id'] ?>"<?= $selected !== null && (int) $selected['id'] === (int) $report['id'] ? ' selected' : '' ?>>
                        <?= View::escape((string) $report['exam_name']) ?> (<?= View::escape((string) $report['academic_year']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <?php if ($selected !== null): ?>
        <section class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                <div>
                    <h2 class="font-semibold"><?= View::escape((string) $selected['exam_name']) ?></h2>
                    <p class="mt-1 text-xs text-emerald-100/40"><?= View::escape((string) $selected['academic_year']) ?></p>
                </div>
                <div class="flex items-center gap-5 text-right">
                    <span><span class="block font-mono text-[9px] uppercase tracking-widest text-emerald-100/40">Total</span><span class="block text-lg font-semibold"><?= $total ?></span></span>
                    <span><span class="block font-mono text-[9px] uppercase tracking-widest text-emerald-100/40">GPA</span><span class="block text-lg font-semibold"><?= number_format($average, 2) ?></span></span>
                    <span><span class="block font-mono text-[9px] uppercase tracking-widest text-emerald-100/40">Grade</span><span class="block text-lg font-semibold <?= $failed !== [] ? 'text-rose-300' : 'text-green-200' ?>"><?= $grade ?></span></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="admit-list-table w-full min-w-[560px] text-left text-sm">
                    <thead class="font-mono text-xs uppercase tracking-widest text-emerald-100/40">
                        <tr>
                            <th class="px-5 py-3 font-normal">Subject</th>
                            <th class="px-4 py-3 text-right font-normal">Written</th>
                            <th class="px-4 py-3 text-right font-normal">MCQ</th>
                            <th class="px-4 py-3 text-right font-normal">Practical</th>
                            <th class="px-4 py-3 text-right font-normal">CA</th>
                            <th class="px-4 py-3 text-right font-normal">Total</th>
                            <th class="px-5 py-3 text-center font-normal">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                    <?php if ($marks === []): ?>
                        <tr><td colspan="7" class="px-5 py-8 text-center text-emerald-100/40">No marks have been entered for this examination yet.</td></tr>
                    <?php else: foreach ($marks as $mark): ?>
                        <tr>
                            <td class="px-5 py-2 font-medium"><?= View::escape((string) $mark['subject_name']) ?></td>
                            <td class="px-4 py-2 text-right text-emerald-100/70"><?= (int) $mark['written_marks'] ?></td>
                            <td class="px-4 py-2 text-right text-emerald-100/70"><?= (int) $mark['mcq_marks'] ?></td>
                            <td class="px-4 py-2 text-right text-emerald-100/70"><?= (int) $mark['practical_marks'] ?></td>
                            <td class="px-4 py-2 text-right text-emerald-100/70"><?= (int) $mark['ca_marks'] ?></td>
                            <td class="px-4 py-2 text-right font-medium"><?= (int) $mark['total'] ?> <span class="text-xs text-emerald-100/40">/ <?= (int) $mark['full_marks'] ?></span></td>
                            <td class="px-5 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs <?= $mark['letter_grade'] === 'F' ? 'bg-rose-400/15 text-rose-200' : 'bg-green-400/15 text-green-200' ?>"><?= View::escape((string) $mark['letter_grade']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($failed !== []): ?>
                <p class="border-t border-white/10 px-5 py-4 text-sm text-rose-200">Failed subject<?= count($failed) === 1 ? '' : 's' ?>: <?= View::escape(implode(', ', $failed)) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
