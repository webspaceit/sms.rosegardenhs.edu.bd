<?php
use App\Core\Url;
use App\Core\View;

$photo = trim((string) ($student['photo'] ?? ''));
$photoUrl = $photo !== '' ? Url::to('/uploads/file?path=' . rawurlencode($photo)) : '';
$signatureUrl = static fn (string $key): string => trim((string) ($schoolSettings[$key] ?? '')) !== ''
    ? Url::to('/uploads/file?path=' . rawurlencode((string) $schoolSettings[$key]))
    : '';
$schoolName = trim((string) ($schoolSettings['school_name'] ?? '')) ?: 'WSIT School';
$examTitle = trim($examName . ' ' . (string) ($student['year_name'] ?? ''));
?>
<style>
    .admit-card-compact-sheet{width:100%;max-width:1000px;min-height:390px;margin:1.5rem auto;padding:1.25rem;border:2px solid #475569;background:#fff;color:#111827;box-shadow:0 18px 45px rgba(0,0,0,.2)}
    .admit-card-compact-head{display:grid;grid-template-columns:76px minmax(0,1fr) 50px;align-items:start;gap:1rem;padding-bottom:.75rem;text-align:center}
    .admit-card-compact-logo{width:70px;height:78px;object-fit:contain}
    .admit-card-compact-photo{width:45px;height:55px;object-fit:cover;border:1px solid #64748b;background:#f1f5f9}
    .admit-card-compact-school{margin:0;font-size:1.05rem;font-weight:700;line-height:1.25;color:#1f2937}
    .admit-card-compact-address{margin:.15rem 0;color:#334155;font-size:.78rem}
    .admit-card-compact-title{margin:.2rem 0 0;font-size:1.25rem;font-weight:800;line-height:1.2;color:#111827}
    .admit-card-compact-exam{margin:.12rem 0;font-size:1rem;font-weight:700;color:#1f2937}
    .admit-card-compact-info{display:grid;gap:.05rem;margin-top:.3rem}
    .admit-card-compact-row{display:grid;grid-template-columns:145px 14px minmax(0,1fr);align-items:baseline;gap:.2rem;border-bottom:1px dotted #cbd5e1;padding:.28rem .15rem;font-size:.9rem}
    .admit-card-compact-row strong{font-weight:600;overflow-wrap:anywhere}
    .admit-card-compact-pair{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .admit-card-compact-pair .admit-card-compact-row{grid-template-columns:145px 14px minmax(0,1fr)}
    .admit-card-compact-signatures{display:grid;grid-template-columns:1fr 1fr;align-items:end;gap:4rem;margin:1.6rem 2rem .1rem;text-align:center}
    .admit-card-compact-signature{display:flex;min-height:24px;flex-direction:column;align-items:center;justify-content:flex-end;font-size:.75rem;font-weight:600;color:#334155}.admit-card-compact-signature img{display:block;width:auto;max-width:100%;height:12mm;max-height:38px;object-fit:contain;margin:0 auto 1mm}.admit-card-compact-signature span{display:block;width:100%;border-top:1px solid #334155;padding-top:.25rem}
    @media(max-width:640px){.admit-card-compact-sheet{padding:.8rem}.admit-card-compact-head{grid-template-columns:48px minmax(0,1fr) 36px;gap:.45rem}.admit-card-compact-logo{width:44px;height:58px}.admit-card-compact-photo{width:32px;height:40px}.admit-card-compact-row{grid-template-columns:110px 12px minmax(0,1fr);font-size:.76rem}.admit-card-compact-pair{grid-template-columns:1fr;gap:0}.admit-card-compact-pair .admit-card-compact-row{grid-template-columns:110px 12px minmax(0,1fr)}.admit-card-compact-signatures{gap:1.5rem;margin:1.2rem .5rem .1rem}}
    @media print{
        @page{size:A4 landscape;margin:10mm}
        body:has(#admit-cards-print.admit-card-compact-print) #admit-cards-print.admit-card-compact-print{position:absolute!important;inset:0 auto auto 0!important;display:flex!important;align-items:center!important;justify-content:center!important;width:277mm!important;height:190mm!important;max-width:none!important;margin:0!important;padding:0!important;box-sizing:border-box!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-sheet{width:277mm!important;max-width:none!important;min-height:112mm!important;margin:0!important;padding:7mm!important;border:1px solid #334155!important;box-shadow:none!important;break-inside:avoid!important;page-break-inside:avoid!important;print-color-adjust:exact;-webkit-print-color-adjust:exact}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-head{grid-template-columns:23mm minmax(0,1fr) 15mm!important;gap:4mm!important;padding-bottom:2mm!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-logo{width:21mm!important;height:25mm!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-photo{width:13mm!important;height:15mm!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-school{font-size:13pt!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-address{font-size:8pt!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-title{font-size:17pt!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-exam{font-size:12pt!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-row{grid-template-columns:42mm 4mm minmax(0,1fr)!important;gap:1mm!important;padding:1.3mm .5mm!important;font-size:10pt!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-pair{gap:8mm!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-pair .admit-card-compact-row{grid-template-columns:42mm 4mm minmax(0,1fr)!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-signatures{gap:30mm!important;margin:10mm 20mm 0!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-signature{font-size:8pt!important}
        body:has(#admit-cards-print.admit-card-compact-print) .admit-card-compact-signature img{height:10mm!important;max-height:10mm!important}
    }
</style>
<div class="admit-card-toolbar mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-2 pt-4">
    <a class="admit-print-back" href="<?= Url::to('/admit-cards') ?>">← Back to admit cards</a>
    <div class="flex flex-wrap items-center gap-2">
        <a class="admit-print-back" href="<?= Url::to('/admit-cards/print?id=' . (int) $student['id'] . '&format=existing') ?>">Existing format</a>
        <a class="admit-print-back" href="<?= Url::to('/admit-cards/print?id=' . (int) $student['id'] . '&format=new') ?>">New format</a>
        <button type="button" class="form-action-button" onclick="window.print()">Print admit card</button>
    </div>
</div>
<div id="admit-cards-print" class="admit-card-compact-print">
    <article class="admit-card-compact-sheet">
        <header class="admit-card-compact-head">
            <img class="admit-card-compact-logo" src="<?= Url::to('/favicon.png') ?>" alt="School logo">
            <div>
                <h2 class="admit-card-compact-school"><?= View::escape($schoolName) ?></h2>
                <p class="admit-card-compact-address"><?= View::escape(trim((string) ($schoolSettings['school_address'] ?? ''))) ?></p>
                <h1 class="admit-card-compact-title">Admit Card</h1>
                <p class="admit-card-compact-exam"><?= View::escape($examTitle) ?></p>
            </div>
            <?php if ($photoUrl !== ''): ?><img class="admit-card-compact-photo" src="<?= View::escape($photoUrl) ?>" alt="Student photo"><?php else: $avatarGender = (string) ($student['gender'] ?? ''); $avatarName = (string) $student['name']; $avatarClass = 'admit-card-compact-photo'; require BASE_PATH . '/resources/views/results/partials/student-avatar.php'; endif; ?>
        </header>
        <div class="admit-card-compact-info">
            <div class="admit-card-compact-row"><span>Examinee Name</span><b>:</b><strong><?= View::escape((string) $student['name']) ?></strong></div>
            <?php if (trim((string) ($student['father_name'] ?? '')) !== ''): ?><div class="admit-card-compact-row"><span>Father's Name</span><b>:</b><strong><?= View::escape((string) $student['father_name']) ?></strong></div><?php endif; ?>
            <?php if (trim((string) ($student['mother_name'] ?? '')) !== ''): ?><div class="admit-card-compact-row"><span>Mother's Name</span><b>:</b><strong><?= View::escape((string) $student['mother_name']) ?></strong></div><?php endif; ?>
            <div class="admit-card-compact-pair">
                <div class="admit-card-compact-row"><span>Class</span><b>:</b><strong><?= View::escape((string) $student['class_name']) ?></strong></div>
                <div class="admit-card-compact-row"><span>Section</span><b>:</b><strong><?= View::escape((string) $student['section_name']) ?></strong></div>
            </div>
            <div class="admit-card-compact-pair">
                <div class="admit-card-compact-row"><span>Student ID</span><b>:</b><strong><?= View::escape((string) $student['student_id']) ?></strong></div>
                <div class="admit-card-compact-row"><span>Roll No</span><b>:</b><strong><?= (int) $student['roll_no'] ?></strong></div>
            </div>
        </div>
        <div class="admit-card-compact-signatures">
            <div class="admit-card-compact-signature"><?php if (($signature = $signatureUrl('class_teacher_signature')) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="Class teacher signature"><?php endif; ?><span>Class Teacher</span></div>
            <div class="admit-card-compact-signature"><?php if (($signature = $signatureUrl('principal_signature')) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="Principal signature"><?php endif; ?><span>Principal</span></div>
        </div>
    </article>
</div>
