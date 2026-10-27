<?php
/**
 * Create or change an account.
 *
 * @var array<string, mixed>|null $account
 * @var array<string, string> $roles
 * @var array<int, string> $students
 * @var array<int, string> $teachers
 * @var array<int, string> $guardians
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$editing = $account !== null;
$value = static function (string $name, mixed $fallback = '') use ($account): mixed {
    return $account !== null && array_key_exists($name, $account) && $account[$name] !== null
        ? $account[$name]
        : $fallback;
};
$linkType = (string) $value('link_type', '');
?>
<div class="mx-auto max-w-3xl">
    <a href="<?= Url::to('/users') ?>" class="text-sm text-teal-300 hover:text-teal-200">← User accounts</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $editing ? 'Edit account' : 'New account' ?></h1>
    <p class="mt-1 text-sm text-white">A teacher, student or parent account needs to be linked to its person record to reach the portal.</p>

    <form method="post" action="<?= Url::to($editing ? '/users/update' : '/users') ?>" class="mt-7 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-6">
        <?= Csrf::field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $account['id'] ?>"><?php endif; ?>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Name <span class="text-rose-300">*</span></span>
                <input required name="name" value="<?= View::escape((string) $value('name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400">
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Email <span class="text-rose-300">*</span></span>
                <input required type="email" name="email" value="<?= View::escape((string) $value('email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400">
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Password <?= $editing ? '' : '<span class="text-rose-300">*</span>' ?></span>
                <input type="password" name="password"<?= $editing ? '' : ' required' ?> minlength="8" placeholder="<?= $editing ? 'Leave blank to keep the current password' : 'At least 8 characters' ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm outline-none focus:border-teal-400">
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Role <span class="text-rose-300">*</span></span>
                <select name="role" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <?php foreach ($roles as $role => $label): ?>
                        <option value="<?= View::escape($role) ?>"<?= (string) $value('role', 'admin') === $role ? ' selected' : '' ?>><?= View::escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="text-sm">
                <span class="mb-1.5 block text-slate-300">Acts as</span>
                <select name="link_type" id="link-type" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <option value="">Not linked</option>
                    <option value="student"<?= $linkType === 'student' ? ' selected' : '' ?>>A student</option>
                    <option value="teacher"<?= $linkType === 'teacher' ? ' selected' : '' ?>>A teacher</option>
                    <option value="guardian"<?= $linkType === 'guardian' ? ' selected' : '' ?>>A guardian</option>
                </select>
            </label>

            <label class="text-sm" data-link-panel="student" hidden>
                <span class="mb-1.5 block text-slate-300">Student</span>
                <select name="link_id_student" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <option value="0">Choose a student</option>
                    <?php foreach ($students as $id => $name): ?>
                        <option value="<?= (int) $id ?>"<?= $linkType === 'student' && (int) $value('link_id') === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="text-sm" data-link-panel="teacher" hidden>
                <span class="mb-1.5 block text-slate-300">Teacher</span>
                <select name="link_id_teacher" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <option value="0">Choose a teacher</option>
                    <?php foreach ($teachers as $id => $name): ?>
                        <option value="<?= (int) $id ?>"<?= $linkType === 'teacher' && (int) $value('link_id') === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="text-sm" data-link-panel="guardian" hidden>
                <span class="mb-1.5 block text-slate-300">Guardian</span>
                <select name="link_id_guardian" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                    <option value="0">Choose a guardian</option>
                    <?php foreach ($guardians as $id => $name): ?>
                        <option value="<?= (int) $id ?>"<?= $linkType === 'guardian' && (int) $value('link_id') === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="flex items-center gap-2.5 self-end rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-slate-300">
                <input type="checkbox" name="is_active" value="1"<?= (int) $value('is_active', 1) === 1 ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-teal-400 focus:ring-teal-400">
                The account is active
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="<?= Url::to('/users') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300"><?= $editing ? 'Save changes' : 'Create account' ?></button>
        </div>
    </form>
</div>

<script>
(() => {
    const typeSelect = document.getElementById('link-type');
    const panels = document.querySelectorAll('[data-link-panel]');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'link_id';
    hiddenInput.value = '<?= (int) $value('link_id') ?>';
    typeSelect.form.appendChild(hiddenInput);

    const sync = () => {
        const type = typeSelect.value;
        panels.forEach((panel) => {
            const active = panel.dataset.linkPanel === type;
            panel.hidden = !active;
            const select = panel.querySelector('select');
            select.disabled = !active;
        });
        const active = document.querySelector('[data-link-panel="' + type + '"] select');
        hiddenInput.value = active && type !== '' ? active.value : '0';
    };

    typeSelect.addEventListener('change', sync);
    document.querySelectorAll('[data-link-panel] select').forEach((select) => {
        select.addEventListener('change', () => {
            const type = typeSelect.value;
            const active = document.querySelector('[data-link-panel="' + type + '"] select');
            hiddenInput.value = active ? active.value : '0';
        });
    });
    sync();
})();
</script>
