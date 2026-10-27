<?php
use App\Core\View;

$classTotals = array_map(static fn (array $class): int => (int) $class['total'], $classEnrollment);
$classMaximum = max(1, ...$classTotals);
$classEnrollmentTotal = array_sum($classTotals);
?>
<section class="mt-8 overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="class-enrollment-title">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-green-900/60 px-5 py-4 sm:px-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-green-200/70">Class overview</p>
            <h2 id="class-enrollment-title" class="mt-1 text-lg font-semibold">Students by class</h2>
            <p class="mt-1 text-sm text-white">Active enrollment across <?= count($classEnrollment) ?> classes</p>
        </div>
        <div class="rounded-xl border border-green-100/15 bg-emerald-950/35 px-4 py-2 text-right">
            <p class="text-[10px] font-semibold uppercase tracking-[.15em] text-green-100/55">Active students</p>
            <p class="mt-0.5 text-xl font-semibold text-white"><?= $classEnrollmentTotal ?></p>
        </div>
    </div>
    <div class="space-y-4 px-5 py-5 sm:px-6 sm:py-6">
        <?php if ($classEnrollment === []): ?>
            <p class="rounded-xl border border-dashed border-green-100/15 px-4 py-8 text-center text-sm text-green-50/60">Add classes to see the enrollment chart.</p>
        <?php else: foreach ($classEnrollment as $index => $class): ?>
            <?php
            $total = (int) $class['total'];
            $barWidth = $total > 0 ? max(4, ($total / $classMaximum) * 100) : 0;
            $isTopClass = $total > 0 && $total === $classMaximum;
            ?>
            <div class="grid grid-cols-[5.25rem_minmax(0,1fr)_2.5rem] items-center gap-3 sm:grid-cols-[7rem_minmax(0,1fr)_3rem] sm:gap-4">
                <div class="truncate text-sm font-medium text-green-50/85" title="<?= View::escape($class['name']) ?>"><?= View::escape($class['name']) ?></div>
                <div class="relative h-3 overflow-visible rounded-full bg-emerald-950/75 ring-1 ring-inset ring-green-100/[0.07]" role="img" aria-label="<?= View::escape($class['name'] . ': ' . $total . ' active students') ?>">
                    <div class="relative h-full rounded-full bg-gradient-to-r from-emerald-400 via-green-400 to-lime-300 shadow-[0_0_14px_rgba(34,197,94,.2)] transition-all duration-500" style="width: <?= number_format($barWidth, 1, '.', '') ?>%">
                        <?php if ($isTopClass): ?><span class="absolute -right-1.5 top-1/2 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-[#0b3425] bg-red-400 shadow-[0_0_10px_rgba(248,113,113,.75)]"></span><?php endif; ?>
                    </div>
                </div>
                <span class="text-right text-sm font-semibold tabular-nums <?= $isTopClass ? 'text-red-200' : 'text-green-100/80' ?>"><?= $total ?></span>
            </div>
        <?php endforeach; endif; ?>
        <div class="flex items-center justify-between border-t border-green-100/[0.08] pt-3 text-[11px] text-green-100/45">
            <span>0 students</span>
            <span>Bars compare class sizes</span>
            <span><?= $classMaximum ?> max</span>
        </div>
    </div>
</section>
