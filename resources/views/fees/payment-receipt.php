<?php
use App\Core\Url;
use App\Core\View;

$copies = $bankCopies ? ['Bank Copy', 'Office Copy', 'Student Copy'] : ['Office Copy', 'Student Copy'];
$method = strtolower((string) $payment['payment_method']);
$amountWords = static function (int $number): string {
    $small = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $underThousand = static function (int $value) use ($small, $tens): string {
        $words = [];
        if ($value >= 100) { $words[] = $small[intdiv($value, 100)] . ' Hundred'; $value %= 100; }
        if ($value >= 20) { $words[] = $tens[intdiv($value, 10)] . (($value % 10) ? ' ' . $small[$value % 10] : ''); }
        elseif ($value > 0) $words[] = $small[$value];
        return implode(' ', $words);
    };
    if ($number === 0) return 'Zero';
    $parts = [];
    foreach ([[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [1, '']] as [$unit, $label]) {
        if ($number >= $unit) {
            $value = intdiv($number, $unit);
            $parts[] = $underThousand($value) . ($label !== '' ? ' ' . $label : '');
            $number %= $unit;
        }
    }
    return implode(' ', $parts);
};
$total = (float) $payment['amount'];
$whole = (int) floor($total);
$paisa = (int) round(($total - $whole) * 100);
$words = $amountWords($whole) . ' Taka' . ($paisa ? ' and ' . $amountWords($paisa) . ' Paisa' : '') . ' Only';
?>
<style>
    .receipt-shell{color:#111827}.receipt-actions{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-bottom:1rem}.receipt-paper{background:#fff;color:#111827}.receipt-copies{display:grid;grid-template-columns:1fr;gap:1rem}.receipt-copy{border:1px solid #334155;padding:8mm;min-width:0}.receipt-school{display:grid;grid-template-columns:18mm minmax(0,1fr) auto;gap:3mm;align-items:center;border-bottom:1px solid #64748b;padding-bottom:3mm;text-align:center}.receipt-school img{width:17mm;height:17mm;object-fit:contain}.receipt-school h2{font-size:15pt;line-height:1.1;font-weight:800}.receipt-school p{font-size:8pt;margin-top:1mm;color:#334155}.receipt-copy-label{font-size:8pt;font-weight:700;border:1px solid #334155;padding:1mm 2mm;white-space:nowrap}.receipt-meta{display:flex;justify-content:space-between;gap:5mm;margin:3mm 0;font-size:8.5pt}.receipt-student{display:grid;grid-template-columns:1fr 1fr;gap:1mm 5mm;margin-bottom:3mm;font-size:8.5pt}.receipt-lines{width:100%;border-collapse:collapse;font-size:8.2pt}.receipt-lines th,.receipt-lines td{border:1px solid #475569;padding:1.4mm 1.6mm;text-align:left}.receipt-lines th{font-weight:700;background:#f1f5f9}.receipt-lines .money{text-align:right;white-space:nowrap}.receipt-total{text-align:right;margin-top:2mm;font-size:9pt}.receipt-total strong{font-size:14pt}.receipt-words{margin-top:2mm;font-size:8pt}.receipt-signatures{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8mm;margin-top:10mm;text-align:center;font-size:7.5pt}.receipt-signatures span{border-top:1px solid #334155;padding-top:1mm}.receipt-footer{margin-top:4mm;text-align:center;font-size:7pt;color:#475569}.receipt-summary{display:flex;justify-content:flex-end;gap:10mm;margin-top:3mm;font-size:8pt}.receipt-summary div{text-align:right}.receipt-summary b{display:block}
    @media screen and (min-width:900px){.receipt-copies{grid-template-columns:repeat(2,minmax(0,1fr))}.receipt-copies .receipt-copy{padding:6mm}.receipt-copies .receipt-school{grid-template-columns:13mm minmax(0,1fr) auto;gap:2mm}.receipt-copies .receipt-school img{width:12mm;height:12mm}.receipt-copies .receipt-school h2{font-size:11pt}.receipt-copies .receipt-school p{font-size:6.8pt}.receipt-copies .receipt-student{font-size:7.5pt}.receipt-copies .receipt-lines{font-size:7.3pt}.receipt-copies .receipt-lines th,.receipt-copies .receipt-lines td{padding:1.2mm}.receipt-copies .receipt-meta{font-size:7.5pt}.receipt-copies .receipt-total{font-size:8pt}.receipt-copies .receipt-total strong{font-size:12pt}.receipt-copies .receipt-signatures{gap:4mm;margin-top:7mm;font-size:6.7pt}.receipt-copies.bank-layout{grid-template-columns:repeat(3,minmax(0,1fr))}.receipt-copies.bank-layout .receipt-copy{padding:5mm}.receipt-copies.bank-layout .receipt-school{grid-template-columns:12mm minmax(0,1fr) auto;gap:1mm}.receipt-copies.bank-layout .receipt-school img{width:11mm;height:11mm}.receipt-copies.bank-layout .receipt-school h2{font-size:10pt}.receipt-copies.bank-layout .receipt-school p{font-size:6.5pt}.receipt-copies.bank-layout .receipt-student{font-size:7pt}.receipt-copies.bank-layout .receipt-lines{font-size:6.7pt}.receipt-copies.bank-layout .receipt-lines th,.receipt-copies.bank-layout .receipt-lines td{padding:1mm}.receipt-copies.bank-layout .receipt-meta{font-size:7pt}.receipt-copies.bank-layout .receipt-total{font-size:7.5pt}.receipt-copies.bank-layout .receipt-total strong{font-size:11pt}.receipt-copies.bank-layout .receipt-signatures{gap:3mm;margin-top:6mm;font-size:6.3pt}}
    @media print{
        @page{size:<?= $bankCopies ? 'legal landscape' : 'A4 landscape' ?>;margin:5mm}
        html,body{margin:0!important;min-height:0!important;background:#fff!important;color:#111827!important}
        body:has(#payment-receipt-print) *{visibility:hidden!important}
        body:has(#payment-receipt-print) #payment-receipt-print,body:has(#payment-receipt-print) #payment-receipt-print *{visibility:visible!important}
        body:has(#payment-receipt-print) #payment-receipt-print{position:absolute!important;top:0!important;left:0!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;background:#fff!important;color:#111827!important}
        body:has(#payment-receipt-print) .receipt-actions{display:none!important}
        body:has(#payment-receipt-print) .receipt-copies{display:grid!important;grid-template-columns:<?= $bankCopies ? 'repeat(3,minmax(0,1fr))' : 'repeat(2,minmax(0,1fr))' ?>!important;gap:0!important;align-items:stretch}
        body:has(#payment-receipt-print) .receipt-copy{box-sizing:border-box;width:100%;height:<?= $bankCopies ? '203mm' : '200mm' ?>;overflow:hidden;padding:<?= $bankCopies ? '4mm' : '7mm' ?>;border:1px solid #334155;break-inside:avoid;background:#fff!important;color:#111827!important}
        body:has(#payment-receipt-print) .receipt-copy:not(:last-child){border-right:1px dashed #475569}
        body:has(#payment-receipt-print) .receipt-school{grid-template-columns:<?= $bankCopies ? '12mm minmax(0,1fr) auto' : '14mm minmax(0,1fr) auto' ?>;gap:2mm}
        body:has(#payment-receipt-print) .receipt-school img{width:<?= $bankCopies ? '11mm' : '13mm' ?>;height:<?= $bankCopies ? '11mm' : '13mm' ?>}
        body:has(#payment-receipt-print) .receipt-school h2{font-size:<?= $bankCopies ? '10pt' : '12pt' ?>}
        body:has(#payment-receipt-print) .receipt-school p{font-size:<?= $bankCopies ? '6.4pt' : '7pt' ?>}
        body:has(#payment-receipt-print) .receipt-student{font-size:<?= $bankCopies ? '7pt' : '7.5pt' ?>}
        body:has(#payment-receipt-print) .receipt-lines{font-size:<?= $bankCopies ? '6.7pt' : '7.5pt' ?>}
        body:has(#payment-receipt-print) .receipt-lines th,body:has(#payment-receipt-print) .receipt-lines td{padding:<?= $bankCopies ? '1mm' : '1.3mm' ?>}
        body:has(#payment-receipt-print) .receipt-meta{font-size:<?= $bankCopies ? '7pt' : '8pt' ?>}
        body:has(#payment-receipt-print) .receipt-total{font-size:<?= $bankCopies ? '7.5pt' : '8.5pt' ?>}
        body:has(#payment-receipt-print) .receipt-total strong{font-size:<?= $bankCopies ? '11pt' : '13pt' ?>}
        body:has(#payment-receipt-print) .receipt-signatures{margin-top:<?= $bankCopies ? '7mm' : '8mm' ?>;font-size:<?= $bankCopies ? '6.5pt' : '7pt' ?>}
        body:has(#payment-receipt-print) .receipt-copy-label{font-size:<?= $bankCopies ? '7pt' : '7.5pt' ?>}
        body:has(#payment-receipt-print) .receipt-footer{font-size:<?= $bankCopies ? '6pt' : '6.5pt' ?>}
    }
</style>
<div class="receipt-shell mx-auto max-w-7xl">
    <div class="receipt-actions"><div><h1 class="text-2xl font-semibold text-white">Payment receipt</h1><p class="mt-1 text-sm text-white/65"><?= $bankCopies ? 'Legal landscape · three copies' : 'A4 landscape · office and student copies' ?> · <?= View::escape($payment['receipt_no']) ?></p></div><div class="flex gap-2"><a href="<?= Url::to('/fees/collect?student_id=' . (int) $payment['student_id']) ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-white">Collect another</a><button type="button" onclick="window.print()" class="rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-slate-950">Print receipt</button></div></div>
    <main id="payment-receipt-print" class="receipt-paper <?= $bankCopies ? 'bank-layout' : '' ?> rounded-xl shadow-xl">
        <div class="receipt-copies <?= $bankCopies ? 'bank-layout' : '' ?>">
            <?php foreach ($copies as $copy): ?>
            <article class="receipt-copy">
                <header class="receipt-school"><img src="<?= Url::to('/favicon.png') ?>" alt="School logo"><div><h2><?= View::escape($schoolSettings['school_name'] ?? 'WSIT School') ?></h2><?php if (!empty($schoolSettings['school_name_bn'])): ?><p><?= View::escape($schoolSettings['school_name_bn']) ?></p><?php endif; ?><p><?= View::escape($schoolSettings['school_address'] ?? '') ?><?= !empty($schoolSettings['school_phone']) ? ' · ' . View::escape($schoolSettings['school_phone']) : '' ?></p></div><span class="receipt-copy-label"><?= View::escape($copy) ?></span></header>
                <div class="receipt-meta"><strong>Student Payment Receipt</strong><span>Receipt: <b><?= View::escape($payment['receipt_no']) ?></b></span></div>
                <div class="receipt-student">
                    <div><b>Student ID:</b> <?= View::escape($payment['student_code']) ?></div><div><b>Session:</b> <?= View::escape($payment['academic_year'] ?? '—') ?></div>
                    <div><b>Name:</b> <?= View::escape($payment['student_name']) ?></div><div><b>Branch:</b> <?= View::escape($payment['branch_name']) ?></div>
                    <div><b>Class / Section:</b> <?= View::escape(($payment['class_name'] ?? '—') . ' / ' . ($payment['section_name'] ?? '—')) ?></div><div><b>Roll:</b> <?= View::escape($payment['roll_no'] ?? '—') ?></div>
                    <div><b>Contact:</b> <?= View::escape($payment['mobile_number'] ?? '—') ?></div><div><b>Payment date:</b> <?= View::escape(\App\Core\DateFormatter::format($payment['paid_on'])) ?></div>
                </div>
                <table class="receipt-lines"><thead><tr><th>No.</th><th>Particulars</th><th>Invoice / Period</th><th class="money">Discount</th><th class="money">Received</th></tr></thead><tbody>
                    <?php foreach ($allocations as $index => $allocation): ?><tr><td><?= $index + 1 ?></td><td><?= View::escape($allocation['fee_head']) ?></td><td><?= View::escape($allocation['invoice_no'] . ($allocation['month'] ? ' · ' . $allocation['month'] : '')) ?></td><td class="money">৳<?= number_format((float) $allocation['discount_amount'], 2) ?></td><td class="money">৳<?= number_format((float) $allocation['amount'], 2) ?></td></tr><?php endforeach; ?>
                    <tr><td colspan="3" class="money"><b>Total</b></td><td class="money"><b>৳<?= number_format($discountTotal, 2) ?></b></td><td class="money"><b>৳<?= number_format($total, 2) ?></b></td></tr>
                </tbody></table>
                <div class="receipt-summary"><div><span>Previous due</span><b>৳<?= number_format($previousDue, 2) ?></b></div><div><span>Deposit</span><b>৳<?= number_format($total, 2) ?></b></div><div><span>Discount</span><b>৳<?= number_format($discountTotal, 2) ?></b></div><div><span>Due</span><b>৳<?= number_format($dueAfter, 2) ?></b></div></div>
                <div class="receipt-total"><b>Payment method:</b> <?= View::escape($payment['payment_method']) ?><?= !empty($payment['reference_no']) ? ' · Ref: ' . View::escape($payment['reference_no']) : '' ?><br><b>Received:</b> <strong>৳<?= number_format($total, 2) ?></strong></div>
                <p class="receipt-words"><b>Amount in words:</b> <?= View::escape($words) ?></p>
                <?php if (!empty($payment['notes'])): ?><p class="receipt-words"><b>Note:</b> <?= View::escape($payment['notes']) ?></p><?php endif; ?>
                <div class="receipt-signatures"><span>Signature of payer</span><span>Received by<?= !empty($payment['received_by_name']) ? ' · ' . View::escape($payment['received_by_name']) : '' ?></span><span>Authorized signature</span></div>
                <footer class="receipt-footer">Thank you. Keep this receipt for your records.</footer>
            </article>
            <?php endforeach; ?>
        </div>
    </main>
</div>
