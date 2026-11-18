<?php
/**
 * Printable identity cards.
 *
 * @var array<int, array<string, mixed>> $students
 * @var array<int, string> $classes
 * @var array<int, string> $sections
 */
use App\Core\Url;
use App\Core\View;
$schoolName = trim((string) ($schoolSettings['school_name'] ?? '')) ?: 'WSIT School';
?>
<style>
    .student-id-card{width:100%;max-width:280px;aspect-ratio:2.125 / 3.375;margin-inline:auto;display:flex;flex-direction:column}
    @media print{
        @page{size:A4 portrait;margin:10mm}
        body *{visibility:hidden!important}
        #student-id-cards,#student-id-cards *{visibility:visible!important}
        #student-id-cards{position:absolute;inset:0;width:100%;margin:0;padding:0;background:#fff}
        .id-card-screen-controls{display:none!important}
        .student-id-card-grid{display:grid!important;grid-template-columns:repeat(3,2.125in)!important;justify-content:center;gap:5mm!important;margin:0!important}
        .student-id-card{width:2.125in!important;height:3.375in!important;max-width:none!important;aspect-ratio:auto!important;break-inside:avoid;page-break-inside:avoid;box-shadow:none!important;border:1px solid #94a3b8!important}
    }
</style>
<div id="student-id-cards">
    <div class="id-card-screen-controls flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">ID cards</h1>
            <p class="mt-1 text-sm text-white">Printable identity cards for the students of a class.</p>
        </div>
        <button type="button" onclick="window.print()" class="id-card-screen-controls form-action-button">Print ID cards</button>
    </div>

    <form method="get" action="<?= Url::to('/id-cards') ?>" class="id-card-screen-controls mt-7 grid gap-3 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4 sm:grid-cols-2 xl:grid-cols-4">
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
        <div class="flex items-end sm:col-span-2">
            <button class="w-full rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:border-teal-400">Show cards</button>
        </div>
    </form>

    <div class="student-id-card-grid mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <?php if ($students === []): ?>
        <p class="rounded-2xl border border-dashed border-slate-700 p-10 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Choose a class to print its identity cards.</p>
    <?php else: foreach ($students as $student): ?>
        <article class="student-id-card overflow-hidden rounded-2xl border border-slate-800 bg-white pb-1 text-slate-900 shadow-xl">
            <header class="flex h-16 shrink-0 items-center gap-2.5 bg-gradient-to-r from-emerald-800 to-teal-600 px-3 text-white">
                <img src="<?= Url::to('/favicon.png') ?>" alt="" class="h-10 w-10 shrink-0 rounded-full border border-white/40 object-cover">
                <div class="min-w-0"><p class="truncate text-xs font-bold leading-tight"><?= View::escape($schoolName) ?></p><p class="mt-1 text-[8px] font-semibold uppercase tracking-[.18em] text-emerald-50/80">Student identity card</p></div>
            </header>
            <div class="flex flex-1 flex-col items-center px-4 pb-3 pt-3">
                <?php if (!empty($student['photo'])): ?>
                    <img src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>" alt="<?= View::escape((string) $student['name']) ?>" class="h-[4.5rem] w-[4.5rem] shrink-0 rounded-xl border-2 border-emerald-100 object-cover">
                <?php else: ?>
                    <span class="grid h-[4.5rem] w-[4.5rem] shrink-0 place-items-center rounded-xl border-2 border-emerald-100 bg-slate-100 text-2xl font-bold text-slate-500"><?= View::escape(strtoupper(substr((string) $student['name'], 0, 1))) ?></span>
                <?php endif; ?>
                <p class="mt-2 text-center text-sm font-bold leading-tight"><?= View::escape((string) $student['name']) ?></p>
                <p class="mt-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-emerald-800"><?= View::escape((string) $student['student_id']) ?></p>
                <dl class="mt-3 w-full space-y-2 text-[10px] text-slate-600">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-1.5"><dt>Class / Section</dt><dd class="break-words text-right font-semibold text-slate-900"><?= View::escape((string) $student['class_name']) ?> · <?= View::escape((string) $student['section_name']) ?></dd></div>
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-1.5"><dt>Roll number</dt><dd class="text-right font-semibold text-slate-900"><?= (int) $student['roll_no'] ?></dd></div>
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-1.5"><dt>Academic year</dt><dd class="text-right font-semibold text-slate-900"><?= View::escape((string) ($student['academic_year'] ?? '—')) ?></dd></div>
                    <?php if ((string) ($student['guardian_phone'] ?? '') !== ''): ?>
                        <div class="flex items-start justify-between gap-2"><dt>Guardian phone</dt><dd class="break-all text-right font-semibold text-slate-900"><?= View::escape((string) $student['guardian_phone']) ?></dd></div>
                    <?php endif; ?>
                </dl>
                <div class="mt-auto w-full border-t border-slate-200 pt-2 text-center text-[8px] font-semibold uppercase tracking-[.18em] text-teal-700"><?= View::escape($schoolName) ?></div>
            </div>
        </article>
    <?php endforeach; endif; ?>
    </div>
</div>
