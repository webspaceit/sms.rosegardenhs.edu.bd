<?php
/**
 * The account dashboard.
 *
 * @var array<int, array<string, mixed>> $byHead
 * @var array<int, array<string, mixed>> $recent
 * @var array<int, array<string, mixed>> $byMonth
 */
use App\Core\Url;
use App\Core\View;
?>
<div>
    <h1 class="text-3xl font-semibold">Account dashboard</h1>
    <p class="mt-1 text-sm text-white">Fees collected, other income and what has been spent.</p>

    <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php
        $cards = [
            ['Fees collected', $feesCollected, 'teal', 'From paid invoices'],
            ['Fees outstanding', $feesDue, 'amber', 'Due and partly paid'],
            ['Other income', $income, 'emerald', 'Donations and other receipts'],
            ['Total expenses', $expense + $salaryPaid, 'rose', 'Including salary paid'],
        ];
        foreach ($cards as [$label, $value, $tone, $hint]):
            $toneClasses = [
                'teal' => 'text-teal-200',
                'amber' => 'text-amber-200',
                'emerald' => 'text-emerald-200',
                'rose' => 'text-rose-200',
            ][$tone];
        ?>
            <article class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
                <p class="text-sm text-white"><?= View::escape($label) ?></p>
                <p class="mt-2 text-3xl font-semibold <?= $toneClasses ?>"><?= number_format((float) $value, 2) ?></p>
                <p class="mt-2 text-xs text-slate-500"><?= View::escape($hint) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
            <p class="text-sm text-white">Net balance</p>
            <p class="mt-2 text-2xl font-semibold text-slate-100"><?= number_format((float) $balance, 2) ?></p>
        </article>
        <article class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
            <p class="text-sm text-white">Invoices</p>
            <p class="mt-2 text-2xl font-semibold text-slate-100"><?= (int) $feeInvoices ?></p>
        </article>
        <article class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
            <p class="text-sm text-white">Discounts given</p>
            <p class="mt-2 text-2xl font-semibold text-slate-100"><?= number_format((float) $discounts, 2) ?></p>
        </article>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
            <div class="border-b border-green-900/60 px-5 py-4">
                <h2 class="font-semibold">Fees by head</h2>
                <p class="mt-1 text-xs text-slate-500">What each fee head has brought in</p>
            </div>
            <div class="overflow-x-auto">
                <table class="admit-list-table w-full min-w-[420px] text-left text-sm">
                    <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Fee head</th>
                            <th class="px-5 py-3 text-right font-medium">Invoices</th>
                            <th class="px-5 py-3 text-right font-medium">Total</th>
                            <th class="px-5 py-3 text-right font-medium">Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-900/50">
                    <?php if ($byHead === []): ?>
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No fee heads have been set up yet.</td></tr>
                    <?php else: foreach ($byHead as $row): ?>
                        <tr>
                            <td class="px-5 py-2"><?= View::escape((string) $row['name']) ?></td>
                            <td class="px-5 py-2 text-right text-white"><?= (int) $row['invoices'] ?></td>
                            <td class="px-5 py-2 text-right"><?= number_format((float) $row['total'], 2) ?></td>
                            <td class="px-5 py-2 text-right text-teal-200"><?= number_format((float) $row['paid'], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
            <div class="border-b border-green-900/60 px-5 py-4">
                <h2 class="font-semibold">Recent transactions</h2>
                <p class="mt-1 text-xs text-slate-500">The last few entries in the ledger</p>
            </div>
            <div class="overflow-x-auto">
                <table class="admit-list-table w-full min-w-[420px] text-left text-sm">
                    <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Category</th>
                            <th class="px-5 py-3 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-green-900/50">
                    <?php if ($recent === []): ?>
                        <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">Nothing recorded yet.</td></tr>
                    <?php else: foreach ($recent as $row): ?>
                        <tr>
                            <td class="px-5 py-2 text-white"><?= View::escape((string) $row['date']) ?></td>
                            <td class="px-5 py-2">
                                <p class="text-slate-200"><?= View::escape((string) $row['category']) ?></p>
                                <p class="mt-0.5 text-xs text-slate-500"><?= View::escape((string) ($row['description'] ?? '')) ?></p>
                            </td>
                            <td class="px-5 py-2 text-right <?= $row['type'] === 'income' ? 'text-teal-200' : 'text-rose-200' ?>">
                                <?= $row['type'] === 'income' ? '+' : '−' ?><?= number_format((float) $row['amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <?php if ($byMonth !== []): ?>
        <section class="mt-5 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5">
            <h2 class="font-semibold">Income and expenses by month</h2>
            <p class="mt-1 text-xs text-slate-500">The last six months with activity</p>
            <div class="mt-4 space-y-3">
                <?php foreach ($byMonth as $row): ?>
                    <?php
                    $incomeAmount = (float) $row['income'];
                    $expenseAmount = (float) $row['expense'];
                    $peak = max($incomeAmount, $expenseAmount, 1);
                    ?>
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-mono text-white"><?= View::escape((string) $row['month']) ?></span>
                            <span class="text-slate-500">
                                <span class="text-teal-200"><?= number_format($incomeAmount, 0) ?></span>
                                /
                                <span class="text-rose-200"><?= number_format($expenseAmount, 0) ?></span>
                            </span>
                        </div>
                        <div class="mt-1.5 flex gap-1">
                            <span class="h-2 rounded-full bg-teal-400/70" style="width: <?= round($incomeAmount / $peak * 100, 2) ?>%"></span>
                            <span class="h-2 rounded-full bg-rose-400/70" style="width: <?= round($expenseAmount / $peak * 100, 2) ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="<?= Url::to('/fees') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Fee collections</a>
        <a href="<?= Url::to('/accounts/transactions') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Transactions</a>
        <a href="<?= Url::to('/accounts/discounts') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Discounts</a>
        <a href="<?= Url::to('/accounts/carry-forwards') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Carry forwards</a>
        <a href="<?= Url::to('/accounts/reconciliations') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-teal-400">Reconciliations</a>
    </div>
</div>
