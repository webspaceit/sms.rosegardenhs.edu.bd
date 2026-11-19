<?php use App\Core\Csrf; use App\Core\Url; use App\Core\View; ?>
<div class="flex flex-wrap items-start justify-between gap-4">
    <div><h1 class="text-3xl font-semibold">SMS Center</h1><p class="mt-1 text-sm text-white">Send messages through your configured SMS gateway.</p></div>
    <a class="form-action-button" href="<?= Url::to('/sms-settings') ?>">Gateway settings</a>
</div>

<section class="mt-6 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-lg font-semibold">Gateway status</h2><p class="mt-1 text-sm text-green-100/65"><?= $gateway && (int) $gateway['is_active'] === 1 ? 'Active provider: ' . View::escape($gateway['provider']) . ' · Sender ID: ' . View::escape($gateway['sender_id'] ?: 'not set') : 'No active SMS provider is configured.' ?></p></div>
        <span class="rounded-full border px-3 py-1 text-xs font-semibold <?= $gateway && (int) $gateway['is_active'] === 1 && trim((string) $gateway['api_key']) !== '' ? 'border-emerald-300/25 bg-emerald-400/10 text-emerald-200' : 'border-amber-300/25 bg-amber-400/10 text-amber-100' ?>"><?= $gateway && (int) $gateway['is_active'] === 1 && trim((string) $gateway['api_key']) !== '' ? 'Ready to send' : 'Setup required' ?></span>
    </div>
    <?php if (!$gateway || (int) $gateway['is_active'] !== 1 || trim((string) $gateway['api_key']) === '' || trim((string) ($gateway['sender_id'] ?? '')) === ''): ?>
        <p class="mt-4 rounded-xl border border-amber-200/15 bg-amber-950/25 p-3 text-sm text-amber-100/85">Add an active <strong>MRAM SMS</strong> or <strong>BulkSMSBD</strong> provider, API key, and approved sender ID in Gateway settings. The endpoint can be left blank to use the provider default.</p>
    <?php endif; ?>
</section>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7 xl:col-span-2">
        <h2 class="text-lg font-semibold">Compose SMS</h2>
        <p class="mt-1 text-sm text-green-100/65">Numbers are normalized to international format and deduplicated before sending.</p>
        <form method="post" action="<?= Url::to('/sms-center/send') ?>" class="mt-5 space-y-4">
            <?= Csrf::field() ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="sms-audience" class="mb-1.5 block text-sm text-slate-200">Recipients</label>
                    <select id="sms-audience" name="audience" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm text-slate-100" required>
                        <option value="custom">Enter mobile numbers</option>
                        <option value="students">Active students’ mobile numbers</option>
                        <option value="guardians">Students’ linked guardian numbers</option>
                        <option value="teachers">Teachers’ mobile numbers</option>
                    </select>
                </div>
                <div id="sms-class-wrap" hidden>
                    <label for="sms-class" class="mb-1.5 block text-sm text-slate-200">Class (optional)</label>
                    <select id="sms-class" name="class_id" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm text-slate-100">
                        <option value="0">All classes</option>
                        <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"><?= View::escape($class['name']) ?></option><?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-green-100/55">Student and guardian groups use the selected academic session<?= !empty($academicYear['name']) ? ' (' . View::escape($academicYear['name']) . ')' : '' ?>.</p>
                </div>
            </div>
            <div id="sms-recipient-preview" hidden class="rounded-xl border border-green-800/70 bg-[#062f2d]/70 p-3" data-preview-url="<?= View::escape(Url::to('/sms-center/recipients')) ?>">
                <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-medium text-slate-100">Selected mobile numbers</p><p id="sms-recipient-count" class="text-xs text-green-100/65">Loading recipients…</p></div>
                <textarea id="sms-recipient-numbers" rows="4" readonly aria-label="Selected recipient mobile numbers" class="mt-2 w-full resize-y rounded-lg border border-green-900/60 bg-[#031f1e] px-3 py-2 text-xs leading-5 text-green-100 placeholder:text-green-100/45"></textarea>
                <p id="sms-recipient-help" class="mt-1 text-xs text-green-100/55">These numbers will receive the message. Numbers are deduplicated and shown in international format.</p>
            </div>
            <div id="sms-numbers-wrap">
                <label for="sms-numbers" class="mb-1.5 block text-sm text-slate-200">Mobile numbers</label>
                <textarea id="sms-numbers" name="numbers" rows="3" placeholder="017XXXXXXXX, 018XXXXXXXX" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-400"></textarea>
                <p class="mt-1 text-xs text-green-100/55">For manual recipients, separate Bangladeshi numbers with commas, spaces, or new lines.</p>
            </div>
            <div>
                <div class="mb-1.5 flex items-center justify-between gap-2"><label for="sms-message" class="block text-sm text-slate-200">Message</label><span id="sms-character-count" class="text-xs text-green-100/55">0 / 1,000</span></div>
                <textarea id="sms-message" name="message" rows="5" maxlength="1000" required placeholder="Write your message…" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-400"></textarea>
                <p class="mt-1 text-xs text-green-100/55">Gateway SMS charges may apply. Accepted by the gateway means submitted for delivery, not confirmed delivered.</p>
            </div>
            <label class="flex items-start gap-2 text-sm text-amber-100/85"><input type="checkbox" name="confirm_cost" value="1" required class="mt-1 h-4 w-4 accent-emerald-400">I have reviewed the selected recipient group and understand sending SMS may use paid credits.</label>
            <button type="submit" class="form-action-button">Send SMS</button>
        </form>
    </section>

    <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <h2 class="text-lg font-semibold">Test gateway</h2>
        <p class="mt-1 text-sm text-green-100/65">Send one short test message to verify your credentials and sender ID.</p>
        <form method="post" action="<?= Url::to('/sms-center/test') ?>" class="mt-5 space-y-4">
            <?= Csrf::field() ?>
            <div><label for="sms-test-number" class="mb-1.5 block text-sm text-slate-200">Test mobile number</label><input id="sms-test-number" name="test_number" type="tel" inputmode="tel" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" required class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-400"></div>
            <p class="rounded-xl border border-amber-200/15 bg-amber-950/25 p-3 text-xs text-amber-100/80">This sends a real SMS and may consume gateway credits.</p>
            <button type="submit" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm font-semibold text-green-50 hover:border-green-200/40">Send test SMS</button>
        </form>
    </section>
</div>

<script>
(() => {
    const audience = document.getElementById('sms-audience');
    const classWrap = document.getElementById('sms-class-wrap');
    const numbersWrap = document.getElementById('sms-numbers-wrap');
    const numbers = document.getElementById('sms-numbers');
    const message = document.getElementById('sms-message');
    const counter = document.getElementById('sms-character-count');
    const preview = document.getElementById('sms-recipient-preview');
    const previewCount = document.getElementById('sms-recipient-count');
    const previewNumbers = document.getElementById('sms-recipient-numbers');
    const previewUrl = preview.dataset.previewUrl;
    let previewController;
    const update = () => {
        const manual = audience.value === 'custom';
        numbersWrap.hidden = !manual;
        numbers.required = manual;
        classWrap.hidden = !['students', 'guardians'].includes(audience.value);
        preview.hidden = manual;
        if (!manual) loadRecipients();
    };
    const loadRecipients = async () => {
        if (previewController) previewController.abort();
        previewController = new AbortController();
        previewCount.textContent = 'Loading recipients…';
        previewNumbers.value = '';
        const params = new URLSearchParams({ audience: audience.value });
        const classId = document.getElementById('sms-class').value;
        if (classId) params.set('class_id', classId);
        try {
            const response = await fetch(`${previewUrl}?${params}`, { headers: { Accept: 'application/json' }, signal: previewController.signal });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Could not load recipients.');
            previewNumbers.value = (data.numbers || []).join('\n');
            previewCount.textContent = `${data.count || 0} recipient${data.count === 1 ? '' : 's'}`;
            if (!data.count) previewNumbers.placeholder = 'No valid mobile numbers found for this selection.';
            else previewNumbers.placeholder = '';
        } catch (error) {
            if (error.name === 'AbortError') return;
            previewCount.textContent = 'Unable to load recipients';
            previewNumbers.placeholder = error.message;
        }
    };
    audience.addEventListener('change', update);
    document.getElementById('sms-class').addEventListener('change', loadRecipients);
    message.addEventListener('input', () => { counter.textContent = `${message.value.length} / 1,000`; });
    update();
})();
</script>
