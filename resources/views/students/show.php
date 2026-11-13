<?php
use App\Core\Url;
use App\Core\View;
use App\Core\QrCode;
use App\Core\Barcode;

$attendanceTotal = (int) ($attendanceSummary['total'] ?? 0);
$attendanceAttended = (int) ($attendanceSummary['attended'] ?? 0);
$attendanceRate = $attendanceTotal > 0 ? (int) round(($attendanceAttended / $attendanceTotal) * 100) : null;
$requestHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
if (!preg_match('/\A(?:[A-Za-z0-9.-]+|\[[A-Fa-f0-9:]+\])(?::[0-9]{1,5})?\z/D', $requestHost)) $requestHost = 'localhost';
$forwardedProtocol = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$requestScheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') || $forwardedProtocol === 'https' ? 'https' : 'http';
$studentProfileUrl = $requestScheme . '://' . $requestHost . Url::to('/students/view?id=' . (int) $student['id']);
$studentIdQr = (new QrCode())->toDataUri($studentProfileUrl);
$studentIdBarcode = (new Barcode())->toDataUri((string) $student['student_id']);
$statusClass = [
    'active' => 'border-green-300/20 bg-green-300/10 text-green-100',
    'inactive' => 'border-slate-200/15 bg-slate-200/10 text-slate-200',
    'graduated' => 'border-sky-300/20 bg-sky-300/10 text-sky-100',
][$student['status']] ?? 'border-slate-200/15 bg-slate-200/10 text-slate-200';
?>
<div id="student-detail-print" class="student-detail-print mx-auto max-w-6xl">
    <header class="student-detail-web-header flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="<?= Url::to('/students') ?>" class="student-detail-back text-sm text-green-200/75 hover:text-green-100">← Student register</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold">Student details</h1>
                <span class="student-detail-status rounded-full border px-3 py-1 text-xs font-medium <?= $statusClass ?>"><?= View::escape(ucfirst((string) $student['status'])) ?></span>
            </div>
            <p class="mt-1 text-sm text-white">Student ID: <?= View::escape($student['student_id']) ?></p>
        </div>
        <div class="student-detail-actions flex flex-wrap gap-2">
            <button type="button" class="form-action-button" onclick="window.print()">Print details</button>
            <button type="button" class="form-action-button" onclick="window.print()" title="In the print dialog, choose Save as PDF">Save as PDF</button>
            <a href="<?= Url::to('/students') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-50/80 hover:border-green-200/40">Back to register</a>
            <a href="<?= Url::to('/students/edit?id=' . (int) $student['id']) ?>" class="form-action-button">Edit student</a>
            <a href="<?= Url::to('/students/guardians?id=' . (int) $student['id']) ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-50/80">Manage guardians</a>
        </div>
    </header>
    <p class="student-detail-pdf-hint mt-2 text-right text-xs text-green-100/65">To download a PDF, choose <strong>Save as PDF</strong> in the print dialog.</p>
    <header class="student-detail-pdf-heading" aria-label="Student profile report">
        <img src="<?= Url::to('/favicon.png') ?>" alt="" class="student-detail-pdf-logo">
        <div class="student-detail-pdf-heading-copy">
            <p>WSIT SCHOOL</p>
            <h1>Student Profile Report</h1>
        </div>
        <div class="student-detail-pdf-issued"><span>RECORD GENERATED</span><strong><?= \App\Core\DateFormatter::format('now') ?></strong></div>
    </header>

    <?php
    $detailValue = static fn (mixed $value): string => ($value !== null && trim((string) $value) !== '') ? View::escape($value) : '—';
    $studentTabs = [
        'profile' => 'Profile', 'fees' => 'Fees', 'exam' => 'Exam', 'attendance' => 'Attendance',
        'documents' => 'Documents', 'timeline' => 'Timeline', 'behaviour' => 'Student Behaviour',
    ];
    ?>
    <div class="student-reference-topline">
        <div><a href="<?= Url::to('/students') ?>">Students</a><span>›</span><strong>Details</strong></div>
        <span class="student-reference-year">Academic Year: <?= View::escape($student['year_name']) ?></span>
    </div>
    <div class="student-reference-layout">
        <aside class="student-reference-card" aria-label="Student summary">
            <div class="student-reference-identity">
                <?php if (!empty($student['photo'])): ?><img src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>" alt="" class="student-reference-avatar">
                <?php else: ?><div class="student-reference-avatar student-reference-initial"><?= View::escape(strtoupper(substr((string) $student['name'], 0, 1))) ?></div><?php endif; ?>
                <h2><?= View::escape($student['name']) ?></h2>
                <p>Admission No <strong><?= View::escape($student['student_id']) ?></strong></p>
                <p>Roll Number <strong><?= sprintf('%03d', (int) $student['roll_no']) ?></strong></p>
            </div>
            <div class="student-reference-actions" aria-label="Student actions">
                <button type="button" onclick="window.print()" title="Print student details" aria-label="Print student details">▤</button>
                <a href="<?= Url::to('/students/edit?id=' . (int) $student['id']) ?>" title="Edit student" aria-label="Edit student">✎</a>
                <a href="<?= Url::to('/students/guardians?id=' . (int) $student['id']) ?>" title="Manage guardians" aria-label="Manage guardians">♙</a>
                <a href="<?= Url::to('/students') ?>" title="Back to students" aria-label="Back to students">⋮</a>
            </div>
            <dl class="student-reference-facts">
                <div><dt>Class</dt><dd><?= $detailValue($student['class_name']) ?></dd></div>
                <div><dt>Section</dt><dd><?= $detailValue($student['section_name']) ?></dd></div>
                <div><dt>Gender</dt><dd><?= $detailValue($student['gender']) ?></dd></div>
                <div><dt>Status</dt><dd><?= View::escape(ucfirst((string) $student['status'])) ?></dd></div>
            </dl>
            <div class="student-reference-code-label student-reference-qr-label">Open student profile</div>
            <img class="student-reference-qr" src="<?= $studentIdQr ?>" alt="QR code link to student profile <?= View::escape($student['student_id']) ?>" width="112" height="112">
            <div class="student-reference-code-label">Student ID</div>
            <div class="student-reference-code" aria-label="Student ID <?= View::escape($student['student_id']) ?>"></div>
            <div class="student-reference-code-value"><?= View::escape($student['student_id']) ?></div>
        </aside>

        <section class="student-reference-details" aria-label="Student information">
            <nav class="student-reference-tabs" role="tablist" aria-label="Student record sections">
                <?php foreach ($studentTabs as $key => $label): ?>
                    <button type="button" role="tab" id="student-tab-<?= $key ?>" aria-controls="student-panel-<?= $key ?>" aria-selected="<?= $key === 'profile' ? 'true' : 'false' ?>" tabindex="<?= $key === 'profile' ? '0' : '-1' ?>" data-student-tab="<?= $key ?>"><?= View::escape($label) ?></button>
                <?php endforeach; ?>
            </nav>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-profile" aria-labelledby="student-tab-profile" data-student-panel="profile">
                <div class="student-reference-rows">
                    <?php foreach ([
                        'Admission Date' => !empty($student['admission_date']) ? \App\Core\DateFormatter::format($student['admission_date']) : '—',
                        'Date Of Birth' => !empty($student['date_of_birth']) ? \App\Core\DateFormatter::format($student['date_of_birth']) : '—',
                        'Birth Registration No.' => $student['birth_registration_no'] ?? null,
                        'Shift' => $student['shift'] ?? null, 'Mobile Number' => $student['mobile_number'] ?? null,
                        'Email' => $student['email'] ?? null, 'Medical History' => $student['medical_history'] ?? null,
                        'Note' => $student['note'] ?? null,
                    ] as $label => $value): ?><div class="student-reference-row"><span><?= View::escape($label) ?></span><strong><?= $detailValue($value) ?></strong></div><?php endforeach; ?>
                    <div class="student-reference-pair">
                        <div class="student-reference-row"><span>Religion</span><strong><?= $detailValue($student['religion'] ?? null) ?></strong></div>
                        <div class="student-reference-row"><span>Caste</span><strong><?= $detailValue($student['caste'] ?? null) ?></strong></div>
                    </div>
                    <div class="student-reference-row student-reference-group"><strong>Address</strong></div>
                    <div class="student-reference-row"><span>Current Address</span><strong><?= $detailValue($student['current_address'] ?? null) ?></strong></div>
                    <div class="student-reference-row"><span>Permanent Address</span><strong><?= $detailValue($student['permanent_address'] ?? null) ?></strong></div>
                    <div class="student-reference-row student-reference-group"><strong>Parent Guardian Detail</strong></div>
                    <?php
                    $fatherName = ($student['guardian_relation'] ?? '') === 'Father' ? (($student['father_name'] ?? '') ?: ($student['guardian_name'] ?? null)) : ($student['father_name'] ?? null);
                    $fatherPhone = ($student['guardian_relation'] ?? '') === 'Father' ? (($student['father_phone'] ?? '') ?: ($student['guardian_phone'] ?? null)) : ($student['father_phone'] ?? null);
                    $fatherOccupation = ($student['guardian_relation'] ?? '') === 'Father' ? (($student['father_occupation'] ?? '') ?: ($student['guardian_occupation'] ?? null)) : ($student['father_occupation'] ?? null);
                    $motherName = ($student['guardian_relation'] ?? '') === 'Mother' ? (($student['mother_name'] ?? '') ?: ($student['guardian_name'] ?? null)) : ($student['mother_name'] ?? null);
                    $motherPhone = ($student['guardian_relation'] ?? '') === 'Mother' ? (($student['mother_phone'] ?? '') ?: ($student['guardian_phone'] ?? null)) : ($student['mother_phone'] ?? null);
                    $motherOccupation = ($student['guardian_relation'] ?? '') === 'Mother' ? (($student['mother_occupation'] ?? '') ?: ($student['guardian_occupation'] ?? null)) : ($student['mother_occupation'] ?? null);
                    ?>
                    <?php foreach ([
                        'Father Name' => $fatherName, 'Father Phone' => $fatherPhone,
                        'Father Occupation' => $fatherOccupation, 'Mother Name' => $motherName,
                        'Mother Phone' => $motherPhone, 'Mother Occupation' => $motherOccupation,
                        'Guardian Name' => $student['guardian_name'] ?? null, 'Guardian Email' => $student['guardian_email'] ?? null,
                        'Guardian Relation' => $student['guardian_relation'] ?? null, 'Guardian Phone' => $student['guardian_phone'] ?? null,
                        'Guardian Occupation' => $student['guardian_occupation'] ?? null, 'Guardian Address' => $student['guardian_address'] ?? null,
                    ] as $label => $value): ?><div class="student-reference-row"><span><?= View::escape($label) ?></span><strong><?= $detailValue($value) ?></strong></div><?php endforeach; ?>
                    <?php foreach ([
                        'Father Photo' => $student['father_photo'] ?? null,
                        'Mother Photo' => $student['mother_photo'] ?? null,
                        'Guardian Photo' => $student['guardian_photo'] ?? null,
                    ] as $label => $photoPath): ?>
                        <div class="student-reference-row"><span><?= View::escape($label) ?></span><strong><?php if (!empty($photoPath)): ?><a href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $photoPath)) ?>" target="_blank" rel="noopener"><img class="student-reference-photo" src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $photoPath)) ?>" alt="<?= View::escape($label) ?>"></a><?php else: ?>—<?php endif; ?></strong></div>
                    <?php endforeach; ?>
                    <div class="student-reference-row student-reference-group"><strong>Miscellaneous Details</strong></div>
                    <?php foreach ([
                        'Blood Group' => $student['blood_group'] ?? null, 'House' => $student['house_name'] ?? null,
                        'Height' => isset($student['height']) && $student['height'] !== null ? $student['height'] . ' cm' : null,
                        'Weight' => isset($student['weight']) && $student['weight'] !== null ? $student['weight'] . ' kg' : null,
                        'Measurement Date' => !empty($student['measurement_date']) ? \App\Core\DateFormatter::format($student['measurement_date']) : null,
                        'Previous School Details' => $student['previous_school'] ?? null, 'National Identification Number' => $student['national_id'] ?? null,
                        'Local Identification Number' => $student['local_id'] ?? null,
                    ] as $label => $value): ?><div class="student-reference-row"><span><?= View::escape($label) ?></span><strong><?= $detailValue($value) ?></strong></div><?php endforeach; ?>
                    <?php if (!empty($student['transport_route_id']) || !empty($student['transport_stop_id']) || !empty($student['transport_fees_month'])): ?>
                        <div class="student-reference-row student-reference-group"><strong>Transport Details</strong></div>
                        <?php foreach ([
                            'Route' => $student['transport_route_name'] ?? null,
                            'Pickup Point' => $student['transport_stop_name'] ?? null,
                            'Fees Month' => $student['transport_fees_month'] ?? null,
                        ] as $label => $value): ?><div class="student-reference-row"><span><?= View::escape($label) ?></span><strong><?= $detailValue($value) ?></strong></div><?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($student['hostel_room_id'])): ?>
                        <div class="student-reference-row student-reference-group"><strong>Hostel Details</strong></div>
                        <div class="student-reference-row"><span>Hostel Room</span><strong><?= $detailValue($student['hostel_room_no'] ?? null) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-fees" aria-labelledby="student-tab-fees" data-student-panel="fees" hidden>
                <div class="student-reference-panel-heading"><h2>Fee history</h2><p>Recent invoices for this student</p></div>
                <div class="student-reference-table-wrap"><table><thead><tr><th>Invoice</th><th>Fee Head</th><th>Amount</th><th>Status</th><th>Due Date</th></tr></thead><tbody>
                    <?php if ($feeHistory === []): ?><tr><td colspan="5" class="student-reference-empty">No fee invoices found.</td></tr><?php else: foreach ($feeHistory as $invoice): ?><tr><td><?= View::escape($invoice['invoice_no']) ?></td><td><?= View::escape($invoice['fee_head']) ?></td><td>৳<?= number_format((float) $invoice['amount'], 2) ?></td><td><?= View::escape(ucfirst((string) $invoice['status'])) ?></td><td><?= !empty($invoice['due_date']) ? \App\Core\DateFormatter::format($invoice['due_date']) : '—' ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-exam" aria-labelledby="student-tab-exam" data-student-panel="exam" hidden>
                <div class="student-reference-panel-heading"><h2>Exam results</h2><p>Recent academic reports</p></div>
                <div class="student-reference-table-wrap"><table><thead><tr><th>Examination</th><th>Academic Year</th><th>Marks</th><th>Class Position</th></tr></thead><tbody>
                    <?php if ($recentReports === []): ?><tr><td colspan="4" class="student-reference-empty">No academic reports found.</td></tr><?php else: foreach ($recentReports as $report): ?><tr><td><?= View::escape($report['exam_name']) ?></td><td><?= View::escape($report['academic_year']) ?></td><td><?= (int) $report['obtained'] ?> / <?= (int) $report['full_marks'] ?></td><td><?= $report['class_position'] ? (int) $report['class_position'] : '—' ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-attendance" aria-labelledby="student-tab-attendance" data-student-panel="attendance" hidden>
                <div class="student-reference-panel-heading"><h2>Attendance</h2><p>Attendance rate: <?= $attendanceRate !== null ? $attendanceRate . '%' : 'No records yet' ?> · Recorded: <?= $attendanceTotal ?></p></div>
                <div class="student-reference-table-wrap"><table><thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
                    <?php if ($recentAttendance === []): ?><tr><td colspan="3" class="student-reference-empty">No attendance records found.</td></tr><?php else: foreach ($recentAttendance as $record): ?><tr><td><?= \App\Core\DateFormatter::format($record['date']) ?></td><td><?= View::escape(ucwords(str_replace('_', ' ', (string) $record['status']))) ?></td><td><?= View::escape($record['remarks'] ?: '—') ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-documents" aria-labelledby="student-tab-documents" data-student-panel="documents" hidden>
                <div class="student-reference-panel-heading"><h2>Documents</h2><p>Files attached to this student record</p></div>
                <div class="student-reference-table-wrap"><table><thead><tr><th>Document</th><th>Uploaded</th><th>File</th></tr></thead><tbody>
                    <?php if (empty($documents)): ?><tr><td colspan="3" class="student-reference-empty">No documents uploaded.</td></tr><?php else: foreach ($documents as $document): ?><tr><td><?= View::escape($document['title']) ?></td><td><?= \App\Core\DateFormatter::format($document['created_at']) ?></td><td><a href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $document['file_path'])) ?>" target="_blank" rel="noopener">Open file</a></td></tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-timeline" aria-labelledby="student-tab-timeline" data-student-panel="timeline" hidden>
                <div class="student-reference-panel-heading"><h2>Student timeline</h2><p>Admission and previous enrollment history</p></div>
                <div class="student-reference-table-wrap"><table><thead><tr><th>Academic Year</th><th>Class / Section</th><th>Roll</th><th>Status</th></tr></thead><tbody>
                    <tr><td><?= View::escape($student['year_name']) ?></td><td><?= View::escape($student['class_name'] . ' / ' . $student['section_name']) ?></td><td><?= (int) $student['roll_no'] ?></td><td><?= View::escape(ucfirst((string) $student['status'])) ?></td></tr>
                    <?php foreach ($enrollmentHistory as $enrollment): ?><tr><td><?= View::escape($enrollment['year_name']) ?></td><td><?= View::escape($enrollment['class_name'] . ' / ' . $enrollment['section_name']) ?></td><td><?= (int) $enrollment['roll_no'] ?></td><td><?= View::escape(ucfirst((string) $enrollment['status'])) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </div>

            <div class="student-reference-panel" role="tabpanel" id="student-panel-behaviour" aria-labelledby="student-tab-behaviour" data-student-panel="behaviour" hidden>
                <div class="student-reference-panel-heading"><h2>Student behaviour</h2><p>Notes from the student registration record</p></div>
                <?php if (trim((string) ($student['student_behaviour'] ?? '')) !== ''): ?><p class="student-reference-empty-panel whitespace-pre-line"><?= View::escape((string) $student['student_behaviour']) ?></p><?php else: ?><p class="student-reference-empty student-reference-empty-panel">No student behaviour details have been recorded.</p><?php endif; ?>
            </div>
        </section>
    </div>
    <script>
    (() => {
        const tabs = document.querySelectorAll('[data-student-tab]');
        const panels = document.querySelectorAll('[data-student-panel]');
        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const selected = tab.dataset.studentTab;
                tabs.forEach((item) => {
                    const active = item === tab;
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                    item.tabIndex = active ? 0 : -1;
                });
                panels.forEach((panel) => { panel.hidden = panel.dataset.studentPanel !== selected; });
            });
            tab.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                const list = Array.from(tabs);
                const offset = event.key === 'ArrowRight' ? 1 : -1;
                list[(list.indexOf(tab) + offset + list.length) % list.length].focus();
            });
        });
    })();
    </script>

    <div class="student-detail-screen-summary student-detail-print-grid mt-7 grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-green-900/60 dashboard-gradient p-5 sm:p-6" aria-labelledby="student-profile-title">
            <div class="flex items-center gap-4 border-b border-green-100/10 pb-5">
                <?php if (!empty($student['photo'])): ?><img src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>" alt="" class="h-14 w-14 shrink-0 rounded-2xl border border-green-200/20 object-cover"><?php else: ?><div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl border border-green-200/20 bg-green-200/10 text-xl font-semibold text-green-100"><?= View::escape(strtoupper(substr((string) $student['name'], 0, 1))) ?></div><?php endif; ?>
                <div class="min-w-0"><h2 id="student-profile-title" class="truncate text-xl font-semibold"><?= View::escape($student['name']) ?></h2></div>
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs text-white">Student ID</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['student_id']) ?></dd></div>
                <div><dt class="text-xs text-white">Gender</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['gender'] ?: 'Not specified') ?></dd></div>
                <div><dt class="text-xs text-white">Date of birth</dt><dd class="mt-1 text-sm font-medium"><?= !empty($student['date_of_birth']) ? \App\Core\DateFormatter::format($student['date_of_birth']) : 'Not provided' ?></dd></div>
                <div><dt class="text-xs text-white">Admission date</dt><dd class="mt-1 text-sm font-medium"><?= !empty($student['admission_date']) ? \App\Core\DateFormatter::format($student['admission_date']) : 'Not provided' ?></dd></div>
                <div><dt class="text-xs text-white">Birth registration number</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['birth_registration_no'] ?: 'Not provided') ?></dd></div>
                <div><dt class="text-xs text-white">Previous school</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['previous_school'] ?: 'Not provided') ?></dd></div>
                <div><dt class="text-xs text-white">Current status</dt><dd class="mt-1 text-sm font-medium"><?= View::escape(ucfirst((string) $student['status'])) ?></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-white">Current address</dt><dd class="mt-1 text-sm font-medium"><?= nl2br(View::escape($student['current_address'] ?: 'Not provided')) ?></dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-green-900/60 dashboard-gradient p-5 sm:p-6" aria-labelledby="enrollment-details-title">
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-green-200/65">Placement</p>
            <h2 id="enrollment-details-title" class="mt-1 text-lg font-semibold">Enrollment details</h2>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><dt class="text-xs text-white">Class</dt><dd class="mt-1 font-medium"><?= View::escape($student['class_name']) ?></dd></div>
                <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><dt class="text-xs text-white">Section</dt><dd class="mt-1 font-medium"><?= View::escape($student['section_name']) ?></dd></div>
                <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><dt class="text-xs text-white">Roll number</dt><dd class="mt-1 font-medium"><?= (int) $student['roll_no'] ?></dd></div>
                <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><dt class="text-xs text-white">Academic year</dt><dd class="mt-1 font-medium"><?= View::escape($student['year_name']) ?></dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-green-900/60 dashboard-gradient p-5 sm:p-6" aria-labelledby="guardian-details-title">
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-green-200/65">Family contact</p>
            <h2 id="guardian-details-title" class="mt-1 text-lg font-semibold">Guardian details</h2>
            <?php if (!empty($student['guardian_name'])): ?>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-white">Name</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['guardian_name']) ?></dd></div>
                    <div><dt class="text-xs text-white">Relation</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['guardian_relation']) ?></dd></div>
                    <div><dt class="text-xs text-white">Phone</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['guardian_phone']) ?></dd></div>
                    <div><dt class="text-xs text-white">Email</dt><dd class="mt-1 break-all text-sm font-medium"><?= View::escape($student['guardian_email'] ?: 'Not provided') ?></dd></div>
                </dl>
            <?php else: ?>
                <p class="mt-5 rounded-xl border border-dashed border-green-100/15 px-4 py-6 text-sm text-white">No guardian is linked to this student yet.</p>
            <?php endif; ?>
        </section>

        <section class="rounded-2xl border border-green-900/60 dashboard-gradient p-5 sm:p-6" aria-labelledby="attendance-summary-title">
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-green-200/65">Attendance</p>
            <h2 id="attendance-summary-title" class="mt-1 text-lg font-semibold">Attendance summary</h2>
            <?php if ($attendanceTotal > 0): ?>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><p class="text-xs text-white">Attendance</p><p class="mt-1 text-xl font-semibold text-green-100"><?= $attendanceRate ?>%</p></div>
                    <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><p class="text-xs text-white">Recorded</p><p class="mt-1 text-xl font-semibold"><?= $attendanceTotal ?></p></div>
                    <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><p class="text-xs text-white">Attended</p><p class="mt-1 text-xl font-semibold"><?= $attendanceAttended ?></p></div>
                    <div class="rounded-xl border border-green-100/10 bg-emerald-950/20 p-3"><p class="text-xs text-white">Absent</p><p class="mt-1 text-xl font-semibold text-rose-100"><?= (int) $attendanceSummary['absent'] ?></p></div>
                </div>
            <?php else: ?><p class="mt-5 rounded-xl border border-dashed border-green-100/15 px-4 py-6 text-sm text-white">No attendance has been recorded for this student.</p><?php endif; ?>
        </section>
    </div>

    <section class="student-detail-pdf-summary mt-7" aria-label="Printable student details">
        <table class="student-detail-pdf-table student-detail-pdf-student-table">
            <colgroup><col class="student-detail-pdf-label-col"><col class="student-detail-pdf-value-col"><col class="student-detail-pdf-photo-col"></colgroup>
            <thead><tr><th colspan="3">STUDENT DETAILS</th></tr></thead>
            <tbody>
                <tr><th scope="row">Name</th><td><?= View::escape($student['name']) ?></td><td class="student-detail-pdf-student-photo" rowspan="25"><?php if (!empty($student['photo'])): ?><img class="student-detail-pdf-photo" src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>" alt="Student photo"><?php else: ?><span class="student-detail-pdf-photo-placeholder">No photo</span><?php endif; ?></td></tr>
                <tr><th scope="row">Admission no.</th><td><?= View::escape($student['student_id']) ?></td></tr>
                <tr><th scope="row">Roll no.</th><td><?= (int) $student['roll_no'] ?></td></tr>
                <tr><th scope="row">Class</th><td><?= View::escape($student['class_name']) ?></td></tr>
                <tr><th scope="row">Section</th><td><?= View::escape($student['section_name']) ?></td></tr>
                <tr><th scope="row">Shift</th><td><?= $detailValue($student['shift'] ?? null) ?></td></tr>
                <tr><th scope="row">Gender</th><td><?= $detailValue($student['gender'] ?? null) ?></td></tr>
                <tr><th scope="row">Status</th><td><?= View::escape(ucfirst((string) $student['status'])) ?></td></tr>
                <tr><th scope="row">Admission date</th><td><?= !empty($student['admission_date']) ? \App\Core\DateFormatter::format($student['admission_date']) : '—' ?></td></tr>
                <tr><th scope="row">Date of birth</th><td><?= !empty($student['date_of_birth']) ? \App\Core\DateFormatter::format($student['date_of_birth']) : '—' ?></td></tr>
                <tr><th scope="row">Birth certificate / local ID</th><td><?= $detailValue($student['birth_registration_no'] ?? null) ?></td></tr>
                <tr><th scope="row">Academic year</th><td><?= View::escape($student['year_name']) ?></td></tr>
                <tr><th scope="row">Religion</th><td><?= $detailValue($student['religion'] ?? null) ?></td></tr>
                <tr><th scope="row">Caste</th><td><?= $detailValue($student['caste'] ?? null) ?></td></tr>
                <tr><th scope="row">Mobile number</th><td><?= $detailValue($student['mobile_number'] ?? null) ?></td></tr>
                <tr><th scope="row">Email</th><td><?= $detailValue($student['email'] ?? null) ?></td></tr>
                <tr><th scope="row">Blood group</th><td><?= $detailValue($student['blood_group'] ?? null) ?></td></tr>
                <tr><th scope="row">Student house</th><td><?= $detailValue($student['house_name'] ?? null) ?></td></tr>
                <tr><th scope="row">Height</th><td><?= isset($student['height']) && $student['height'] !== null ? View::escape($student['height'] . ' cm') : '—' ?></td></tr>
                <tr><th scope="row">Weight</th><td><?= isset($student['weight']) && $student['weight'] !== null ? View::escape($student['weight'] . ' kg') : '—' ?></td></tr>
                <tr><th scope="row">Measurement date</th><td><?= !empty($student['measurement_date']) ? \App\Core\DateFormatter::format($student['measurement_date']) : '—' ?></td></tr>
                <tr><th scope="row">Medical history</th><td><?= nl2br($detailValue($student['medical_history'] ?? null)) ?></td></tr>
                <tr><th scope="row">Student behaviour</th><td><?= nl2br($detailValue($student['student_behaviour'] ?? null)) ?></td></tr>
                <tr><th scope="row">Previous school</th><td><?= nl2br($detailValue($student['previous_school'] ?? null)) ?></td></tr>
                <tr><th scope="row">Note</th><td><?= nl2br($detailValue($student['note'] ?? null)) ?></td></tr>

            </tbody>
        </table>

        <table class="student-detail-pdf-table student-detail-pdf-student-table">
            <colgroup><col class="student-detail-pdf-label-col"><col class="student-detail-pdf-value-col"><col class="student-detail-pdf-photo-col"></colgroup>
            <thead><tr><th colspan="3">PARENT / GUARDIAN DETAIL</th></tr></thead>
            <tbody>
                <tr><th scope="row">Father name</th><td><?= $detailValue($fatherName) ?></td><td class="student-detail-pdf-student-photo" rowspan="3"><?php if (!empty($student['father_photo'])): ?><img class="student-detail-pdf-photo" src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['father_photo'])) ?>" alt="Father photo"><?php else: ?><span class="student-detail-pdf-photo-placeholder">No photo</span><?php endif; ?></td></tr>
                <tr><th scope="row">Father phone</th><td><?= $detailValue($fatherPhone) ?></td></tr>
                <tr><th scope="row">Father occupation</th><td><?= $detailValue($fatherOccupation) ?></td></tr>
                <tr><th scope="row">Mother name</th><td><?= $detailValue($motherName) ?></td><td class="student-detail-pdf-student-photo" rowspan="3"><?php if (!empty($student['mother_photo'])): ?><img class="student-detail-pdf-photo" src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['mother_photo'])) ?>" alt="Mother photo"><?php else: ?><span class="student-detail-pdf-photo-placeholder">No photo</span><?php endif; ?></td></tr>
                <tr><th scope="row">Mother phone</th><td><?= $detailValue($motherPhone) ?></td></tr>
                <tr><th scope="row">Mother occupation</th><td><?= $detailValue($motherOccupation) ?></td></tr>
                <tr><th scope="row">Guardian name</th><td><?= $detailValue($student['guardian_name'] ?? null) ?></td><td class="student-detail-pdf-student-photo" rowspan="6"><?php if (!empty($student['guardian_photo'])): ?><img class="student-detail-pdf-photo" src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['guardian_photo'])) ?>" alt="Guardian photo"><?php else: ?><span class="student-detail-pdf-photo-placeholder">No photo</span><?php endif; ?></td></tr>
                <tr><th scope="row">Guardian relation</th><td><?= $detailValue($student['guardian_relation'] ?? null) ?></td></tr>
                <tr><th scope="row">Guardian phone</th><td><?= $detailValue($student['guardian_phone'] ?? null) ?></td></tr>
                <tr><th scope="row">Guardian email</th><td><?= $detailValue($student['guardian_email'] ?? null) ?></td></tr>
                <tr><th scope="row">Guardian occupation</th><td><?= $detailValue($student['guardian_occupation'] ?? null) ?></td></tr>
                <tr><th scope="row">Guardian address</th><td><?= nl2br($detailValue($student['guardian_address'] ?? null)) ?></td></tr>
            </tbody>
        </table>

        <table class="student-detail-pdf-table">
            <tbody>

                <tr class="student-detail-pdf-section"><th colspan="4">Add More Details</th></tr>
                <tr><th scope="row">Current address</th><td colspan="3"><?= nl2br($detailValue($student['current_address'] ?? null)) ?></td></tr>
                <tr><th scope="row">Permanent address</th><td colspan="3"><?= nl2br($detailValue($student['permanent_address'] ?? null)) ?></td></tr>
                <tr><th scope="row">National identification number</th><td><?= $detailValue($student['national_id'] ?? null) ?></td><th scope="row">Local identification number</th><td><?= $detailValue($student['local_id'] ?? null) ?></td></tr>
                <tr><th scope="row">Previous school details</th><td colspan="3"><?= nl2br($detailValue($student['previous_school'] ?? null)) ?></td></tr>
                <tr><th scope="row">Note</th><td colspan="3"><?= nl2br($detailValue($student['note'] ?? null)) ?></td></tr>

                <?php if (!empty($student['transport_route_id']) || !empty($student['transport_stop_id']) || !empty($student['transport_fees_month'])): ?>
                    <tr class="student-detail-pdf-section"><th colspan="4">Transport Details</th></tr>
                    <tr><th scope="row">Route list</th><td><?= $detailValue($student['transport_route_name'] ?? null) ?></td><th scope="row">Pickup point</th><td><?= $detailValue($student['transport_stop_name'] ?? null) ?></td></tr>
                    <tr><th scope="row">Fees month</th><td colspan="3"><?= $detailValue($student['transport_fees_month'] ?? null) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($student['hostel_room_id'])): ?>
                    <tr class="student-detail-pdf-section"><th colspan="4">Hostel Details</th></tr>
                    <tr><th scope="row">Hostel room</th><td colspan="3"><?= $detailValue($student['hostel_room_no'] ?? null) ?></td></tr>
                <?php endif; ?>
                <tr class="student-detail-pdf-section"><th colspan="4">Attendance summary</th></tr>
                <?php if ($attendanceTotal > 0): ?>
                    <tr><th scope="row">Attendance rate</th><td><?= $attendanceRate ?>%</td><th scope="row">Recorded</th><td><?= $attendanceTotal ?></td></tr>
                    <tr><th scope="row">Attended</th><td><?= $attendanceAttended ?></td><th scope="row">Absent</th><td><?= (int) ($attendanceSummary['absent'] ?? 0) ?></td></tr>
                <?php else: ?><tr><td colspan="4">No attendance has been recorded for this student.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>

    <div class="student-detail-print-history student-detail-print-grid mt-5 grid gap-5 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="attendance-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="attendance-history-title" class="font-semibold">Recent attendance</h2><p class="mt-1 text-xs text-white">Latest recorded attendance entries</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[420px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Remarks</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if ($recentAttendance === []): ?><tr><td colspan="3" class="px-5 py-7 text-center text-white">No attendance records found.</td></tr><?php else: foreach ($recentAttendance as $record): ?><tr><td class="px-5 py-2 text-slate-300"><?= \App\Core\DateFormatter::format($record['date']) ?></td><td class="px-5 py-2"><span class="student-detail-status rounded-full border border-green-100/10 bg-emerald-950/30 px-2.5 py-1 text-xs text-green-50/80"><?= View::escape(ucwords(str_replace('_', ' ', (string) $record['status']))) ?></span></td><td class="px-5 py-2 text-white"><?= View::escape($record['remarks'] ?: '—') ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="fee-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="fee-history-title" class="font-semibold">Recent fee invoices</h2><p class="mt-1 text-xs text-white">Latest fee records for this student</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[620px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Invoice</th><th class="px-5 py-3 font-medium">Fee head</th><th class="px-5 py-3 font-medium">Amount</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Due date</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if ($feeHistory === []): ?><tr><td colspan="5" class="px-5 py-7 text-center text-white">No fee invoices found.</td></tr><?php else: foreach ($feeHistory as $invoice): ?><tr><td class="px-5 py-2 text-slate-300"><?= View::escape($invoice['invoice_no']) ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape($invoice['fee_head']) ?></td><td class="px-5 py-2 text-slate-300">৳<?= number_format((float) $invoice['amount'], 2) ?></td><td class="px-5 py-2"><span class="student-detail-status rounded-full border border-green-100/10 bg-emerald-950/30 px-2.5 py-1 text-xs text-green-50/80"><?= View::escape(ucfirst((string) $invoice['status'])) ?></span></td><td class="px-5 py-2 text-slate-300"><?= !empty($invoice['due_date']) ? \App\Core\DateFormatter::format($invoice['due_date']) : '—' ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>

        <section class="student-detail-enrollment-page-break overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="enrollment-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="enrollment-history-title" class="font-semibold">Previous enrollments</h2><p class="mt-1 text-xs text-white">Academic year and class history recorded during promotions</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[520px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Academic year</th><th class="px-5 py-3 font-medium">Class / section</th><th class="px-5 py-3 font-medium">Roll</th><th class="px-5 py-3 font-medium">Status</th></tr></thead><tbody class="divide-y divide-green-900/50"><?php if ($enrollmentHistory === []): ?><tr><td colspan="4" class="px-5 py-7 text-center text-white">No promotion history recorded yet.</td></tr><?php else: foreach ($enrollmentHistory as $enrollment): ?><tr><td class="px-5 py-2 text-slate-300"><?= View::escape($enrollment['year_name']) ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape($enrollment['class_name'] . ' · ' . $enrollment['section_name']) ?></td><td class="px-5 py-2 text-slate-300"><?= (int) $enrollment['roll_no'] ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape(ucfirst((string) $enrollment['status'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
        </section>
        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="results-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="results-history-title" class="font-semibold">Recent academic reports</h2><p class="mt-1 text-xs text-white">Recorded exam summaries</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[520px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Examination</th><th class="px-5 py-3 font-medium">Academic year</th><th class="px-5 py-3 font-medium">Marks</th><th class="px-5 py-3 font-medium">Class position</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if ($recentReports === []): ?><tr><td colspan="4" class="px-5 py-7 text-center text-white">No academic reports found.</td></tr><?php else: foreach ($recentReports as $report): ?><tr><td class="px-5 py-2 font-medium text-slate-200"><?= View::escape($report['exam_name']) ?></td><td class="px-5 py-2 text-white"><?= View::escape($report['academic_year']) ?></td><td class="px-5 py-2 text-slate-300"><?= (int) $report['obtained'] ?> / <?= (int) $report['full_marks'] ?></td><td class="px-5 py-2 text-slate-300"><?= $report['class_position'] ? (int) $report['class_position'] : '—' ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>
        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="documents-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="documents-history-title" class="font-semibold">Student documents</h2><p class="mt-1 text-xs text-white">Files attached to this student profile</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[420px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Document</th><th class="px-5 py-3 font-medium">Uploaded</th><th class="px-5 py-3 font-medium">File</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if (empty($documents)): ?><tr><td colspan="3" class="px-5 py-7 text-center text-white">No documents uploaded.</td></tr><?php else: foreach ($documents as $document): ?><tr><td class="px-5 py-2 text-slate-300"><?= View::escape($document['title']) ?></td><td class="px-5 py-2 text-slate-300"><?= \App\Core\DateFormatter::format($document['created_at']) ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape(basename((string) $document['file_path'])) ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>
        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="behaviour-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="behaviour-history-title" class="font-semibold">Student behaviour</h2><p class="mt-1 text-xs text-white">Behaviour notes from the student profile</p></div>
            <?php if (trim((string) ($student['student_behaviour'] ?? '')) !== ''): ?><p class="whitespace-pre-line px-5 py-4 text-sm text-white"><?= View::escape((string) $student['student_behaviour']) ?></p><?php else: ?><p class="px-5 py-4 text-sm text-white">No student behaviour details have been recorded.</p><?php endif; ?>
        </section>
    </div>
</div>
