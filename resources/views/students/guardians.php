<?php
/**
 * Guardian links for a student.
 *
 * @var array<string, mixed> $student
 * @var array<int, array<string, mixed>> $linked
 * @var array<int, array<string, mixed>> $available
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="<?= Url::to('/students/view?id=' . (int) $student['id']) ?>" class="text-sm text-green-300 hover:text-green-200">← Student details</a>
        <h1 class="mt-2 text-3xl font-semibold">Guardian links</h1>
        <p class="mt-1 text-sm text-white"><?= View::escape((string) $student['name']) ?> · <?= View::escape((string) $student['student_id']) ?></p>
    </div>
</div>

<section class="mt-7 grid gap-6 lg:grid-cols-2">
    <div class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="border-b border-green-900/60 px-5 py-4">
            <h2 class="font-semibold">Linked guardians</h2>
            <p class="mt-1 text-xs text-slate-400">Guardians who can see this student in their portal.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="admit-list-table w-full text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-5 py-3 font-medium">Guardian</th>
                        <th class="px-5 py-3 font-medium">Relation</th>
                        <th class="px-5 py-3 font-medium">Phone</th>
                        <th class="px-5 py-3 font-medium">Primary</th>
                        <th class="px-5 py-3 text-center font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php if ($linked === []): ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No guardian linked yet.</td></tr>
                <?php else: foreach ($linked as $guardian): ?>
                    <tr class="hover:bg-slate-800/30">
                        <td class="px-5 py-2 font-medium"><?= View::escape((string) $guardian['name']) ?></td>
                        <td class="px-5 py-2"><?= View::escape((string) $guardian['relation']) ?></td>
                        <td class="px-5 py-2"><?= View::escape((string) $guardian['phone']) ?></td>
                        <td class="px-5 py-2">
                            <?php if ((int) $guardian['is_primary'] === 1): ?>
                                <span class="rounded-full bg-green-400/10 px-2.5 py-1 text-xs text-green-200">Primary</span>
                            <?php else: ?>
                                <form method="post" action="<?= Url::to('/students/guardians/primary') ?>" class="inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                    <input type="hidden" name="guardian_id" value="<?= (int) $guardian['id'] ?>">
                                    <button class="text-xs text-green-300 hover:text-green-200">Make primary</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-2 text-center">
                            <form method="post" action="<?= Url::to('/students/guardians/unlink') ?>" onsubmit="return confirm('Unlink this guardian?')">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                <input type="hidden" name="guardian_id" value="<?= (int) $guardian['id'] ?>">
                                <button class="rounded-md border border-rose-400/30 px-3 py-1.5 text-xs text-rose-200 hover:border-rose-400">Unlink</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="border-b border-green-900/60 px-5 py-4">
            <h2 class="font-semibold">Available guardians</h2>
            <p class="mt-1 text-xs text-slate-400">Guardians not yet linked to this student.</p>
        </div>
        <div class="p-5">
            <?php if ($available === []): ?>
                <p class="text-sm text-slate-500">All guardians are already linked, or none exist yet.</p>
            <?php else: ?>
                <form method="post" action="<?= Url::to('/students/guardians/link') ?>" class="grid gap-4">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium">Guardian</span>
                        <select name="guardian_id" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" style="background-color:#062f2d;border-color:rgba(22,101,52,.8);color:#f1f5f9">
                            <option value="">Choose a guardian…</option>
                            <?php foreach ($available as $g): ?>
                                <option value="<?= (int) $g['id'] ?>"><?= View::escape((string) $g['name']) ?> · <?= View::escape((string) $g['relation']) ?> · <?= View::escape((string) $g['phone']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_primary" value="1" class="rounded border-slate-700">
                        <span>Set as primary guardian</span>
                    </label>
                    <button class="rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Link guardian</button>
                </form>
            <?php endif; ?>
            <div class="mt-4 border-t border-green-900/60 pt-4">
                <a href="<?= Url::to('/guardians/new') ?>" class="text-sm text-green-300 hover:text-green-200">＋ Create a new guardian first</a>
            </div>
        </div>
    </div>
</section>