<?php
/**
 * The scores of an online exam.
 *
 * @var array<string, mixed> $exam
 * @var array<int, array<string, mixed>> $submissions
 */
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-4xl">
    <a href="<?= Url::to('/online-exams/questions?exam=' . (int) $exam['id']) ?>" class="text-sm text-teal-300 hover:text-teal-200">← Questions</a>
    <h1 class="mt-2 text-3xl font-semibold">Results · <?= View::escape((string) $exam['title']) ?></h1>
    <p class="mt-1 text-sm text-white"><?= count($submissions) ?> submission(s) out of <?= (int) $exam['total_marks'] ?> marks</p>

    <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="overflow-x-auto">
            <table class="admit-list-table w-full min-w-[520px] text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Roll</th>
                        <th class="px-5 py-3 font-medium">Student</th>
                        <th class="px-5 py-3 text-right font-medium">Score</th>
                        <th class="px-5 py-3 text-right font-medium">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php if ($submissions === []): ?>
                    <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Nobody has sat this exam yet.</td></tr>
                <?php else: foreach ($submissions as $row): ?>
                    <tr>
                        <td class="px-5 py-2 text-white"><?= (int) $row['roll_no'] ?></td>
                        <td class="px-5 py-2">
                            <p class="font-medium"><?= View::escape((string) $row['student_name']) ?></p>
                            <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) $row['student_code']) ?></p>
                        </td>
                        <td class="px-5 py-2 text-right font-medium text-teal-200"><?= number_format((float) $row['score'], 2) ?></td>
                        <td class="px-5 py-2 text-right text-white"><?= View::escape((string) ($row['submitted_at'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
