<?php
use App\Core\Auth;
use App\Core\Toast;
use App\Core\View;

$toast = Toast::pull();
$appUser = Auth::user();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::escape($title ?? 'School Management') ?> · WSIT School</title>
    <link rel="icon" type="image/png" href="<?= \App\Core\Url::to('/favicon.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{mono:['ui-monospace','SFMono-Regular','Menlo','monospace']}}}}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif}
        .dashboard-shell{background:radial-gradient(ellipse at 82% 0%,rgba(34,197,94,.14),transparent 38rem),linear-gradient(135deg,#0b4935 0%,#073d30 42%,#062f2d 100%)}
        .dashboard-gradient{background:linear-gradient(180deg,#064e3b 0%,#064039 45%,#052e32 100%)}
        :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=reset]):not([type=range]),textarea,select){background-color:#062f2d;border-color:rgba(22,101,52,.8);color:#f1f5f9;color-scheme:dark}
        :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=reset]):not([type=file]):not([type=range]),textarea)::placeholder{color:#94a3b8;opacity:1}
        :is(input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=reset]):not([type=range]),textarea,select):focus{border-color:#34d399;outline:none;box-shadow:0 0 0 2px rgba(52,211,153,.18)}
        input[type=file]::file-selector-button{margin-right:.75rem;border:1px solid rgba(187,247,208,.25);border-radius:.5rem;background:rgba(134,239,172,.1);padding:.4rem .7rem;color:#dcfce7;font:inherit;cursor:pointer}
        input[type=file]::file-selector-button:hover{background:rgba(134,239,172,.18)}
        select option{background-color:#062f2d;color:#f1f5f9}
        select option:checked,select option:hover,select option:focus,select option:active{background-color:#047857;color:#fff}
        .student-actions>summary{cursor:pointer;list-style:none}
        .student-actions>summary::-webkit-details-marker{display:none}
        .student-actions{position:relative;z-index:1;width:2.25rem;height:2.25rem;flex:none}
        .student-actions:hover,.student-actions:focus-within,.student-actions[open]{z-index:30}
        .student-actions-controls{position:absolute;top:50%;right:100%;z-index:40;width:max-content;flex-wrap:nowrap;transform:translateY(-50%);padding:.35rem;border:1px solid rgba(187,247,208,.2);border-radius:.75rem;background:linear-gradient(135deg,#0b4935 0%,#073d30 55%,#062f2d 100%);box-shadow:0 12px 28px rgba(0,0,0,.38);display:none}
        .student-actions-controls.is-floating{position:fixed;top:var(--actions-top,50%);left:var(--actions-left,auto);right:auto;transform:translateY(-50%)}
        .student-actions:hover .student-actions-controls,.student-actions:focus-within .student-actions-controls,.student-actions[open] .student-actions-controls,.student-actions.actions-menu-visible .student-actions-controls{display:flex}
        .form-action-button{display:inline-flex;min-height:44px;align-items:center;gap:.7rem;border:1px solid rgba(187,247,208,.28);border-radius:.85rem;background:rgba(2,44,34,.5);padding:.4rem .85rem;color:#f0fdf4;font-size:.875rem;font-weight:600;transition:background-color .18s,border-color .18s,box-shadow .18s}
        .form-action-button::before{content:"＋";display:grid;height:2rem;width:2rem;flex:none;place-items:center;border:1px solid rgba(187,247,208,.24);border-radius:.65rem;background:rgba(134,239,172,.08);color:#bbf7d0;font-size:1rem}
        .form-action-button::after{content:"";height:.5rem;width:.5rem;flex:none;border-radius:999px;background:#f87171;box-shadow:0 0 9px rgba(239,35,60,.65)}
        .form-action-button:hover{border-color:rgba(187,247,208,.48);background:rgba(134,239,172,.1);box-shadow:inset 0 0 18px rgba(34,197,94,.06),0 0 0 1px rgba(34,197,94,.08)}
        .form-action-button:focus-visible{outline:2px solid #86efac;outline-offset:2px}
        .form-action-button:disabled{cursor:not-allowed;opacity:.4}
        .admit-card-page{width:100%;max-width:980px;margin:1.25rem auto;padding:1rem;border-radius:1.15rem;background:#fff;color:#0f172a;box-shadow:0 20px 55px rgba(0,0,0,.28)}
        .admit-card-sheet{position:relative;isolation:isolate;overflow:hidden;min-height:480px;border:6px double #0f172a;padding:1rem;background-color:#fff;background-image:linear-gradient(rgba(255,255,255,.9),rgba(255,255,255,.9)),radial-gradient(circle at 50% 60%,rgba(16,185,129,.12),transparent 48%);color:#111827}
        .admit-card-watermark{position:absolute;z-index:-1;top:50%;left:50%;width:300px;height:300px;object-fit:contain;opacity:.055;transform:translate(-50%,-50%);pointer-events:none}
        .admit-card-school-head{display:grid;grid-template-columns:76px minmax(0,1fr) 96px;align-items:center;gap:1rem;border-bottom:2px solid #0f766e;padding-bottom:.75rem;text-align:center}
        .admit-card-school-logo{width:72px;height:72px;object-fit:contain}
        .admit-card-exam-title{margin:0;text-align:center;color:#0f172a;font-size:1.35rem;font-weight:800;line-height:1.2}
        .admit-card-band{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:.75rem;margin:.7rem 0 .5rem}
        .admit-card-band-label{border:2px solid #0f172a;border-radius:999px;padding:.2rem .7rem;font-size:.75rem;font-weight:700;white-space:nowrap}
        .admit-card-badge{border-radius:999px;background:#020617;padding:.25rem 1.2rem;color:#fff;font-size:1.15rem;font-weight:800;white-space:nowrap}
        .admit-card-details{display:grid;grid-template-columns:minmax(0,1fr) 88px;gap:1rem;margin:.65rem 0}
        .admit-card-fields{display:grid;grid-template-columns:1fr 1fr;gap:.32rem 1.1rem;font-size:.82rem}
        .admit-card-field{display:grid;grid-template-columns:115px minmax(0,1fr);gap:.25rem;align-items:baseline}
        .admit-card-field dt{font-weight:700;color:#1e293b}
        .admit-card-field dd{margin:0;min-width:0;font-weight:600;color:#111827;overflow-wrap:anywhere}
        .admit-card-photo{width:84px;height:100px;object-fit:cover;border:1px solid #334155;background:#f1f5f9}
        .admit-card-schedule{width:100%;border-collapse:collapse;margin:.55rem 0;color:#111827;font-size:.76rem}
        .admit-card-schedule th,.admit-card-schedule td{border:1px solid #334155;padding:.12rem .35rem;text-align:center}
        .admit-card-schedule th{background:#ecfdf5;font-weight:700;font-size:.9rem}
        .admit-card-guidelines{margin:.35rem 0;color:#111827;font-family:'Noto Sans Bengali','Nirmala UI',sans-serif;font-size:.76rem;line-height:1.45}
        .admit-card-signatures{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-top:.9rem;text-align:center}
        .admit-card-signature{border-top:1px solid #334155;padding-top:.2rem;font-size:.67rem;font-weight:600;color:#111827}
        .dashboard-shell main .admit-card-page .admit-card-schedule{width:100%;margin:.55rem 0;border:1px solid #334155;border-collapse:collapse;border-radius:0;background:#fff;box-shadow:none;color:#111827;font-size:.76rem}
        .dashboard-shell main .admit-card-page .admit-card-schedule th,.dashboard-shell main .admit-card-page .admit-card-schedule td{border:1px solid #334155;padding:.12rem .35rem;text-align:center}
        .dashboard-shell main .admit-card-page .admit-card-schedule thead{background:#ecfdf5;color:#111827;font-size:.9rem}
        @media screen and (max-width:640px){.admit-card-page{padding:.65rem}.admit-card-sheet{padding:.7rem}.admit-card-school-head{grid-template-columns:48px minmax(0,1fr);gap:.5rem;text-align:left}.admit-card-school-logo{width:46px;height:46px}.admit-card-school-head>div:last-child{display:none}.admit-card-details{grid-template-columns:minmax(0,1fr) 64px;gap:.5rem}.admit-card-photo{width:62px;height:78px}.admit-card-fields{grid-template-columns:1fr}.admit-card-band{gap:.3rem}.admit-card-band-label{padding:.2rem .4rem;font-size:.62rem}.admit-card-badge{padding:.25rem .6rem;font-size:.9rem}}
        @media print{
            @page{size:A4 portrait;margin:0}
            html,body{margin:0!important;min-height:0!important;background:#fff!important}
            body:has(#admit-cards-print) *{visibility:hidden!important}
            body:has(#admit-cards-print) #admit-cards-print,body:has(#admit-cards-print) #admit-cards-print *{visibility:visible!important}
            body:has(#admit-cards-print) #admit-cards-print{position:absolute!important;top:0!important;left:0!important;width:210mm!important;margin:0!important;padding:0!important}
            body:has(#admit-cards-print) .admit-card-page{display:block;width:210mm;height:297mm;max-width:none;margin:0;padding:8mm;border:0;border-radius:0;background:#fff;box-shadow:none;break-after:page;page-break-after:always}
            body:has(#admit-cards-print) .admit-card-page:last-child{break-after:auto;page-break-after:auto}
            body:has(#admit-cards-print) .admit-card-sheet{width:194mm;height:auto;min-height:137mm;overflow:visible;margin:0;border:1.2mm double #0f172a;padding:4mm;background-color:#fff!important;print-color-adjust:exact;-webkit-print-color-adjust:exact}
            body:has(#admit-cards-print) .admit-card-school-head{grid-template-columns:18mm minmax(0,1fr) 22mm;gap:3mm;padding-bottom:2mm}
            body:has(#admit-cards-print) .admit-card-school-logo{width:17mm;height:17mm}
            body:has(#admit-cards-print) .admit-card-exam-title{font-size:14pt}
            body:has(#admit-cards-print) .admit-card-details{grid-template-columns:minmax(0,1fr) 22mm;gap:3mm}
            body:has(#admit-cards-print) .admit-card-fields{gap:1mm 3mm;font-size:8pt}
            body:has(#admit-cards-print) .admit-card-field{grid-template-columns:30mm minmax(0,1fr);gap:1mm}
            body:has(#admit-cards-print) .admit-card-photo{width:21mm;height:25mm}
            body:has(#admit-cards-print) .admit-card-schedule{font-size:7pt}
            body:has(#admit-cards-print) .admit-card-schedule th{font-size:8.5pt;font-weight:700}
            body:has(#admit-cards-print) .admit-card-schedule th,body:has(#admit-cards-print) .admit-card-schedule td{padding:.5mm}
            body:has(#admit-cards-print) .admit-card-guidelines{font-size:7.2pt;line-height:1.3}
            body:has(#admit-cards-print) .admit-card-signatures{margin-top:3mm}
            body:has(#admit-cards-print) .admit-card-signature{font-size:6.5pt}
            body:has(#admit-cards-print) .admit-card-toolbar{display:none!important}
        }
        .dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]){display:inline-flex;min-height:44px;align-items:center;gap:.7rem;border:1px solid rgba(187,247,208,.28);border-radius:.85rem;background:rgba(2,44,34,.5);padding:.4rem .85rem;color:#f0fdf4;font-size:.875rem;font-weight:600;transition:background-color .18s,border-color .18s,box-shadow .18s}
        .dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"])::before{content:"＋";display:grid;height:2rem;width:2rem;flex:none;place-items:center;border:1px solid rgba(187,247,208,.24);border-radius:.65rem;background:rgba(134,239,172,.08);color:#bbf7d0;font-size:1rem}
        .dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"])::after{content:"";height:.5rem;width:.5rem;flex:none;border-radius:999px;background:#f87171;box-shadow:0 0 9px rgba(239,35,60,.65)}
        .dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]):hover{border-color:rgba(187,247,208,.48);background:rgba(134,239,172,.1);box-shadow:inset 0 0 18px rgba(34,197,94,.06),0 0 0 1px rgba(34,197,94,.08)}
        .dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]):focus-visible{outline:2px solid #86efac;outline-offset:2px}
        .dashboard-shell main form button:not([class*="text-red-200"]):not([class*="text-rose-200"]):disabled{cursor:not-allowed;opacity:.4}
        .dashboard-shell main a[class*="rounded-lg"],.dashboard-shell main a[class*="rounded-md"]{display:inline-flex;min-height:44px;align-items:center;gap:.7rem;border:1px solid rgba(187,247,208,.28);border-radius:.85rem;background:rgba(2,44,34,.5);padding:.4rem .85rem;color:#f0fdf4;font-size:.875rem;font-weight:600;text-decoration:none;transition:background-color .18s,border-color .18s,box-shadow .18s}
        .dashboard-shell main a[class*="rounded-lg"]::before,.dashboard-shell main a[class*="rounded-md"]::before{content:"＋";display:grid;height:2rem;width:2rem;flex:none;place-items:center;border:1px solid rgba(187,247,208,.24);border-radius:.65rem;background:rgba(134,239,172,.08);color:#bbf7d0;font-size:1rem}
        .dashboard-shell main a[class*="rounded-lg"]::after,.dashboard-shell main a[class*="rounded-md"]::after{content:"";height:.5rem;width:.5rem;flex:none;border-radius:999px;background:#f87171;box-shadow:0 0 9px rgba(239,35,60,.65)}
        .dashboard-shell main a[class*="rounded-lg"]:hover,.dashboard-shell main a[class*="rounded-md"]:hover{border-color:rgba(187,247,208,.48);background:rgba(134,239,172,.1);box-shadow:inset 0 0 18px rgba(34,197,94,.06),0 0 0 1px rgba(34,197,94,.08)}
        .dashboard-shell main a[class*="rounded-lg"]:focus-visible,.dashboard-shell main a[class*="rounded-md"]:focus-visible{outline:2px solid #86efac;outline-offset:2px}
        .dashboard-shell main table{background-color:rgba(11,52,37,.8)}
        .dashboard-shell main table{border:1px solid rgba(74,222,128,.24);border-collapse:separate;border-spacing:0;border-radius:14px;box-shadow:0 0 0 1px rgba(34,197,94,.035)}
        .dashboard-shell main table th,.dashboard-shell main table td{border-right:1px solid rgba(74,222,128,.14)}
        .admit-list-shell{overflow:hidden;border:1px solid rgba(134,239,172,.2);border-radius:1.1rem;background:linear-gradient(145deg,rgba(8,67,48,.94),rgba(5,47,45,.96));box-shadow:0 16px 42px rgba(0,0,0,.16)}
        .admit-list-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.35rem 1.5rem;border-bottom:1px solid rgba(134,239,172,.18);background:linear-gradient(105deg,rgba(16,185,129,.14),rgba(6,78,59,.12))}
        .admit-list-heading h1{margin:0;font-size:1.55rem;font-weight:700;color:#f0fdf4}.admit-list-heading p:not(.admit-list-eyebrow){margin:.3rem 0 0;color:#a7c7bb;font-size:.88rem}
        .admit-list-eyebrow{margin:0 0 .25rem;color:#6ee7b7;font-size:.66rem;font-weight:700;letter-spacing:.16em}
        .admit-list-total{display:grid;gap:.1rem;text-align:right;color:#a7c7bb;font-size:.75rem}.admit-list-total strong{color:#d1fae5;font-size:1.35rem}
        .admit-list-filters{display:grid;grid-template-columns:minmax(140px,1fr) minmax(140px,1fr) minmax(190px,1.5fr) auto auto auto;align-items:end;gap:.75rem;padding:1rem 1.25rem;border-bottom:1px solid rgba(134,239,172,.15);background:rgba(2,44,34,.35)}
        .admit-list-filters label{display:grid;gap:.35rem;color:#b7d2c5;font-size:.72rem;font-weight:600}.admit-list-filters select,.admit-list-filters input{width:100%;min-height:42px;border:1px solid rgba(74,222,128,.23);border-radius:.65rem;background:#073b32;color:#f0fdf4;padding:.55rem .75rem;font-size:.85rem}
        .admit-list-filter-button,.admit-list-clear,.admit-list-action{display:inline-flex;min-height:42px;align-items:center;justify-content:center;gap:.45rem;border:1px solid rgba(134,239,172,.28);border-radius:.65rem;background:rgba(16,185,129,.12);padding:.5rem .85rem;color:#d1fae5;font-size:.8rem;font-weight:700;text-decoration:none;white-space:nowrap;transition:.18s}
        .admit-list-filter-button::before,.admit-list-filter-button::after,.admit-list-clear::before,.admit-list-clear::after,.admit-list-action::before,.admit-list-action::after{display:none!important;content:none!important}
        .admit-list-filter-button:hover,.admit-list-clear:hover,.admit-list-action:hover{border-color:#6ee7b7;background:rgba(16,185,129,.22);color:#fff}
        .admit-list-clear{background:transparent;color:#a7c7bb}
        .admit-list-per-page{max-width:110px}.admit-list-table-wrap{overflow-x:auto}
        .dashboard-shell main table.admit-list-table{width:100%;min-width:980px;border:0;border-radius:0;background:transparent;box-shadow:none;border-collapse:separate;border-spacing:0;color:#d8e8df}
        .dashboard-shell main table.admit-list-table th{border-right:1px solid rgba(167,243,208,.16);border-bottom:1px solid rgba(167,243,208,.26);background:linear-gradient(180deg,#087557,#066347);padding:.5rem .8rem;color:#eafff3;font-size:.85rem;font-weight:700;letter-spacing:.07em;white-space:nowrap}
        .dashboard-shell main table.admit-list-table td{border-right:1px solid rgba(134,239,172,.11);border-bottom:1px solid rgba(134,239,172,.13);padding:.4rem .8rem;color:#bfd4ca;font-size:.78rem;line-height:1.35;vertical-align:middle}
        .dashboard-shell main table.admit-list-table th:last-child,.dashboard-shell main table.admit-list-table td:last-child{border-right:0}
        .dashboard-shell main table.admit-list-table tbody tr{background:rgba(4,48,39,.28);transition:background .16s}.dashboard-shell main table.admit-list-table tbody tr:nth-child(even){background:rgba(19,101,71,.12)}.dashboard-shell main table.admit-list-table tbody tr:hover{background:rgba(52,211,153,.1)}
        .dashboard-shell main table.admit-list-table td.admit-list-registration{color:#a7f3d0;font-family:ui-monospace,monospace;font-size:.74rem;white-space:nowrap}.dashboard-shell main table.admit-list-table td.admit-list-name{color:#f0fdf4;font-weight:700}
        .dashboard-shell main table.admit-list-table td.admit-list-empty{text-align:center;padding:3rem 1rem;color:#9fb9ad}
        .admit-list-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem 1.25rem;border-top:1px solid rgba(134,239,172,.16);color:#a7c7bb;font-size:.77rem}.admit-list-footer p{margin:0}
        .admit-list-footer nav{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}.admit-list-footer nav a,.admit-list-footer nav span{display:grid;min-height:34px;min-width:34px;place-items:center;border:1px solid rgba(134,239,172,.2);border-radius:.55rem;padding:.35rem .55rem;color:#cde7d8;text-decoration:none}.admit-list-footer nav a:hover{border-color:#6ee7b7;background:rgba(16,185,129,.12)}.admit-list-footer nav .current{border-color:rgba(110,231,183,.6);background:rgba(16,185,129,.2);color:#d1fae5;font-weight:700}.admit-list-footer nav .disabled{color:#68877a;opacity:.65}
        .admit-print-back{color:#bbf7d0;font-size:.85rem;text-decoration:none}.admit-print-back:hover{color:#fff}
        @media(max-width:850px){.admit-list-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.admit-list-search{grid-column:1/-1}.admit-list-per-page{max-width:none}}
        @media(max-width:540px){.admit-list-heading{align-items:flex-start;padding:1rem}.admit-list-heading h1{font-size:1.3rem}.admit-list-total{font-size:.68rem}.admit-list-filters{grid-template-columns:1fr 1fr;padding:.8rem}.admit-list-filters>label:first-child,.admit-list-search{grid-column:1/-1}.admit-list-filter-button,.admit-list-clear{width:100%}.admit-list-footer{align-items:flex-start;flex-direction:column}.admit-list-footer nav{gap:.25rem}}
        .dashboard-shell main table th:last-child,.dashboard-shell main table td:last-child{border-right:0}
        .dashboard-shell main table thead th{border-bottom:1px solid rgba(74,222,128,.22)}
        .dashboard-shell main table thead{background-color:rgba(2,44,34,.72);color:rgba(167,243,208,.68)}
        .dashboard-shell main table tbody{background:none}
        .dashboard-shell main table tbody tr:hover{background-color:rgba(34,197,94,.08)}
        .dashboard-shell main table tbody tr+tr{border-color:rgba(20,83,45,.55)}
        .dashboard-shell main table.student-list-table tbody tr>td{border-bottom:1px solid rgba(74,222,128,.2)}
        .dashboard-shell main table.student-list-table tbody tr:last-child>td{border-bottom:0}
        .dashboard-shell main table tbody td[colspan]{background-color:rgba(2,44,34,.42)}
        .toast-enter{animation:toast-in .2s ease-out}
        @keyframes toast-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
        .nav-group>summary{list-style:none;cursor:pointer}
        .nav-group>summary::-webkit-details-marker{display:none}
        .nav-group>summary::marker{content:""}
        .nav-group[open] .nav-chevron{transform:rotate(180deg)}
        .sidebar-scroll{scrollbar-width:thin;scrollbar-color:rgba(74,222,128,.42) transparent;overscroll-behavior:contain;scrollbar-gutter:stable}
        .sidebar-scroll::-webkit-scrollbar{width:6px}
        .sidebar-scroll::-webkit-scrollbar-track{background:transparent;margin-block:10px}
        .sidebar-scroll::-webkit-scrollbar-thumb{border-radius:999px;background:linear-gradient(180deg,rgba(74,222,128,.52),rgba(22,163,74,.35));border:1px solid rgba(220,252,231,.08)}
        .sidebar-scroll::-webkit-scrollbar-thumb:hover{background:linear-gradient(180deg,rgba(248,113,113,.85),rgba(220,38,38,.7))}
        .sidebar-scroll::-webkit-scrollbar-corner{background:transparent}
        #sidebar{transform:translateX(-100%)}
        #sidebar.nav-open{transform:translateX(0)}
        @media (min-width:1024px){#sidebar{transform:none}}
    </style>
    <script src="<?= \App\Core\Url::to('/assets/app.js?v=' . (string) @filemtime(__DIR__ . '/../../public/assets/app.js')) ?>" defer></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <?php if ($appUser !== null && empty($publicPage)): ?>
    <div class="dashboard-shell min-h-screen lg:flex">
        <?php require BASE_PATH . '/resources/views/partials/sidebar.php'; ?>
        <main class="min-w-0 flex-1 p-5 sm:p-8">
            <?php if (\App\Core\BranchContext::canSwitch()): $availableBranches = \App\Core\BranchContext::activeBranches(); $activeBranchId = \App\Core\BranchContext::id(); ?>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-green-100/15 bg-emerald-950/35 px-4 py-3">
                <div><p class="text-[11px] font-semibold uppercase tracking-[.16em] text-green-200/60">Working campus</p><p class="mt-1 text-sm font-semibold text-green-50"><?php foreach ($availableBranches as $branch): if ((int) $branch['id'] === $activeBranchId) { echo View::escape($branch['name']); break; } endforeach; ?></p></div>
                <?php if (count($availableBranches) > 1): ?><form method="post" action="<?= \App\Core\Url::to('/branches/active') ?>" class="flex items-center gap-2"><?= \App\Core\Csrf::field() ?><input type="hidden" name="return_to" value="<?= View::escape(parse_url($_SERVER['REQUEST_URI'] ?? '/dashboard', PHP_URL_PATH) ?: '/dashboard') ?>"><label class="sr-only" for="active-branch-select">Select working campus</label><select id="active-branch-select" name="branch_id" class="rounded-lg border border-green-800 bg-[#062f2d] px-3 py-2 text-sm text-slate-100" onchange="this.form.requestSubmit()"><?php foreach ($availableBranches as $branch): ?><option value="<?= (int) $branch['id'] ?>" <?= (int) $branch['id'] === $activeBranchId ? 'selected' : '' ?>><?= View::escape($branch['name']) ?></option><?php endforeach; ?></select></form><?php endif; ?>
            </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
    <?php else: ?>
    <?= $content ?>
    <?php endif; ?>

    <?php if ($toast): ?><div id="app-toast" role="<?= $toast['type'] === 'error' ? 'alert' : 'status' ?>" aria-live="<?= $toast['type'] === 'error' ? 'assertive' : 'polite' ?>" class="toast-enter fixed bottom-5 right-5 z-50 flex max-w-sm items-center gap-3 rounded-xl border px-4 py-3 shadow-2xl <?= $toast['type'] === 'error' ? 'border-red-400/30 bg-red-950 text-red-100' : 'border-green-400/30 bg-slate-900 text-slate-100' ?>">
        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full <?= $toast['type'] === 'error' ? 'bg-red-400/15 text-red-300' : 'bg-green-400/15 text-green-300' ?>"><?= $toast['type'] === 'error' ? '!' : '✓' ?></span>
        <span class="text-sm"><?= View::escape($toast['message']) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="ml-2 text-white hover:text-white" aria-label="Dismiss">×</button>
    </div><script>setTimeout(()=>document.getElementById('app-toast')?.remove(),4200)</script><?php endif; ?>

    <script>
    (function () {
        'use strict';

        var STORAGE_KEY = 'wsit.nav.groups';

        // Restore the collapsed/expanded state of each navigation group.
        // The script is re-executed after every AJAX navigation, so it always
        // works against the freshly rendered markup.
        var stored = {};
        try { stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}') || {}; } catch (error) { stored = {}; }

        document.querySelectorAll('details[data-nav-group]').forEach(function (group) {
            var key = group.getAttribute('data-nav-group');
            if (Object.prototype.hasOwnProperty.call(stored, key)) {
                group.open = stored[key] === true;
            }
            group.addEventListener('toggle', function () {
                stored[key] = group.open;
                try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify(stored)); } catch (error) { /* storage unavailable */ }
            });
        });

        // Mobile drawer. The document level listeners are bound once and look
        // the elements up on every call, so repeated AJAX navigation cannot
        // stack duplicate handlers.
        function setDrawer(open) {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('nav-overlay');
            var toggle = document.getElementById('nav-toggle');
            if (!sidebar || !overlay) return;
            sidebar.classList.toggle('nav-open', open);
            overlay.classList.toggle('hidden', !open);
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('overflow-hidden', open);
        }

        if (!window.__wsitNavBound) {
            window.__wsitNavBound = true;
            function positionStudentActions(details) {
                if (!details || !details.matches('.student-actions')) return;
                var panel = details.querySelector('.student-actions-controls');
                var trigger = details.querySelector('summary');
                if (!panel || !trigger) return;
                panel.classList.add('is-floating');
                var anchor = trigger.getBoundingClientRect();
                var panelRect = panel.getBoundingClientRect();
                var left = anchor.left - panelRect.width + 8;
                left = Math.max(8, Math.min(window.innerWidth - panelRect.width - 8, left));
                var top = anchor.top + anchor.height / 2;
                top = Math.max(panelRect.height / 2 + 8, Math.min(window.innerHeight - panelRect.height / 2 - 8, top));
                panel.style.setProperty('--actions-left', left + 'px');
                panel.style.setProperty('--actions-top', top + 'px');
            }
            document.addEventListener('pointerover', function (event) {
                var details = event.target.closest ? event.target.closest('.student-actions') : null;
                if (!details) return;
                window.clearTimeout(details.__actionsCloseTimer);
                details.classList.add('actions-menu-visible');
                positionStudentActions(details);
            });
            document.addEventListener('pointerout', function (event) {
                var details = event.target.closest ? event.target.closest('.student-actions') : null;
                if (!details || (event.relatedTarget && details.contains(event.relatedTarget))) return;
                window.clearTimeout(details.__actionsCloseTimer);
                details.__actionsCloseTimer = window.setTimeout(function () {
                    if (details.matches(':hover,:focus-within,[open]')) return;
                    details.classList.remove('actions-menu-visible');
                }, 500);
            });
            document.addEventListener('focusin', function (event) {
                var details = event.target.closest ? event.target.closest('.student-actions') : null;
                if (details) {
                    details.classList.add('actions-menu-visible');
                    positionStudentActions(details);
                }
            });
            document.addEventListener('focusout', function (event) {
                var details = event.target.closest ? event.target.closest('.student-actions') : null;
                if (!details || (event.relatedTarget && details.contains(event.relatedTarget))) return;
                window.setTimeout(function () {
                    if (!details.matches(':hover,:focus-within,[open]')) details.classList.remove('actions-menu-visible');
                }, 0);
            });
            document.addEventListener('toggle', function (event) {
                var details = event.target;
                if (!details.matches || !details.matches('.student-actions')) return;
                if (details.open) positionStudentActions(details);
                else {
                    var panel = details.querySelector('.student-actions-controls');
                    if (panel) panel.classList.remove('is-floating');
                }
            }, true);
            window.addEventListener('resize', function () {
                document.querySelectorAll('.student-actions:hover,.student-actions:focus-within,.student-actions[open]').forEach(positionStudentActions);
            });
            window.addEventListener('scroll', function () {
                document.querySelectorAll('.student-actions:hover,.student-actions:focus-within,.student-actions[open]').forEach(positionStudentActions);
            }, true);
            document.addEventListener('click', function (event) {
                var toggle = event.target.closest ? event.target.closest('#nav-toggle') : null;
                var close = event.target.closest ? event.target.closest('#nav-close') : null;
                var overlay = event.target.closest ? event.target.closest('#nav-overlay') : null;
                if (!toggle && !close && !overlay) return;
                event.preventDefault();
                setDrawer(Boolean(toggle));
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') setDrawer(false);
            });
        }
    })();
    </script>
</body>
</html>
