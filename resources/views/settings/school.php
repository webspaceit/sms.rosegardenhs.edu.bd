<?php
/**
 * School settings form.
 *
 * @var array<string, array<string, mixed>> $groups
 * @var array<string, string> $values
 */
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-3xl">
    <h1 class="text-3xl font-semibold">School settings</h1>
    <p class="mt-1 text-sm text-white">These values appear on the notice board, printed documents and the school header.</p>

    <form method="post" action="<?= Url::to('/settings/school') ?>" class="mt-7 space-y-6">
        <?= \App\Core\Csrf::field() ?>

        <?php foreach ($groups as $group): ?>
            <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-6">
                <h2 class="font-semibold"><?= View::escape($group['title']) ?></h2>
                <p class="mt-1 text-sm text-slate-500"><?= View::escape($group['hint']) ?></p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <?php foreach ($group['fields'] as $key => $field): ?>
                        <?php if (($field['type'] ?? 'text') === 'checkbox'): ?>
                            <label class="flex items-center gap-3 rounded-lg border border-green-800/80 bg-[#062f2d] px-4 py-3 text-sm text-slate-300">
                                <input type="checkbox" name="<?= View::escape($key) ?>" value="1" <?= ($values[$key] ?? '1') === '1' ? 'checked' : '' ?> class="h-4 w-4 rounded border-green-700 bg-[#062f2d] text-teal-400 focus:ring-teal-400">
                                <span><?= View::escape($field['label']) ?></span>
                            </label>
                        <?php else: ?>
                            <div class="<?= (int) ($field['max'] ?? 0) > 150 ? 'sm:col-span-2' : '' ?>">
                                <label for="setting-<?= View::escape($key) ?>" class="mb-1.5 block text-sm text-slate-300"><?= View::escape($field['label']) ?></label>
                                <input id="setting-<?= View::escape($key) ?>" name="<?= View::escape($key) ?>" value="<?= View::escape($values[$key] ?? '') ?>" maxlength="<?= (int) ($field['max'] ?? 0) ?>" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-teal-400">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="flex justify-end gap-3">
            <a href="<?= Url::to('/dashboard') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save settings</button>
        </div>
    </form>
</div>
