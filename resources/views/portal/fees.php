<?php
/**
 * A student's fee invoices.
 *
 * @var array<string, mixed> $student
 * @var array<int, array<string, mixed>> $invoices
 * @var float $paid
 * @var float $due
 */
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">My fees</p>
        <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $student['name']) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60"><?= View::escape((string) $student['student_id']) ?> · <?= View::escape((string) $student['class_name']) ?></p>
    </div>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-3">
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Paid</p>
        <p class="mt-2 text-3xl font-semibold text-green-200"><?= number_format($paid, 2) ?></p>
    </article>
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Outstanding</p>
        <p class="mt-2 text-3xl font-semibold <?= $due > 0 ? 'text-amber-200' : 'text-emerald-200' ?>"><?= number_format($due, 2) ?></p>
    </article>
    <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <p class="text-sm text-emerald-100/50">Invoices</p>
        <p class="mt-2 text-3xl font-semibold text-sky-200"><?= count($invoices) ?></p>
    </article>
</div>

<section class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
    <div class="border-b border-white/10 px-5 py-4">
        <h2 class="font-semibold">Invoices</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="admit-list-table w-full min-w-[640px] text-left text-sm">
            <thead class="font-mono text-xs uppercase tracking-widest text-emerald-100/40">
                <tr>
                    <th class="px-5 py-3 font-normal">Invoice</th>
                    <th class="px-5 py-3 font-normal">Fee head</th>
                    <th class="px-5 py-3 font-normal">Month</th>
                    <th class="px-5 py-3 text-right font-normal">Amount</th>
                    <th class="px-5 py-3 font-normal">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
            <?php if ($invoices === []): ?>
                <tr><td colspan="5" class="px-5 py-8 text-center text-emerald-100/40">No invoice has been raised.</td></tr>
            <?php else: foreach ($invoices as $invoice): ?>
                <tr>
                    <td class="px-5 py-2 font-mono text-xs text-emerald-100/60"><?= View::escape((string) $invoice['invoice_no']) ?></td>
                    <td class="px-5 py-2 font-medium"><?= View::escape((string) $invoice['head_name']) ?></td>
                    <td class="px-5 py-2 text-emerald-100/60"><?= View::escape((string) ($invoice['month'] ?? '—')) ?></td>
                    <td class="px-5 py-2 text-right font-medium"><?= number_format((float) $invoice['amount'], 2) ?></td>
                    <td class="px-5 py-2">
                        <span class="rounded-full px-2.5 py-1 text-xs <?= $invoice['status'] === 'paid' ? 'bg-green-400/15 text-green-200' : 'bg-amber-400/15 text-amber-200' ?>"><?= View::escape(ucfirst((string) $invoice['status'])) ?></span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
