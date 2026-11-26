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
$hasRoutineTime = count(array_filter($module['fields'], static fn (array $field): bool => ($field['type'] ?? '') === 'time' && !empty($field['timeRole']))) > 0;
$value = static function (string $name, mixed $fallback = '') use ($record): mixed {
    return $record !== null && array_key_exists($name, $record) && $record[$name] !== null
        ? $record[$name]
        : $fallback;
};
?>
<?php if ($hasFormattedDate || $hasRoutineTime): ?>
<style>
<?php if ($hasFormattedDate): ?>
.flatpickr-calendar{width:306px!important;border:1px solid #dce5e9!important;border-radius:12px!important;background:#fff!important;box-shadow:0 8px 24px rgba(15,45,60,.12)!important;font-family:inherit!important;overflow:hidden}
.flatpickr-months{height:38px!important;align-items:center}
.flatpickr-months .flatpickr-month{height:38px!important;color:#075b4b!important;fill:#075b4b!important}
.flatpickr-current-month{padding-top:7px!important;font-size:18px!important;font-weight:600!important;color:#075b4b!important}
.flatpickr-current-month .flatpickr-monthDropdown-months{width:116px!important;height:27px!important;border:1px solid transparent!important;border-radius:6px!important;background:transparent!important;background-color:transparent!important;box-shadow:none!important;padding:1px 13px 1px 5px!important;font-size:15px!important;font-weight:600!important;line-height:1.2!important;color:#075b4b!important}
.flatpickr-current-month .flatpickr-monthDropdown-months option{background:#fff!important;color:#334155!important;padding:4px 8px!important;font-size:13px!important;line-height:1.2!important}
.flatpickr-current-month input.cur-year{background:transparent!important;background-color:transparent!important;box-shadow:none!important;font-size:16px!important;font-weight:600!important;color:#075b4b!important}
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
<?php endif; ?>
<?php if ($hasRoutineTime): ?>
.routine-time-picker{position:relative}
.routine-time-popup{position:absolute;z-index:30;top:calc(100% + 7px);left:0;display:flex;width:min(340px,calc(100vw - 32px));flex-direction:column;gap:14px;padding:16px;border:1px solid rgba(94,234,212,.3);border-radius:16px;background:#062F2D;color:#f0fdfa;box-shadow:0 18px 38px rgba(0,0,0,.42)}
.routine-time-popup[hidden]{display:none}
.routine-time-popup-head,.routine-time-popup-foot{display:flex;align-items:center;justify-content:space-between;gap:12px}
.routine-time-popup-head{padding-bottom:10px;border-bottom:1px solid rgba(167,243,208,.14)}
.routine-time-popup-title{font-size:13px;font-weight:700;letter-spacing:.01em}
.routine-time-current{border:1px solid rgba(45,212,191,.22);border-radius:999px;background:rgba(13,148,136,.16);padding:4px 9px;color:#99f6e4;font-size:12px;font-variant-numeric:tabular-nums}
.routine-time-columns{display:grid;grid-template-columns:1fr 1.15fr .9fr;gap:10px}
.routine-time-column{min-width:0}
.routine-time-column-label{display:block;margin:0 0 6px;color:#99bcb4;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.routine-time-select{display:block;width:100%;min-width:0;height:42px;border:1px solid rgba(167,243,208,.2);border-radius:9px;background:#082f2d;color:#f0fdfa;padding:0 10px;font-size:14px;font-variant-numeric:tabular-nums;outline:none}
.routine-time-select:focus{border-color:#2dd4bf;box-shadow:0 0 0 2px rgba(45,212,191,.14)}
.routine-time-popup-foot{padding-top:10px;border-top:1px solid rgba(167,243,208,.14)}
.routine-time-hint{color:#86a9a1;font-size:11px}
.routine-time-actions{display:flex;align-items:center;gap:8px}
.routine-time-now,.routine-time-done{border:1px solid rgba(94,234,212,.35);border-radius:8px;padding:7px 12px;color:#fff;font-size:12px;font-weight:700;cursor:pointer;transition:background .12s}
.routine-time-now{background:transparent;color:#99f6e4}
.routine-time-done{background:#0e766e}
.routine-time-now:hover{background:rgba(45,212,191,.12)}
.routine-time-done:hover{background:#119487}
.dashboard-shell main form .routine-time-picker button[data-time-toggle]{display:flex!important;min-width:0!important;width:100%!important;height:auto!important;min-height:46px!important;justify-content:space-between!important;gap:12px!important;padding:.625rem .75rem!important;border:1px solid rgba(187,247,208,.28)!important;border-radius:.75rem!important;background:#062F2D!important;color:#f0fdfa!important;font-size:.875rem!important;font-weight:500!important}
.dashboard-shell main form .routine-time-picker button[data-time-toggle]::before,.dashboard-shell main form .routine-time-picker button[data-time-toggle]::after,.dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]).routine-time-now::before,.dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]).routine-time-now::after,.dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]).routine-time-done::before,.dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]).routine-time-done::after{display:none!important;width:0!important;height:0!important;margin:0!important;padding:0!important;border:0!important;content:none!important;box-shadow:none!important}
.dashboard-shell main form .routine-time-popup button.routine-time-now,.dashboard-shell main form .routine-time-popup button.routine-time-done{display:inline-flex!important;width:auto!important;min-width:0!important;height:auto!important;min-height:34px!important;align-items:center!important;justify-content:center!important;gap:0!important;margin:0!important;padding:7px 12px!important;border-width:1px!important;border-radius:8px!important;font-size:12px!important;font-weight:700!important}
.dashboard-shell main form .routine-time-popup button.routine-time-now{background:transparent!important;color:#99f6e4!important}
.dashboard-shell main form .routine-time-popup button.routine-time-done{background:#0e766e!important;color:#fff!important}
.dashboard-shell main form .routine-time-popup button.routine-time-now:hover{background:rgba(45,212,191,.12)!important}
.dashboard-shell main form .routine-time-popup button.routine-time-done:hover{background:#119487!important}
<?php endif; ?>
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
            $inputValue = $type === 'time' && is_string($current) ? substr($current, 0, 5) : (string) $current;
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
                <?php elseif ($type === 'time' && !empty($field['timeRole'])): ?>
                    <?php
                    $timeParts = $inputValue !== '' ? explode(':', $inputValue) : ['09', '00'];
                    $hour24 = (int) ($timeParts[0] ?? 9);
                    $minute = (int) ($timeParts[1] ?? 0);
                    $period = $hour24 >= 12 ? 'PM' : 'AM';
                    $hour12 = $hour24 % 12 ?: 12;
                    $timeDisplay = sprintf('%02d:%02d %s', $hour12, $minute, $period);
                    $timeSelectedHour = $inputValue !== '' ? $hour12 : 9;
                    $timeSelectedMinute = $inputValue !== '' ? $minute : 0;
                    $timeSelectedPeriod = $inputValue !== '' ? $period : 'AM';
                    ?>
                    <div class="routine-time-picker" data-time-picker>
                        <input type="hidden" id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" value="<?= View::escape($inputValue) ?>" data-routine-time="<?= View::escape((string) $field['timeRole']) ?>" data-time-value step="<?= (int) ($field['step'] ?? 60) ?>">
                        <button type="button" class="flex w-full items-center justify-between rounded-lg border border-green-800/80 bg-[#062F2D] px-3 py-2.5 text-left text-sm outline-none focus:border-teal-400" aria-haspopup="listbox" aria-expanded="false" data-time-toggle>
                            <span data-time-display><?= View::escape($inputValue !== '' ? $timeDisplay : 'Select time…') ?></span>
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"></circle><path stroke-linecap="round" d="M12 7v5l3 2"></path></svg>
                        </button>
                        <div class="routine-time-popup" role="dialog" aria-label="Choose time" hidden data-time-popup>
                            <div class="routine-time-popup-head"><span class="routine-time-popup-title">Choose time</span><span class="routine-time-current" data-time-current><?= View::escape($inputValue !== '' ? $timeDisplay : '09:00 AM') ?></span></div>
                            <div class="routine-time-columns">
                                <label class="routine-time-column"><span class="routine-time-column-label">Hour</span><select class="routine-time-select" data-time-hour aria-label="Hour"><?php for ($hourOption = 1; $hourOption <= 12; $hourOption++): ?><option value="<?= $hourOption ?>"<?= $timeSelectedHour === $hourOption ? ' selected' : '' ?>><?= sprintf('%02d', $hourOption) ?></option><?php endfor; ?></select></label>
                                <label class="routine-time-column"><span class="routine-time-column-label">Minute</span><select class="routine-time-select" data-time-minute aria-label="Minute"><?php for ($minuteOption = 0; $minuteOption < 60; $minuteOption++): ?><option value="<?= $minuteOption ?>"<?= $timeSelectedMinute === $minuteOption ? ' selected' : '' ?>><?= sprintf('%02d', $minuteOption) ?></option><?php endfor; ?></select></label>
                                <label class="routine-time-column"><span class="routine-time-column-label">Period</span><select class="routine-time-select" data-time-period aria-label="AM or PM"><option value="AM"<?= $timeSelectedPeriod === 'AM' ? ' selected' : '' ?>>AM</option><option value="PM"<?= $timeSelectedPeriod === 'PM' ? ' selected' : '' ?>>PM</option></select></label>
                            </div>
                            <div class="routine-time-popup-foot"><span class="routine-time-hint">12-hour time · 1-minute steps</span><div class="routine-time-actions"><button type="button" class="routine-time-now" data-time-now>Now</button><button type="button" class="routine-time-done" data-time-done>Done</button></div></div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php $isPhone = str_contains(strtolower($name), 'phone') || str_contains(strtolower($name), 'mobile'); ?>
                    <input id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" type="<?= $isPhone ? 'tel' : View::escape(in_array($type, ['number', 'date', 'time'], true) ? $type : 'text') ?>" value="<?= View::escape($inputValue) ?>"<?= $required ? ' required' : '' ?><?= $autoGenerated || $readOnly ? ' readonly' : '' ?><?= $isPhone ? ' inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." placeholder="01XXXXXXXXX or +8801XXXXXXXXX"' : (isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '') ?><?= isset($field['step']) ? ' step="' . View::escape((string) $field['step']) . '"' : '' ?><?= !$isPhone && isset($field['placeholder']) ? ' placeholder="' . View::escape((string) $field['placeholder']) . '"' : '' ?><?= !empty($field['timeRole']) ? ' data-routine-time="' . View::escape((string) $field['timeRole']) . '" style="background-color:#062F2D;color-scheme:dark"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062F2D] px-3 py-2.5 text-sm outline-none focus:border-teal-400<?= $autoGenerated || $readOnly ? ' cursor-not-allowed opacity-80' : '' ?>">
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

const routineStartTime = document.querySelector('input[data-routine-time="start"]');
const routineEndTime = document.querySelector('input[data-routine-time="end"]');
if (routineStartTime && routineEndTime) {
    const syncEndTimeMinimum = () => {
        routineEndTime.min = routineStartTime.value;
        if (routineEndTime.value && routineEndTime.value <= routineStartTime.value) {
            routineEndTime.value = '';
            const endPicker = routineEndTime.closest('[data-time-picker]');
            const endDisplay = endPicker?.querySelector('[data-time-display]');
            const endCurrentDisplay = endPicker?.querySelector('[data-time-current]');
            const endHour = endPicker?.querySelector('[data-time-hour]');
            const endMinute = endPicker?.querySelector('[data-time-minute]');
            const endPeriod = endPicker?.querySelector('[data-time-period]');
            if (endHour) endHour.value = '9';
            if (endMinute) endMinute.value = '0';
            if (endPeriod) endPeriod.value = 'AM';
            if (endDisplay) endDisplay.textContent = 'Select time…';
            if (endCurrentDisplay) endCurrentDisplay.textContent = 'Select time';
        }
    };
    routineStartTime.addEventListener('input', syncEndTimeMinimum);
    routineStartTime.addEventListener('change', syncEndTimeMinimum);
    syncEndTimeMinimum();
}

document.querySelectorAll('[data-time-picker]').forEach(picker => {
    const value = picker.querySelector('[data-time-value]');
    const display = picker.querySelector('[data-time-display]');
    const currentDisplay = picker.querySelector('[data-time-current]');
    const toggle = picker.querySelector('[data-time-toggle]');
    const popup = picker.querySelector('[data-time-popup]');
    const done = picker.querySelector('[data-time-done]');
    const hourSelect = picker.querySelector('[data-time-hour]');
    const minuteSelect = picker.querySelector('[data-time-minute]');
    const periodSelect = picker.querySelector('[data-time-period]');
    const nowButton = picker.querySelector('[data-time-now]');
    if (!value || !display || !toggle || !popup || !hourSelect || !minuteSelect || !periodSelect) return;

    const format = () => `${String(hourSelect.value).padStart(2, '0')}:${String(minuteSelect.value).padStart(2, '0')} ${periodSelect.value}`;
    const syncValue = () => {
        const hour12 = Number(hourSelect.value);
        const hour24 = (hour12 % 12) + (periodSelect.value === 'PM' ? 12 : 0);
        value.value = `${String(hour24).padStart(2, '0')}:${String(minuteSelect.value).padStart(2, '0')}`;
        display.textContent = format();
        if (currentDisplay) currentDisplay.textContent = format();
        value.dispatchEvent(new Event('input', { bubbles: true }));
        value.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const close = () => { popup.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
    if (value.value) display.textContent = format();
    [hourSelect, minuteSelect, periodSelect].forEach(select => select.addEventListener('change', syncValue));
    nowButton?.addEventListener('click', () => {
        const now = new Date();
        const hour24 = now.getHours();
        hourSelect.value = String(hour24 % 12 || 12);
        minuteSelect.value = String(now.getMinutes());
        periodSelect.value = hour24 >= 12 ? 'PM' : 'AM';
        syncValue();
    });
    toggle.addEventListener('click', () => {
        popup.hidden = !popup.hidden;
        toggle.setAttribute('aria-expanded', String(!popup.hidden));
    });
    done?.addEventListener('click', close);
    document.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
});

const initializeFormattedDatePickers = () => document.querySelectorAll('input[data-formatted-date]').forEach(display => {
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
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFormattedDatePickers, { once: true });
} else {
    initializeFormattedDatePickers();
}
</script>
