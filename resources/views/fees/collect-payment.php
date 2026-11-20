<?php use App\Core\Csrf; use App\Core\Url; use App\Core\View; ?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="<?= Url::to('/fees') ?>" class="text-sm text-teal-300">← Fee collections</a><h1 class="mt-2 text-3xl font-semibold">Receive student payment</h1><p class="mt-1 text-sm text-green-50/65">Choose a student, review outstanding fee lines, and record one payment across any number of invoices.</p></div>
    <a href="<?= Url::to('/fees/receipts') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm">Payment receipts</a>
</div>

<form method="get" action="<?= Url::to('/fees/new') ?>" class="mt-6 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
    <div class="mb-5 flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full bg-teal-400/15 font-semibold text-teal-200">1</span><div><h2 class="font-semibold">Find student</h2><p class="text-xs text-green-50/55">Filter the list by class and section.</p></div></div>
    <div class="grid gap-4 md:grid-cols-3">
        <div><label for="filter-class" class="mb-1.5 block text-sm">Class</label><select id="filter-class" name="class_id" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"><option value="">All classes</option><?php foreach ($classes as $id => $name): ?><option value="<?= (int) $id ?>"<?= $selectedClassId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option><?php endforeach; ?></select></div>
        <div><label for="filter-section" class="mb-1.5 block text-sm">Section</label><select id="filter-section" name="section_id" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"><option value="">All sections</option><?php foreach ($sections as $id => $section): ?><option value="<?= (int) $id ?>" data-class="<?= (int) $section['class_id'] ?>"<?= $selectedSectionId === (int) $id ? ' selected' : '' ?>><?= View::escape($section['name']) ?></option><?php endforeach; ?></select></div>
        <div><label for="payment-student" class="mb-1.5 block text-sm">Student <span class="text-red-300">*</span></label><select id="payment-student" name="student_id" required class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"><option value="">Select a student…</option><?php foreach ($students as $option): ?><option value="<?= (int) $option['id'] ?>" data-class="<?= (int) $option['class_id'] ?>" data-section="<?= (int) $option['section_id'] ?>"<?= $student && (int) $student['id'] === (int) $option['id'] ? ' selected' : '' ?>><?= View::escape($option['name']) ?> · <?= View::escape($option['student_id']) ?><?= $option['class_name'] ? ' · ' . View::escape($option['class_name']) : '' ?><?= $option['section_name'] ? ' / ' . View::escape($option['section_name']) : '' ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="mt-4 flex justify-end"><button class="form-action-button">Load student</button></div>
</form>

<?php if ($student): ?>
<form method="post" action="<?= Url::to('/fees/new') ?>" id="payment-form" class="mt-5 space-y-5">
    <?= Csrf::field() ?><input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
    <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <div class="mb-5 flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full bg-teal-400/15 font-semibold text-teal-200">2</span><div><h2 class="font-semibold">Student and payment details</h2><p class="text-xs text-green-50/55">Confirm the student identity and payment date.</p></div></div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-green-100/10 bg-green-950/20 p-4 sm:col-span-2"><p class="text-xs uppercase tracking-wide text-green-50/45">Student</p><p class="mt-1 text-lg font-semibold"><?= View::escape($student['name']) ?></p><p class="mt-1 text-sm text-green-50/60">ID <?= View::escape($student['student_id']) ?> · Roll <?= View::escape($student['roll_no'] ?? '—') ?></p></div>
            <div class="rounded-xl border border-green-100/10 bg-green-950/20 p-4"><p class="text-xs uppercase tracking-wide text-green-50/45">Class / Section</p><p class="mt-2 font-medium"><?= View::escape($student['class_name'] ?? '—') ?><?= $student['section_name'] ? ' / ' . View::escape($student['section_name']) : '' ?></p></div>
            <div class="rounded-xl border border-green-100/10 bg-green-950/20 p-4"><p class="text-xs uppercase tracking-wide text-green-50/45">Academic year</p><p class="mt-2 font-medium"><?= View::escape($student['academic_year'] ?? '—') ?></p></div>
            <div><label for="payment-date" class="mb-1.5 block text-sm">Payment date</label><input id="payment-date" name="paid_on" type="date" value="<?= date('Y-m-d') ?>" required class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"></div>
            <div><label for="payment-method" class="mb-1.5 block text-sm">Payment method</label><select id="payment-method" name="payment_method" required class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"><option value="Cash">Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Cheque">Cheque</option><option value="Mobile Banking">Mobile Banking</option><option value="Card">Card</option><option value="Other">Other</option></select></div>
            <div class="sm:col-span-2"><label for="payment-reference" class="mb-1.5 block text-sm">Bank / transaction reference <span class="text-green-50/45">(optional)</span></label><input id="payment-reference" name="reference_no" maxlength="100" placeholder="Cheque number, bank reference, transaction ID…" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"></div>
        </div>
    </section>
    <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full bg-teal-400/15 font-semibold text-teal-200">3</span><div><h2 class="font-semibold">Outstanding fee lines</h2><p class="text-xs text-green-50/55">Enter how much of this payment should go to each invoice.</p></div></div><?php if ($invoices !== []): ?><label class="text-sm">Period <select id="period-filter" class="ml-2 rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2"><option value="">All periods</option><?php foreach (array_unique(array_filter(array_column($invoices, 'month'))) as $period): ?><option value="<?= View::escape((string) $period) ?>"><?= View::escape((string) $period) ?></option><?php endforeach; ?></select></label><?php endif; ?></div>
        <?php if ($invoices === []): ?>
            <div class="rounded-xl border border-dashed border-green-100/20 p-8 text-center"><p class="font-medium">No outstanding invoices for this student.</p><p class="mt-1 text-sm text-green-50/55">Generate fee invoices before recording a payment.</p><a href="<?= Url::to('/fees/generate') ?>" class="mt-4 inline-flex rounded-lg border border-green-100/20 px-4 py-2 text-sm">Generate invoices</a></div>
        <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-green-900/60"><table class="w-full min-w-[1000px] text-left text-sm"><thead class="bg-green-950/40 text-green-100/65"><tr><th class="px-4 py-3">Fee / Invoice</th><th class="px-4 py-3">Period</th><th class="px-4 py-3 text-right">Billed</th><th class="px-4 py-3 text-right">Already paid</th><th class="px-4 py-3 text-right">Prior discount</th><th class="px-4 py-3 text-right">Outstanding</th><th class="w-44 px-4 py-3 text-right">Approved discount</th><th class="w-44 px-4 py-3 text-right">Receive now</th></tr></thead><tbody class="divide-y divide-green-900/50">
        <?php foreach ($invoices as $invoice): $outstanding = max(0, (float) $invoice['amount'] - (float) $invoice['allocated'] - (float) $invoice['discounted']); ?>
            <tr data-invoice-row data-period="<?= View::escape((string) ($invoice['month'] ?? '')) ?>"><td class="px-4 py-3"><div class="font-medium"><?= View::escape($invoice['fee_head']) ?></div><div class="mt-1 font-mono text-xs text-green-50/45"><?= View::escape($invoice['invoice_no']) ?></div></td><td class="px-4 py-3"><?= View::escape($invoice['month'] ?: '—') ?></td><td class="px-4 py-3 text-right">৳<?= number_format((float) $invoice['amount'], 2) ?></td><td class="px-4 py-3 text-right">৳<?= number_format((float) $invoice['allocated'], 2) ?></td><td class="px-4 py-3 text-right">৳<?= number_format((float) $invoice['discounted'], 2) ?></td><td class="px-4 py-3 text-right font-semibold">৳<?= number_format($outstanding, 2) ?></td><td class="px-4 py-3"><?php if (\App\Core\Guard::can('accounts.manage')): ?><input type="number" min="0" max="<?= number_format($outstanding, 2, '.', '') ?>" step="0.01" name="discounts[<?= (int) $invoice['id'] ?>]" value="0.00" data-max="<?= number_format($outstanding, 2, '.', '') ?>" class="payment-discount w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2 text-right text-sm" aria-label="Approved discount for <?= View::escape($invoice['invoice_no']) ?>"><?php else: ?><span class="text-xs text-green-50/45">Accounts permission required</span><?php endif; ?></td><td class="px-4 py-3"><input type="number" min="0" max="<?= number_format($outstanding, 2, '.', '') ?>" step="0.01" name="allocations[<?= (int) $invoice['id'] ?>]" value="0.00" data-max="<?= number_format($outstanding, 2, '.', '') ?>" class="payment-allocation w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2 text-right text-sm" aria-label="Amount to receive for <?= View::escape($invoice['invoice_no']) ?>"></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><div class="rounded-xl border border-green-100/10 bg-green-950/20 p-4"><p class="text-xs text-green-50/55">Previous due</p><p class="mt-1 text-lg font-semibold">৳<?= number_format(array_sum(array_map(static fn ($invoice) => max(0, (float) $invoice['amount'] - (float) $invoice['allocated'] - (float) $invoice['discounted']), $invoices)), 2) ?></p></div><div class="rounded-xl border border-teal-300/20 bg-teal-400/10 p-4"><p class="text-xs text-teal-100/70">Deposit today</p><p id="payment-total" class="mt-1 text-lg font-bold text-teal-100">৳0.00</p></div><div class="rounded-xl border border-green-100/10 bg-green-950/20 p-4"><p class="text-xs text-green-50/55">Approved discounts</p><p id="discount-total" class="mt-1 text-lg font-semibold">৳0.00</p></div><div class="rounded-xl border border-green-100/10 bg-green-950/20 p-4"><p class="text-xs text-green-50/55">Due after payment</p><p id="payment-due" class="mt-1 text-lg font-semibold">৳0.00</p></div></div>
        <div class="mt-4"><label for="payment-notes" class="mb-1.5 block text-sm">Receipt note / discount approval reason <span class="text-green-50/45">(optional)</span></label><input id="payment-notes" name="notes" maxlength="255" placeholder="Reason is recorded with any approved discount" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm"></div>
        <p class="mt-4 rounded-xl border border-amber-200/15 bg-amber-950/25 p-3 text-xs text-amber-100/80">Bank Transfer and Cheque print as three copies on Legal paper. Other methods print as a single A4 receipt.</p>
        <div class="mt-5 flex flex-wrap justify-end gap-3"><a href="<?= Url::to('/fees') ?>" class="rounded-lg border border-green-100/20 px-5 py-2.5 text-sm">Cancel</a><button class="form-action-button" id="payment-submit" disabled>Save &amp; Print receipt</button></div>
        <?php endif; ?>
    </section>
</form>
<?php endif; ?>

<script>
(() => {
    const classSelect = document.getElementById('filter-class');
    const sectionSelect = document.getElementById('filter-section');
    const studentSelect = document.getElementById('payment-student');
    const syncStudents = () => {
        const classId = classSelect?.value || '';
        const sectionId = sectionSelect?.value || '';
        [...(studentSelect?.options || [])].forEach(option => {
            if (!option.value) return;
            option.hidden = (classId && option.dataset.class !== classId) || (sectionId && option.dataset.section !== sectionId);
            if (option.hidden && option.selected) studentSelect.value = '';
        });
    };
    classSelect?.addEventListener('change', () => { sectionSelect.value = ''; syncStudents(); });
    sectionSelect?.addEventListener('change', syncStudents);
    syncStudents();
    const inputs = [...document.querySelectorAll('.payment-allocation')];
    const discountInputs = [...document.querySelectorAll('.payment-discount')];
    const total = document.getElementById('payment-total');
    const discountTotal = document.getElementById('discount-total');
    const due = document.getElementById('payment-due');
    const submit = document.getElementById('payment-submit');
    const totalDue = inputs.reduce((sum, input) => sum + Math.round(Number(input.dataset.max) * 100), 0);
    const format = cents => `৳${(cents / 100).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
    const refresh = () => {
        let cents = 0, discountCents = 0;
        inputs.forEach(input => {
            let value = Math.max(0, Math.round((Number(input.value) || 0) * 100));
            const maximum = Math.round(Number(input.dataset.max) * 100);
            if (value > maximum) { value = maximum; input.value = (value / 100).toFixed(2); }
            cents += value;
        });
        discountInputs.forEach(input => {
            let value = Math.max(0, Math.round((Number(input.value) || 0) * 100));
            const maximum = Math.round(Number(input.dataset.max) * 100);
            const paymentInput = document.querySelector(`input[name="${input.name.replace('discounts[', 'allocations[')}"]`);
            const payment = Math.round((Number(paymentInput?.value) || 0) * 100);
            if (value + payment > maximum) { value = Math.max(0, maximum - payment); input.value = (value / 100).toFixed(2); }
            discountCents += value;
        });
        if (total) total.textContent = format(cents);
        if (discountTotal) discountTotal.textContent = format(discountCents);
        if (due) due.textContent = format(Math.max(0, totalDue - cents - discountCents));
        if (submit) submit.disabled = cents + discountCents === 0;
    };
    inputs.forEach(input => input.addEventListener('input', refresh));
    discountInputs.forEach(input => input.addEventListener('input', refresh));
    refresh();
    document.getElementById('period-filter')?.addEventListener('change', event => {
        document.querySelectorAll('[data-invoice-row]').forEach(row => { row.hidden = event.target.value !== '' && row.dataset.period !== event.target.value; });
    });
})();
</script>
