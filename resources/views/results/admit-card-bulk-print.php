<?php
use App\Core\Url;
use App\Core\View;

$studentIds = array_map(static fn (array $card): int => (int) $card['student']['id'], $cards);
$switchUrl = static fn (string $target): string => Url::to('/admit-cards/bulk-print') . '?' . http_build_query(['format' => $target, 'student_ids' => $studentIds]);
$schoolName = trim((string) ($schoolSettings['school_name'] ?? '')) ?: 'WSIT School';
$signatureUrl = static fn (string $key): string => trim((string) ($schoolSettings[$key] ?? '')) !== ''
    ? Url::to('/uploads/file?path=' . rawurlencode((string) $schoolSettings[$key]))
    : '';
$cardPages = array_chunk($cards, 3);
?>
<style>
    .admit-bulk-toolbar{max-width:1100px;margin:1rem auto;padding:0 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
    .admit-bulk-toolbar a,.admit-bulk-toolbar button{border:1px solid #34745e;border-radius:.65rem;padding:.65rem 1rem;background:#073b30;color:#e8fff5;text-decoration:none;font:inherit;cursor:pointer}
    .admit-bulk-sheet{max-width:1000px;margin:1rem auto}
    .admit-bulk-card{box-sizing:border-box;min-height:265px;margin:0 0 1rem;padding:1rem;border:1.5px solid #475569;background:#fff;color:#111827;box-shadow:0 8px 22px #0002;break-inside:avoid;page-break-inside:avoid}
    .admit-bulk-head{display:grid;grid-template-columns:55px 1fr 50px;align-items:center;gap:.65rem;text-align:center;border-bottom:1px solid #334155;padding-bottom:.35rem}
    .admit-bulk-logo{width:50px;height:52px;object-fit:contain}.admit-bulk-photo{width:45px;height:55px;object-fit:cover;border:1px solid #64748b}
    .admit-bulk-head h2{font-size:.95rem;margin:0}.admit-bulk-head p{font-size:.72rem;margin:.1rem 0}.admit-bulk-head h1{font-size:1.05rem;margin:.15rem 0 0}
    .admit-bulk-fields{display:grid;grid-template-columns:1fr 1fr;gap:0 .8rem;margin-top:.3rem}.admit-bulk-field{display:grid;grid-template-columns:110px 1fr;gap:.25rem;border-bottom:1px solid #cbd5e1;padding:.25rem;font-size:.75rem}.admit-bulk-field b{font-weight:600}
    .admit-bulk-signatures{display:grid;grid-template-columns:1fr 1fr;gap:3rem;margin:1rem 1.25rem .1rem;text-align:center}.admit-bulk-signatures>div{display:flex;min-height:25px;flex-direction:column;align-items:center;justify-content:flex-end}.admit-bulk-signatures img{display:block;width:auto;max-width:100%;height:10mm;max-height:32px;object-fit:contain;margin:0 auto 1mm}.admit-bulk-signatures span{display:block;width:100%;border-top:1px solid #334155;padding-top:.2rem;font-size:.65rem}
    .admit-bulk-compact .admit-bulk-head{grid-template-columns:50px 1fr 40px;border:0;padding-bottom:.15rem}.admit-bulk-compact .admit-bulk-logo{height:45px}.admit-bulk-compact .admit-bulk-photo{width:32px;height:39px}.admit-bulk-compact .admit-bulk-fields{display:block;margin-top:.1rem}.admit-bulk-compact .admit-bulk-field{grid-template-columns:115px 1fr;padding:.15rem .25rem;font-size:.72rem}.admit-bulk-compact .admit-bulk-field b:after{content:":"}.admit-bulk-compact .admit-bulk-pair{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}.admit-bulk-compact .admit-bulk-pair .admit-bulk-field{grid-template-columns:115px 1fr}.admit-bulk-compact .admit-bulk-signatures{margin-top:.45rem}
    @media(max-width:650px){.admit-bulk-card{padding:.7rem}.admit-bulk-fields{grid-template-columns:1fr}.admit-bulk-compact .admit-bulk-pair{grid-template-columns:1fr;gap:0}}
    @media print{
        @page{size:A4 portrait;margin:10mm}
        html,body{margin:0!important;min-height:0!important;background:#fff!important}
        body:has(#admit-cards-print) #admit-cards-print{display:block!important;min-height:0!important;background:#fff!important;color:#111827!important;box-sizing:border-box!important}
        .admit-bulk-toolbar{display:none!important}
        .admit-bulk-sheet{width:190mm;height:190mm;max-width:none;margin:0 auto;display:grid;grid-template-rows:repeat(3,minmax(0,1fr));break-after:page;page-break-after:always;box-sizing:border-box}
        .admit-bulk-sheet:last-child{break-after:auto;page-break-after:auto}
        .admit-bulk-card{width:190mm;height:100%;min-height:0;margin:0;padding:3mm 4mm;box-shadow:none;border:1px solid #334155;print-color-adjust:exact;-webkit-print-color-adjust:exact}
        .admit-bulk-head{grid-template-columns:14mm minmax(0,1fr) 15mm;gap:2mm;padding-bottom:1mm}
        .admit-bulk-logo{width:13mm;height:14mm}.admit-bulk-photo{width:13mm;height:15mm}
        .admit-bulk-head h2{font-size:9pt}.admit-bulk-head p{font-size:6.5pt}.admit-bulk-head h1{font-size:10pt}
        .admit-bulk-fields{gap:0 3mm;margin-top:1mm}.admit-bulk-field{grid-template-columns:29mm minmax(0,1fr);gap:1mm;padding:.8mm .5mm;font-size:7.5pt}
        .admit-bulk-signatures{gap:25mm;margin:3mm 12mm 0}.admit-bulk-signatures>div{min-height:0}.admit-bulk-signatures img{height:8mm;max-height:8mm}.admit-bulk-signatures span{font-size:6.5pt}
        .admit-bulk-compact .admit-bulk-head{grid-template-columns:14mm minmax(0,1fr) 15mm;gap:2mm;padding-bottom:1mm}
        .admit-bulk-compact .admit-bulk-logo{width:13mm;height:14mm}.admit-bulk-compact .admit-bulk-photo{width:13mm;height:15mm}
        .admit-bulk-compact .admit-bulk-head h2{font-size:9pt}.admit-bulk-compact .admit-bulk-head h1{font-size:10pt}.admit-bulk-compact .admit-bulk-head p{font-size:6.5pt}
        .admit-bulk-compact .admit-bulk-field{grid-template-columns:29mm minmax(0,1fr);padding:.8mm .5mm;font-size:7.5pt}
        .admit-bulk-compact .admit-bulk-pair{gap:3mm}.admit-bulk-compact .admit-bulk-pair .admit-bulk-field{grid-template-columns:29mm minmax(0,1fr)}
        .admit-bulk-compact .admit-bulk-signatures{gap:25mm;margin:3mm 12mm 0}
    }
</style>
<div class="admit-bulk-toolbar">
    <a href="<?= Url::to('/admit-cards') ?>">← Back to admit cards</a>
    <span><?= count($cards) ?> cards · <?= $format === 'new' ? 'New' : 'Existing' ?> format · 3 per A4 page</span>
    <div><a href="<?= View::escape($switchUrl('existing')) ?>">Existing format</a> <a href="<?= View::escape($switchUrl('new')) ?>">New format</a> <button type="button" onclick="window.print()">Print all</button></div>
</div>
<main id="admit-cards-print">
<?php foreach ($cardPages as $pageCards): ?>
    <section class="admit-bulk-sheet">
    <?php foreach ($pageCards as $card): $student = $card['student']; $photo = trim((string) ($student['photo'] ?? '')); $photoUrl = $photo !== '' ? Url::to('/uploads/file?path=' . rawurlencode($photo)) : ''; ?>
        <article class="admit-bulk-card<?= $format === 'new' ? ' admit-bulk-compact' : '' ?>">
            <header class="admit-bulk-head">
                <img class="admit-bulk-logo" src="<?= Url::to('/favicon.png') ?>" alt="School logo">
                <div><h2><?= View::escape($schoolName) ?></h2><p><?= View::escape(trim((string) ($schoolSettings['school_address'] ?? ''))) ?></p><h1><?= $format === 'new' ? 'Admit Card' : View::escape((string) $card['exam_name'] . ' · Admit Card') ?></h1><?php if ($format === 'new'): ?><p><?= View::escape((string) $card['exam_name'] . ' ' . (string) ($student['year_name'] ?? '')) ?></p><?php endif; ?></div>
                <?php if ($photoUrl !== ''): ?><img class="admit-bulk-photo" src="<?= View::escape($photoUrl) ?>" alt="Student photo"><?php else: $avatarGender = (string) ($student['gender'] ?? ''); $avatarName = (string) $student['name']; $avatarClass = 'admit-bulk-photo'; require BASE_PATH . '/resources/views/results/partials/student-avatar.php'; endif; ?>
            </header>
            <section class="admit-bulk-fields">
                <div class="admit-bulk-field"><b><?= $format === 'new' ? 'Examinee Name' : 'Student name' ?></b><span><?= View::escape((string) $student['name']) ?></span></div>
                <div class="admit-bulk-field"><b>Student ID</b><span><?= View::escape((string) $student['student_id']) ?></span></div>
                <?php if (trim((string) ($student['father_name'] ?? '')) !== ''): ?><div class="admit-bulk-field"><b>Father's name</b><span><?= View::escape((string) $student['father_name']) ?></span></div><?php endif; ?>
                <?php if (trim((string) ($student['mother_name'] ?? '')) !== ''): ?><div class="admit-bulk-field"><b>Mother's name</b><span><?= View::escape((string) $student['mother_name']) ?></span></div><?php endif; ?>
                <?php if ($format === 'new'): ?><div class="admit-bulk-pair"><div class="admit-bulk-field"><b>Class</b><span><?= View::escape((string) $student['class_name']) ?></span></div><div class="admit-bulk-field"><b>Section</b><span><?= View::escape((string) $student['section_name']) ?></span></div></div><div class="admit-bulk-pair"><div class="admit-bulk-field"><b>Roll No</b><span><?= (int) $student['roll_no'] ?></span></div><div class="admit-bulk-field"><b>Session</b><span><?= View::escape((string) ($student['year_name'] ?? '')) ?></span></div></div>
                <?php else: ?><div class="admit-bulk-field"><b>Class / section</b><span><?= View::escape((string) $student['class_name'] . ' · ' . (string) $student['section_name']) ?></span></div><div class="admit-bulk-field"><b>Roll number</b><span><?= (int) $student['roll_no'] ?></span></div><?php endif; ?>
            </section>
            <div class="admit-bulk-signatures">
                <div><?php if (($signature = $signatureUrl('class_teacher_signature')) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="Class teacher signature"><?php endif; ?><span>Class Teacher</span></div>
                <div><?php if (($signature = $signatureUrl('principal_signature')) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="Principal signature"><?php endif; ?><span>Principal</span></div>
            </div>
        </article>
    <?php endforeach; ?>
    </section>
<?php endforeach; ?>
</main>
