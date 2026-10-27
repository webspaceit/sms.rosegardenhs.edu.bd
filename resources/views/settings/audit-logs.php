<?php
use App\Core\Url;
use App\Core\View;
?>
<header class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-3xl font-semibold">Audit log</h1><p class="mt-1 text-sm text-white">Recent account and record changes · <?= (int) $total ?> events</p></div></header>
<section class="mt-6 overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient">
  <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[850px] text-left text-sm"><thead class="text-xs uppercase tracking-wide text-green-100/60"><tr><th class="px-4 py-3">When</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Record</th><th class="px-4 py-3">Details</th><th class="px-4 py-3">IP address</th></tr></thead><tbody class="divide-y divide-green-900/50">
  <?php if ($records === []): ?><tr><td colspan="6" class="px-4 py-10 text-center text-white">No audit events have been recorded yet.</td></tr><?php else: foreach ($records as $record): ?>
    <tr><td class="whitespace-nowrap px-4 py-3 text-slate-300"><?= View::escape(date('M j, Y H:i', strtotime((string) $record['created_at']))) ?></td><td class="px-4 py-3 text-slate-200"><?= View::escape($record['user_email'] ?: 'Unauthenticated') ?></td><td class="px-4 py-3 text-green-100"><?= View::escape($record['action']) ?></td><td class="px-4 py-3 text-slate-300"><?= View::escape($record['entity']) ?><?= $record['entity_id'] ? ' #' . (int) $record['entity_id'] : '' ?></td><td class="max-w-sm px-4 py-3 text-slate-400"><?= View::escape($record['details'] ?: '—') ?></td><td class="px-4 py-3 text-slate-400"><?= View::escape($record['ip_address'] ?: '—') ?></td></tr>
  <?php endforeach; endif; ?>
  </tbody></table></div>
  <div class="flex items-center justify-between border-t border-green-900/60 px-4 py-3 text-sm"><span class="text-slate-400">Page <?= (int) $page ?> of <?= (int) $pages ?></span><div class="flex gap-2"><?php if ($page > 1): ?><a class="rounded-lg border border-green-800 px-3 py-1.5" href="<?= Url::to('/audit-logs?page=' . ($page - 1)) ?>">Previous</a><?php endif; ?><?php if ($page < $pages): ?><a class="rounded-lg border border-green-800 px-3 py-1.5" href="<?= Url::to('/audit-logs?page=' . ($page + 1)) ?>">Next</a><?php endif; ?></div></div>
</section>
