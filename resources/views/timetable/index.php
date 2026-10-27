<?php
/**
 * The weekly class timetable.
 *
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array<int, string> $days
 * @var array<string, string> $periods
 * @var array<string, array<string, array<string, mixed>>> $grid
 */
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold">Weekly timetable</h1>
        <p class="mt-1 text-sm text-white">The periods of the week for one class, laid out as it is printed.</p>
    </div>
    <a href="<?= Url::to('/class-routines') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Edit the periods →</a>
</div>

<form method="get" action="<?= Url::to('/timetable') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-3">
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Class</span>
        <select name="class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <option value="0">Select a class</option>
            <?php foreach ($classes as $id => $name): ?>
                <option value="<?= (int) $id ?>"<?= $classId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Section</span>
        <select name="section_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <option value="0">All sections</option>
            <?php foreach ($sections as $id => $name): ?>
                <option value="<?= (int) $id ?>"<?= $sectionId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="flex items-end">
        <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Show timetable</button>
    </div>
</form>

<?php if ($classId <= 0): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">Choose a class to see its timetable.</div>
<?php elseif ($periods === []): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">No period has been set up for this class yet.</div>
<?php else: ?>
    <section class="mt-6 overflow-x-auto rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <table class="admit-list-table w-full min-w-[900px] text-left text-sm">
            <thead class="bg-green-950/40">
                <tr>
                    <th class="px-5 py-3 font-medium text-slate-300">Time</th>
                    <?php foreach ($days as $day): ?>
                        <th class="px-3 py-3 text-center font-medium text-slate-300"><?= View::escape(substr($day, 0, 3)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-900/50">
            <?php foreach ($periods as $start => $end): ?>
                <tr>
                    <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-white"><?= View::escape($start) ?><span class="text-white"> – </span><?= View::escape($end) ?></td>
                    <?php foreach ($days as $day): ?>
                        <?php $slot = $grid[$day][$start] ?? null; ?>
                        <td class="px-2 py-2">
                            <?php if ($slot === null): ?>
                                <span class="block rounded-lg border border-dashed border-slate-800 px-2 py-3 text-center text-[10px] text-slate-700">—</span>
                            <?php else: ?>
                                <span class="block rounded-lg border border-teal-400/15 bg-teal-400/[0.07] px-2 py-2">
                                    <span class="block truncate text-xs font-medium text-teal-100"><?= View::escape((string) $slot['subject_name']) ?></span>
                                    <span class="block truncate text-[10px] text-white"><?= View::escape((string) ($slot['teacher_name'] ?? '—')) ?></span>
                                    <?php if (($slot['room'] ?? '') !== ''): ?>
                                        <span class="block text-[10px] text-slate-500"><?= View::escape((string) $slot['room']) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>
