<?php
use App\Core\Url;
use App\Core\View;

$departmentMaximum = max(1, ...array_map(static fn (array $department): int => (int) $department['total'], $departments));
$dashboardAlerts = [];
if ($overdueFeeCount > 0) {
    $dashboardAlerts[] = [
        'tone' => 'rose',
        'title' => 'Overdue fee invoices',
        'detail' => number_format((int) $overdueFeeCount) . ' invoice(s) are overdue, totaling ৳' . number_format((float) $overdueFeeAmount, 0) . '.',
        'url' => '/fees',
    ];
}
if ($attendanceRate !== null && $attendanceRate < 75) {
    $dashboardAlerts[] = [
        'tone' => 'amber',
        'title' => 'Attendance needs attention',
        'detail' => 'Today\'s recorded attendance is ' . (int) $attendanceRate . '% across ' . (int) $attendanceMarked . ' marked students.',
        'url' => '/batch-attendance',
    ];
}
if ($pendingComplaints > 0) {
    $dashboardAlerts[] = [
        'tone' => 'amber',
        'title' => 'Complaints to review',
        'detail' => number_format((int) $pendingComplaints) . ' complaint(s) are pending or under investigation.',
        'url' => '/complaints',
    ];
}
if ($newAdmissions > 0) {
    $dashboardAlerts[] = [
        'tone' => 'green',
        'title' => 'New student admissions',
        'detail' => number_format((int) $newAdmissions) . ' student record(s) were added in the last 7 days.',
        'url' => '/students',
    ];
}
foreach ($activeNotices as $notice) {
    $dashboardAlerts[] = [
        'tone' => 'blue',
        'title' => (string) $notice['title'],
        'detail' => ucfirst((string) $notice['type']) . ' notice · ' . date('M j, Y', strtotime((string) $notice['published_date'])),
        'url' => '/notices',
    ];
}
$toneClasses = [
    'rose' => 'border-l-rose-400 bg-rose-400/[0.07] text-rose-100',
    'amber' => 'border-l-amber-400 bg-amber-400/[0.07] text-amber-100',
    'green' => 'border-l-green-400 bg-green-400/[0.07] text-green-100',
    'blue' => 'border-l-sky-400 bg-sky-400/[0.07] text-sky-100',
];
?>
<div class="mt-8 grid gap-5 xl:grid-cols-2">
    <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="department-overview-title">
        <div class="border-b border-green-900/60 px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-green-200/65">Teaching staff</p>
            <h2 id="department-overview-title" class="mt-1 text-lg font-semibold">Department overview</h2>
            <p class="mt-1 text-sm text-white">Teachers grouped by their saved department</p>
        </div>
        <div class="space-y-4 p-5">
            <?php if ($departments === []): ?>
                <p class="rounded-xl border border-dashed border-green-100/15 px-4 py-8 text-center text-sm text-white">Add teacher records with department details to see this chart.</p>
            <?php else: foreach ($departments as $department): ?>
                <?php $total = (int) $department['total']; $width = max(4, ($total / $departmentMaximum) * 100); ?>
                <div class="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)_2.5rem] items-center gap-3 sm:grid-cols-[minmax(0,9rem)_minmax(0,1fr)_3rem]">
                    <span class="truncate text-sm text-green-50/85" title="<?= View::escape($department['name']) ?>"><?= View::escape($department['name']) ?></span>
                    <div class="h-2.5 overflow-hidden rounded-full bg-emerald-950/75 ring-1 ring-inset ring-green-100/[0.07]" role="img" aria-label="<?= View::escape($department['name'] . ': ' . $total . ' teachers') ?>">
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-400 via-green-400 to-lime-300" style="width: <?= number_format($width, 1, '.', '') ?>%"></div>
                    </div>
                    <span class="text-right text-sm font-semibold tabular-nums text-green-100"><?= $total ?></span>
                </div>
            <?php endforeach; endif; ?>
            <a href="<?= Url::to('/teachers') ?>" class="inline-flex text-xs font-medium text-green-200/75 hover:text-green-100">Open teacher register →</a>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="upcoming-events-title">
        <div class="flex items-center justify-between gap-3 border-b border-green-900/60 px-5 py-4">
            <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-green-200/65">School calendar</p><h2 id="upcoming-events-title" class="mt-1 text-lg font-semibold">Upcoming events</h2></div>
            <a href="<?= Url::to('/events') ?>" class="text-xs font-medium text-green-200/75 hover:text-green-100">Calendar →</a>
        </div>
        <div class="divide-y divide-green-100/[0.08] px-5">
            <?php if ($upcomingEvents === []): ?>
                <p class="py-8 text-center text-sm text-white">No upcoming events are scheduled.</p>
            <?php else: foreach ($upcomingEvents as $event): ?>
                <article class="flex gap-3 py-3.5">
                    <time datetime="<?= View::escape($event['start_date']) ?>" class="grid h-11 w-12 shrink-0 place-content-center rounded-lg border border-green-200/15 bg-green-300/[0.08] text-center"><span class="text-[10px] uppercase text-green-100/65"><?= date('M', strtotime((string) $event['start_date'])) ?></span><span class="text-sm font-semibold text-green-100"><?= date('d', strtotime((string) $event['start_date'])) ?></span></time>
                    <div class="min-w-0"><h3 class="truncate text-sm font-medium text-green-50"><?= View::escape($event['title']) ?></h3><p class="mt-1 text-xs text-white"><?= date('D, M j, Y', strtotime((string) $event['start_date'])) ?><?php if (!empty($event['end_date']) && $event['end_date'] !== $event['start_date']): ?> – <?= date('M j, Y', strtotime((string) $event['end_date'])) ?><?php endif; ?></p><?php if (!empty($event['description'])): ?><p class="mt-1 line-clamp-1 text-xs text-slate-500"><?= View::escape($event['description']) ?></p><?php endif; ?></div>
                </article>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="dashboard-alerts-title">
        <div class="flex items-center justify-between gap-3 border-b border-green-900/60 px-5 py-4"><div><p class="text-xs font-semibold uppercase tracking-[.16em] text-green-200/65">Updates</p><h2 id="dashboard-alerts-title" class="mt-1 text-lg font-semibold">Alerts &amp; notices</h2></div><span class="rounded-full border border-green-100/15 px-2.5 py-1 text-xs text-green-100/70"><?= count($dashboardAlerts) ?> item(s)</span></div>
        <div class="grid gap-2 p-4 sm:grid-cols-2">
            <?php if ($dashboardAlerts === []): ?>
                <p class="rounded-xl border border-dashed border-green-100/15 px-4 py-6 text-center text-sm text-white sm:col-span-2">You’re all caught up. There are no active alerts or notices.</p>
            <?php else: foreach ($dashboardAlerts as $alert): ?>
                <a href="<?= Url::to($alert['url']) ?>" class="rounded-lg border border-l-[3px] border-green-100/[0.08] px-4 py-3 transition hover:bg-white/[0.03] <?= $toneClasses[$alert['tone']] ?? $toneClasses['blue'] ?>"><h3 class="text-sm font-semibold"><?= View::escape($alert['title']) ?></h3><p class="mt-1 text-xs leading-5 text-slate-300/75"><?= View::escape($alert['detail']) ?></p></a>
            <?php endforeach; endif; ?>
        </div>
    </section>
</div>
