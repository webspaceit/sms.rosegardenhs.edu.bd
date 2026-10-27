<?php
/** Shared responsive school navigation drawer. */
use App\Core\Icon;
use App\Core\Nav;
use App\Core\Url;
use App\Core\View;

$path = Nav::currentPath();
$groups = Nav::visibleGroups();
?>
<div class="sticky top-0 z-30 border-b border-emerald-100/10 bg-emerald-950 px-4 py-3 shadow-lg shadow-black/10 lg:hidden">
    <div class="flex items-center gap-3">
        <button type="button" id="nav-toggle" class="grid h-10 w-10 place-items-center rounded-full border border-green-100/30 text-green-100 transition hover:bg-white/10" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar">
            <?= Icon::render('menu', 'h-5 w-5') ?>
        </button>
        <a href="<?= Url::to('/dashboard') ?>" class="shrink-0"><img src="<?= Url::to('/favicon.png') ?>" alt="" class="h-9 w-9 rounded-full object-cover shadow-[0_0_12px_rgba(34,197,94,0.28)]"></a>
        <span class="font-semibold tracking-tight text-emerald-50">WSIT School</span>
        <span class="ml-auto text-xs text-emerald-200/60">Management</span>
    </div>
</div>

<div id="nav-overlay" class="fixed inset-0 z-40 hidden bg-slate-950/55 backdrop-blur-[2px] transition-opacity lg:hidden" aria-hidden="true"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-[min(86vw,360px)] max-w-[360px] -translate-x-full flex-col overflow-hidden border-r border-emerald-100/15 bg-[linear-gradient(180deg,#064e3b_0%,#064039_45%,#052e32_100%)] shadow-[20px_0_70px_rgba(0,0,0,0.38)] transition-transform duration-300 ease-out lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-72 lg:max-w-none lg:shrink-0 lg:translate-x-0 lg:rounded-none lg:shadow-[8px_0_40px_rgba(0,0,0,0.16)]">
    <div class="pointer-events-none absolute -right-24 -top-28 h-72 w-72 rounded-full bg-green-300/10 blur-3xl"></div>
    <button type="button" id="nav-close" class="absolute left-4 top-4 z-10 grid h-11 w-11 place-items-center rounded-full border border-green-100/35 bg-emerald-950/25 text-green-100/90 transition hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close navigation">
        <?= Icon::render('close', 'h-5 w-5') ?>
    </button>

    <div class="relative shrink-0 border-b border-emerald-100/10 px-5 pb-5 pt-16 lg:pt-8">
        <div class="flex items-center gap-3">
            <a href="<?= Url::to('/dashboard') ?>" class="shrink-0 grid h-16 w-16 place-items-center rounded-full border-2 border-green-200/65 bg-emerald-950/25 shadow-[0_0_0_7px_rgba(239,35,60,0.08),0_0_28px_rgba(34,197,94,0.22)] transition hover:scale-105">
                <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-full border border-green-200/35 bg-emerald-900/70">
                    <img src="<?= Url::to('/favicon.png') ?>" alt="WSIT School logo" class="h-full w-full object-cover">
                </div>
            </a>
            <p class="text-xs font-medium uppercase tracking-[0.24em] text-green-100/75">WSIT School</p>
        </div>
        <label class="relative mt-4 block text-left">
            <span class="sr-only">Search menu</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-100/55" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.5 4.5"></path></svg>
            <input id="nav-search" type="text" role="searchbox" placeholder="Search menu..." autocomplete="off" class="w-full rounded-xl border border-green-100/20 bg-emerald-950/30 py-2.5 pl-10 pr-10 text-sm text-green-50 placeholder:text-green-100/45 outline-none transition focus:border-green-200/45 focus:bg-emerald-950/45 focus:ring-2 focus:ring-green-300/10">
            <button id="nav-search-clear" type="button" class="absolute right-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 rounded-full text-lg leading-none text-green-100/55 transition hover:bg-white/10 hover:text-white" aria-label="Clear menu search">×</button>
        </label>
    </div>

    <nav class="sidebar-scroll relative flex-1 space-y-5 overflow-y-auto px-4 py-5" aria-label="Main navigation">
        <?php foreach ($groups as $group): ?>
            <?php
            $groupActive = Nav::groupIsActive($group, $path);
            $pending = Nav::pendingCount($group);
            $groupKey = preg_replace('/[^a-z0-9]+/', '-', strtolower($group['label']));
            ?>
            <details class="nav-group group" data-nav-group="<?= View::escape($groupKey) ?>" data-initial-open="<?= $groupActive || !$group['collapsible'] ? 'true' : 'false' ?>"<?= $groupActive || !$group['collapsible'] ? ' open' : '' ?>>
                <summary class="mb-2 flex cursor-pointer list-none items-center gap-2.5 px-1 text-green-100/80 transition hover:text-green-50">
                    <span class="shrink-0 text-green-200/75"><?= Icon::render($group['icon'], 'h-4 w-4') ?></span>
                    <span class="whitespace-nowrap text-[10px] font-semibold uppercase tracking-[0.2em]"><?= View::escape($group['label']) ?></span>
                    <span class="h-px flex-1 bg-gradient-to-r from-green-100/25 to-transparent"></span>
                    <?php if ($pending > 0): ?><span class="rounded-full border border-green-100/15 bg-emerald-950/20 px-2 py-0.5 text-[9px] text-green-100/45" title="<?= $pending ?> planned modules"><?= $pending ?></span><?php endif; ?>
                    <span class="nav-chevron shrink-0 text-green-100/50 transition-transform duration-200"><?= Icon::render('chevron', 'h-3.5 w-3.5') ?></span>
                </summary>
                <ul class="overflow-hidden rounded-[1.35rem] border border-emerald-50/15 bg-emerald-950/20 p-1.5 shadow-inner shadow-black/10">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php require BASE_PATH . '/resources/views/partials/nav-item.php'; ?>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
        <p id="nav-search-empty" class="hidden rounded-xl border border-dashed border-green-100/15 px-3 py-5 text-center text-sm text-green-100/55" aria-live="polite">No menu items match your search.</p>
    </nav>

    <div class="relative shrink-0 border-t border-emerald-100/10 bg-emerald-950/20 px-4 py-4">
        <form method="post" action="<?= Url::to('/logout') ?>" data-no-ajax>
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-emerald-100/65 transition hover:bg-red-400/10 hover:text-red-100">
                <?= Icon::render('logout', 'h-4 w-4') ?>
                Sign out
            </button>
        </form>
    </div>
</aside>
