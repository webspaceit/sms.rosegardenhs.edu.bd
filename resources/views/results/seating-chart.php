<?php
/**
 * The examination seating chart.
 *
 * @var array<int, string> $exams
 * @var array<int, string> $classes
 * @var array<string, mixed>|null $exam
 * @var array<int, array<string, mixed>> $students
 * @var array<int, array<string, mixed>> $seats
 * @var array<string, array<int, array<string, mixed>>> $halls
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold">Seating chart</h1>
        <p class="mt-1 text-sm text-white">Place the students of a class into halls and seats for an examination.</p>
    </div>
</div>

<form method="get" action="<?= Url::to('/seating-chart') ?>" class="mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2">
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Examination</span>
        <select name="exam" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <option value="0">Select an examination</option>
            <?php foreach ($exams as $id => $name): ?>
                <option value="<?= (int) $id ?>"<?= $examId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="text-sm">
        <span class="mb-1.5 block text-slate-300">Class</span>
        <select name="class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
            <option value="0">Select a class</option>
            <?php foreach ($classes as $id => $name): ?>
                <option value="<?= (int) $id ?>"<?= $classId === (int) $id ? ' selected' : '' ?>><?= View::escape($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="flex items-end sm:col-span-2">
        <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Show chart</button>
    </div>
</form>

<?php if ($examId <= 0 || $classId <= 0): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">Choose an examination and a class.</div>
<?php else: ?>
    <form method="post" action="<?= Url::to('/seating-chart/generate') ?>" class="mt-6 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-5">
        <?= Csrf::field() ?>
        <input type="hidden" name="exam" value="<?= (int) $examId ?>">
        <input type="hidden" name="class_id" value="<?= (int) $classId ?>">
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Hall name</span>
            <input name="hall" value="Main hall" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Seats per row</span>
            <input type="number" name="columns" value="5" min="1" max="20" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm">
            <span class="mb-1.5 block text-slate-300">Rows per hall</span>
            <input type="number" name="rows" value="4" min="1" max="30" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
        </label>
        <div class="flex items-end">
            <button class="w-full rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Build chart</button>
        </div>
    </form>

    <?php if ($seats === []): ?>
        <div class="mt-6 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500">
            No seating plan for this examination yet. <?= count($students) ?> student(s) are waiting to be placed.
        </div>
    <?php else: ?>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-white"><?= count($seats) ?> student(s) seated across <?= count($halls) ?> hall(s).</p>
            <form method="post" action="<?= Url::to('/seating-chart/clear') ?>" data-confirm="Clear this seating chart?">
                <?= Csrf::field() ?>
                <input type="hidden" name="exam" value="<?= (int) $examId ?>">
                <button class="rounded-lg border border-slate-700 px-3 py-2 text-xs text-rose-200 hover:border-rose-400">Clear chart</button>
            </form>
        </div>

        <?php foreach ($halls as $hall => $hallSeats): ?>
            <?php
            $rows = (int) max(array_map(static fn (array $seat): int => (int) $seat['row_no'], $hallSeats));
            $columns = (int) max(array_map(static fn (array $seat): int => (int) $seat['column_no'], $hallSeats));
            $byPosition = [];
            foreach ($hallSeats as $seat) {
                $byPosition[(int) $seat['row_no']][(int) $seat['column_no']] = $seat;
            }
            ?>
            <section class="mt-5 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
                <div class="border-b border-green-900/60 px-5 py-4">
                    <h2 class="font-semibold"><?= View::escape($hall) ?></h2>
                    <p class="mt-1 text-xs text-slate-500"><?= count($hallSeats) ?> student(s)</p>
                </div>
                <div class="overflow-x-auto p-5">
                    <table class="admit-list-table w-full min-w-[560px] text-left text-xs">
                        <thead>
                            <tr>
                                <th class="w-8 pb-2"></th>
                                <?php for ($column = 1; $column <= $columns; $column++): ?>
                                    <th class="pb-2 text-center font-mono text-slate-500"><?= $column ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php for ($row = 1; $row <= $rows; $row++): ?>
                            <tr>
                                <td class="pr-2 align-middle font-mono text-slate-500"><?= $row ?></td>
                                <?php for ($column = 1; $column <= $columns; $column++): ?>
                                    <?php $seat = $byPosition[$row][$column] ?? null; ?>
                                    <td class="p-1">
                                        <?php if ($seat === null): ?>
                                            <span class="block rounded-lg border border-dashed border-slate-800 px-2 py-3 text-center text-slate-700">—</span>
                                        <?php else: ?>
                                            <span class="block rounded-lg border border-teal-400/20 bg-teal-400/[0.07] px-2 py-2">
                                                <span class="block font-mono text-[10px] text-teal-300/70"><?= View::escape((string) $seat['seat_no']) ?> · <?= View::escape((string) $seat['section_name']) ?></span>
                                                <span class="block truncate font-medium text-teal-50"><?= View::escape((string) $seat['student_name']) ?></span>
                                                <span class="block text-[10px] text-white">Roll <?= (int) $seat['roll_no'] ?> · <?= View::escape((string) $seat['student_id']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
