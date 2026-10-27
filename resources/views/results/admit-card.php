<?php
/**
 * Printable examination admit cards.
 *
 * @var array<int, array<string, mixed>> $results
 * @var array<string, mixed>|null $config
 * @var array<string, string> $schoolSettings
 * @var array<int, array<string, mixed>> $admitSchedule
 */
use App\Core\Url;
use App\Core\View;
?>
<div>
    <?php require BASE_PATH . '/resources/views/results/partials/config-bar.php'; ?>

    <?php if ($results !== []): ?>
        <div class="admit-card-toolbar mt-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-white">Print-ready admit cards · one card per A4 page</p>
            <button type="button" class="form-action-button" onclick="window.print()">Print admit cards</button>
        </div>
    <?php endif; ?>

    <div id="admit-cards-print" class="mt-5">
    <?php if ($results === []): ?>
        <p class="rounded-2xl border border-dashed border-green-900/70 bg-[#0b3425]/70 p-10 text-center text-sm text-white">
            No results have been entered for this setup yet.
        </p>
    <?php else: foreach ($results as $row): ?>
        <?php
        $photo = trim((string) ($row['photo'] ?? ''));
        $photoUrl = $photo !== '' ? Url::to('/' . ltrim($photo, '/')) : '';
        $examCenter = '';
        foreach ($admitSchedule as $scheduledPaper) {
            if (trim((string) ($scheduledPaper['hall'] ?? '')) !== '') {
                $examCenter = (string) $scheduledPaper['hall'];
                break;
            }
        }
        if ($examCenter === '') {
            $examCenter = trim((string) ($config['config'] ?? ''));
        }
        if ($examCenter === '') {
            $examCenter = (string) ($schoolSettings['school_address'] ?? '');
        }
        $showGenericGuardian = trim((string) ($row['father_name'] ?? '')) === ''
            && trim((string) ($row['mother_name'] ?? '')) === ''
            && trim((string) ($row['guardian_name'] ?? '')) !== '';
        ?>
        <article class="admit-card-page">
            <div class="admit-card-sheet">
                <img class="admit-card-watermark" src="<?= Url::to('/favicon.png') ?>" alt="" aria-hidden="true">

                <header class="admit-card-school-head">
                    <img class="admit-card-school-logo" src="<?= Url::to('/favicon.png') ?>" alt="School logo">
                    <div>
                        <h2 class="m-0 text-xl font-extrabold leading-tight text-slate-900"><?= View::escape($schoolSettings['school_name'] ?? 'WSIT School') ?></h2>
                        <?php if (trim((string) ($schoolSettings['school_name_bn'] ?? '')) !== ''): ?>
                            <p class="mt-0.5 font-semibold text-slate-700" style="font-family:'Noto Sans Bengali','Nirmala UI',sans-serif"><?= View::escape($schoolSettings['school_name_bn']) ?></p>
                        <?php endif; ?>
                        <?php if (trim((string) ($schoolSettings['school_eiin'] ?? '')) !== ''): ?>
                            <p class="text-xs font-semibold text-slate-700">EIIN: <?= View::escape($schoolSettings['school_eiin']) ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-xs text-slate-600">
                            <?= View::escape(implode(' · ', array_filter([
                                trim((string) ($schoolSettings['school_address'] ?? '')),
                                trim((string) ($schoolSettings['school_phone'] ?? '')),
                                trim((string) ($schoolSettings['school_email'] ?? '')),
                            ]))) ?>
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-300 bg-white/90 px-2 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-600">
                        Student copy<br><span class="mt-1 block break-all font-mono text-[9px] tracking-normal text-slate-900"><?= View::escape((string) $row['student_code']) ?></span>
                    </div>
                </header>

                <h1 class="admit-card-exam-title mt-2"><?= View::escape((string) ($config['exam_name'] ?? 'Examination')) ?> · <?= View::escape((string) ($config['academic_year'] ?? '')) ?></h1>

                <div class="admit-card-band">
                    <span class="admit-card-band-label">Reg. No.: <?= View::escape((string) $row['student_code']) ?></span>
                    <span class="admit-card-badge">Admit Card</span>
                    <span class="admit-card-band-label text-right">Exam Roll: <?= (int) $row['roll_no'] ?></span>
                </div>

                <div class="admit-card-details">
                    <dl class="admit-card-fields">
                        <div class="admit-card-field"><dt>Student name</dt><dd><?= View::escape((string) $row['student_name']) ?></dd></div>
                        <?php if (trim((string) ($row['student_name_bn'] ?? '')) !== ''): ?>
                            <div class="admit-card-field"><dt>Name in Bangla</dt><dd style="font-family:'Noto Sans Bengali','Nirmala UI',sans-serif"><?= View::escape((string) $row['student_name_bn']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (trim((string) ($row['father_name'] ?? '')) !== ''): ?>
                            <div class="admit-card-field"><dt>Father's name</dt><dd><?= View::escape((string) $row['father_name']) ?></dd></div>
                        <?php endif; ?>
                        <?php if (trim((string) ($row['mother_name'] ?? '')) !== ''): ?>
                            <div class="admit-card-field"><dt>Mother's name</dt><dd><?= View::escape((string) $row['mother_name']) ?></dd></div>
                        <?php endif; ?>
                        <?php if ($showGenericGuardian): ?>
                            <div class="admit-card-field"><dt>Guardian's name</dt><dd><?= View::escape((string) $row['guardian_name']) ?></dd></div>
                        <?php endif; ?>
                        <div class="admit-card-field"><dt>Student ID</dt><dd><?= View::escape((string) $row['student_code']) ?></dd></div>
                        <div class="admit-card-field"><dt>Class / section</dt><dd><?= View::escape((string) ($config['class_name'] ?? $row['class_name'])) ?><?= trim((string) ($config['section'] ?? '')) !== '' ? ' · ' . View::escape((string) $config['section']) : '' ?></dd></div>
                        <div class="admit-card-field"><dt>Roll number</dt><dd><?= (int) $row['roll_no'] ?></dd></div>
                        <div class="admit-card-field"><dt>Exam center</dt><dd><?= $examCenter !== '' ? View::escape($examCenter) : 'To be announced' ?></dd></div>
                        <?php if (trim((string) ($row['guardian_phone'] ?? '')) !== ''): ?>
                            <div class="admit-card-field"><dt>Guardian phone</dt><dd><?= View::escape((string) $row['guardian_phone']) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <?php if ($photoUrl !== ''): ?>
                        <img class="admit-card-photo" src="<?= View::escape($photoUrl) ?>" alt="Photo of <?= View::escape((string) $row['student_name']) ?>">
                    <?php else: ?>
                        <div class="admit-card-photo grid place-items-center text-3xl font-bold text-emerald-800" aria-label="No student photo"><?= View::escape(strtoupper(substr((string) $row['student_name'], 0, 1))) ?></div>
                    <?php endif; ?>
                </div>

                <h2 class="my-1 text-center text-sm font-bold text-slate-900">Exam Schedule</h2>
                <table class="admit-card-schedule">
                    <thead><tr><th>Subject</th><th>Exam date</th><th>Day</th><th>Time / hall</th></tr></thead>
                    <tbody>
                    <?php if ($admitSchedule === []): ?>
                        <tr><td colspan="4">Not scheduled</td></tr>
                    <?php else: foreach ($admitSchedule as $scheduledPaper): ?>
                        <?php
                        $scheduleDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $scheduledPaper['date']);
                        $startTime = trim((string) ($scheduledPaper['start_time'] ?? ''));
                        $endTime = trim((string) ($scheduledPaper['end_time'] ?? ''));
                        $examTime = $startTime !== '' ? date('g:i A', strtotime($startTime)) : '';
                        if ($endTime !== '') {
                            $examTime .= ($examTime !== '' ? ' – ' : '') . date('g:i A', strtotime($endTime));
                        }
                        $examVenue = trim((string) ($scheduledPaper['hall'] ?? ''));
                        ?>
                        <tr>
                            <td><?= View::escape((string) $scheduledPaper['subject_name']) ?></td>
                            <td><?= $scheduleDate ? View::escape($scheduleDate->format('d M Y')) : '—' ?></td>
                            <td><?= $scheduleDate ? View::escape($scheduleDate->format('l')) : '—' ?></td>
                            <td><?= View::escape(implode(' · ', array_filter([$examTime, $examVenue]))) ?: '—' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <section class="admit-card-guidelines">
                    <h2 class="mb-1 text-[.82rem] font-bold">পরীক্ষার্থীদের জন্য নির্দেশনা</h2>
                    <ol class="list-decimal space-y-0.5 pl-5">
                        <li>প্রবেশপত্র ছাড়া কোনো পরীক্ষার্থী পরীক্ষাকক্ষে প্রবেশ করতে পারবে না।</li>
                        <li>পরীক্ষা শুরুর অন্তত ৩০ মিনিট আগে কেন্দ্রে উপস্থিত হতে হবে।</li>
                        <li>মোবাইল ফোন বা অননুমোদিত সামগ্রী পরীক্ষাকক্ষে আনা যাবে না।</li>
                        <li>পরীক্ষা চলাকালে কক্ষ পরিদর্শকের নির্দেশনা অনুসরণ করতে হবে।</li>
                    </ol>
                </section>

                <div class="admit-card-signatures">
                    <div class="admit-card-signature">Class teacher</div>
                    <div class="admit-card-signature">Invigilator</div>
                    <div class="admit-card-signature">Exam controller</div>
                    <div class="admit-card-signature">Head teacher</div>
                </div>
                <footer class="mt-2 border-t border-slate-300 pt-1 text-center text-[9px] text-slate-600">
                    This admit card is valid only for <?= View::escape((string) ($config['exam_name'] ?? 'the examination')) ?>, <?= View::escape((string) ($config['academic_year'] ?? '')) ?>. Please bring it to every examination.
                </footer>
            </div>
        </article>
    <?php endforeach; endif; ?>
    </div>
</div>
