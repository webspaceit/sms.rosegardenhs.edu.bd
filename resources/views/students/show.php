<?php
use App\Core\Url;
use App\Core\View;

$attendanceTotal = (int) ($attendanceSummary['total'] ?? 0);
$attendanceAttended = (int) ($attendanceSummary['attended'] ?? 0);
$attendanceRate = $attendanceTotal > 0 ? (int) round(($attendanceAttended / $attendanceTotal) * 100) : null;
$statusClass = [
    'active' => 'border-green-300/20 bg-green-300/10 text-green-100',
    'inactive' => 'border-slate-200/15 bg-slate-200/10 text-slate-200',
    'graduated' => 'border-sky-300/20 bg-sky-300/10 text-sky-100',
][$student['status']] ?? 'border-slate-200/15 bg-slate-200/10 text-slate-200';
?>
<div class="mx-auto max-w-6xl">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="<?= Url::to('/students') ?>" class="text-sm text-green-200/75 hover:text-green-100">← Student register</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold">Student details</h1>
                <span class="rounded-full border px-3 py-1 text-xs font-medium <?= $statusClass ?>"><?= View::escape(ucfirst((string) $student['status'])) ?></span>
            </div>
            <p class="mt-1 text-sm text-white">Student ID: <?= View::escape($student['student_id']) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="<?= Url::to('/students') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-50/80 hover:border-green-200/40">Back to register</a>
            <a href="<?= Url::to('/students/edit?id=' . (int) $student['id']) ?>" class="form-action-button">Edit student</a>
            <a href="<?= Url::to('/students/guardians?id=' . (int) $student['id']) ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-50/80">Manage guardians</a>
        </div>
    </header>

    <div class="mt-7 grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-green-900/60 dashboard-gradient p-5 sm:p-6" aria-labelledby="student-profile-title">
            <div class="flex items-center gap-4 border-b border-green-100/10 pb-5">
                <?php if (!empty($student['photo'])): ?><img src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>" alt="" class="h-14 w-14 shrink-0 rounded-2xl border border-green-200/20 object-cover"><?php else: ?><div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl border border-green-200/20 bg-green-200/10 text-xl font-semibold text-green-100"><?= View::escape(strtoupper(substr((string) $student['name'], 0, 1))) ?></div><?php endif; ?>
                <div class="min-w-0"><h2 id="student-profile-title" class="truncate text-xl font-semibold"><?= View::escape($student['name']) ?></h2><?php if (!empty($student['name_bn'])): ?><p class="mt-1 text-sm text-white"><?= View::escape($student['name_bn']) ?></p><?php endif; ?></div>
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs text-white">Student ID</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['student_id']) ?></dd></div>
                <div><dt class="text-xs text-white">Gender</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['gender'] ?: 'Not specified') ?></dd></div>
                <div><dt class="text-xs text-white">Date of birth</dt><dd class="mt-1 text-sm font-medium"><?= !empty($student['date_of_birth']) ? date('M j, Y', strtotime((string) $student['date_of_birth'])) : 'Not provided' ?></dd></div>
                <div><dt class="text-xs text-white">Admission date</dt><dd class="mt-1 text-sm font-medium"><?= !empty($student['admission_date']) ? date('M j, Y', strtotime((string) $student['admission_date'])) : 'Not provided' ?></dd></div>
                <div><dt class="text-xs text-white">Birth registration number</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['birth_registration_no'] ?: 'Not provided') ?></dd></div>
                <div><dt class="text-xs text-white">Previous school</dt><dd class="mt-1 text-sm font-medium"><?= View::escape($student['previous_school'] ?: 'Not provided') ?></dd></div>
                <div><dt class="text-xs text-white">Current status</dt><dd class="mt-1 text-sm font-medium"><?= View::escape(ucfirst((string) $student['status'])) ?></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-white">Address</dt><dd class="mt-1 text-sm font-medium"><?= nl2br(View::escape($student['address'] ?: 'Not provided')) ?></dd></div>
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

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="attendance-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="attendance-history-title" class="font-semibold">Recent attendance</h2><p class="mt-1 text-xs text-white">Latest recorded attendance entries</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[420px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Remarks</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if ($recentAttendance === []): ?><tr><td colspan="3" class="px-5 py-7 text-center text-white">No attendance records found.</td></tr><?php else: foreach ($recentAttendance as $record): ?><tr><td class="px-5 py-2 text-slate-300"><?= date('M j, Y', strtotime((string) $record['date'])) ?></td><td class="px-5 py-2"><span class="rounded-full border border-green-100/10 bg-emerald-950/30 px-2.5 py-1 text-xs text-green-50/80"><?= View::escape(ucwords(str_replace('_', ' ', (string) $record['status']))) ?></span></td><td class="px-5 py-2 text-white"><?= View::escape($record['remarks'] ?: '—') ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient" aria-labelledby="fee-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="fee-history-title" class="font-semibold">Recent fee invoices</h2><p class="mt-1 text-xs text-white">Latest fee records for this student</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[500px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Invoice</th><th class="px-5 py-3 font-medium">Fee head</th><th class="px-5 py-3 font-medium">Amount</th><th class="px-5 py-3 font-medium">Status</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if ($feeHistory === []): ?><tr><td colspan="4" class="px-5 py-7 text-center text-white">No fee invoices found.</td></tr><?php else: foreach ($feeHistory as $invoice): ?><tr><td class="px-5 py-2 text-slate-300"><?= View::escape($invoice['invoice_no']) ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape($invoice['fee_head']) ?></td><td class="px-5 py-2 text-slate-300">৳<?= number_format((float) $invoice['amount'], 2) ?></td><td class="px-5 py-2"><span class="rounded-full border border-green-100/10 bg-emerald-950/30 px-2.5 py-1 text-xs text-green-50/80"><?= View::escape(ucfirst((string) $invoice['status'])) ?></span></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="enrollment-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="enrollment-history-title" class="font-semibold">Previous enrollments</h2><p class="mt-1 text-xs text-white">Academic year and class history recorded during promotions</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[520px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Academic year</th><th class="px-5 py-3 font-medium">Class / section</th><th class="px-5 py-3 font-medium">Roll</th><th class="px-5 py-3 font-medium">Status</th></tr></thead><tbody class="divide-y divide-green-900/50"><?php if ($enrollmentHistory === []): ?><tr><td colspan="4" class="px-5 py-7 text-center text-white">No promotion history recorded yet.</td></tr><?php else: foreach ($enrollmentHistory as $enrollment): ?><tr><td class="px-5 py-2 text-slate-300"><?= View::escape($enrollment['year_name']) ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape($enrollment['class_name'] . ' · ' . $enrollment['section_name']) ?></td><td class="px-5 py-2 text-slate-300"><?= (int) $enrollment['roll_no'] ?></td><td class="px-5 py-2 text-slate-300"><?= View::escape(ucfirst((string) $enrollment['status'])) ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
        </section>
        <section class="overflow-hidden rounded-2xl border border-green-900/60 dashboard-gradient xl:col-span-2" aria-labelledby="results-history-title">
            <div class="border-b border-green-900/60 px-5 py-4"><h2 id="results-history-title" class="font-semibold">Recent academic reports</h2><p class="mt-1 text-xs text-white">Recorded exam summaries</p></div>
            <div class="overflow-x-auto"><table class="admit-list-table w-full min-w-[520px] text-left text-sm"><thead class="text-sm uppercase tracking-wide text-green-100/55"><tr><th class="px-5 py-3 font-medium">Examination</th><th class="px-5 py-3 font-medium">Academic year</th><th class="px-5 py-3 font-medium">Marks</th><th class="px-5 py-3 font-medium">Class position</th></tr></thead><tbody class="divide-y divide-green-900/50">
                <?php if ($recentReports === []): ?><tr><td colspan="4" class="px-5 py-7 text-center text-white">No academic reports found.</td></tr><?php else: foreach ($recentReports as $report): ?><tr><td class="px-5 py-2 font-medium text-slate-200"><?= View::escape($report['exam_name']) ?></td><td class="px-5 py-2 text-white"><?= View::escape($report['academic_year']) ?></td><td class="px-5 py-2 text-slate-300"><?= (int) $report['obtained'] ?> / <?= (int) $report['full_marks'] ?></td><td class="px-5 py-2 text-slate-300"><?= $report['class_position'] ? (int) $report['class_position'] : '—' ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>
    </div>
</div>
