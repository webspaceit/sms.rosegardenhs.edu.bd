<?php
/**
 * The setup bar shared by the configured result screens.
 *
 * @var string $type
 * @var array<int, array<string, mixed>> $configs
 * @var array<string, mixed>|null $config
 */
use App\Core\Url;
use App\Core\View;

$route = match ($type) {
    'tabulation' => '/tabulation-sheets',
    'merit-list' => '/merit-lists',
    'section-result' => '/section-results',
    'fail-list' => '/fail-lists',
    'admit-card' => '/admit-cards',
    default => '/reports',
};
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold"><?= View::escape($title) ?></h1>
        <p class="mt-1 text-sm text-white">
            <?php if ($config !== null): ?>
                <?= View::escape((string) $config['class_name']) ?><?= $config['section'] !== '' ? ' · ' . View::escape((string) $config['section']) : '' ?>
                — <?= View::escape((string) $config['exam_name']) ?> (<?= View::escape((string) $config['academic_year']) ?>)
            <?php else: ?>
                No setup has been created for this screen yet.
            <?php endif; ?>
        </p>
    </div>
    <div class="flex flex-wrap items-end gap-3">
        <form method="get" action="<?= Url::to($route) ?>" class="flex items-end gap-2">
            <label class="text-xs">
                <span class="mb-1 block text-slate-500">Setup</span>
                <select name="config" onchange="this.form.submit()" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                    <?php foreach ($configs as $row): ?>
                        <option value="<?= (int) $row['id'] ?>"<?= $config !== null && (int) $config['id'] === (int) $row['id'] ? ' selected' : '' ?>>
                            <?= View::escape((string) $row['class_name'] . ($row['section'] !== '' ? ' · ' . $row['section'] : '') . ' — ' . $row['exam_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
        <a href="<?= Url::to('/reports/config?type=' . urlencode($type)) ?>" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-teal-400">Setup</a>
    </div>
</div>
