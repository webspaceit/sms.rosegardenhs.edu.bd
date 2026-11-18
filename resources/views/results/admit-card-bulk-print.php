<?php
use App\Core\Url;
use App\Core\View;

require BASE_PATH . '/resources/views/results/partials/class-name-word.php';
$studentIds = array_map(static fn (array $card): int => (int) $card['student']['id'], $cards);
$switchUrl = static fn (string $target): string => Url::to('/admit-cards/bulk-print') . '?' . http_build_query(['format' => $target, 'student_ids' => $studentIds]);
$schoolName = trim((string) ($schoolSettings['school_name'] ?? '')) ?: 'WSIT School';
$signatureUrl = static fn (string $key): string => trim((string) ($schoolSettings[$key] ?? '')) !== ''
    ? Url::to('/uploads/file?path=' . rawurlencode((string) $schoolSettings[$key]))
    : '';
$cardsPerPage = $format === 'existing' ? 2 : 4;
$cardPages = array_chunk($cards, $cardsPerPage);
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
    .admit-bulk-existing-card .admit-card-sheet{width:100%;min-height:0;margin:0;padding:3mm;border:1px solid #0f172a;border-radius:0;background:#fff;color:#111827;box-shadow:none}
    .admit-bulk-existing-card .admit-card-school-head{grid-template-columns:18mm minmax(0,1fr) 16mm;gap:3mm;padding-bottom:2mm}
    .admit-bulk-existing-card .admit-card-school-logo{width:17mm;height:17mm}
    .admit-bulk-existing-card .admit-card-school-head h2{margin:0;font-size:12pt;font-weight:800;line-height:1.2;color:#111827}
    .admit-bulk-existing-card .admit-card-school-head p{margin:.5mm 0 0;font-size:7.5pt;color:#334155}
    .admit-bulk-existing-card .admit-card-photo{width:15mm;height:18mm}
    .admit-bulk-existing-card .admit-card-exam-title{margin-top:1mm;font-size:12pt}
    .admit-bulk-existing-card .admit-card-band{gap:2mm;margin:2mm 0}
    .admit-bulk-existing-card .admit-card-band-label{padding:.7mm 2mm;font-size:7pt}
    .admit-bulk-existing-card .admit-card-badge{padding:1mm 4mm;font-size:11pt}
    .admit-bulk-existing-card .admit-card-details{margin:1.5mm 0}
    .admit-bulk-existing-card .admit-card-fields{gap:1mm 3mm;font-size:7.5pt}
    .admit-bulk-existing-card .admit-card-fields-column{gap:1mm}
    .admit-bulk-existing-card .admit-card-field{grid-template-columns:27mm minmax(0,1fr);gap:1mm}
    .admit-bulk-existing-card .admit-card-schedule-title{margin:1mm 0;font-size:8pt;font-weight:700;text-align:center}
    .admit-bulk-existing-card .admit-card-schedule{margin:1mm 0;font-size:6.5pt}
    .admit-bulk-existing-card .admit-card-schedule th{font-size:7pt}
    .admit-bulk-existing-card .admit-card-schedule th,.admit-bulk-existing-card .admit-card-schedule td{padding:.5mm}
    .admit-bulk-existing-card .admit-card-guidelines{margin:1mm 0;font-size:6.5pt;line-height:1.25}
    .admit-bulk-existing-card .admit-card-guidelines h2{margin-bottom:.5mm;font-size:7pt}
    .admit-bulk-existing-card .admit-card-signatures{gap:2mm;margin-top:2mm}
    .admit-bulk-existing-card .admit-card-signature{font-size:5.7pt}
    .admit-bulk-existing-card .admit-card-signature img{height:6mm;max-height:6mm}
    .admit-bulk-existing-card .admit-card-sheet>footer{margin-top:1mm;padding-top:.5mm;font-size:6pt}
    @media(max-width:650px){.admit-bulk-card{padding:.7rem}.admit-bulk-fields{grid-template-columns:1fr}.admit-bulk-compact .admit-bulk-pair{grid-template-columns:1fr;gap:0}}
    @media print{
        @page{size:A4 portrait;margin:0}
        html,body{margin:0!important;min-height:0!important;background:#fff!important}
        body:has(#admit-cards-print) .dashboard-shell{display:block!important;min-height:0!important;background:#fff!important}
        body:has(#admit-cards-print) .dashboard-shell> :not(main){display:none!important}
        body:has(#admit-cards-print) .dashboard-shell>main{display:block!important;width:100%!important;min-height:0!important;margin:0!important;padding:0!important;background:#fff!important}
        body:has(#admit-cards-print) .dashboard-shell>main> :not(#admit-cards-print){display:none!important}
        body:has(#admit-cards-print) #admit-cards-print{position:static!important;top:auto!important;left:auto!important;display:block!important;width:194mm!important;max-width:none!important;min-height:0!important;margin:0 auto!important;padding:0!important;background:#fff!important;color:#111827!important;box-sizing:border-box!important}
        .admit-bulk-toolbar{display:none!important}
        .admit-bulk-sheet{width:194mm;height:296mm;max-width:none;margin:0;padding:8mm 0 7mm;display:grid;grid-template-rows:repeat(4,minmax(0,1fr));row-gap:3mm;break-inside:avoid;page-break-inside:avoid;break-after:page;page-break-after:always;box-sizing:border-box}
        .admit-bulk-format-existing .admit-bulk-sheet{grid-template-rows:repeat(2,minmax(0,1fr));row-gap:3mm;padding:4mm 0 3mm}
        .admit-bulk-sheet:last-child{break-after:auto;page-break-after:auto}
        .admit-bulk-card{width:100%;height:100%;min-height:0;margin:0;padding:3mm 4mm;box-shadow:none;border:1px solid #334155;print-color-adjust:exact;-webkit-print-color-adjust:exact}
        .admit-bulk-head{grid-template-columns:14mm minmax(0,1fr) 15mm;gap:2mm;padding-bottom:1mm}
        .admit-bulk-logo{width:13mm;height:14mm}.admit-bulk-photo{width:13mm;height:15mm}
        .admit-bulk-head h2{font-size:9pt}.admit-bulk-head p{font-size:6.5pt}.admit-bulk-head h1{font-size:10pt}
        .admit-bulk-fields{gap:0 3mm;margin-top:1mm}.admit-bulk-field{grid-template-columns:29mm minmax(0,1fr);gap:1mm;padding:.8mm .5mm;font-size:7.5pt}
        .admit-bulk-signatures{gap:25mm;margin:3mm 12mm 0}.admit-bulk-signatures>div{min-height:0}.admit-bulk-signatures img{height:8mm;max-height:8mm}.admit-bulk-signatures span{font-size:6.5pt}
        .admit-bulk-compact .admit-bulk-head{grid-template-columns:14mm minmax(0,1fr) 15mm;gap:2mm;padding-bottom:1mm}
        .admit-bulk-compact .admit-bulk-logo{width:13mm;height:14mm}.admit-bulk-compact .admit-bulk-photo{width:13mm;height:15mm}
        .admit-bulk-compact .admit-bulk-head h2{font-size:10.5pt}.admit-bulk-compact .admit-bulk-head h1{font-size:11.5pt}.admit-bulk-compact .admit-bulk-head p{font-size:7.5pt}
        .admit-bulk-compact .admit-bulk-field{grid-template-columns:29mm minmax(0,1fr);padding:.8mm .5mm;font-size:7.5pt}
        .admit-bulk-compact .admit-bulk-pair{gap:3mm}.admit-bulk-compact .admit-bulk-pair .admit-bulk-field{grid-template-columns:29mm minmax(0,1fr)}
        .admit-bulk-compact .admit-bulk-signatures{gap:25mm;margin:3mm 12mm 0}
        .admit-bulk-compact{padding:2mm 4mm}
        .admit-bulk-compact .admit-bulk-fields{display:block;margin-top:.4mm}
        .admit-bulk-compact .admit-bulk-field{padding:.6mm .3mm;font-size:8.2pt;line-height:1.12}
        .admit-bulk-compact .admit-bulk-pair{gap:2mm}
        .admit-bulk-compact .admit-bulk-signatures{position:static;grid-template-columns:1fr 1fr;gap:25mm;margin:1mm 12mm 0!important}
        .admit-bulk-compact .admit-bulk-signatures>div{height:auto;min-height:0}
        .admit-bulk-compact .admit-bulk-signatures img{width:auto;height:6mm;max-width:100%;max-height:6mm;object-fit:contain;margin:0 auto .3mm}
        .admit-bulk-compact .admit-bulk-signatures span{display:block;width:100%;border-top:1px solid #334155;padding-top:.35mm;font-size:6.5pt;line-height:1.05}
        .admit-bulk-existing-card{width:194mm;height:143mm;max-width:none;margin:0;padding:0;border:0;border-radius:0;background:#fff;box-shadow:none;break-inside:avoid;page-break-inside:avoid}
        .admit-bulk-existing-card .admit-card-sheet{width:194mm;height:143mm;min-height:0;overflow:hidden;margin:0;border:1.2mm double #0f172a;padding:1.5mm;background-color:#fff!important;box-sizing:border-box;print-color-adjust:exact;-webkit-print-color-adjust:exact}
        .admit-bulk-existing-card .admit-card-watermark{width:38mm;height:38mm}
        .admit-bulk-existing-card .admit-card-school-head{grid-template-columns:14mm minmax(0,1fr) 14mm;gap:2mm;padding-bottom:1mm}
        .admit-bulk-existing-card .admit-card-school-logo{width:13mm;height:13mm}
        .admit-bulk-existing-card .admit-card-exam-title{margin-top:.5mm;font-size:12pt}
        .admit-bulk-existing-card .admit-card-band{gap:1.5mm;margin:1mm 0}
        .admit-bulk-existing-card .admit-card-band-label{padding:.5mm 1.5mm;font-size:6.5pt}
        .admit-bulk-existing-card .admit-card-badge{padding:.6mm 3mm;font-size:10pt}
        .admit-bulk-existing-card .admit-card-details{display:block}
        .admit-bulk-existing-card .admit-card-fields{gap:1mm 3mm;font-size:8pt}
        .admit-bulk-existing-card .admit-card-fields-column{gap:1mm}
        .admit-bulk-existing-card .admit-card-field{grid-template-columns:30mm minmax(0,1fr);gap:1mm}
        .admit-bulk-existing-card .admit-card-photo{width:15mm;height:18mm}
        .admit-bulk-existing-card .admit-card-schedule-title{margin:.5mm 0;text-align:center;font-size:7.5pt;font-weight:700}
        .admit-bulk-existing-card .admit-card-schedule{margin:.5mm 0;font-size:7pt;line-height:1.05}
        .admit-bulk-existing-card .admit-card-schedule th{font-size:7.2pt;font-weight:700}
        .admit-bulk-existing-card .admit-card-schedule th,.admit-bulk-existing-card .admit-card-schedule td{padding:.25mm}
        .admit-bulk-existing-card .admit-card-guidelines{margin:.5mm 0;font-size:7pt;line-height:1.12}
        .admit-bulk-existing-card .admit-card-guidelines h2{margin-bottom:.25mm;font-size:7.2pt}
        .admit-bulk-existing-card .admit-card-signatures{gap:1.5mm;margin-top:1mm}
        .admit-bulk-existing-card .admit-card-signature{font-size:6.5pt;line-height:1.05}
        .admit-bulk-existing-card .admit-card-signature img{height:6mm;max-height:6mm;margin:0 auto .3mm}
        .admit-bulk-existing-card .admit-card-signature span{padding-top:.3mm}
        .admit-bulk-existing-card .admit-card-sheet>footer{margin-top:.5mm;padding-top:.3mm;font-size:6pt;line-height:1}
    }
</style>
<div class="admit-bulk-toolbar">
    <a href="<?= Url::to('/admit-cards') ?>">← Back to admit cards</a>
    <span><?= count($cards) ?> cards · <?= $format === 'new' ? 'New' : 'Existing' ?> format · <?= $cardsPerPage ?> per A4 page</span>
    <div><a href="<?= View::escape($switchUrl('existing')) ?>">Existing format</a> <a href="<?= View::escape($switchUrl('new')) ?>">New format</a> <button type="button" onclick="window.print()">Print all</button></div>
</div>
<main id="admit-cards-print" class="admit-cards-bulk-print<?= $format === 'existing' ? ' admit-bulk-format-existing' : '' ?>">
<?php foreach ($cardPages as $pageCards): ?>
    <section class="admit-bulk-sheet">
    <?php foreach ($pageCards as $card): $student = $card['student']; $photo = trim((string) ($student['photo'] ?? '')); $photoUrl = $photo !== '' ? Url::to('/uploads/file?path=' . rawurlencode($photo)) : ''; ?>
        <?php if ($format === 'existing'): ?>
        <article class="admit-card-page admit-bulk-existing-card">
            <div class="admit-card-sheet">
                <img class="admit-card-watermark" src="<?= Url::to('/favicon.png') ?>" alt="" aria-hidden="true">
                <header class="admit-card-school-head">
                    <img class="admit-card-school-logo" src="<?= Url::to('/favicon.png') ?>" alt="School logo">
                    <div>
                        <h2><?= View::escape($schoolSettings['school_name'] ?? 'WSIT School') ?></h2>
                        <?php if (trim((string) ($schoolSettings['school_name_bn'] ?? '')) !== ''): ?><p><?= View::escape($schoolSettings['school_name_bn']) ?></p><?php endif; ?>
                        <p><?= View::escape(implode(' · ', array_filter([trim((string) ($schoolSettings['school_address'] ?? '')), trim((string) ($schoolSettings['school_phone'] ?? '')), trim((string) ($schoolSettings['school_email'] ?? ''))]))) ?></p>
                    </div>
                    <?php if ($photoUrl !== ''): ?><img class="admit-card-photo" src="<?= View::escape($photoUrl) ?>" alt="Photo of <?= View::escape((string) $student['name']) ?>"><?php else: $avatarGender = (string) ($student['gender'] ?? ''); $avatarName = (string) $student['name']; $avatarClass = 'admit-card-photo'; require BASE_PATH . '/resources/views/results/partials/student-avatar.php'; endif; ?>
                </header>
                <h1 class="admit-card-exam-title"><?= View::escape((string) $card['exam_name']) ?></h1>
                <div class="admit-card-band"><span class="admit-card-band-label">Student ID: <?= View::escape((string) $student['student_id']) ?></span><span class="admit-card-badge">Admit Card</span><span class="admit-card-band-label text-right">Roll: <?= (int) $student['roll_no'] ?></span></div>
                <div class="admit-card-details"><div class="admit-card-fields">
                    <dl class="admit-card-fields-column">
                        <div class="admit-card-field"><dt>Student name</dt><dd><?= View::escape((string) $student['name']) ?></dd></div>
                        <?php if (trim((string) ($student['father_name'] ?? '')) !== ''): ?><div class="admit-card-field"><dt>Father's name</dt><dd><?= View::escape((string) $student['father_name']) ?></dd></div><?php endif; ?>
                        <?php if (trim((string) ($student['mother_name'] ?? '')) !== ''): ?><div class="admit-card-field"><dt>Mother's name</dt><dd><?= View::escape((string) $student['mother_name']) ?></dd></div><?php endif; ?>
                    </dl>
                    <dl class="admit-card-fields-column">
                        <div class="admit-card-field"><dt>Class</dt><dd><?= View::escape($formatClassName($student['class_name'])) ?></dd></div>
                        <div class="admit-card-field"><dt>Section</dt><dd><?= View::escape((string) $student['section_name']) ?></dd></div>
                    </dl>
                </div></div>
                <h2 class="admit-card-schedule-title">Exam Schedule · <?= View::escape((string) $card['exam_name']) ?></h2>
                <table class="admit-card-schedule"><thead><tr><th>Sl.</th><th>Subject</th><th>Date</th><th>Day</th><th>Time / hall</th></tr></thead><tbody>
                    <?php if (($card['schedule'] ?? []) === []): ?><tr><td colspan="5">Schedule not available</td></tr><?php else: foreach ($card['schedule'] as $scheduleIndex => $paper): ?>
                        <?php $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $paper['date']); $time = ''; if (trim((string) ($paper['start_time'] ?? '')) !== '') { $time = date('g:i A', strtotime((string) $paper['start_time'])); } if (trim((string) ($paper['end_time'] ?? '')) !== '') { $time .= ($time !== '' ? ' – ' : '') . date('g:i A', strtotime((string) $paper['end_time'])); } ?>
                        <tr><td><?= (int) $scheduleIndex + 1 ?></td><td><?= View::escape((string) $paper['subject_name']) ?></td><td><?= $date ? View::escape(\App\Core\DateFormatter::format($date->format('Y-m-d'))) : '—' ?></td><td><?= $date ? View::escape($date->format('l')) : '—' ?></td><td><?= View::escape(implode(' · ', array_filter([$time, trim((string) ($paper['hall'] ?? ''))]))) ?: '—' ?></td></tr>
                    <?php endforeach; endif; ?>
                </tbody></table>
                <section class="admit-card-guidelines"><h2>পরীক্ষার্থীদের জন্য নির্দেশনা</h2><ol class="list-decimal space-y-0.5 pl-5"><li>প্রবেশপত্র ছাড়া কোনো পরীক্ষার্থী পরীক্ষাকক্ষে প্রবেশ করতে পারবে না।</li><li>পরীক্ষা শুরুর অন্তত ৩০ মিনিট আগে কেন্দ্রে উপস্থিত হতে হবে।</li><li>মোবাইল ফোন বা অননুমোদিত সামগ্রী পরীক্ষাকক্ষে আনা যাবে না।</li></ol></section>
                <div class="admit-card-signatures"><?php foreach (['class_teacher_signature' => 'Class teacher', 'invigilator_signature' => 'Invigilator', 'exam_controller_signature' => 'Exam controller', 'head_teacher_signature' => 'Head teacher'] as $signatureKey => $signatureLabel): ?><div class="admit-card-signature"><?php if (($signature = $signatureUrl($signatureKey)) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="<?= View::escape($signatureLabel) ?> signature"><?php endif; ?><span><?= View::escape($signatureLabel) ?></span></div><?php endforeach; ?></div>
                <footer class="border-t border-slate-300 text-center text-slate-600">Please bring this admit card to every examination.</footer>
            </div>
        </article>
        <?php else: ?>
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
                <?php if ($format === 'new'): ?><div class="admit-bulk-pair"><div class="admit-bulk-field"><b>Class</b><span><?= View::escape($formatClassName($student['class_name'])) ?></span></div><div class="admit-bulk-field"><b>Section</b><span><?= View::escape((string) $student['section_name']) ?></span></div></div><div class="admit-bulk-pair"><div class="admit-bulk-field"><b>Roll No</b><span><?= (int) $student['roll_no'] ?></span></div><div class="admit-bulk-field"><b>Session</b><span><?= View::escape((string) ($student['year_name'] ?? '')) ?></span></div></div>
                <?php else: ?><div class="admit-bulk-field"><b>Class / section</b><span><?= View::escape($formatClassName($student['class_name']) . ' · ' . (string) $student['section_name']) ?></span></div><div class="admit-bulk-field"><b>Roll number</b><span><?= (int) $student['roll_no'] ?></span></div><?php endif; ?>
            </section>
            <div class="admit-bulk-signatures">
                <div><?php if (($signature = $signatureUrl('class_teacher_signature')) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="Class teacher signature"><?php endif; ?><span>Class Teacher</span></div>
                <div><?php if (($signature = $signatureUrl('principal_signature')) !== ''): ?><img src="<?= View::escape($signature) ?>" alt="Principal signature"><?php endif; ?><span>Principal</span></div>
            </div>
        </article>
        <?php endif; ?>
    <?php endforeach; ?>
    </section>
<?php endforeach; ?>
</main>
