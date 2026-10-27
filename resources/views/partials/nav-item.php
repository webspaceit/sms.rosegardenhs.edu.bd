<?php
/** Recursive entry used by the shared navigation drawer. */
use App\Core\Icon;
use App\Core\Nav;
use App\Core\Url;
use App\Core\View;

$isLive = $item['href'] !== null;
$isActive = Nav::isActive($item, $path);
?>
<li class="mb-[2%] last:mb-0">
    <?php if ($isLive): ?>
        <a href="<?= Url::to($item['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?> class="group/item flex min-h-12 items-center gap-3 rounded-xl border px-2.5 py-1.5 transition duration-200 <?= $isActive ? 'border-green-200/35 bg-green-100/[0.075] text-green-50 shadow-[inset_0_0_18px_rgba(34,197,94,0.06),0_0_0_1px_rgba(34,197,94,0.08)]' : 'border-green-100/10 bg-emerald-950/15 text-emerald-50/80 hover:border-green-100/25 hover:bg-green-100/[0.06] hover:text-white' ?>">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border <?= $isActive ? 'border-green-100/35 bg-green-100/[0.12] text-green-100' : 'border-green-100/15 bg-green-100/[0.045] text-green-100/75 group-hover/item:border-green-100/30 group-hover/item:text-green-50' ?>">
                <?= Icon::render($item['icon'], 'h-[18px] w-[18px]') ?>
            </span>
            <span class="min-w-0 flex-1 truncate text-[13px] font-medium"><?= View::escape($item['title']) ?></span>
            <span class="h-2 w-2 shrink-0 rounded-full bg-red-400 shadow-[0_0_9px_rgba(239,35,60,0.8)] transition-opacity <?= $isActive ? 'opacity-100' : 'opacity-0 group-hover/item:opacity-100' ?>"></span>
        </a>
    <?php else: ?>
        <div class="flex min-h-12 cursor-default items-center gap-3 rounded-xl border border-green-100/[0.05] bg-emerald-950/10 px-2.5 py-1.5 text-emerald-50/35" title="Not available yet" aria-disabled="true">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-green-100/[0.07] bg-green-100/[0.025] text-green-100/30"><?= Icon::render($item['icon'], 'h-[18px] w-[18px]') ?></span>
            <span class="min-w-0 flex-1 truncate text-[13px]"><?= View::escape($item['title']) ?></span>
            <span class="shrink-0 rounded-full border border-green-100/[0.09] bg-emerald-950/20 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[0.12em] text-green-100/35">Soon</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($item['children'])): ?>
        <ul class="mb-1 ml-5 overflow-hidden rounded-xl border-l border-green-100/15 pl-2">
            <?php foreach ($item['children'] as $child): ?>
                <?php
                $sibling = $item;
                $item = $child;
                require BASE_PATH . '/resources/views/partials/nav-item.php';
                $item = $sibling;
                ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</li>
