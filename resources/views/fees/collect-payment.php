<?php use App\Core\Csrf; use App\Core\Url; use App\Core\View; ?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="<?= Url::to('/fees') ?>" class="text-sm text-teal-300">← Fee collections</a><h1 class="mt-2 text-3xl font-semibold">Collect student payment</h1><p class="mt-1 text-sm text-white">Allocate one payment across one or more outstanding invoices.</p></div>
    <div class="flex gap-2"><a href="<?= Url::to('/fees/receipts') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm">Payment receipts</a><a href="<?= Url::to('/fees') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm">Fee invoices</a></div>
</div>
<form method="get" action="<?= Url::to('/fees/collect') ?>" class="mt-6 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
    <label for="payment-student" class="mb-2 block text-sm font-medium">Student</label>
    <div class="flex flex-col gap-3 sm:flex-row">
        <select id="payment-student" name="student_id" required class="min-w-0 flex-1 rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm">
            <option value="">Select a student…</option>
            <?php foreach ($students as $option): ?><option value="<?= (int) $option['id'] ?>"<?= $student && (int) $student['id'] === (int) $option['id'] ? ' selected' : '' ?>><?= View::escape($option['name']) ?> · <?= View::escape($option['student_id']) ?><?= $option['class_name'] ? ' · ' . View::escape($option['class_name']) : '' ?></option><?php endforeach; ?>
        </select>
        <button class="rounded-lg border border-green-100/20 px-5 py-2.5 text-sm font-semibold">Load outstanding invoices</button>
    </div>
</form>

<?php if ($student): ?>
<section class="mt-5 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
    <div class="mb-5"><h2 class="text-xl font-semibold"><?= View::escape($student['name']) ?></h2><p class="mt-1 text-sm text-green-100/60">Student ID <?= View::escape($student['student_id']) ?> · Roll <?= View::escape($student['roll_no'] ?? '—') ?><?= $student['class_name'] ? ' · ' . View::escape($student['class_name']) : '' ?></p></div>
    <?php if ($invoices === []): ?>
        <p class="rounded-xl border border-dashed border-green-100/20 p-7 text-center text-sm text-green-50/65">This student has no outstanding invoices.</p>
    <?php else: ?>
    <form method="post" action="<?= Url::to('/fees/collect') ?>" id="payment-form" class="space-y-5">
        <?= Csrf::field() ?><input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
        <div class="overflow-x-auto rounded-xl border border-green-900/60">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-green-950/40 text-green-100/65"><tr><th class="px-4 py-3">Invoice</th><th class="px-4 py-3">Fee</th><th class="px-4 py-3">Period</th><th class="px-4 py-3 text-right">Outstanding</th><th class="w-48 px-4 py-3 text-right">Apply payment</th></tr></thead>
                <tbody class="divide-y divide-green-900/50">
                <?php foreach ($invoices as $invoice): $outstanding = max(0, (float) $invoice['amount'] - (float) $invoice['allocated']); ?>
                    <tr><td class="px-4 py-3 font-mono text-xs"><?= View::escape($invoice['invoice_no']) ?></td><td class="px-4 py-3"><?= View::escape($invoice['fee_head']) ?></td><td class="px-4 py-3"><?= View::escape($invoice['month'] ?: '—') ?></td><td class="px-4 py-3 text-right">৳<?= number_format($outstanding, 2) ?></td><td class="px-4 py-3"><input type="number" min="0" max="<?= number_format($outstanding, 2, '.', '') ?>" step="0.01" name="allocations[<?= (int) $invoice['id'] ?>]" value="0.00" data-max="<?= number_format($outstanding, 2, '.', '') ?>" class="payment-allocation w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2 text-right text-sm" aria-label="Amount to apply to <?= View::escape($invoice['invoice_no']) ?>"></td></tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="border-t border-green-900/60 bg-green-950/25"><tr><td colspan="3" class="px-4 py-3 font-semibold">Payment total</td><td colspan="2" id="payment-total" class="px-4 py-3 text-right text-lg font-bold">৳0.00</td></tr></tfoot>
            </table>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div><label for="payment-date" class="mb-1.5 block text-sm">Payment date</label><input id="payment-date" name="paid_on" type="date" value="<?= date('Y-m-d') ?>" required class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"></div>
            <div><label for="payment-method" class="mb-1.5 block text-sm">Payment method</label><select id="payment-method" name="payment_method" required class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"><option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Cheque">Cheque</option><option value="Mobile Banking">Mobile Banking</option><option value="Card">Card</option><option value="Other">Other</option></select></div>
            <div><label for="payment-reference" class="mb-1.5 block text-sm">Bank / transaction reference</label><input id="payment-reference" name="reference_no" maxlength="100" placeholder="Optional" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"></div>
            <div><label for="payment-notes" class="mb-1.5 block text-sm">Notes</label><input id="payment-notes" name="notes" maxlength="255" placeholder="Optional" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"></div>
        </div>
        <p class="rounded-xl border border-amber-200/15 bg-amber-950/25 p-3 text-xs text-amber-100/80">Bank Transfer and Cheque print as three copies on Legal paper. Other payment methods print as a single A4 receipt. Mobile Banking uses A4.</p>
        <div class="flex justify-end"><button class="form-action-button" id="payment-submit" disabled>Record payment and print receipt</button></div>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<script>
(() => {
    const inputs = [...document.querySelectorAll('.payment-allocation')];
    const total = document.getElementById('payment-total');
    const submit = document.getElementById('payment-submit');
    const refresh = () => {
        let cents = 0;
        inputs.forEach(input => {
            let value = Math.max(0, Math.round((Number(input.value) || 0) * 100));
            const maximum = Math.round(Number(input.dataset.max) * 100);
            if (value > maximum) { value = maximum; input.value = (value / 100).toFixed(2); }
            cents += value;
        });
        if (total) total.textContent = `৳${(cents / 100).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        if (submit) submit.disabled = cents === 0;
    };
    inputs.forEach(input => input.addEventListener('input', refresh));
    refresh();
})();
</script>
