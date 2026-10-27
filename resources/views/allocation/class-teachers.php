<?php
/**
 * Who is in charge of each class.
 *
 * @var array<int, array<string, mixed>> $classes
 * @var array<int, string> $teachers
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-4xl">
    <h1 class="text-3xl font-semibold">Class teachers</h1>
    <p class="mt-1 text-sm text-white">The teacher who is in charge of each class. A class teacher also has a subject assignment in that class.</p>

    <section class="mt-7 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="overflow-x-auto">
            <table class="admit-list-table w-full min-w-[560px] text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Class</th>
                        <th class="px-5 py-3 font-medium">Current teacher</th>
                        <th class="px-5 py-3 font-medium">Change to</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php if ($classes === []): ?>
                    <tr><td colspan="3" class="px-5 py-10 text-center text-slate-500">No classes have been set up yet.</td></tr>
                <?php else: foreach ($classes as $row): ?>
                    <tr>
                        <td class="px-5 py-2">
                            <p class="font-medium"><?= View::escape((string) $row['class_name']) ?></p>
                            <p class="mt-0.5 text-xs text-slate-500">Level <?= (int) $row['numeric_level'] ?></p>
                        </td>
                        <td class="px-5 py-2">
                            <?php if ((string) $row['teacher_name'] !== ''): ?>
                                <p class="text-slate-200"><?= View::escape((string) $row['teacher_name']) ?></p>
                                <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) $row['subject_name']) ?></p>
                            <?php else: ?>
                                <span class="text-slate-500">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-2">
                            <form method="post" action="<?= Url::to('/class-teachers/update') ?>" class="flex items-center gap-2">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="class_id" value="<?= (int) $row['class_id'] ?>">
                                <label class="sr-only" for="teacher-<?= (int) $row['class_id'] ?>">Class teacher for <?= View::escape((string) $row['class_name']) ?></label>
                                <select id="teacher-<?= (int) $row['class_id'] ?>" name="teacher_id" class="flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                                    <option value="0">Nobody</option>
                                    <?php foreach ($teachers as $id => $name): ?>
                                        <option value="<?= (int) $id ?>"<?= (int) $row['teacher_id'] === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="rounded-lg border border-slate-700 px-3 py-2 text-xs hover:border-teal-400">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
