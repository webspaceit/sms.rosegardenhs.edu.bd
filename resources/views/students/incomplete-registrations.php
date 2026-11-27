<?php
/** @var array<int, array<string, mixed>> $students */
use App\Core\Url;
use App\Core\View;
?>
<div class="w-full min-w-0">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="<?= Url::to('/students') ?>" class="text-sm text-green-300 hover:text-green-200">← Student register</a>
            <h1 class="mt-2 text-3xl font-semibold">Incomplete registrations</h1>
            <p class="mt-1 text-sm text-white">Quick admissions and CSV imports waiting for a full student profile.</p>
        </div>
        <span class="rounded-full border border-amber-200/20 bg-amber-950/40 px-3 py-1.5 text-sm text-amber-100"><?= count($students) ?> pending</span>
    </header>

    <section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <?php if ($students === []): ?>
            <div class="p-8 text-center">
                <h2 class="text-lg font-semibold">No incomplete registrations</h2>
                <p class="mt-2 text-sm text-slate-300">Quick admissions and imported students that need full registration will appear here.</p>
                <a href="<?= Url::to('/students/quick-admit') ?>" class="form-action-button mt-4 inline-flex">Quick admit</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-emerald-700/80 text-xs uppercase tracking-wide text-green-50">
                        <tr>
                            <th class="px-4 py-3">Sl.</th>
                            <th class="px-4 py-3">Student ID</th>
                            <th class="px-4 py-3">Student name</th>
                            <th class="px-4 py-3">Class / Section</th>
                            <th class="px-4 py-3">Shift</th>
                            <th class="px-4 py-3">Roll</th>
                            <th class="px-4 py-3">Academic year</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-900/60">
                        <?php foreach ($students as $index => $student): ?>
                            <tr class="hover:bg-emerald-900/20">
                                <td class="px-4 py-3 text-slate-300"><?= $index + 1 ?></td>
                                <td class="px-4 py-3 font-mono text-xs text-teal-100"><?= View::escape($student['student_id']) ?></td>
                                <td class="px-4 py-3 font-medium"><?= View::escape($student['name']) ?></td>
                                <td class="px-4 py-3"><?= View::escape($student['class_name'] . ' / ' . $student['section_name']) ?></td>
                                <td class="px-4 py-3"><?= View::escape($student['shift'] ?: '—') ?></td>
                                <td class="px-4 py-3"><?= (int) $student['roll_no'] ?></td>
                                <td class="px-4 py-3"><?= View::escape($student['year_name']) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="<?= Url::to('/students/view?id=' . (int) $student['id']) ?>" class="rounded-lg border border-green-100/20 px-3 py-2 text-xs text-green-100 hover:border-green-300">Details</a>
                                        <a href="<?= Url::to('/students/admission-slip?id=' . (int) $student['id']) ?>" class="rounded-lg border border-green-100/20 px-3 py-2 text-xs text-green-100 hover:border-green-300">Admission slip</a>
                                        <a href="<?= Url::to('/students/edit?id=' . (int) $student['id']) ?>" class="form-action-button">Complete registration</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
