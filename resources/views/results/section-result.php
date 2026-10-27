<?php
/**
 * The section result: every student of the section with their total.
 *
 * @var array<int, array<string, mixed>> $results
 */
use App\Core\View;
?>
<div>
    <?php require BASE_PATH . '/resources/views/results/partials/config-bar.php'; ?>

    <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="border-b border-green-900/60 px-5 py-4">
            <h2 class="font-semibold">Section result</h2>
            <p class="mt-1 text-xs text-slate-500">Total marks and grade for each student. A dash means the student has failed a subject.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="admit-list-table w-full min-w-[720px] text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Roll</th>
                        <th class="px-5 py-3 font-medium">Student</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                        <th class="px-5 py-3 text-right font-medium">GPA</th>
                        <th class="px-5 py-3 font-medium">Grade</th>
                        <th class="px-5 py-3 text-right font-medium">Position</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php if ($results === []): ?>
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No results have been entered for this setup yet.</td></tr>
                <?php else: foreach ($results as $row): ?>
                    <tr class="hover:bg-slate-800/30">
                        <td class="px-5 py-2 text-white"><?= (int) $row['roll_no'] ?></td>
                        <td class="px-5 py-2">
                            <p class="font-medium"><?= View::escape((string) $row['student_name']) ?></p>
                            <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) $row['student_code']) ?></p>
                        </td>
                        <td class="px-5 py-2 text-right font-medium"><?= $row['has_failed'] ? '—' : (int) $row['total'] ?></td>
                        <td class="px-5 py-2 text-right text-slate-300"><?= $row['has_failed'] ? '—' : number_format((float) $row['gpa'], 2) ?></td>
                        <td class="px-5 py-2">
                            <span class="rounded-full px-2.5 py-1 text-xs <?= $row['has_failed'] ? 'bg-rose-400/10 text-rose-200' : 'bg-teal-400/10 text-teal-200' ?>"><?= View::escape((string) $row['grade']) ?></span>
                        </td>
                        <td class="px-5 py-2 text-right text-white"><?= $row['has_failed'] ? '—' : (int) $row['position'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
