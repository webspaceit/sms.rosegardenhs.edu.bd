<?php
/**
 * A student's own attendance.
 *
 * @var array<string, mixed> $student
 * @var string $month
 * @var array<int, string> $days
 * @var array<string, mixed> $summary
 * @var array<string, mixed> $monthSummary
 */
use App\Core\Url;
use App\Core\View;

$marks = [
    'present' => ['P', 'bg-green-400/25 text-green-100'],
    'late' => ['L', 'bg-amber-400/25 text-amber-100'],
    'half_day' => ['H', 'bg-sky-400/25 text-sky-100'],
    'absent' => ['A', 'bg-rose-400/25 text-rose-100'],
    'leave' => ['Lv', 'bg-white/10 text-emerald-100/60'],
];
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">My attendance</p>
        <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $student['name']) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60"><?= View::escape((string) $student['class_name']) ?> · Roll <?= (int) $student['roll_no'] ?></p>
    </div>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-3">
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">This month</p>
        <p class="mt-2 text-3xl font-semibold text-green-200"><?= (float) $monthSummary['percent'] ?>%</p>
        <p class="mt-2 text-xs text-emerald-100/40"><?= (int) $monthSummary['present'] ?> present of <?= (int) $monthSummary['total'] ?> days</p>
    </article>
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Overall</p>
        <p class="mt-2 text-3xl font-semibold text-sky-200"><?= (float) $summary['percent'] ?>%</p>
        <p class="mt-2 text-xs text-emerald-100/40"><?= (int) $summary['total'] ?> days recorded</p>
    </article>
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Absent</p>
        <p class="mt-2 text-3xl font-semibold text-rose-200"><?= (int) $summary['absent'] ?></p>
        <p class="mt-2 text-xs text-emerald-100/40"><?= (int) $summary['late'] ?> late · <?= (int) $summary['leave'] ?> on leave</p>
    </article>
</div>

<form method="get" action="<?= Url::to('/portal/attendance') ?>" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
    <label class="text-sm">
        <span class="mb-1.5 block text-emerald-100/60">Month</span>
        <input type="month" name="month" value="<?= View::escape($month) ?>" onchange="this.form.submit()" class="rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm">
    </label>
</form>

<section class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
    <div class="border-b border-white/10 px-5 py-4">
        <h2 class="font-semibold">Day by day</h2>
        <p class="mt-1 text-xs text-emerald-100/40">Every school day of the month you have a mark for</p>
    </div>
    <div class="overflow-x-auto">
        <table class="admit-list-table w-full min-w-[640px] text-left text-sm">
            <thead class="font-mono text-xs uppercase tracking-widest text-emerald-100/40">
                <tr><th class="px-5 py-3 font-normal">Day</th><th class="px-5 py-3 font-normal">Status</th><th class="px-5 py-3 font-normal">Remark</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
            <?php if ($days === []): ?>
                <tr><td colspan="3" class="px-5 py-8 text-center text-emerald-100/40">No attendance has been recorded for this month.</td></tr>
            <?php else: ksort($days); foreach ($days as $day => $status): ?>
                <tr>
                    <td class="px-5 py-2"><?= (int) $day ?></td>
                    <td class="px-5 py-2">
                        <?php $mark = $marks[$status] ?? ['?', 'bg-white/10 text-emerald-100/60']; ?>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $mark[1] ?>"><?= $mark[0] ?></span>
                        <span class="ml-2 text-emerald-100/60"><?= View::escape(ucfirst(str_replace('_', ' ', $status))) ?></span>
                    </td>
                    <td class="px-5 py-2 text-emerald-100/50">—</td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
