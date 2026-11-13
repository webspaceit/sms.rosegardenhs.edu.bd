<?php
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-5xl">
    <a href="<?= Url::to('/academics') ?>" class="text-sm text-green-300 hover:text-green-200">← Academic setup</a>
    <header class="mt-2 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">Academic Groups</h1>
            <p class="mt-1 text-sm text-white">Manage the study groups available to Class 9 students.</p>
        </div>
    </header>

    <section class="mt-6 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <h2 class="text-lg font-semibold">Add academic group</h2>
        <form method="post" action="<?= Url::to('/academic-groups/create') ?>" class="mt-4 flex flex-wrap gap-3">
            <?= Csrf::field() ?>
            <label class="min-w-[220px] flex-1">
                <span class="sr-only">Group name</span>
                <input required maxlength="100" name="name" placeholder="e.g. Science" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
            </label>
            <button class="rounded-lg bg-green-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-green-300">Add group</button>
        </form>
    </section>

    <section class="mt-5 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="border-b border-green-900/60 px-5 py-4">
            <h2 class="font-semibold">Available groups</h2>
            <p class="mt-1 text-sm text-slate-300"><?= count($groups) ?> group<?= count($groups) === 1 ? '' : 's' ?></p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px] border-collapse text-left text-sm">
                <thead class="bg-emerald-800/70 text-xs uppercase tracking-wide text-emerald-50"><tr><th class="border border-green-700/50 px-4 py-3">Group name</th><th class="border border-green-700/50 px-4 py-3">Students</th><th class="border border-green-700/50 px-4 py-3">Actions</th></tr></thead>
                <tbody>
                <?php if ($groups === []): ?>
                    <tr><td colspan="3" class="border border-green-900/50 px-4 py-8 text-center text-slate-300">No groups yet. Add the Class 9 study groups above.</td></tr>
                <?php else: foreach ($groups as $group): ?>
                    <tr>
                        <td class="border border-green-900/50 px-4 py-3">
                            <form id="academic-group-update-<?= (int) $group['id'] ?>" method="post" action="<?= Url::to('/academic-groups/update') ?>" class="flex gap-2">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                                <input required maxlength="100" name="name" value="<?= View::escape($group['name']) ?>" class="min-w-0 flex-1 rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2 text-sm outline-none focus:border-emerald-400">
                            </form>
                        </td>
                        <td class="border border-green-900/50 px-4 py-3 text-slate-200"><?= (int) $group['student_count'] ?></td>
                        <td class="border border-green-900/50 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button form="academic-group-update-<?= (int) $group['id'] ?>" class="rounded-lg border border-green-700/70 px-3 py-2 text-xs font-semibold hover:border-green-400">Save</button>
                                <form method="post" action="<?= Url::to('/academic-groups/delete') ?>" data-confirm="Delete the <?= View::escape($group['name']) ?> group?" data-no-ajax>
                                    <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                                    <button <?= (int) $group['student_count'] > 0 ? 'disabled title="This group is assigned to students"' : '' ?> class="rounded-lg border border-rose-500/40 px-3 py-2 text-xs font-semibold text-rose-200 hover:border-rose-400 disabled:cursor-not-allowed disabled:opacity-40">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <p class="border-t border-green-900/60 px-5 py-3 text-xs text-slate-300">Renaming a group updates students already assigned to it. A group with students assigned cannot be deleted.</p>
    </section>
</div>
