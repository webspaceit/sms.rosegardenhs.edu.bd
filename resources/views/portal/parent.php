<?php
/**
 * The parent home.
 *
 * @var array<string, mixed> $guardian
 * @var array<int, array<string, mixed>> $children
 * @var array<string, mixed>|null $selected
 * @var array<string, mixed> $attendance
 * @var array<int, array<string, mixed>> $fees
 * @var array<int, array<string, mixed>> $reports
 * @var float $due
 */
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">Parent portal</p>
        <h1 class="mt-2 text-3xl font-semibold">Welcome, <?= View::escape(explode(' ', (string) $guardian['name'])[0]) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60"><?= View::escape((string) $guardian['phone']) ?><?= ($guardian['district'] ?? '') !== '' ? ' · ' . View::escape((string) $guardian['district']) : '' ?></p>
    </div>
</div>

<?php if ($children === []): ?>
    <div class="mt-7 rounded-2xl border border-dashed border-white/10 p-10 text-center text-sm text-emerald-100/40">
        No student is linked to your account yet. Please contact the school office.
    </div>
<?php else: ?>
    <form method="get" action="<?= Url::to('/portal/parent') ?>" class="mt-7 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
        <label class="text-sm">
            <span class="mb-1.5 block text-emerald-100/60">Child</span>
            <select name="child" onchange="this.form.submit()" class="rounded-lg border border-white/10 bg-black/30 px-3 py-2.5 text-sm">
                <?php foreach ($children as $child): ?>
                    <option value="<?= (int) $child['id'] ?>"<?= $selected !== null && (int) $selected['id'] === (int) $child['id'] ? ' selected' : '' ?>>
                        <?= View::escape((string) $child['name']) ?> — <?= View::escape((string) $child['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <p class="text-sm text-emerald-100/50">Attendance</p>
            <p class="mt-2 text-3xl font-semibold text-green-200"><?= (float) $attendance['percent'] ?>%</p>
            <p class="mt-2 text-xs text-emerald-100/40"><?= (int) $attendance['present'] ?> present of <?= (int) $attendance['total'] ?> days</p>
        </article>
        <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <p class="text-sm text-emerald-100/50">Fees due</p>
            <p class="mt-2 text-3xl font-semibold <?= $due > 0 ? 'text-amber-200' : 'text-emerald-200' ?>"><?= number_format($due, 2) ?></p>
            <p class="mt-2 text-xs text-emerald-100/40">Across <?= count($fees) ?> invoice(s)</p>
        </article>
        <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <p class="text-sm text-emerald-100/50">Report cards</p>
            <p class="mt-2 text-3xl font-semibold text-sky-200"><?= count($reports) ?></p>
            <p class="mt-2 text-xs text-emerald-100/40">Published so far</p>
        </article>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <h2 class="font-semibold">Results</h2>
            <div class="mt-4 space-y-2">
                <?php if ($reports === []): ?>
                    <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No report card has been published yet.</p>
                <?php else: foreach ($reports as $report): ?>
                    <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium"><?= View::escape((string) $report['exam_name']) ?></span>
                            <span class="block text-xs text-emerald-100/40"><?= View::escape((string) $report['academic_year']) ?></span>
                        </span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <h2 class="font-semibold">Fee invoices</h2>
            <div class="mt-4 space-y-2">
                <?php if ($fees === []): ?>
                    <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No invoice has been raised.</p>
                <?php else: foreach ($fees as $invoice): ?>
                    <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium"><?= View::escape((string) $invoice['head_name']) ?></span>
                            <span class="block text-xs text-emerald-100/40"><?= View::escape((string) $invoice['invoice_no']) ?></span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block text-sm font-medium"><?= number_format((float) $invoice['amount'], 2) ?></span>
                            <span class="block text-xs <?= $invoice['status'] === 'paid' ? 'text-green-300' : 'text-amber-300' ?>"><?= View::escape(ucfirst((string) $invoice['status'])) ?></span>
                        </span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </div>
<?php endif; ?>
