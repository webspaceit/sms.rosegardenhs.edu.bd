<?php
use App\Core\View;

$chartValues = array_map(static fn (array $point): int => (int) $point['total'], $admissionsTrend);
$chartMaximum = max(1, ...$chartValues);
$chartMidpoint = (int) ceil($chartMaximum / 2);
$chartBars = [];
foreach ($admissionsTrend as $index => $point) {
    $total = (int) $point['total'];
    $height = $total > 0 ? max(8, ($total / $chartMaximum) * 126) : 3;
    $chartBars[] = [
        'x' => 113 + ($index * 125),
        'y' => 174 - $height,
        'height' => $height,
        'label' => (string) $point['label'],
        'total' => $total,
        'latest' => $index === count($admissionsTrend) - 1,
    ];
}
?>
<section class="mt-8 overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="admissions-chart-title">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-green-900/60 px-5 py-4 sm:px-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-green-200/70">Enrollment overview</p>
            <h2 id="admissions-chart-title" class="mt-1 text-lg font-semibold">Student admissions</h2>
            <p class="mt-1 text-sm text-white">New records over the last six months</p>
        </div>
        <div class="flex items-center gap-2 rounded-full border border-green-100/15 bg-emerald-950/35 px-3 py-1.5 text-xs text-green-100/75">
            <span class="h-2 w-2 rounded-full bg-green-300 shadow-[0_0_10px_rgba(74,222,128,.7)]"></span>
            Monthly admissions
        </div>
    </div>
    <div class="px-4 pb-5 pt-5 sm:px-6">
        <div class="mb-3 flex items-center justify-between text-xs text-green-100/60">
            <span>Admissions</span>
            <span>Monthly totals</span>
        </div>
        <div class="w-full overflow-hidden" role="img" aria-label="Monthly student admissions bar chart for the last six months">
            <svg viewBox="0 0 760 218" preserveAspectRatio="xMidYMid meet" class="h-56 w-full">
                <defs>
                    <linearGradient id="admissions-bar" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="#86efac" />
                        <stop offset="100%" stop-color="#16a34a" />
                    </linearGradient>
                    <linearGradient id="admissions-bar-latest" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="#fca5a5" />
                        <stop offset="100%" stop-color="#ef4444" />
                    </linearGradient>
                </defs>
                <line x1="54" y1="48" x2="730" y2="48" stroke="rgba(167,243,208,.12)" stroke-dasharray="4 7" />
                <line x1="54" y1="111" x2="730" y2="111" stroke="rgba(167,243,208,.10)" stroke-dasharray="4 7" />
                <line x1="54" y1="174" x2="730" y2="174" stroke="rgba(167,243,208,.18)" />
                <text x="14" y="52" fill="rgba(209,250,229,.55)" font-size="11"><?= $chartMaximum ?></text>
                <text x="14" y="115" fill="rgba(209,250,229,.45)" font-size="11"><?= $chartMidpoint ?></text>
                <text x="14" y="178" fill="rgba(209,250,229,.45)" font-size="11">0</text>
                <?php foreach ($chartBars as $bar): ?>
                    <rect x="<?= $bar['x'] - 22 ?>" y="48" width="44" height="126" rx="9" fill="rgba(2,44,34,.62)" stroke="rgba(167,243,208,.06)" />
                    <rect x="<?= $bar['x'] - 22 ?>" y="<?= number_format($bar['y'], 1, '.', '') ?>" width="44" height="<?= number_format($bar['height'], 1, '.', '') ?>" rx="8" fill="url(#<?= $bar['latest'] ? 'admissions-bar-latest' : 'admissions-bar' ?>)" opacity="<?= $bar['total'] === 0 ? '.45' : '1' ?>"><title><?= View::escape($bar['label'] . ': ' . $bar['total'] . ' new records') ?></title></rect>
                    <text x="<?= $bar['x'] ?>" y="<?= max(39, $bar['y'] - 10) ?>" text-anchor="middle" fill="<?= $bar['latest'] ? '#fecaca' : '#d1fae5' ?>" font-size="12" font-weight="600"><?= $bar['total'] ?></text>
                    <text x="<?= $bar['x'] ?>" y="204" text-anchor="middle" fill="rgba(209,250,229,.68)" font-size="12"><?= View::escape($bar['label']) ?></text>
                <?php endforeach; ?>
            </svg>
        </div>
    </div>
</section>
