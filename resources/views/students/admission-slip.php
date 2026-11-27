<?php
/** @var array<string, mixed> $student */
/** @var array<string, string> $schoolSettings */
use App\Core\DateFormatter;
use App\Core\Url;
use App\Core\View;

$schoolName = trim((string) ($schoolSettings['school_name'] ?? '')) ?: 'WSIT School';
$admissionDate = !empty($student['admission_date']) ? DateFormatter::format((string) $student['admission_date']) : DateFormatter::format((string) $student['created_at']);
$field = static fn (mixed $value): string => ($value !== null && trim((string) $value) !== '') ? View::escape($value) : '—';
?>
<style>
    .admission-slip-page{max-width:900px;margin:0 auto;color:#e2f5ec}
    .admission-slip-actions{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.25rem}
    .admission-slip-paper{background:#fff;color:#17211c;border:1px solid #aab8b0;border-radius:12px;padding:34px;box-shadow:0 18px 48px rgba(0,0,0,.2)}
    .admission-slip-school{display:flex;align-items:center;gap:18px;text-align:center;border-bottom:2px solid #0b6d50;padding-bottom:18px}
    .admission-slip-school img{width:70px;height:70px;object-fit:contain;flex:none}
    .admission-slip-school-copy{flex:1}.admission-slip-school h2{font-size:25px;line-height:1.2;font-weight:800;color:#064e3b}.admission-slip-school p{margin-top:4px;font-size:12px;color:#475569}
    .admission-slip-title{text-align:center;margin:22px 0 18px}.admission-slip-title h1{font-size:21px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.admission-slip-title p{margin-top:4px;font-size:12px;color:#52625a}
    .admission-slip-meta{display:flex;justify-content:space-between;gap:1rem;margin-bottom:16px;padding:10px 12px;border:1px solid #cbd5d1;border-radius:7px;background:#f6faf8;font-size:12px}
    .admission-slip-table{width:100%;border-collapse:collapse}.admission-slip-table th,.admission-slip-table td{border:1px solid #cbd5d1;padding:11px 12px;text-align:left;font-size:13px}.admission-slip-table th{width:25%;background:#f2f7f4;color:#33483d;font-weight:700}.admission-slip-note{margin-top:18px;border-left:4px solid #d69b27;background:#fffaeb;padding:12px 14px;font-size:12px;line-height:1.5;color:#604b1d}
    .admission-slip-signatures{display:grid;grid-template-columns:1fr 1fr;gap:50px;margin-top:65px;text-align:center;font-size:12px}.admission-slip-signatures span{border-top:1px solid #334155;padding-top:7px}
    .admission-slip-footer{margin-top:25px;text-align:center;font-size:10px;color:#64748b}
    @media(max-width:600px){.admission-slip-paper{padding:18px}.admission-slip-school{gap:10px}.admission-slip-school img{width:52px;height:52px}.admission-slip-school h2{font-size:18px}.admission-slip-table th,.admission-slip-table td{padding:8px;font-size:11px}.admission-slip-signatures{gap:20px;margin-top:45px}}
    @media print{@page{size:A4 portrait;margin:15mm}html,body{margin:0!important;min-height:0!important;background:#fff!important;color:#111!important}body:has(.admission-slip-page) *{visibility:hidden!important}body:has(.admission-slip-page) .admission-slip-page,body:has(.admission-slip-page) .admission-slip-page *{visibility:visible!important}body:has(.admission-slip-page) .admission-slip-page{position:absolute!important;top:0!important;left:0!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;color:#111!important}.admission-slip-actions{display:none!important}.admission-slip-paper{border:1px solid #64748b!important;border-radius:0!important;padding:12mm!important;box-shadow:none!important;break-inside:avoid}.admission-slip-school img{-webkit-print-color-adjust:exact;print-color-adjust:exact}.admission-slip-note{background:#fffaeb!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<div class="admission-slip-page">
    <div class="admission-slip-actions">
        <div><a href="<?= Url::to('/students/view?id=' . (int) $student['id']) ?>" class="text-sm text-green-200 hover:text-white">← Student details</a><p class="mt-2 text-sm text-green-100/70">Choose Print or Save as PDF in the print dialog.</p></div>
        <div class="flex flex-wrap gap-2"><a href="<?= Url::to('/students/view?id=' . (int) $student['id']) ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm">Student details</a><button type="button" onclick="window.print()" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm">Print admission slip</button><button type="button" onclick="window.print()" class="form-action-button">Save as PDF</button></div>
    </div>
    <main class="admission-slip-paper">
        <header class="admission-slip-school">
            <img src="<?= Url::to('/favicon.png') ?>" alt="School logo">
            <div class="admission-slip-school-copy"><h2><?= View::escape($schoolName) ?></h2><?php if (!empty($schoolSettings['school_name_bn'])): ?><p><?= View::escape($schoolSettings['school_name_bn']) ?></p><?php endif; ?><p><?= $field($schoolSettings['school_address'] ?? '') ?><?= !empty($schoolSettings['school_phone']) ? ' · ' . View::escape($schoolSettings['school_phone']) : '' ?></p></div>
        </header>
        <div class="admission-slip-title"><h1>Student Admission Slip</h1><p>Keep this slip for your admission record.</p></div>
        <div class="admission-slip-meta"><span>Admission No: <strong><?= View::escape($student['student_id']) ?></strong></span><span>Admission date: <strong><?= View::escape($admissionDate) ?></strong></span></div>
        <table class="admission-slip-table"><tbody>
            <tr><th>Student name</th><td><?= $field($student['name']) ?></td><th>Academic year</th><td><?= $field($student['year_name']) ?></td></tr>
            <tr><th>Class</th><td><?= $field($student['class_name']) ?></td><th>Section</th><td><?= $field($student['section_name']) ?></td></tr>
            <tr><th>Roll number</th><td><?= sprintf('%03d', (int) $student['roll_no']) ?></td><th>Shift</th><td><?= $field($student['shift'] ?? '') ?></td></tr>
            <?php if (!empty($student['academic_group'])): ?><tr><th>Academic group</th><td colspan="3"><?= View::escape($student['academic_group']) ?></td></tr><?php endif; ?>
            <tr><th>Guardian name</th><td><?= $field($student['guardian_name'] ?? '') ?></td><th>Relation</th><td><?= $field($student['guardian_relation'] ?? '') ?></td></tr>
            <tr><th>Guardian phone</th><td><?= $field($student['guardian_phone'] ?? '') ?></td><th>Date of birth</th><td><?= !empty($student['date_of_birth']) ? View::escape(DateFormatter::format((string) $student['date_of_birth'])) : '—' ?></td></tr>
        </tbody></table>
        <?php if (($student['registration_status'] ?? '') === 'pending'): ?><p class="admission-slip-note"><strong>Registration is incomplete.</strong> Please contact the school office to provide any remaining documents and complete the full student registration.</p><?php endif; ?>
        <div class="admission-slip-signatures"><span>Parent / Guardian signature</span><span>Authorized school signature</span></div>
        <p class="admission-slip-footer">Generated on <?= View::escape(DateFormatter::format('now')) ?></p>
    </main>
</div>
