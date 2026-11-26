<?php
/**
 * Generic module form.
 *
 * @var array<string, mixed> $module
 * @var array<string, mixed>|null $record
 */
use App\Core\Crud;
use App\Core\Url;
use App\Core\View;
use App\Models\Catalogue;

$editing = $record !== null;
$formColumns = max(1, min(4, (int) ($module['formColumns'] ?? 2)));
$gridClass = $formColumns >= 4 ? 'grid gap-5 sm:grid-cols-2 xl:grid-cols-4' : ($formColumns >= 3 ? 'grid gap-5 sm:grid-cols-2 xl:grid-cols-3' : 'grid gap-5 sm:grid-cols-2');
$wideSpan = $formColumns >= 3 ? 'sm:col-span-2 xl:col-span-' . $formColumns : 'sm:col-span-2';
$hasFormattedDate = count(array_filter($module['fields'], static fn (array $field): bool => ($field['type'] ?? '') === 'date' && ($field['displayFormat'] ?? '') === 'd-F-Y')) > 0;
$value = static function (string $name, mixed $fallback = '') use ($record): mixed {
    return $record !== null && array_key_exists($name, $record) && $record[$name] !== null
        ? $record[$name]
        : $fallback;
};
?>
<?php if ($hasFormattedDate): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
.flatpickr-calendar{width:306px!important;border:1px solid #dce5e9!important;border-radius:12px!important;background:#fff!important;box-shadow:0 8px 24px rgba(15,45,60,.12)!important;font-family:inherit!important;overflow:hidden}
.flatpickr-months{height:38px!important;align-items:center}
.flatpickr-months .flatpickr-month{height:38px!important;color:#075b4b!important;fill:#075b4b!important}
.flatpickr-current-month{padding-top:7px!important;font-size:18px!important;font-weight:600!important;color:#075b4b!important}
.flatpickr-current-month .flatpickr-monthDropdown-months{background:transparent!important;background-color:transparent!important;box-shadow:none!important;font-size:18px!important;font-weight:600!important;color:#075b4b!important}
.flatpickr-current-month input.cur-year{background:transparent!important;background-color:transparent!important;box-shadow:none!important;font-size:18px!important;font-weight:600!important;color:#075b4b!important}
.flatpickr-current-month .flatpickr-monthDropdown-months:hover,.flatpickr-current-month input.cur-year:hover{background:transparent!important}
.flatpickr-months .flatpickr-prev-month,.flatpickr-months .flatpickr-next-month{top:7px!important;color:#64748b!important;fill:#64748b!important}
.flatpickr-weekdays{height:31px!important;background:#fff!important}
span.flatpickr-weekday{height:31px!important;line-height:31px!important;background:#fff!important;color:#64748b!important;font-size:13px!important;font-weight:600!important}
.flatpickr-days,.dayContainer{width:100%!important;min-width:100%!important;max-width:100%!important}
.dayContainer{padding:0 5px 6px!important}
.flatpickr-day{flex-basis:14.285714%!important;width:14.285714%!important;max-width:none!important;height:39px!important;line-height:37px!important;border-radius:9px!important;color:#40536d!important;font-size:14px!important}
.flatpickr-day:hover{background:#f1f8f6!important;border-color:#f1f8f6!important}
.flatpickr-day.today{border-color:#34b99a!important;color:#40536d!important}
.flatpickr-day.selected,.flatpickr-day.selected:hover{background:#08785f!important;border-color:#08785f!important;color:#fff!important}
.flatpickr-day.prevMonthDay,.flatpickr-day.nextMonthDay,.flatpickr-day.flatpickr-disabled{color:#cbd5e1!important}
.flatpickr-calendar.arrowTop:before,.flatpickr-calendar.arrowTop:after{display:none!important}
.dashboard-shell main form button[data-open-date-picker]:not([class*="text-red-200"]):not([class*="text-rose-200"]){display:grid!important;min-width:2rem!important;width:2rem!important;height:2rem!important;min-height:2rem!important;place-items:center!important;gap:0!important;padding:0!important;border:0!important;border-radius:.5rem!important;background:transparent!important;color:#10a878!important}
.dashboard-shell main form button[data-open-date-picker]:not([class*="text-red-200"]):not([class*="text-rose-200"])::before,.dashboard-shell main form button[data-open-date-picker]:not([class*="text-red-200"]):not([class*="text-rose-200"])::after{display:none!important;content:none!important}
.dashboard-shell main form button[data-open-date-picker]:not([class*="text-red-200"]):not([class*="text-rose-200"]):hover{background:rgba(16,168,120,.1)!important;box-shadow:none!important}
</style>
<?php endif; ?>
<div class="<?= !empty($module['formFullWidth']) ? 'w-full max-w-none' : 'mx-auto max-w-3xl' ?>">
    <a href="<?= Url::to($module['route']) ?>" class="text-sm text-teal-300 hover:text-teal-200">← <?= View::escape($module['title']) ?></a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $editing ? 'Edit' : 'New' ?> <?= View::escape($module['singular']) ?></h1>
    <p class="mt-1 text-sm text-white"><?= $editing ? 'Update this record.' : 'Add a new ' . View::escape($module['singular']) . ' record.' ?></p>

    <form method="post" enctype="multipart/form-data" action="<?= Url::to($editing ? $module['route'] . '/update' : $module['route']) ?>" class="mt-7 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><?php endif; ?>

        <div class="<?= $gridClass ?>">
        <?php foreach ($module['fields'] as $field): ?>
            <?php
            $name = (string) $field['name'];
            $type = (string) ($field['type'] ?? 'text');
            $label = (string) $field['label'];
            $required = (bool) ($field['required'] ?? false);
            $hint = (string) ($field['hint'] ?? '');
            $autoGenerate = $field['autoGenerate'] ?? null;
            $autoValue = !$editing && is_callable($autoGenerate) ? $autoGenerate() : null;
            $current = $value($name, $autoValue ?? ($field['default'] ?? ''));
            $autoGenerated = !$editing && is_callable($autoGenerate);
            $readOnly = !empty($field['readOnly']) || ($editing && !empty($field['readOnlyOnEdit']));
            $formattedDate = $type === 'date' && ($field['displayFormat'] ?? '') === 'd-F-Y';
            $dateIsoValue = '';
            $dateDisplayValue = '';
            if ($formattedDate && $current !== '') {
                try {
                    $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $current);
                    if ($dateValue !== false) {
                        $dateIsoValue = $dateValue->format('Y-m-d');
                        $dateDisplayValue = $dateValue->format('d-F-Y');
                    }
                } catch (Throwable) {
                    // Leave malformed legacy dates blank so they can be corrected.
                }
            }
            $wide = (bool) ($field['wide'] ?? in_array($type, ['textarea', 'multiselect'], true));
            ?>
            <div class="<?= $wide ? $wideSpan : '' ?>">
                <label for="field-<?= View::escape($name) ?>" class="mb-1.5 block text-sm text-slate-300">
                    <?= View::escape($label) ?><?= $required ? ' <span class="text-rose-300">*</span>' : '' ?>
                </label>

                <?php if ($type === 'textarea'): ?>
                    <textarea id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>"<?= $required ? ' required' : '' ?><?= isset($field['rows']) ? ' rows="' . (int) $field['rows'] . '"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400"><?= View::escape((string) $current) ?></textarea>

                <?php elseif ($type === 'select'): ?>
                    <?php
                    $options = Crud::fieldOptions($field);
                    if (!empty($field['preserveExisting']) && $editing && $current !== '' && !array_key_exists((string) $current, $options)) {
                        $legacyDesignation = Catalogue::designationById((int) $current);
                        if ($legacyDesignation !== null) $options[(int) $current] = $legacyDesignation;
                    }
                    ?>
                    <?php
                    $otherInput = (string) ($field['otherInput'] ?? '');
                    $otherValue = (string) ($field['otherValue'] ?? 'Other');
                    $qualificationIsOther = $otherInput !== '' && $current !== '' && !array_key_exists((string) $current, $options);
                    $selectCurrent = $qualificationIsOther ? $otherValue : (string) $current;
                    ?>
                    <select id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>"<?= $required ? ' required' : '' ?><?= $otherInput !== '' ? ' data-other-select="other-' . View::escape($name) . '"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400">
                        <?php if (!$required): ?><option value="">Select…</option><?php endif; ?>
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <option value="<?= View::escape((string) $optionValue) ?>"<?= $selectCurrent === (string) $optionValue ? ' selected' : '' ?>><?= View::escape((string) $optionLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($otherInput !== ''): ?>
                        <input id="other-<?= View::escape($name) ?>" name="<?= View::escape($otherInput) ?>" type="text" value="<?= View::escape($qualificationIsOther ? (string) $current : '') ?>" placeholder="<?= View::escape((string) ($field['otherLabel'] ?? 'Enter other ' . strtolower($label))) ?>" maxlength="<?= (int) ($field['otherMax'] ?? 150) ?>"<?= $qualificationIsOther ? '' : ' hidden' ?> class="mt-2 w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400">
                    <?php endif; ?>

                <?php elseif ($type === 'multiselect'): ?>
                    <?php $options = Crud::fieldOptions($field); $selected = array_map('strval', explode(',', (string) $current)); ?>
                    <div class="space-y-1.5 rounded-lg border border-green-800/80 bg-[#062f2d] p-3">
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <label class="flex items-center gap-2.5 text-sm text-slate-300">
                                <input type="checkbox" name="<?= View::escape($name) ?>[]" value="<?= View::escape((string) $optionValue) ?>"<?= in_array((string) $optionValue, $selected, true) ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-teal-400 focus:ring-teal-400">
                                <?= View::escape((string) $optionLabel) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($type === 'checkbox'): ?>
                    <label class="flex items-center gap-2.5 rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm text-slate-300">
                        <input type="checkbox" name="<?= View::escape($name) ?>" value="1"<?= (int) $current === 1 ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-teal-400 focus:ring-teal-400">
                        <?= View::escape((string) ($field['checkboxLabel'] ?? $label)) ?>
                    </label>

                <?php elseif ($type === 'file'): ?>
                    <?php if (!empty($field['image'])): ?>
                        <div class="flex items-center gap-3">
                            <input id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" type="file"<?= $required && !$editing ? ' required' : '' ?> accept="<?= View::escape(implode(',', array_map(static fn ($extension) => '.' . $extension, $field['extensions'] ?? []))) ?>" data-image-preview="preview-<?= View::escape($name) ?>" class="min-w-0 flex-1 rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm">
                            <img id="preview-<?= View::escape($name) ?>" src="<?= $current ? Url::to('/uploads/file?path=' . rawurlencode((string) $current)) : '' ?>" alt="Photo preview" class="h-12 w-12 shrink-0 rounded-lg border border-green-800/70 object-cover<?= $current ? '' : ' hidden' ?>">
                            <?php if (!empty($field['deletable'])): ?>
                                <input type="hidden" name="remove_<?= View::escape($name) ?>" value="0" data-image-remove="preview-<?= View::escape($name) ?>">
                                <button type="button" data-image-delete-button="preview-<?= View::escape($name) ?>" title="<?= $current ? 'Delete photo from server' : 'Remove selected photo' ?>" aria-label="<?= $current ? 'Delete photo from server' : 'Remove selected photo' ?>" class="h-10 w-10 shrink-0 rounded-lg border border-red-900/70 text-red-200 hover:bg-red-950/50<?= $current ? '' : ' hidden' ?>">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="mx-auto h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($current): ?><p id="photo-message-<?= View::escape($name) ?>" class="mt-1.5 text-xs text-slate-400">Current photo. Choose a file to replace it or use the delete icon to remove it from the server.</p><?php endif; ?>
                    <?php else: ?>
                        <input id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" type="file"<?= $required && !$editing ? ' required' : '' ?> accept="<?= View::escape(implode(',', array_map(static fn ($extension) => '.' . $extension, $field['extensions'] ?? []))) ?>" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm">
                        <?php if ($current): ?><p class="mt-1.5 text-xs text-slate-400">Current file: <a class="text-teal-300 underline" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $current)) ?>">View file</a>. Leave blank to keep it.</p><?php endif; ?>
                    <?php endif; ?>
                <?php elseif ($formattedDate): ?>
                    <div class="relative">
                        <input id="field-<?= View::escape($name) ?>-display" type="text" value="<?= View::escape($dateDisplayValue) ?>" placeholder="DD-Month-YYYY" autocomplete="off"<?= $required ? ' required' : '' ?> data-formatted-date="<?= View::escape($name) ?>" aria-label="<?= View::escape($label) ?> in day-month-year format" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 pr-12 text-sm outline-none focus:border-teal-400">
                        <input id="field-<?= View::escape($name) ?>-value" type="hidden" name="<?= View::escape($name) ?>" value="<?= View::escape($dateIsoValue) ?>" data-date-value="<?= View::escape($name) ?>">
                        <button type="button" aria-label="Choose <?= View::escape(strtolower($label)) ?>" data-open-date-picker="<?= View::escape($name) ?>" class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-teal-200 hover:bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg></button>
                    </div>
                <?php else: ?>
                    <?php $isPhone = str_contains(strtolower($name), 'phone') || str_contains(strtolower($name), 'mobile'); ?>
                    <input id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" type="<?= $isPhone ? 'tel' : View::escape($type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text')) ?>" value="<?= View::escape((string) $current) ?>"<?= $required ? ' required' : '' ?><?= $autoGenerated || $readOnly ? ' readonly' : '' ?><?= $isPhone ? ' inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." placeholder="01XXXXXXXXX or +8801XXXXXXXXX"' : (isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '') ?><?= isset($field['step']) ? ' step="' . View::escape((string) $field['step']) . '"' : '' ?><?= !$isPhone && isset($field['placeholder']) ? ' placeholder="' . View::escape((string) $field['placeholder']) . '"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400<?= $autoGenerated || $readOnly ? ' cursor-not-allowed opacity-80' : '' ?>">
                <?php endif; ?>

                <?php if ($hint !== ''): ?><p class="mt-1.5 text-xs text-slate-500"><?= View::escape($hint) ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>

        <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-green-900/60 pt-5">
            <a href="<?= Url::to($module['route']) ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button class="form-action-button"><?= $editing ? 'Save changes' : 'Save ' . View::escape($module['singular']) ?></button>
        </div>
    </form>
</div>
<?php if ($hasFormattedDate): ?><script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script><?php endif; ?>
<script>
document.querySelectorAll('input[data-image-preview]').forEach(input => {
    const preview = document.getElementById(input.dataset.imagePreview);
    const deleteButton = document.querySelector(`[data-image-delete-button="${input.dataset.imagePreview}"]`);
    const removeInput = document.querySelector(`[data-image-remove="${input.dataset.imagePreview}"]`);
    const message = document.getElementById(`photo-message-${input.name}`);
    const originalSrc = preview?.getAttribute('src') || '';

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/') || !preview) return;
        if (removeInput) removeInput.value = '0';
        if (message) message.textContent = 'New photo selected. The old server photo will be removed when you save.';
        if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
        const objectUrl = URL.createObjectURL(file);
        preview.dataset.objectUrl = objectUrl;
        preview.src = objectUrl;
        preview.classList.remove('hidden');
        deleteButton?.classList.remove('hidden');
    });

    deleteButton?.addEventListener('click', () => {
        input.value = '';
        if (preview?.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
        if (preview) {
            preview.removeAttribute('src');
            preview.classList.add('hidden');
        }
        const hasServerPhoto = Boolean(originalSrc);
        if (removeInput) removeInput.value = hasServerPhoto ? '1' : '0';
        deleteButton.classList.add('hidden');
        if (message) message.textContent = hasServerPhoto ? 'Photo will be deleted from the server when you save.' : 'Selected photo removed.';
    });
});

document.querySelectorAll('select[data-other-select]').forEach(select => {
    const otherField = document.getElementById(select.dataset.otherSelect);
    if (!otherField) return;
    const toggleOtherField = () => {
        const show = select.value === 'Other';
        otherField.hidden = !show;
        otherField.required = show;
        if (!show) otherField.value = '';
    };
    select.addEventListener('change', toggleOtherField);
    toggleOtherField();
});

document.querySelectorAll('input[data-formatted-date]').forEach(display => {
    const name = display.dataset.formattedDate;
    const value = document.querySelector(`[data-date-value="${name}"]`);
    if (!window.flatpickr || !value) return;
    const picker = flatpickr(display, {
        dateFormat: 'd-F-Y',
        allowInput: true,
        disableMobile: true,
        monthSelectorType: 'dropdown',
        onChange: dates => {
            value.value = dates.length ? picker.formatDate(dates[0], 'Y-m-d') : '';
            display.setCustomValidity('');
        },
    });
    if (value.value) picker.setDate(value.value, false, 'Y-m-d');
    const syncTypedDate = () => {
        if (!display.value.trim()) {
            value.value = '';
            display.setCustomValidity('');
            return;
        }
        const parsed = picker.parseDate(display.value, 'd-F-Y');
        if (parsed) {
            value.value = picker.formatDate(parsed, 'Y-m-d');
            display.value = picker.formatDate(parsed, 'd-F-Y');
            display.setCustomValidity('');
        } else {
            value.value = '';
            display.setCustomValidity('Enter the date as DD-Month-YYYY, for example 26-November-2026.');
        }
    };
    display.addEventListener('change', syncTypedDate);
    display.addEventListener('blur', syncTypedDate);
    document.querySelector(`[data-open-date-picker="${name}"]`)?.addEventListener('click', () => picker.open());
});
</script>
