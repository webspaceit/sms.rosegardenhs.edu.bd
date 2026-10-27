<?php
/**
 * The printable mark sheet of a class.
 *
 * @var array<int, array<string, mixed>> $subjects
 * @var array<int, array<string, mixed>> $rows
 */
use App\Core\Url;
use App\Core\View;

$grandTotal = 0;
foreach ($rows as $row) {
    $grandTotal += (int) $row['total'];
}
?>
<div class="flex flex-wrap items-end justify-between gap-4 print:hidden">
    <div>
        <h1 class="text-3xl font-semibold">Mark sheet</h1>
        <p class="mt-1 text-sm text-white"><?= View::escape($className) ?> — <?= View::escape($examName) ?> (<?= View::escape($academicYear) ?>)</p>
    </div>
    <div class="flex gap-3">
        <a href="<?= Url::to('/marks?class_id=' . (int) $classId . '&exam=' . urlencode($examName) . '&year=' . urlencode($academicYear)) ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Back to entry</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Print</button>
    </div>
</div>

<section class="mt-6 overflow-hidden rounded-2xl border border-slate-800 bg-white text-slate-900 print:rounded-none print:border-0">
    <header class="border-b border-slate-200 px-5 py-4 text-center">
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">WSIT School</p>
        <h2 class="mt-1 text-xl font-semibold"><?= View::escape($className) ?> — Mark sheet</h2>
        <p class="mt-1 text-sm text-slate-600"><?= View::escape($examName) ?> · <?= View::escape($academicYear) ?></p>
    </header>

    <div class="overflow-x-auto">
        <table class="admit-list-table w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Roll</th>
                    <th class="px-4 py-3 font-medium">Student</th>
                    <?php foreach ($subjects as $subject): ?>
                        <th class="px-3 py-3 text-center font-medium"><?= View::escape((string) $subject['name']) ?><span class="block text-[9px] font-normal text-slate-500">/ <?= (int) $subject['full_marks'] ?></span></th>
                    <?php endforeach; ?>
                    <th class="px-4 py-3 text-right font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
            <?php if ($rows === []): ?>
                <tr><td colspan="<?= count($subjects) + 3 ?>" class="px-4 py-8 text-center text-slate-500">No students to show.</td></tr>
            <?php else: foreach ($rows as $row): ?>
                <tr>
                    <td class="px-4 py-2 text-slate-600"><?= (int) $row['student']['roll_no'] ?></td>
                    <td class="px-4 py-2">
                        <span class="block font-medium"><?= View::escape((string) $row['student']['name']) ?></span>
                        <span class="block text-[10px] text-slate-500"><?= View::escape((string) $row['student']['student_id']) ?></span>
                    </td>
                    <?php foreach ($subjects as $subject): ?>
                        <?php $obtained = (int) ($row['marks'][(string) $subject['name']] ?? 0); ?>
                        <td class="px-3 py-2 text-center"><?= $obtained === 0 ? '<span class="text-slate-300">—</span>' : $obtained ?></td>
                    <?php endforeach; ?>
                    <td class="px-4 py-2 text-right font-semibold"><?= (int) $row['total'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if ($rows !== []): ?>
                <tfoot class="border-t-2 border-slate-300 bg-slate-50">
                    <tr>
                        <td class="px-4 py-2 font-medium" colspan="<?= count($subjects) + 2 ?>">Class total</td>
                        <td class="px-4 py-2 text-right font-semibold"><?= $grandTotal ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <footer class="flex justify-between gap-8 border-t border-slate-200 px-5 py-6 text-xs text-slate-500">
        <span class="border-t border-slate-400 pt-1">Class teacher</span>
        <span class="border-t border-slate-400 pt-1">Head teacher</span>
        <span class="border-t border-slate-400 pt-1">Principal</span>
    </footer>
</section>
