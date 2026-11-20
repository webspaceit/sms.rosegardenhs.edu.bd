<?php use App\Core\Csrf; use App\Core\Url; use App\Core\View; ?>
<div class="mx-auto max-w-4xl">
    <a href="<?= Url::to('/fees') ?>" class="text-sm font-medium text-green-300 hover:text-green-200">← Fee collections</a>
    <div class="mt-3 flex items-start gap-4">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border border-emerald-200/20 bg-emerald-400/10 text-emerald-200">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17M8 13h3m2 0h3m-8 3.5h3"/></svg>
        </div>
        <div><h1 class="text-3xl font-semibold">Generate fee invoices</h1><p class="mt-1 max-w-2xl text-sm leading-6 text-green-100/65">Choose an academic year and billing month. The system creates due invoices from each class fee structure and skips invoices already generated.</p></div>
    </div>

    <form method="post" action="<?= Url::to('/fees/generate') ?>" class="mt-7 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/85 shadow-xl shadow-black/10">
        <?= Csrf::field() ?>
        <div class="grid gap-6 p-5 sm:p-7">
            <div>
                <div class="mb-2 flex items-center gap-2"><span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-400/15 text-xs font-bold text-emerald-200">1</span><h2 class="font-semibold">Academic year</h2></div>
                <label for="fee-academic-year" class="sr-only">Academic year</label>
                <select id="fee-academic-year" required name="academic_year_id" class="block w-full rounded-xl border border-green-800/80 bg-[#062f2d] px-4 py-3 text-sm">
                    <?php foreach ($years as $year): ?><option value="<?= (int) $year['id'] ?>" <?= $year['is_current'] ? 'selected' : '' ?>><?= View::escape($year['name']) ?><?= $year['is_current'] ? ' · Current academic year' : '' ?></option><?php endforeach; ?>
                </select>
                <p class="mt-1.5 text-xs text-green-100/50">Only active students in this academic year are billed.</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-2xl border border-green-800/60 bg-[#062f2d]/65 p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2"><span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-400/15 text-xs font-bold text-emerald-200">2</span><h2 class="font-semibold">Billing month</h2></div>
                        <span class="rounded-full border border-green-100/10 bg-green-950/40 px-2.5 py-1 text-[11px] text-green-100/60">Required</span>
                    </div>
                    <div class="relative">
                        <label for="fee-billing-month" class="sr-only">Choose billing month</label>
                        <input id="fee-billing-month" type="month" name="month" required value="<?= date('Y-m') ?>" class="block min-h-14 w-full rounded-xl border border-green-700/80 bg-[#031f1e] px-4 py-3 text-lg font-semibold tracking-wide text-white shadow-inner shadow-black/15">
                        <button type="button" data-open-picker="fee-billing-month" aria-label="Open billing month calendar" class="absolute right-2 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-lg text-emerald-200 transition hover:bg-emerald-300/10 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17M8 13h3m2 0h3m-8 3.5h3"/></svg>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2" aria-label="Quick billing month selection">
                        <button type="button" data-month-offset="-1" class="rounded-lg border border-green-100/15 px-3 py-2 text-xs font-medium text-green-100/75 transition hover:border-green-100/30 hover:bg-green-100/5">‹ Previous month</button>
                        <button type="button" data-month-offset="0" class="rounded-lg border border-green-100/15 px-3 py-2 text-xs font-medium text-green-100/75 transition hover:border-green-100/30 hover:bg-green-100/5">This month</button>
                        <button type="button" data-month-offset="1" class="rounded-lg border border-green-100/15 px-3 py-2 text-xs font-medium text-green-100/75 transition hover:border-green-100/30 hover:bg-green-100/5">Next month ›</button>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-green-100/50">Monthly fee heads use this month. Yearly and one-time fee heads are included according to their fee structure.</p>
                </section>

                <section class="rounded-2xl border border-green-800/60 bg-[#062f2d]/65 p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2"><span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-400/15 text-xs font-bold text-emerald-200">3</span><h2 class="font-semibold">Invoice due date</h2></div>
                        <span class="rounded-full border border-green-100/10 bg-green-950/40 px-2.5 py-1 text-[11px] text-green-100/60">Optional</span>
                    </div>
                    <div class="relative">
                        <label for="fee-due-date" class="sr-only">Choose invoice due date</label>
                        <input id="fee-due-date" type="date" name="due_date" class="block min-h-14 w-full rounded-xl border border-green-700/80 bg-[#031f1e] px-4 py-3 text-base font-semibold tracking-wide text-white shadow-inner shadow-black/15">
                        <button type="button" data-open-picker="fee-due-date" aria-label="Open due date calendar" class="absolute right-2 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-lg text-emerald-200 transition hover:bg-emerald-300/10 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17M8 13h3m2 0h3m-8 3.5h3"/></svg>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2" aria-label="Quick due date selection">
                        <button type="button" data-due-preset="month-end" class="rounded-lg border border-green-100/15 px-3 py-2 text-xs font-medium text-green-100/75 transition hover:border-green-100/30 hover:bg-green-100/5">Billing month end</button>
                        <button type="button" data-due-preset="next-7" class="rounded-lg border border-green-100/15 px-3 py-2 text-xs font-medium text-green-100/75 transition hover:border-green-100/30 hover:bg-green-100/5">Next month · 7th</button>
                        <button type="button" data-due-preset="next-14" class="rounded-lg border border-green-100/15 px-3 py-2 text-xs font-medium text-green-100/75 transition hover:border-green-100/30 hover:bg-green-100/5">Next month · 14th</button>
                        <button type="button" data-due-preset="clear" class="rounded-lg px-2 py-2 text-xs text-green-100/50 transition hover:text-green-100">Clear</button>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-green-100/50">Choose a date from the calendar or use a quick option. Leave blank if invoices should have no due date.</p>
                </section>
            </div>

            <div class="flex flex-col gap-4 border-t border-green-900/60 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3 text-xs leading-5 text-green-100/55"><svg aria-hidden="true" viewBox="0 0 24 24" class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg><p>Existing invoices for this academic year and month are safely skipped if you generate again.</p></div>
                <div class="flex shrink-0 justify-end gap-3"><a href="<?= Url::to('/fees') ?>" class="rounded-xl border border-green-100/15 px-5 py-3 text-sm font-semibold text-green-50/80 transition hover:bg-green-100/5">Cancel</a><button class="form-action-button">Generate invoices</button></div>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const monthInput = document.getElementById('fee-billing-month');
    const dueInput = document.getElementById('fee-due-date');
    const openPicker = (input) => {
        if (typeof input.showPicker === 'function') input.showPicker();
        else input.focus();
    };
    document.querySelectorAll('[data-open-picker]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.openPicker);
            if (input) openPicker(input);
        });
    });
    const toMonthValue = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    document.querySelectorAll('[data-month-offset]').forEach(button => {
        button.addEventListener('click', () => {
            const current = /^\d{4}-\d{2}$/.test(monthInput.value) ? new Date(`${monthInput.value}-01T12:00:00`) : new Date();
            current.setMonth(current.getMonth() + Number(button.dataset.monthOffset));
            monthInput.value = toMonthValue(current);
            monthInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    document.querySelectorAll('[data-due-preset]').forEach(button => {
        button.addEventListener('click', () => {
            const preset = button.dataset.duePreset;
            if (preset === 'clear') { dueInput.value = ''; return; }
            const selected = /^\d{4}-\d{2}$/.test(monthInput.value) ? new Date(`${monthInput.value}-01T12:00:00`) : new Date();
            const year = selected.getFullYear();
            const month = selected.getMonth();
            let date;
            if (preset === 'month-end') date = new Date(year, month + 1, 0);
            else date = new Date(year, month + 1, preset === 'next-7' ? 7 : 14);
            dueInput.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        });
    });
})();
</script>
