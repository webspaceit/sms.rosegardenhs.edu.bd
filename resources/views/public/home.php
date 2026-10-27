<?php
/**
 * The public facing school website.
 *
 * @var array<string, string> $settings
 * @var array<int, array<string, mixed>> $notices
 * @var array<int, array<string, mixed>> $events
 * @var array<int, array<string, mixed>> $holidays
 * @var array<string, int> $counts
 */
use App\Core\Url;
use App\Core\View;

$name = $settings['school_name'] ?? 'WSIT School';
?>
<div class="min-h-screen bg-[#06150d]">
    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-teal-400 to-sky-500 font-bold text-slate-950">WS</span>
            <div class="min-w-0">
                <p class="truncate font-semibold"><?= View::escape($name) ?></p>
                <p class="truncate text-xs text-emerald-100/50"><?= View::escape($settings['school_address'] ?? '') ?></p>
            </div>
            <a href="<?= Url::to('/login') ?>" class="ml-auto rounded-lg border border-white/15 px-4 py-2 text-sm text-emerald-50 transition hover:border-teal-400/50">Sign in</a>
        </div>
    </header>

    <section class="border-b border-white/10">
        <div class="mx-auto max-w-6xl px-5 py-16 sm:py-24">
            <p class="font-mono text-xs uppercase tracking-[0.25em] text-teal-300/80"><?= View::escape($settings['school_name_bn'] ?? 'Admissions open') ?></p>
            <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight sm:text-5xl"><?= View::escape($name) ?></h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-emerald-100/60">
                A clearer view of your school, every day. Bring student records, academics, attendance and school operations together in one place.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?= Url::to('/login') ?>" class="rounded-lg bg-teal-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-teal-300">Enter the system</a>
                <?php if (($settings['school_phone'] ?? '') !== ''): ?>
                    <a href="tel:<?= View::escape($settings['school_phone']) ?>" class="rounded-lg border border-white/15 px-5 py-2.5 text-sm text-emerald-50 transition hover:border-teal-400/50"><?= View::escape($settings['school_phone']) ?></a>
                <?php endif; ?>
            </div>

            <dl class="mt-12 grid gap-4 sm:grid-cols-3">
                <?php foreach ([
                    ['Students', $counts['students'] ?? 0, 'Enrolled this year'],
                    ['Teachers', $counts['teachers'] ?? 0, 'On the faculty roster'],
                    ['Classes', $counts['classes'] ?? 0, 'From Class 1 upward'],
                ] as [$label, $value, $hint]): ?>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                        <dt class="text-sm text-emerald-100/50"><?= View::escape($label) ?></dt>
                        <dd class="mt-2 text-3xl font-semibold"><?= (int) $value ?></dd>
                        <p class="mt-1 text-xs text-emerald-100/40"><?= View::escape($hint) ?></p>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-14">
        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                <h2 class="font-mono text-xs uppercase tracking-[0.2em] text-teal-300/80">Notice board</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($notices === []): ?>
                        <p class="rounded-2xl border border-dashed border-white/10 p-6 text-center text-sm text-emerald-100/40">No notices have been published.</p>
                    <?php else: foreach ($notices as $notice): ?>
                        <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <div class="flex items-center gap-2 text-xs text-emerald-100/40">
                                <span class="rounded-full border border-white/10 px-2 py-0.5"><?= View::escape((string) $notice['type']) ?></span>
                                <span><?= View::escape((string) $notice['published_date']) ?></span>
                            </div>
                            <h3 class="mt-2 font-semibold"><?= View::escape((string) $notice['title']) ?></h3>
                            <p class="mt-1 text-sm leading-6 text-emerald-100/60"><?= View::escape((string) $notice['content']) ?></p>
                        </article>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div>
                <h2 class="font-mono text-xs uppercase tracking-[0.2em] text-teal-300/80">Coming up</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($events === [] && $holidays === []): ?>
                        <p class="rounded-2xl border border-dashed border-white/10 p-6 text-center text-sm text-emerald-100/40">Nothing is scheduled at the moment.</p>
                    <?php endif; ?>
                    <?php foreach ($events as $event): ?>
                        <article class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <span class="h-10 w-1 shrink-0 rounded-full" style="background: <?= View::escape((string) $event['color']) ?>"></span>
                            <div>
                                <p class="font-semibold"><?= View::escape((string) $event['title']) ?></p>
                                <p class="mt-0.5 text-xs text-emerald-100/50"><?= View::escape((string) $event['start_date']) ?></p>
                                <?php if ((string) ($event['description'] ?? '') !== ''): ?>
                                    <p class="mt-1 text-sm text-emerald-100/60"><?= View::escape((string) $event['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php foreach ($holidays as $holiday): ?>
                        <article class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                            <span class="h-10 w-1 shrink-0 rounded-full bg-amber-400"></span>
                            <div>
                                <p class="font-semibold"><?= View::escape((string) $holiday['title']) ?></p>
                                <p class="mt-0.5 text-xs text-emerald-100/50"><?= View::escape((string) $holiday['date']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t border-white/10">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-6 text-xs text-emerald-100/40">
            <p><?= View::escape($settings['school_footer'] ?? 'WSIT School Management System') ?></p>
            <p><?= View::escape($settings['school_email'] ?? '') ?></p>
        </div>
    </footer>
</div>
