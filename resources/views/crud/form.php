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

$editing = $record !== null;
$value = static function (string $name, mixed $fallback = '') use ($record): mixed {
    return $record !== null && array_key_exists($name, $record) && $record[$name] !== null
        ? $record[$name]
        : $fallback;
};
?>
<div class="mx-auto max-w-3xl">
    <a href="<?= Url::to($module['route']) ?>" class="text-sm text-teal-300 hover:text-teal-200">← <?= View::escape($module['title']) ?></a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $editing ? 'Edit' : 'New' ?> <?= View::escape($module['singular']) ?></h1>
    <p class="mt-1 text-sm text-white"><?= $editing ? 'Update this record.' : 'Add a new ' . View::escape($module['singular']) . ' record.' ?></p>

    <form method="post" enctype="multipart/form-data" action="<?= Url::to($editing ? $module['route'] . '/update' : $module['route']) ?>" class="mt-7 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><?php endif; ?>

        <div class="grid gap-5 sm:grid-cols-2">
        <?php foreach ($module['fields'] as $field): ?>
            <?php
            $name = (string) $field['name'];
            $type = (string) ($field['type'] ?? 'text');
            $label = (string) $field['label'];
            $required = (bool) ($field['required'] ?? false);
            $hint = (string) ($field['hint'] ?? '');
            $current = $value($name, $field['default'] ?? '');
            $wide = (bool) ($field['wide'] ?? in_array($type, ['textarea', 'multiselect'], true));
            ?>
            <div class="<?= $wide ? 'sm:col-span-2' : '' ?>">
                <label for="field-<?= View::escape($name) ?>" class="mb-1.5 block text-sm text-slate-300">
                    <?= View::escape($label) ?><?= $required ? ' <span class="text-rose-300">*</span>' : '' ?>
                </label>

                <?php if ($type === 'textarea'): ?>
                    <textarea id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>"<?= $required ? ' required' : '' ?><?= isset($field['rows']) ? ' rows="' . (int) $field['rows'] . '"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400"><?= View::escape((string) $current) ?></textarea>

                <?php elseif ($type === 'select'): ?>
                    <?php $options = Crud::fieldOptions($field); ?>
                    <select id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>"<?= $required ? ' required' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400">
                        <?php if (!$required): ?><option value="">Select…</option><?php endif; ?>
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <option value="<?= View::escape((string) $optionValue) ?>"<?= (string) $current === (string) $optionValue ? ' selected' : '' ?>><?= View::escape((string) $optionLabel) ?></option>
                        <?php endforeach; ?>
                    </select>

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
                    <input id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" type="file"<?= $required && !$editing ? ' required' : '' ?> accept="<?= View::escape(implode(',', array_map(static fn ($extension) => '.' . $extension, $field['extensions'] ?? []))) ?>" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm">
                    <?php if ($current): ?><p class="mt-1.5 text-xs text-slate-400">Current file: <a class="text-teal-300 underline" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $current)) ?>">View file</a>. Leave blank to keep it.</p><?php endif; ?>
                <?php else: ?>
                    <input id="field-<?= View::escape($name) ?>" name="<?= View::escape($name) ?>" type="<?= View::escape($type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text')) ?>" value="<?= View::escape((string) $current) ?>"<?= $required ? ' required' : '' ?><?= isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '' ?><?= isset($field['step']) ? ' step="' . View::escape((string) $field['step']) . '"' : '' ?><?= isset($field['placeholder']) ? ' placeholder="' . View::escape((string) $field['placeholder']) . '"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400">
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
