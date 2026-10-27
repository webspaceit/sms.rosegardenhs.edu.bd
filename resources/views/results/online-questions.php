<?php
/**
 * The questions of an online exam.
 *
 * @var array<string, mixed> $exam
 * @var array<int, array<string, mixed>> $questions
 */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<div class="mx-auto max-w-4xl">
    <a href="<?= Url::to('/online-exams') ?>" class="text-sm text-teal-300 hover:text-teal-200">← Online examinations</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= View::escape((string) $exam['title']) ?></h1>
    <p class="mt-1 text-sm text-white"><?= count($questions) ?> question(s) · <?= (int) $exam['total_marks'] ?> marks · <?= (int) $exam['duration_minutes'] ?> minutes</p>

    <div class="mt-5 flex flex-wrap gap-3">
        <a href="<?= Url::to('/online-exams/results?exam=' . (int) $exam['id']) ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Results</a>
    </div>

    <section class="mt-6 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
        <h2 class="font-semibold">Add a question</h2>
        <form method="post" action="<?= Url::to('/online-exams/questions/store') ?>" class="mt-4 space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="exam" value="<?= (int) $exam['id'] ?>">
            <label class="block text-sm">
                <span class="mb-1.5 block text-slate-300">Question</span>
                <textarea name="question" required rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></textarea>
            </label>
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Type</span>
                    <select name="type" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                        <option value="mcq">Multiple choice</option>
                        <option value="written">Written</option>
                    </select>
                </label>
                <label class="text-sm sm:col-span-2">
                    <span class="mb-1.5 block text-slate-300">Options</span>
                    <textarea name="options" rows="3" placeholder="One option per line" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></textarea>
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Correct answer</span>
                    <input name="correct_answer" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
                <label class="text-sm">
                    <span class="mb-1.5 block text-slate-300">Marks</span>
                    <input type="number" name="marks" value="1" min="0" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                </label>
            </div>
            <div class="flex justify-end">
                <button class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Add question</button>
            </div>
        </form>
    </section>

    <section class="mt-5 space-y-3">
    <?php if ($questions === []): ?>
        <p class="rounded-2xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-500">No questions have been added yet.</p>
    <?php else: foreach ($questions as $index => $question): ?>
        <article class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-medium"><?= (int) $index + 1 ?>. <?= View::escape((string) $question['question']) ?></p>
                    <p class="mt-1 text-xs text-slate-500">
                        <?= $question['type'] === 'mcq' ? 'Multiple choice' : 'Written' ?> · <?= (int) $question['marks'] ?> mark(s)
                        <?php if ((string) ($question['correct_answer'] ?? '') !== ''): ?>
                            · answer: <?= View::escape((string) $question['correct_answer']) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ((string) ($question['options'] ?? '') !== ''): ?>
                        <p class="mt-1 text-xs text-white"><?= View::escape(implode('  ·  ', array_map('strval', (array) json_decode((string) $question['options'], true)))) ?></p>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= Url::to('/online-exams/questions/delete') ?>" data-confirm="Remove this question?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="exam" value="<?= (int) $exam['id'] ?>">
                    <input type="hidden" name="id" value="<?= (int) $question['id'] ?>">
                    <button class="rounded-md border border-slate-700 px-3 py-1.5 text-xs text-rose-200 hover:border-rose-400">Remove</button>
                </form>
            </div>
        </article>
    <?php endforeach; endif; ?>
    </section>
</div>
