<?php
/**
 * The examination a student sits.
 *
 * @var array<string, mixed> $student
 * @var array<string, mixed> $exam
 * @var array<int, array<string, mixed>> $questions
 * @var array<int, array<int, string>> $options
 * @var array<string, mixed>|null $submission
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">Online examination</p>
        <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $exam['title']) ?></h1>
        <p class="mt-1 text-sm text-emerald-100/60">
            <?= View::escape((string) $student['name']) ?> ·
            <?= (int) $exam['duration_minutes'] ?> minutes ·
            <?= (int) $exam['total_marks'] ?> marks
        </p>
    </div>
    <a href="<?= Url::to('/portal/exams') ?>" class="rounded-lg border border-white/15 px-4 py-2.5 text-sm text-emerald-100/80 transition hover:border-green-300/40">← Back</a>
</div>

<?php if ($submission !== null): ?>
    <div class="mt-7 rounded-2xl border border-green-400/20 bg-green-400/[0.06] p-8 text-center">
        <p class="font-mono text-xs uppercase tracking-[0.2em] text-green-300/80">Already submitted</p>
        <p class="mt-3 text-4xl font-semibold text-green-100"><?= number_format((float) $submission['score'], 2) ?></p>
        <p class="mt-2 text-sm text-emerald-100/60">out of <?= (int) $exam['total_marks'] ?> marks</p>
        <p class="mt-1 text-xs text-emerald-100/40">Submitted on <?= View::escape((string) ($submission['submitted_at'] ?? '—')) ?></p>
    </div>
<?php elseif ($questions === []): ?>
    <div class="mt-7 rounded-2xl border border-dashed border-white/10 p-10 text-center text-sm text-emerald-100/40">This examination has no questions yet.</div>
<?php else: ?>
    <?php if ((string) ($exam['instructions'] ?? '') !== ''): ?>
        <p class="mt-6 rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm text-emerald-100/70"><?= View::escape((string) $exam['instructions']) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= Url::to('/portal/exams/submit') ?>" class="mt-6 space-y-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="exam" value="<?= (int) $exam['id'] ?>">

        <?php foreach ($questions as $index => $question): ?>
            <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="flex gap-3">
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-green-400/15 text-xs font-semibold text-green-200"><?= (int) $index + 1 ?></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium leading-6"><?= View::escape((string) $question['question']) ?></p>
                        <p class="mt-1 text-xs text-emerald-100/40"><?= (int) $question['marks'] ?> mark(s) · <?= $question['type'] === 'mcq' ? 'Choose one' : 'Write your answer' ?></p>

                        <?php if ($question['type'] === 'mcq'): ?>
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <?php foreach (($options[$question['id']] ?? []) as $optionIndex => $option): ?>
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-white/5 bg-black/20 px-3 py-2.5 text-sm transition hover:border-green-300/30">
                                        <input type="radio" name="answer[<?= (int) $question['id'] ?>]" value="<?= View::escape((string) $option) ?>" class="h-4 w-4 border-white/20 bg-black/30 text-green-400 focus:ring-green-400">
                                        <span><?= View::escape((string) $option) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <textarea name="answer[<?= (int) $question['id'] ?>]" rows="4" class="mt-4 w-full rounded-xl border border-white/10 bg-black/20 px-3 py-2.5 text-sm outline-none focus:border-green-300/40" placeholder="Write your answer here"></textarea>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <p class="text-xs text-emerald-100/40">Only the multiple choice answers are marked automatically. A teacher marks the written answers.</p>
            <button class="rounded-lg bg-green-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-green-300">Submit answers</button>
        </div>
    </form>
<?php endif; ?>
