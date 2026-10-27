<?php
/**
 * The portal shell.
 *
 * Teachers, students and parents get a simpler page than the administration
 * screens: a top bar, the account they are signed in with, and a short menu
 * of the pages that belong to their role.
 */
use App\Core\Auth;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;

$toast = Toast::pull();
$portalUser = Auth::user();
$portalMenu = $portalMenu ?? [];
$portalRole = (string) ($portalUser['role'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::escape($title ?? 'Portal') ?> · WSIT School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif}
        :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=reset]):not([type=file]):not([type=range]),textarea,select){background-color:#062f2d;border-color:rgba(22,101,52,.8);color:#f1f5f9;color-scheme:dark}
        :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=reset]):not([type=file]):not([type=range]),textarea)::placeholder{color:#94a3b8;opacity:1}
        :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=reset]):not([type=file]):not([type=range]),textarea,select):focus{border-color:#34d399;outline:none;box-shadow:0 0 0 2px rgba(52,211,153,.18)}
        select option{background-color:#062f2d;color:#f1f5f9}
        select option:checked,select option:hover,select option:focus,select option:active{background-color:#047857;color:#fff}
        .toast-enter{animation:toast-in .2s ease-out}
        @keyframes toast-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    </style>
    <script src="<?= Url::to('/assets/app.js?v=' . (string) @filemtime(__DIR__ . '/../../public/assets/app.js')) ?>" defer></script>
</head>
<body class="min-h-screen bg-[#06150d] text-emerald-50 antialiased">
    <header class="sticky top-0 z-30 border-b border-white/10 bg-[#06150d]/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-5 py-3">
            <a href="<?= Url::to('/portal') ?>" class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-green-400 to-sky-500 font-bold text-slate-950">WS</span>
                <span class="hidden sm:block">
                    <span class="block text-sm font-semibold leading-tight">WSIT School</span>
                    <span class="block font-mono text-[9px] uppercase tracking-[0.2em] text-emerald-100/50"><?= View::escape(ucfirst($portalRole)) ?> portal</span>
                </span>
            </a>

            <?php if ($portalMenu !== []): ?>
                <nav class="flex flex-wrap items-center gap-1 sm:ml-4" aria-label="Portal">
                    <?php foreach ($portalMenu as $item): ?>
                        <a href="<?= Url::to($item['href']) ?>" class="rounded-lg px-3 py-1.5 text-[13px] text-emerald-100/70 transition hover:bg-white/[0.06] hover:text-white"><?= View::escape($item['title']) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-medium leading-tight"><?= View::escape((string) ($portalUser['name'] ?? '')) ?></span>
                    <span class="block font-mono text-[9px] uppercase tracking-[0.18em] text-emerald-100/50"><?= View::escape($portalRole) ?></span>
                </span>
                <span class="grid h-9 w-9 place-items-center rounded-full bg-green-400/15 text-sm font-semibold text-green-100"><?= View::escape(strtoupper(substr((string) ($portalUser['name'] ?? 'U'), 0, 1))) ?></span>
                <form method="post" action="<?= Url::to('/logout') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <button class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-emerald-100/60 transition hover:border-red-300/40 hover:text-red-200">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-7">
        <?= $content ?>
    </main>

    <?php if ($toast): ?>
        <div id="app-toast" role="<?= $toast['type'] === 'error' ? 'alert' : 'status' ?>" aria-live="<?= $toast['type'] === 'error' ? 'assertive' : 'polite' ?>" class="toast-enter fixed bottom-5 right-5 z-50 flex max-w-sm items-center gap-3 rounded-xl border px-4 py-3 shadow-2xl <?= $toast['type'] === 'error' ? 'border-rose-400/30 bg-rose-950 text-rose-100' : 'border-green-400/30 bg-emerald-950 text-emerald-50' ?>">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full <?= $toast['type'] === 'error' ? 'bg-rose-400/15 text-rose-300' : 'bg-green-400/15 text-green-300' ?>"><?= $toast['type'] === 'error' ? '!' : '✓' ?></span>
            <span class="text-sm"><?= View::escape($toast['message']) ?></span>
            <button type="button" onclick="this.parentElement.remove()" class="ml-2 text-emerald-100/50 hover:text-white" aria-label="Dismiss">×</button>
        </div>
        <script>setTimeout(()=>document.getElementById('app-toast')?.remove(),4200)</script>
    <?php endif; ?>
</body>
</html>
