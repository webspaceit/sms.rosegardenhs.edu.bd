<?php
/**
 * The student home.
 *
 * @var array<string, mixed> $student
 * @var array<string, mixed> $attendance
 * @var array<int, array<string, mixed>> $reports
 * @var array<int, array<string, mixed>> $fees
 * @var array<int, array<string, mixed>> $exams
 * @var array<int, array<string, mixed>> $routine
 * @var array<int, array<string, mixed>> $notices
 */
use App\Core\Url;
use App\Core\View;

$due = 0.0;
foreach ($fees as $invoice) {
    if ($invoice['status'] !== 'paid') {
        $due += (float) $invoice['amount'];
    }
}
$latestReport = $reports[0] ?? null;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">Student portal</p>
        <h1 class="mt-2 text-3xl font-semibold">Assalamu alaikum, <?= View::escape(explode(' ', (string) $student['name'])[0]) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60">
            <?= View::escape((string) $student['class_name'] ?? '') ?> · <?= View::escape((string) $student['section_name'] ?? '') ?>
            · Roll <?= (int) ($student['roll_no'] ?? 0) ?> · <?= View::escape((string) $student['student_id']) ?>
        </p>
    </div>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    $cards = [
        ['Attendance', $attendance['percent'] . '%', 'Overall attendance', 'green'],
        ['Fees due', number_format($due, 2), 'Unpaid invoices', $due > 0 ? 'amber' : 'green'],
        ['Latest GPA', $latestReport === null ? '—' : 'See results', 'Most recent exam', 'sky'],
        ['Online exams', (string) count($exams), 'Available to sit', 'emerald'],
    ];
    foreach ($cards as [$label, $value, $hint, $tone]):
        $toneClass = [
            'green' => 'text-green-200',
            'amber' => 'text-amber-200',
            'sky' => 'text-sky-200',
            'emerald' => 'text-emerald-200',
        ][$tone];
    ?>
        <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <p class="text-sm text-emerald-100/50"><?= View::escape($label) ?></p>
            <p class="mt-2 text-3xl font-semibold <?= $toneClass ?>"><?= View::escape($value) ?></p>
            <p class="mt-2 text-xs text-emerald-100/40"><?= View::escape($hint) ?></p>
        </article>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold">Today's classes</h2>
            <span class="font-mono text-xs uppercase tracking-widest text-emerald-100/40"><?= date('l') ?></span>
        </div>
        <div class="mt-4 space-y-2">
            <?php if ($routine === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No classes scheduled today.</p>
            <?php else: foreach ($routine as $slot): ?>
                <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                    <span class="w-24 shrink-0 font-mono text-xs text-emerald-100/60"><?= View::escape((string) $slot['start_time']) ?></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium"><?= View::escape((string) $slot['subject_name']) ?></span>
                        <span class="block text-xs text-emerald-100/40"><?= View::escape((string) ($slot['teacher_name'] ?? '—')) ?><?= $slot['room'] ? ' · ' . View::escape((string) $slot['room']) : '' ?></span>
                    </span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold">Online examinations</h2>
            <a href="<?= Url::to('/portal/exams') ?>" class="text-xs text-green-300 hover:text-green-200">See all →</a>
        </div>
        <div class="mt-4 space-y-2">
            <?php if ($exams === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No examination is open for you right now.</p>
            <?php else: foreach ($exams as $exam): ?>
                <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium"><?= View::escape((string) $exam['title']) ?></span>
                        <span class="block text-xs text-emerald-100/40">
                            <?= (int) $exam['question_count'] ?> question(s) · <?= (int) $exam['duration_minutes'] ?> minutes · <?= (int) $exam['total_marks'] ?> marks
                        </span>
                    </span>
                    <?php if ((int) $exam['attempted'] > 0): ?>
                        <span class="shrink-0 rounded-full border border-white/10 px-2.5 py-1 text-xs text-emerald-100/50">Sat</span>
                    <?php else: ?>
                        <a href="<?= Url::to('/portal/exams/take?exam=' . (int) $exam['id']) ?>" class="shrink-0 rounded-lg bg-green-400 px-3 py-1.5 text-xs font-semibold text-slate-950">Start</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold">My results</h2>
            <a href="<?= Url::to('/portal/results') ?>" class="text-xs text-green-300 hover:text-green-200">See all →</a>
        </div>
        <div class="mt-4 space-y-2">
            <?php if ($reports === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">No report card has been published yet.</p>
            <?php else: foreach (array_slice($reports, 0, 4) as $report): ?>
                <div class="flex items-center gap-4 rounded-xl border border-white/5 bg-black/20 p-3">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium"><?= View::escape((string) $report['exam_name']) ?></span>
                        <span class="block text-xs text-emerald-100/40"><?= View::escape((string) $report['academic_year']) ?></span>
                    </span>
                    <a href="<?= Url::to('/portal/results?report=' . (int) $report['id']) ?>" class="shrink-0 rounded-lg border border-white/10 px-3 py-1.5 text-xs hover:border-green-300/40">Open</a>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
        <h2 class="font-semibold">Notice board</h2>
        <div class="mt-4 space-y-2">
            <?php if ($notices === []): ?>
                <p class="rounded-xl border border-dashed border-white/10 p-5 text-center text-sm text-emerald-100/40">Nothing to read right now.</p>
            <?php else: foreach ($notices as $notice): ?>
                <article class="rounded-xl border border-white/5 bg-black/20 p-3">
                    <div class="flex items-center gap-2 text-xs text-emerald-100/40">
                        <span class="rounded-full border border-white/10 px-2 py-0.5"><?= View::escape((string) $notice['type']) ?></span>
                        <span><?= View::escape((string) $notice['published_date']) ?></span>
                    </div>
                    <h3 class="mt-2 text-sm font-semibold"><?= View::escape((string) $notice['title']) ?></h3>
                    <p class="mt-1 text-xs leading-5 text-emerald-100/60"><?= View::escape((string) $notice['content']) ?></p>
                </article>
            <?php endforeach; endif; ?>
        </div>
    </section>
</div>
