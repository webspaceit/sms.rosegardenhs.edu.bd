<?php
/**
 * The monthly attendance register.
 *
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 * @var array<int, bool> $days
 * @var array<int, array<string, mixed>> $students
 * @var array<int, array<int, string>> $marks
 */
use App\Core\Url;
use App\Core\View;

$letter = [
    'present' => ['P', 'bg-teal-400/25 text-teal-100'],
    'late' => ['L', 'bg-amber-400/25 text-amber-100'],
    'half_day' => ['H', 'bg-sky-400/25 text-sky-100'],
    'absent' => ['A', 'bg-rose-400/25 text-rose-100'],
    'leave' => ['Lv', 'bg-slate-700 text-slate-300'],
];
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold">Attendance register</h1>
        <p class="mt-1 text-sm text-white">Every student of a class, day by day, with their percentage at the end.</p>
    </div>
</div>

<form method="get" action="<?= Url::to('/attendance-register') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-4">
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
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Month</span>
        <input type="month" name="month" value="<?= View::escape($month) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
    </label>
    <div class="flex items-end">
        <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Show register</button>
    </div>
</form>

<?php if ($students === []): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">Choose a class to build the register.</div>
<?php else: ?>
    <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-green-900/60 px-5 py-4">
            <div>
                <h2 class="font-semibold"><?= count($students) ?> student(s)</h2>
                <p class="mt-1 text-xs text-slate-500"><?= View::escape(date('F Y', strtotime($month . '-01'))) ?> · P present, L late, H half day, A absent, Lv leave</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admit-list-table w-full min-w-[900px] text-left text-sm">
                <thead class="bg-green-950/40">
                    <tr>
                        <th class="sticky left-0 z-10 bg-slate-950/95 px-5 py-3 font-medium text-slate-300">Roll &amp; student</th>
                        <?php foreach ($days as $day => $isWeekday): ?>
                            <th class="px-1 py-3 text-center text-[10px] font-normal <?= $isWeekday ? 'text-white' : 'text-slate-700' ?>"><?= $day ?></th>
                        <?php endforeach; ?>
                        <th class="px-5 py-3 text-right font-medium text-slate-300">Present %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                <?php foreach ($students as $student): ?>
                    <?php
                    $studentMarks = $marks[(int) $student['id']] ?? [];
                    $present = 0;
                    $recorded = 0;
                    foreach ($studentMarks as $status) {
                        $recorded++;
                        if (in_array($status, ['present', 'late', 'half_day'], true)) {
                            $present++;
                        }
                    }
                    $percent = $recorded > 0 ? round($present / $recorded * 100, 1) : 0.0;
                    ?>
                    <tr class="hover:bg-slate-800/30">
                        <td class="sticky left-0 z-10 bg-slate-900/95 px-5 py-2">
                            <span class="block font-medium"><?= (int) $student['roll_no'] ?>. <?= View::escape((string) $student['name']) ?></span>
                            <span class="block text-[10px] text-slate-500"><?= View::escape((string) $student['student_id']) ?></span>
                        </td>
                        <?php foreach ($days as $day => $isWeekday): ?>
                            <?php
                            $status = $studentMarks[$day] ?? null;
                            $mark = $status !== null ? ($letter[$status] ?? ['?', 'bg-slate-800 text-white']) : ['', 'bg-slate-800/40'];
                            ?>
                            <td class="px-1 py-2 text-center <?= $isWeekday ? '' : 'opacity-40' ?>">
                                <span class="inline-grid h-6 w-6 place-items-center rounded text-[10px] font-semibold <?= $mark[1] ?>"><?= $mark[0] ?></span>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-5 py-2 text-right">
                            <span class="rounded-full px-2.5 py-1 text-xs <?= $percent >= 75 ? 'bg-teal-400/10 text-teal-200' : ($percent >= 50 ? 'bg-amber-400/10 text-amber-200' : 'bg-rose-400/10 text-rose-200') ?>"><?= $percent ?>%</span>
                            <span class="ml-2 text-[10px] text-slate-500"><?= $present ?>/<?= $recorded ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
