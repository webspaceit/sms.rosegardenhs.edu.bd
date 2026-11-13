<?php
use App\Core\Url;
use App\Core\View;

$editing = $student !== null;
$old = $_SESSION['_student_old'] ?? [];
$fieldValue = static fn (string $key, mixed $fallback = ''): mixed => $old[$key] ?? ($student[$key] ?? $fallback);
$selectedClassId = (int) $fieldValue('class_id', $classes[0]['id'] ?? 0);
$nextStudentId = $nextStudentId ?? '';
$genderOptions = ['Male', 'Female', 'Other'];
$relationOptions = ['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'];
$guardian = $primaryGuardian ?? null;
$removedDocumentIds = array_map('intval', (array) ($old['document_remove_ids'] ?? []));
?>
<div class="mx-auto max-w-6xl">
    <a href="<?= Url::to('/students') ?>" class="text-sm text-green-300 hover:text-green-200">← Student register</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $editing ? 'Edit Student' : 'Student Admission' ?></h1>
    <p class="mt-1 text-sm text-white"><?= $editing ? 'Update the student record.' : 'Enter the full admission details for the student.' ?></p>

    <form id="student-registration-form" method="post" enctype="multipart/form-data" action="<?= Url::to($editing ? '/students/update' : '/students') ?>" class="mt-7 space-y-5">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $student['id'] ?>"><?php endif; ?>
        <input type="hidden" name="academic_year_id" value="<?= (int) ($student['academic_year_id'] ?? ($year['id'] ?? 0)) ?>">
        <?php if (!$editing): ?><input type="hidden" name="status" value="active"><input type="hidden" name="guardian_mode" value="new"><?php endif; ?>

        <!-- Student Admission -->
        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Student Admission</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-2 block text-sm font-medium">Admission No <span class="text-red-300">*</span></span><input readonly name="student_id" value="<?= View::escape((string) $fieldValue('student_id', $nextStudentId)) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2.5 text-sm text-slate-300"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Class <span class="text-red-300">*</span></span><select required id="student-class-field" name="class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" data-shift="<?= View::escape((string) ($class['shift'] ?? 'Day')) ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>><?= View::escape($class['name']) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Section <span class="text-red-300">*</span></span><select required id="student-section-field" name="section_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($sections as $section): ?><option data-class-id="<?= (int) $section['class_id'] ?>" value="<?= (int) $section['id'] ?>" <?= (int) $fieldValue('section_id', $student['section_id'] ?? 0) === (int) $section['id'] ? 'selected' : '' ?>><?= View::escape($section['class_name'] . ' · ' . $section['name']) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Shift</span><select id="student-shift-field" name="shift" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach (['Morning', 'Day'] as $shiftOpt): ?><option value="<?= $shiftOpt ?>" <?= $fieldValue('shift') === $shiftOpt ? 'selected' : '' ?>><?= $shiftOpt ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Roll Number <span class="text-red-300">*</span></span><input required min="1" max="65535" type="number" name="roll_no" placeholder="Roll Number" value="<?= View::escape((string) $fieldValue('roll_no')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Student Name <span class="text-red-300">*</span></span><input required maxlength="150" name="name" placeholder="Student Name" value="<?= View::escape((string) $fieldValue('name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Gender <span class="text-red-300">*</span></span><select name="gender" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($genderOptions as $gender): ?><option value="<?= $gender ?>" <?= $fieldValue('gender') === $gender ? 'selected' : '' ?>><?= $gender ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Date Of Birth</span><input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" value="<?= View::escape((string) $fieldValue('date_of_birth')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Birth Certificate / Local ID</span><input maxlength="50" name="birth_registration_no" value="<?= View::escape((string) $fieldValue('birth_registration_no')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <?php if ($editing): ?><label class="block"><span class="mb-2 block text-sm font-medium">Status</span><select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated'] as $statusValue => $statusLabel): ?><option value="<?= $statusValue ?>" <?= $fieldValue('status', 'active') === $statusValue ? 'selected' : '' ?>><?= $statusLabel ?></option><?php endforeach; ?></select></label><?php endif; ?>
                <label class="block"><span class="mb-2 block text-sm font-medium">Religion</span><select name="religion" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach (['Islam', 'Hinduism', 'Christianity', 'Buddhism', 'Other'] as $religionOpt): ?><option value="<?= $religionOpt ?>" <?= $fieldValue('religion') === $religionOpt ? 'selected' : '' ?>><?= $religionOpt ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Caste</span><input maxlength="100" name="caste" placeholder="Caste" value="<?= View::escape((string) $fieldValue('caste')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mobile Number</span><input maxlength="20" name="mobile_number" placeholder="Mobile Number" value="<?= View::escape((string) $fieldValue('mobile_number')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Email</span><input type="email" maxlength="190" name="email" placeholder="Email" value="<?= View::escape((string) $fieldValue('email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Admission Date</span><input type="date" name="admission_date" value="<?= View::escape((string) $fieldValue('admission_date', $editing ? '' : date('Y-m-d'))) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Blood Group</span><select name="blood_group" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?><option value="<?= $bg ?>" <?= $fieldValue('blood_group') === $bg ? 'selected' : '' ?>><?= $bg ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Student House</span><select name="house_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select House</option><?php foreach ($houses as $hid => $hname): ?><option value="<?= (int) $hid ?>" <?= (int) $fieldValue('house_id') === (int) $hid ? 'selected' : '' ?>><?= View::escape($hname) ?></option><?php endforeach; ?></select></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Height</span><input type="number" step="0.01" name="height" placeholder="Height (cm)" value="<?= View::escape((string) $fieldValue('height')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Weight</span><input type="number" step="0.01" name="weight" placeholder="Weight (kg)" value="<?= View::escape((string) $fieldValue('weight')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Measurement Date</span><input type="date" name="measurement_date" value="<?= View::escape((string) $fieldValue('measurement_date', $editing ? '' : date('Y-m-d'))) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Student Photo (100px × 100px)</span><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php if (!empty($student['photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>">View current photo</a><?php endif; ?></label>

                <label class="block sm:col-span-1 xl:col-span-2"><span class="mb-2 block text-sm font-medium">Medical History</span><textarea name="medical_history" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Medical History"><?= View::escape((string) $fieldValue('medical_history')) ?></textarea></label>
                <label class="block sm:col-span-1 xl:col-span-2"><span class="mb-2 block text-sm font-medium">Student Behaviour</span><textarea name="student_behaviour" maxlength="5000" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Record relevant behaviour notes"><?= View::escape((string) $fieldValue('student_behaviour')) ?></textarea></label>
            </div>
        </section>

        <!-- Parent / Guardian -->
        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Parent / Guardian Detail</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Name</span><input maxlength="150" name="father_name" placeholder="Father Name" value="<?= View::escape((string) $fieldValue('father_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Phone</span><input maxlength="20" name="father_phone" placeholder="Father Phone" value="<?= View::escape((string) $fieldValue('father_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Occupation</span><input maxlength="100" name="father_occupation" placeholder="Father Occupation" value="<?= View::escape((string) $fieldValue('father_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Photo</span><input type="file" name="father_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php if (!empty($student['father_photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['father_photo'])) ?>">View current photo</a><?php endif; ?></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Name</span><input maxlength="150" name="mother_name" placeholder="Mother Name" value="<?= View::escape((string) $fieldValue('mother_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Phone</span><input maxlength="20" name="mother_phone" placeholder="Mother Phone" value="<?= View::escape((string) $fieldValue('mother_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Occupation</span><input maxlength="100" name="mother_occupation" placeholder="Mother Occupation" value="<?= View::escape((string) $fieldValue('mother_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Photo</span><input type="file" name="mother_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php if (!empty($student['mother_photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['mother_photo'])) ?>">View current photo</a><?php endif; ?></label>
            </div>

            <?php if ($editing): ?>
                <?php if ($guardian !== null): ?>
                <input type="hidden" name="guardian_id" value="<?= (int) $guardian['id'] ?>">
                <input type="hidden" name="guardian_mode" value="existing">
                <label class="mt-4 block text-sm text-slate-300">Guardian action<select name="guardian_action" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm sm:max-w-md"><option value="none" selected>Keep unchanged</option><option value="update">Update this guardian</option><option value="unlink">Unlink from this student</option></select></label>
                <?php if ((int) ($guardian['linked_students'] ?? 1) > 1): ?><p class="mt-2 text-xs text-amber-200">Guardian details are shared with other students. Changes here will create a separate guardian record for this student.</p><?php endif; ?>
                <div class="mt-6 grid gap-5 border-t border-green-900/50 pt-5 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Name</span><input maxlength="150" name="guardian_name" value="<?= View::escape((string) $fieldValue('guardian_name', $guardian['name'])) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Relation</span><select name="guardian_relation" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($relationOptions as $relation): ?><option value="<?= $relation ?>" <?= $fieldValue('guardian_relation', $guardian['relation']) === $relation ? 'selected' : '' ?>><?= $relation ?></option><?php endforeach; ?></select></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Email</span><input type="email" maxlength="190" name="guardian_email" value="<?= View::escape((string) $fieldValue('guardian_email', $guardian['email'])) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Phone</span><input maxlength="20" name="guardian_phone" value="<?= View::escape((string) $fieldValue('guardian_phone', $guardian['phone'])) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Occupation</span><input maxlength="100" name="guardian_occupation" value="<?= View::escape((string) $fieldValue('guardian_occupation', $guardian['occupation'])) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Photo</span><input type="file" name="guardian_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php if (!empty($guardian['photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $guardian['photo'])) ?>">View current photo</a><?php endif; ?></label>
                    <label class="block sm:col-span-2 xl:col-span-4"><span class="mb-2 block text-sm font-medium">Guardian Address</span><textarea name="guardian_address" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?= View::escape((string) $fieldValue('guardian_address', $guardian['address'])) ?></textarea></label>
                </div>
                <?php if (count($linkedGuardians ?? []) > 1): ?><p class="mt-3 text-xs text-slate-400">Other linked guardians: <?= View::escape(implode(', ', array_map(static fn (array $item): string => (string) $item['name'], array_filter($linkedGuardians, static fn (array $item): bool => (int) $item['id'] !== (int) ($guardian['id'] ?? 0)))) ) ?></p><?php endif; ?>
                <?php else: ?>
                    <p class="mt-5 text-sm text-slate-400">No guardian is linked to this student.</p>
                    <label class="mt-3 flex items-center gap-2 text-sm text-slate-300"><input id="create-guardian-toggle" type="checkbox" name="create_guardian" value="1" <?= $fieldValue('create_guardian') === '1' ? 'checked' : '' ?>> Add a guardian while editing</label>
                    <div id="edit-new-guardian" class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-4 <?= $fieldValue('create_guardian') === '1' ? '' : 'hidden' ?>">
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Name <span class="text-red-300">*</span></span><input maxlength="150" name="guardian_name" value="<?= View::escape((string) $fieldValue('guardian_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Relation</span><select name="guardian_relation" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($relationOptions as $relation): ?><option value="<?= $relation ?>" <?= $fieldValue('guardian_relation', 'Father') === $relation ? 'selected' : '' ?>><?= $relation ?></option><?php endforeach; ?></select></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Phone <span class="text-red-300">*</span></span><input maxlength="20" name="guardian_phone" value="<?= View::escape((string) $fieldValue('guardian_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Email</span><input type="email" maxlength="190" name="guardian_email" value="<?= View::escape((string) $fieldValue('guardian_email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Occupation</span><input maxlength="100" name="guardian_occupation" value="<?= View::escape((string) $fieldValue('guardian_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Photo</span><input type="file" name="guardian_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block sm:col-span-2 xl:col-span-4"><span class="mb-2 block text-sm font-medium">Guardian Address</span><textarea name="guardian_address" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?= View::escape((string) $fieldValue('guardian_address')) ?></textarea></label>
                    </div>
                <?php endif; ?>
            <?php else: ?>
            <div class="mt-6 grid gap-5 border-t border-green-900/50 pt-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Name <span class="text-red-300">*</span></span><input required maxlength="150" name="guardian_name" placeholder="Guardian Name" value="<?= View::escape((string) $fieldValue('guardian_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Relation</span><select name="guardian_relation" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($relationOptions as $relation): ?><option value="<?= $relation ?>" <?= $fieldValue('guardian_relation', 'Father') === $relation ? 'selected' : '' ?>><?= $relation ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Email</span><input type="email" maxlength="190" name="guardian_email" placeholder="Guardian Email" value="<?= View::escape((string) $fieldValue('guardian_email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Photo</span><input type="file" name="guardian_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Phone <span class="text-red-300">*</span></span><input required maxlength="20" name="guardian_phone" placeholder="Guardian Phone" value="<?= View::escape((string) $fieldValue('guardian_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Occupation</span><input maxlength="100" name="guardian_occupation" placeholder="Guardian Occupation" value="<?= View::escape((string) $fieldValue('guardian_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block sm:col-span-2"><span class="mb-2 block text-sm font-medium">Guardian Address</span><input maxlength="10000" name="guardian_address" placeholder="Guardian Address" value="<?= View::escape((string) $fieldValue('guardian_address')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
            </div>
            <?php endif; ?>
        </section>

        <!-- Add More Details -->
        <details open class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <summary class="cursor-pointer text-lg font-semibold">Add More Details</summary>
            <div class="mt-5 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold">Student Address Details</h3>
                    <div class="mt-3 grid gap-5 sm:grid-cols-2">
                        <label class="block"><span class="mb-2 block text-sm font-medium">Current Address</span><textarea name="current_address" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Current Address"><?= View::escape((string) $fieldValue('current_address')) ?></textarea></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Permanent Address</span><textarea name="permanent_address" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Permanent Address"><?= View::escape((string) $fieldValue('permanent_address')) ?></textarea></label>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-semibold">Miscellaneous Details</h3>
                    <div class="mt-3 grid gap-5 sm:grid-cols-2">
                        <label class="block"><span class="mb-2 block text-sm font-medium">National Identification Number</span><input maxlength="50" name="national_id" placeholder="National ID" value="<?= View::escape((string) $fieldValue('national_id')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Local Identification Number</span><input maxlength="50" name="local_id" placeholder="Local ID / Birth Certificate No" value="<?= View::escape((string) $fieldValue('local_id')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Previous School Details</span><textarea name="previous_school" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Previous School Details"><?= View::escape((string) $fieldValue('previous_school')) ?></textarea></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Note</span><textarea name="note" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Note"><?= View::escape((string) $fieldValue('note')) ?></textarea></label>
                    </div>
                </div>
            </div>
        </details>

        <?php if ($enableTransport ?? true): ?>
        <!-- Transport -->
        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Transport Details</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-3">
                <label class="block"><span class="mb-2 block text-sm font-medium">Route List</span><select name="transport_route_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($transportRoutes as $rid => $rname): ?><option value="<?= (int) $rid ?>" <?= (int) $fieldValue('transport_route_id') === (int) $rid ? 'selected' : '' ?>><?= View::escape($rname) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Pickup Point</span><select name="transport_stop_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($transportStops as $sid => $sname): ?><option value="<?= (int) $sid ?>" <?= (int) $fieldValue('transport_stop_id') === (int) $sid ? 'selected' : '' ?>><?= View::escape($sname) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Fees Month</span><input maxlength="20" name="transport_fees_month" placeholder="January" value="<?= View::escape((string) $fieldValue('transport_fees_month', 'January')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($enableHostel ?? true): ?>
        <!-- Hostel -->
        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Hostel Details</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-medium">Hostel Room</span><select name="hostel_room_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($hostelRooms as $hid => $hname): ?><option value="<?= (int) $hid ?>" <?= (int) $fieldValue('hostel_room_id') === (int) $hid ? 'selected' : '' ?>><?= View::escape($hname) ?></option><?php endforeach; ?></select></label>
            </div>
        </section>
        <?php endif; ?>

        <!-- Documents -->
        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Upload Documents</h2>
            <div id="student-document-rows" class="mt-5 grid gap-4">
                <?php $documentIndex = 0; foreach (($documents ?? []) as $document): ?>
                    <?php if (in_array((int) $document['id'], $removedDocumentIds, true)): ?><input type="hidden" name="document_remove_ids[]" value="<?= (int) $document['id'] ?>"><?php continue; endif; ?>
                <div class="student-document-row grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto]" data-existing="1">
                    <input type="hidden" class="student-document-id" name="document_id_<?= $documentIndex ?>" value="<?= (int) $document['id'] ?>">
                    <input class="student-document-title w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" name="document_title_<?= $documentIndex ?>" placeholder="Title" value="<?= View::escape((string) ($old['document_title_' . $documentIndex] ?? $document['title'])) ?>">
                    <input type="file" class="student-document-file w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" name="document_file_<?= $documentIndex ?>">
                    <a href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $document['file_path'])) ?>" target="_blank" rel="noopener" class="rounded-lg border border-green-100/20 px-3 py-2 text-sm text-teal-200">Open</a>
                    <button type="button" class="student-document-remove-row rounded-lg border border-slate-700 px-3 py-2 text-sm text-rose-200 hover:border-rose-400">Remove</button>
                </div>
                <?php $documentIndex++; endforeach; ?>
                <?php foreach ($removedDocumentIds as $removedId): ?><input type="hidden" name="document_remove_ids[]" value="<?= (int) $removedId ?>"><?php endforeach; ?>
                <?php $newDocumentIndex = $documentIndex; ?>
                <div class="student-document-row grid gap-3 sm:grid-cols-[1fr_2fr_auto]" data-existing="0">
                    <input class="student-document-title w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" name="document_title_<?= (int) $newDocumentIndex ?>" placeholder="Title" value="<?= View::escape((string) $fieldValue('document_title_' . $newDocumentIndex)) ?>">
                    <input type="file" class="student-document-file w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" name="document_file_<?= (int) $newDocumentIndex ?>">
                    <?php if ($newDocumentIndex > 0): ?><button type="button" class="student-document-remove-row rounded-lg border border-slate-700 px-3 py-2 text-sm text-rose-200 hover:border-rose-400">Remove row</button><?php endif; ?>
                </div>
            </div>
            <button type="button" id="add-student-document" class="mt-4 rounded-lg border border-green-100/20 px-4 py-2 text-sm text-teal-200 hover:border-teal-300">+ Add another document</button>
            <input type="hidden" id="student-document-count" name="document_count" value="<?= (int) $newDocumentIndex + 1 ?>">
        </section>

        <div class="flex flex-wrap justify-end gap-3 border-t border-green-900/60 pt-5">
            <a href="<?= Url::to('/students') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button <?= !$year ? 'disabled' : '' ?> class="rounded-lg bg-teal-400 px-6 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save</button>
        </div>
    </form>
</div>
<script>
(() => {
  const classField = document.getElementById('student-class-field');
  const sectionField = document.getElementById('student-section-field');
  if (classField && sectionField) {
    const syncSections = () => {
      const classId = classField.value;
      let firstVisible = null;
      for (const option of sectionField.options) {
        const visible = option.dataset.classId === classId;
        option.hidden = !visible;
        if (visible && !firstVisible) firstVisible = option;
      }
      if (!sectionField.selectedOptions[0] || sectionField.selectedOptions[0].dataset.classId !== classId) {
        if (firstVisible) sectionField.value = firstVisible.value;
      }
    };
    classField.addEventListener('change', syncSections);
    syncSections();
  }

  const createGuardianToggle = document.getElementById('create-guardian-toggle');
  const editNewGuardian = document.getElementById('edit-new-guardian');
  createGuardianToggle?.addEventListener('change', () => {
    editNewGuardian?.classList.toggle('hidden', !createGuardianToggle.checked);
  });

  const documentRows = document.getElementById('student-document-rows');
  const addDocumentButton = document.getElementById('add-student-document');
  const documentCount = document.getElementById('student-document-count');
  const reindexDocuments = () => {
    const rows = [...documentRows.querySelectorAll('.student-document-row')].filter(row => !row.hidden);
    rows.forEach((row, index) => {
      row.querySelector('.student-document-title').name = `document_title_${index}`;
      row.querySelector('.student-document-file').name = `document_file_${index}`;
      const id = row.querySelector('.student-document-id');
      if (id) id.name = `document_id_${index}`;
    });
    documentCount.value = String(rows.length);
    addDocumentButton.disabled = rows.length >= 20;
  };
  documentRows.addEventListener('click', event => {
    const button = event.target.closest('.student-document-remove-row');
    if (!button) return;
    const row = button.closest('.student-document-row');
    if (!row) return;
    if (row.dataset.existing === '1') {
      row.querySelector('.student-document-remove').disabled = false;
      row.hidden = true;
    } else if (documentRows.querySelectorAll('.student-document-row[data-existing="0"]').length > 1) {
      row.remove();
    }
    reindexDocuments();
  });

  addDocumentButton?.addEventListener('click', () => {
    const index = documentRows.querySelectorAll('.student-document-row').length;
    if (index >= 20) {
      addDocumentButton.disabled = true;
      return;
    }

    const row = document.createElement('div');
    row.className = 'student-document-row grid gap-3 sm:grid-cols-[1fr_2fr_auto]';

    const title = document.createElement('input');
    title.name = `document_title_${index}`;
    title.placeholder = 'Title';
    title.className = 'student-document-title w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm';

    const file = document.createElement('input');
    file.type = 'file';
    file.name = `document_file_${index}`;
    file.className = 'student-document-file w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm';

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'student-document-remove-row rounded-lg border border-slate-700 px-3 py-2 text-sm text-rose-200 hover:border-rose-400';
    remove.textContent = 'Remove row';
    row.append(title, file, remove);
    documentRows.append(row);
    reindexDocuments();
  });
  reindexDocuments();
})();

</script>
