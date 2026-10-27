<?php
/**
 * The online examinations a student may sit.
 *
 * @var array<string, mixed> $student
 * @var array<int, array<string, mixed>> $exams
 */
use App\Core\Url;
use App\Core\View;
?>
<div>
    <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">Online examinations</p>
    <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $student['name']) ?></h1>
    <p class="mt-1 text-sm text-emerald-100/60">Examinations that are open for your class.</p>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-2">
    <?php if ($exams === []): ?>
        <p class="rounded-2xl border border-dashed border-white/10 p-10 text-center text-sm text-emerald-100/40 sm:col-span-2">No examination is open for you right now.</p>
    <?php else: foreach ($exams as $exam): ?>
        <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="truncate font-semibold"><?= View::escape((string) $exam['title']) ?></h2>
                    <p class="mt-1 text-xs text-emerald-100/40">
                        <?= View::escape((string) ($exam['class_name'] ?? 'All classes')) ?>
                        <?= ($exam['subject_name'] ?? '') !== '' ? ' · ' . View::escape((string) $exam['subject_name']) : '' ?>
                    </p>
                </div>
                <span class="shrink-0 rounded-full border border-white/10 px-2.5 py-1 text-[10px] uppercase tracking-widest text-emerald-100/50"><?= View::escape((string) $exam['status']) ?></span>
            </div>

            <dl class="mt-4 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl border border-white/5 bg-black/20 py-2">
                    <dt class="font-mono text-[9px] uppercase tracking-widest text-emerald-100/40">Questions</dt>
                    <dd class="mt-1 text-sm font-semibold"><?= (int) $exam['question_count'] ?></dd>
                </div>
                <div class="rounded-xl border border-white/5 bg-black/20 py-2">
                    <dt class="font-mono text-[9px] uppercase tracking-widest text-emerald-100/40">Minutes</dt>
                    <dd class="mt-1 text-sm font-semibold"><?= (int) $exam['duration_minutes'] ?></dd>
                </div>
                <div class="rounded-xl border border-white/5 bg-black/20 py-2">
                    <dt class="font-mono text-[9px] uppercase tracking-widest text-emerald-100/40">Marks</dt>
                    <dd class="mt-1 text-sm font-semibold"><?= (int) $exam['total_marks'] ?></dd>
                </div>
            </dl>

            <?php if ((string) ($exam['instructions'] ?? '') !== ''): ?>
                <p class="mt-4 text-xs leading-5 text-emerald-100/60"><?= View::escape((string) $exam['instructions']) ?></p>
            <?php endif; ?>

            <div class="mt-5">
                <?php if ((int) $exam['attempted'] > 0): ?>
                    <span class="inline-block rounded-lg border border-white/10 px-4 py-2 text-sm text-emerald-100/50">Already sat</span>
                <?php else: ?>
                    <a href="<?= Url::to('/portal/exams/take?exam=' . (int) $exam['id']) ?>" class="inline-block rounded-lg bg-green-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-green-300">Start examination</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; endif; ?>
</div>
