<?php
/**
 * The merit list: the students who passed, best first.
 *
 * @var array<int, array<string, mixed>> $results
 */
use App\Core\View;

$passed = array_values(array_filter($results, static fn (array $row): bool => !$row['has_failed']));
?>
<div>
    <?php require BASE_PATH . '/resources/views/results/partials/config-bar.php'; ?>

    <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="border-b border-green-900/60 px-5 py-4">
            <h2 class="font-semibold">Merit list</h2>
            <p class="mt-1 text-xs text-slate-500">Only students who passed every subject appear here, ranked by total marks</p>
        </div>
        <div class="overflow-x-auto">
            <table class="admit-list-table w-full min-w-[720px] text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Merit</th>
                        <th class="px-5 py-3 font-medium">Roll</th>
                        <th class="px-5 py-3 font-medium">Student</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                        <th class="px-5 py-3 text-right font-medium">GPA</th>
                        <th class="px-5 py-3 font-medium">Grade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php if ($passed === []): ?>
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No student has passed this exam yet.</td></tr>
                <?php else: foreach ($passed as $row): ?>
                    <tr class="hover:bg-slate-800/30">
                        <td class="px-5 py-2">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-teal-400/10 text-sm font-semibold text-teal-200"><?= (int) $row['position'] ?></span>
                        </td>
                        <td class="px-5 py-2 text-white"><?= (int) $row['roll_no'] ?></td>
                        <td class="px-5 py-2">
                            <p class="font-medium"><?= View::escape((string) $row['student_name']) ?></p>
                            <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) $row['student_code']) ?></p>
                        </td>
                        <td class="px-5 py-2 text-right font-medium"><?= (int) $row['total'] ?></td>
                        <td class="px-5 py-2 text-right text-slate-300"><?= number_format((float) $row['gpa'], 2) ?></td>
                        <td class="px-5 py-2">
                            <span class="rounded-full bg-teal-400/10 px-2.5 py-1 text-xs text-teal-200"><?= View::escape((string) $row['grade']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
