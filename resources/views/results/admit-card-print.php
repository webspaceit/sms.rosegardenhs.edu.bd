<?php
use App\Core\Url;
use App\Core\View;

$photo = trim((string) ($student['photo'] ?? ''));
$photoUrl = $photo !== '' ? Url::to('/' . ltrim($photo, '/')) : '';
?>
<div class="admit-card-toolbar mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-2 pt-4">
    <a class="admit-print-back" href="<?= Url::to('/admit-cards') ?>">← Back to admit cards</a>
    <button type="button" class="form-action-button" onclick="window.print()">Print admit card</button>
</div>
<div id="admit-cards-print">
    <article class="admit-card-page">
        <div class="admit-card-sheet">
            <img class="admit-card-watermark" src="<?= Url::to('/favicon.png') ?>" alt="" aria-hidden="true">
            <header class="admit-card-school-head">
                <img class="admit-card-school-logo" src="<?= Url::to('/favicon.png') ?>" alt="School logo">
                <div>
                    <h2 class="m-0 text-xl font-extrabold leading-tight text-slate-900"><?= View::escape($schoolSettings['school_name'] ?? 'WSIT School') ?></h2>
                    <?php if (trim((string) ($schoolSettings['school_name_bn'] ?? '')) !== ''): ?><p class="mt-0.5 font-semibold text-slate-700"><?= View::escape($schoolSettings['school_name_bn']) ?></p><?php endif; ?>
                    <p class="mt-1 text-xs text-slate-600"><?= View::escape(implode(' · ', array_filter([
                        trim((string) ($schoolSettings['school_address'] ?? '')),
                        trim((string) ($schoolSettings['school_phone'] ?? '')),
                        trim((string) ($schoolSettings['school_email'] ?? '')),
                    ]))) ?></p>
                </div>
                <div class="rounded-lg border border-slate-300 bg-white/90 px-2 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-600">Registration<br><span class="mt-1 block font-mono text-[9px] tracking-normal text-slate-900"><?= View::escape((string) $student['student_id']) ?></span></div>
            </header>
            <h1 class="admit-card-exam-title mt-2"><?= View::escape($examName) ?> · <?= View::escape((string) $student['year_name']) ?></h1>
            <div class="admit-card-band"><span class="admit-card-band-label">Student ID: <?= View::escape((string) $student['student_id']) ?></span><span class="admit-card-badge">Admit Card</span><span class="admit-card-band-label text-right">Roll: <?= (int) $student['roll_no'] ?></span></div>
            <div class="admit-card-details">
                <dl class="admit-card-fields">
                    <div class="admit-card-field"><dt>Student name</dt><dd><?= View::escape((string) $student['name']) ?></dd></div>
                    <?php if (trim((string) ($student['name_bn'] ?? '')) !== ''): ?><div class="admit-card-field"><dt>Name in Bangla</dt><dd><?= View::escape((string) $student['name_bn']) ?></dd></div><?php endif; ?>
                    <?php if (trim((string) ($student['father_name'] ?? '')) !== ''): ?><div class="admit-card-field"><dt>Father's name</dt><dd><?= View::escape((string) $student['father_name']) ?></dd></div><?php endif; ?>
                    <?php if (trim((string) ($student['mother_name'] ?? '')) !== ''): ?><div class="admit-card-field"><dt>Mother's name</dt><dd><?= View::escape((string) $student['mother_name']) ?></dd></div><?php endif; ?>
                    <div class="admit-card-field"><dt>Class / section</dt><dd><?= View::escape((string) $student['class_name']) ?> · <?= View::escape((string) $student['section_name']) ?></dd></div>
                    <div class="admit-card-field"><dt>Academic year</dt><dd><?= View::escape((string) $student['year_name']) ?></dd></div>
                </dl>
                <?php if ($photoUrl !== ''): ?><img class="admit-card-photo" src="<?= View::escape($photoUrl) ?>" alt="Photo of <?= View::escape((string) $student['name']) ?>"><?php else: ?><div class="admit-card-photo grid place-items-center text-3xl font-bold text-emerald-800"><?= View::escape(strtoupper(substr((string) $student['name'], 0, 1))) ?></div><?php endif; ?>
            </div>
            <h2 class="my-1 text-center text-sm font-bold text-slate-900">Exam Schedule · <?= View::escape($examName) ?></h2>
            <table class="admit-card-schedule"><thead><tr><th>Sl.</th><th>Subject</th><th>Date</th><th>Day</th><th>Time / hall</th></tr></thead><tbody>
                <?php if ($schedule === []): ?><tr><td colspan="5">Schedule not available</td></tr><?php else: foreach ($schedule as $scheduleIndex => $paper): ?>
                    <?php $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $paper['date']); $time = '';
                        if (trim((string) ($paper['start_time'] ?? '')) !== '') { $time = date('g:i A', strtotime((string) $paper['start_time'])); }
                        if (trim((string) ($paper['end_time'] ?? '')) !== '') { $time .= ($time !== '' ? ' – ' : '') . date('g:i A', strtotime((string) $paper['end_time'])); }
                    ?>
                    <tr><td><?= (int) $scheduleIndex + 1 ?></td><td><?= View::escape((string) $paper['subject_name']) ?></td><td><?= $date ? View::escape($date->format('d M Y')) : '—' ?></td><td><?= $date ? View::escape($date->format('l')) : '—' ?></td><td><?= View::escape(implode(' · ', array_filter([$time, trim((string) ($paper['hall'] ?? ''))]))) ?: '—' ?></td></tr>
                <?php endforeach; endif; ?>
            </tbody></table>
            <section class="admit-card-guidelines"><h2 class="mb-1 text-[.82rem] font-bold">পরীক্ষার্থীদের জন্য নির্দেশনা</h2><ol class="list-decimal space-y-0.5 pl-5"><li>প্রবেশপত্র ছাড়া কোনো পরীক্ষার্থী পরীক্ষাকক্ষে প্রবেশ করতে পারবে না।</li><li>পরীক্ষা শুরুর অন্তত ৩০ মিনিট আগে কেন্দ্রে উপস্থিত হতে হবে।</li><li>মোবাইল ফোন বা অননুমোদিত সামগ্রী পরীক্ষাকক্ষে আনা যাবে না।</li></ol></section>
            <div class="admit-card-signatures"><div class="admit-card-signature">Class teacher</div><div class="admit-card-signature">Invigilator</div><div class="admit-card-signature">Exam controller</div><div class="admit-card-signature">Head teacher</div></div>
            <footer class="mt-2 border-t border-slate-300 pt-1 text-center text-[9px] text-slate-600">Please bring this admit card to every examination.</footer>
        </div>
    </article>
</div>
