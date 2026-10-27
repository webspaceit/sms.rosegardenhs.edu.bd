<?php
/**
 * The user accounts.
 *
 * @var array<int, array<string, mixed>> $users
 * @var array<string, string> $roles
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold">User accounts</h1>
        <p class="mt-1 text-sm text-white">Every account that can sign in, and the person record it acts as.</p>
    </div>
    <a href="<?= Url::to('/users/new') ?>" class="rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">＋ New account</a>
</div>

<section class="mt-7 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
    <div class="overflow-x-auto">
        <table class="admit-list-table w-full min-w-[760px] text-left text-sm">
            <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-white">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Linked to</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 text-center font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-900/50">
            <?php if ($users === []): ?>
                <tr><td colspan="6" class="px-5 py-10 text-center text-white">No account has been created yet.</td></tr>
            <?php else: foreach ($users as $account): ?>
                <tr class="hover:bg-slate-800/30">
                    <td class="px-5 py-2 font-medium"><?= View::escape((string) $account['name']) ?></td>
                    <td class="px-5 py-2 text-white"><?= View::escape((string) $account['email']) ?></td>
                    <td class="px-5 py-2">
                        <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs capitalize text-slate-300"><?= View::escape((string) $account['role']) ?></span>
                    </td>
                    <td class="px-5 py-2 text-white">
                        <?php if ($account['link_type'] === null): ?>
                            <span class="text-white">Not linked</span>
                        <?php else: ?>
                            <span class="capitalize text-slate-300"><?= View::escape((string) $account['link_type']) ?></span>
                            <span class="block text-xs text-white"><?= View::escape((string) ($account['linked_name'] ?? '')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-2">
                        <span class="rounded-full px-2.5 py-1 text-xs <?= (int) $account['is_active'] === 1 ? 'bg-teal-400/10 text-teal-200' : 'bg-slate-700 text-slate-300' ?>"><?= (int) $account['is_active'] === 1 ? 'Active' : 'Disabled' ?></span>
                    </td>
                    <td class="px-5 py-2 text-center">
                        <details class="student-actions group inline-flex items-center justify-center" aria-label="Actions">
                            <summary class="grid h-9 w-9 place-items-center rounded-lg border border-green-100/20 text-green-100/75 transition hover:bg-green-100/10 hover:text-white" aria-label="Show actions" title="Actions">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"></circle><circle cx="12" cy="12" r="1.7"></circle><circle cx="19" cy="12" r="1.7"></circle></svg>
                            </summary>
                            <div class="student-actions-controls hidden flex items-center gap-2">
                                <a href="<?= Url::to('/users/edit?id=' . (int) $account['id']) ?>" class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs hover:border-green-300">Edit</a>
                                <form method="post" action="<?= Url::to('/users/delete') ?>" onsubmit="return confirm('Remove this account?')">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                    <button class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs text-rose-200 hover:border-rose-400">Remove</button>
                                </form>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
