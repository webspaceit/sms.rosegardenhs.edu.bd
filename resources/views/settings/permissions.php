<?php
/**
 * Role and permission matrix.
 *
 * @var array<string, array<int, array{id: int, name: string, slug: string}>> $groups
 * @var array<string, string> $roles
 * @var array<string, array<int, int>> $roleMap
 */
use App\Core\Url;
use App\Core\View;

$hasPermissions = $groups !== [];
?>
<div class="mx-auto max-w-6xl">
    <h1 class="text-3xl font-semibold">Permissions &amp; roles</h1>
    <p class="mt-1 text-sm text-white">Tick the permissions each role should hold. The super admin role always has full access.</p>

    <?php if ($hasPermissions): ?>
        <form method="post" action="<?= Url::to('/permissions') ?>" class="mt-7">
            <?= \App\Core\Csrf::field() ?>

            <div class="overflow-x-auto rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
                <table class="admit-list-table w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-green-100/60">
                        <tr>
                            <th class="px-5 py-3 font-medium">Permission</th>
                            <?php foreach ($roles as $role => $label): ?>
                                <th class="px-3 py-3 text-center font-medium"><?= View::escape($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-900/50">
                    <?php foreach ($groups as $module => $permissions): ?>
                        <tr>
                            <td colspan="<?= count($roles) + 1 ?>" class="bg-green-950/40 px-5 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-green-200/80"><?= View::escape($module) ?></td>
                        </tr>
                        <?php foreach ($permissions as $permission): ?>
                            <tr class="hover:bg-green-400/[0.06]">
                                <td class="px-5 py-2">
                                    <span class="text-slate-200"><?= View::escape($permission['name']) ?></span>
                                    <span class="ml-2 font-mono text-[10px] text-white"><?= View::escape($permission['slug']) ?></span>
                                </td>
                                <?php foreach ($roles as $role => $label): ?>
                                    <td class="px-3 py-2 text-center">
                                        <?php if ($role === 'superadmin'): ?>
                                            <span class="text-xs text-white">Always</span>
                                        <?php else: ?>
                                            <input type="checkbox" name="grants[<?= View::escape($role) ?>][]" value="<?= (int) $permission['id'] ?>"<?= in_array((int) $permission['id'], $roleMap[$role] ?? [], true) ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-teal-400 focus:ring-teal-400" aria-label="<?= View::escape($permission['name'] . ' for ' . $label) ?>">
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?= Url::to('/dashboard') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
                <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save permissions</button>
            </div>
        </form>
    <?php endif; ?>
</div>
