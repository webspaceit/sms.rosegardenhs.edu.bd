<?php
/**
 * The fail list: the students who did not pass, and what they failed in.
 *
 * @var array<int, array<string, mixed>> $results
 */
use App\Core\View;

$failed = array_values(array_filter($results, static fn (array $row): bool => $row['has_failed']));
?>
<div>
    <?php require BASE_PATH . '/resources/views/results/partials/config-bar.php'; ?>

    <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="border-b border-green-900/60 px-5 py-4">
            <h2 class="font-semibold">Fail list</h2>
            <p class="mt-1 text-xs text-slate-500">Students with a subject below 33 marks, and the subjects they failed</p>
        </div>
        <div class="overflow-x-auto">
            <table class="admit-list-table w-full min-w-[720px] text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Roll</th>
                        <th class="px-5 py-3 font-medium">Student</th>
                        <th class="px-5 py-3 font-medium">Failed subjects</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php if ($failed === []): ?>
                    <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Nobody has failed this exam.</td></tr>
                <?php else: foreach ($failed as $row): ?>
                    <tr class="hover:bg-slate-800/30">
                        <td class="px-5 py-2 text-white"><?= (int) $row['roll_no'] ?></td>
                        <td class="px-5 py-2">
                            <p class="font-medium"><?= View::escape((string) $row['student_name']) ?></p>
                            <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) $row['student_code']) ?></p>
                        </td>
                        <td class="px-5 py-2">
                            <?php foreach ($row['failed_subjects'] as $subject): ?>
                                <span class="mr-1.5 mt-1 inline-block rounded-full bg-rose-400/10 px-2.5 py-1 text-xs text-rose-200"><?= View::escape($subject) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td class="px-5 py-2 text-right text-slate-300"><?= (int) $row['total'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
