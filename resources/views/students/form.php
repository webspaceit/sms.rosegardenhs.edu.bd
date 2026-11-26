<?php
use App\Core\Url;
use App\Core\View;

$editing = $student !== null;
$old = $_SESSION['_student_old'] ?? [];
$fieldValue = static fn (string $key, mixed $fallback = ''): mixed => $old[$key] ?? ($student[$key] ?? $fallback);
$selectedClassId = (int) $fieldValue('class_id', $classes[0]['id'] ?? 0);
$selectedSectionId = (int) $fieldValue('section_id', $student['section_id'] ?? 0);
$selectedShift = (string) $fieldValue('shift', '');
if (!$editing && $selectedShift === '') {
    foreach ($classes as $classOption) {
        if ((int) $classOption['id'] === $selectedClassId) {
            $selectedShift = in_array($classOption['shift'] ?? '', ['Morning', 'Day'], true) ? (string) $classOption['shift'] : 'Day';
            break;
        }
    }
}
$suggestedRoll = 1;
foreach ($sections as $sectionOption) {
    if ((int) $sectionOption['class_id'] === $selectedClassId && $selectedSectionId === 0) $selectedSectionId = (int) $sectionOption['id'];
    if ((int) $sectionOption['id'] === $selectedSectionId) {
        $rollKey = $selectedShift === 'Morning' ? 'next_roll_morning' : ($selectedShift === 'Day' ? 'next_roll_day' : 'next_roll');
        $suggestedRoll = (int) ($sectionOption[$rollKey] ?? 1);
    }
}
$selectedClassLevel = 0;
$selectedClassHasAcademicGroup = false;
foreach ($classes as $classOption) {
    if ((int) $classOption['id'] === $selectedClassId) {
        $selectedClassLevel = (int) $classOption['numeric_level'];
        $normalizedClassName = strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string) $classOption['name']) ?? '');
        $normalizedClassName = preg_replace('/^CLASS/', '', $normalizedClassName) ?? $normalizedClassName;
        $knownClassNames = ['1', 'ONE', '2', 'TWO', '3', 'THREE', '4', 'FOUR', '5', 'FIVE', '6', 'SIX', '7', 'SEVEN', '8', 'EIGHT', '9', 'NINE', '10', 'TEN'];
        $selectedClassHasAcademicGroup = in_array($normalizedClassName, $knownClassNames, true)
            ? in_array($normalizedClassName, ['9', 'NINE', '10', 'TEN'], true)
            : in_array($selectedClassLevel, [9, 10], true);
        break;
    }
}
$nextStudentId = $nextStudentId ?? '';
$genderOptions = ['Male', 'Female', 'Other'];
$relationOptions = ['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'];
$guardian = $primaryGuardian ?? null;
$removedDocumentIds = array_map('intval', (array) ($old['document_remove_ids'] ?? []));
$wizardFiles = (array) ($_SESSION['_student_wizard']['files'] ?? []);
$wizardDocumentNames = (array) ($_SESSION['_student_wizard']['original_names'] ?? []);
$studentPhotoRequired = empty($student['photo']) && empty($wizardFiles['photo']);
?>
<div class="w-full min-w-0">
    <a href="<?= Url::to('/students') ?>" class="text-sm text-green-300 hover:text-green-200">← Student register</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $editing ? 'Edit Student' : 'Student Admission' ?></h1>
    <p class="mt-1 text-sm text-white"><?= $editing ? 'Update the student record.' : 'Enter the full admission details for the student.' ?></p>

    <?php if (!$editing): ?><div id="student-wizard-progress" class="mt-6 grid grid-cols-4 gap-2" aria-label="Admission steps"><?php foreach (['Student', 'Parent / Guardian', 'More details', 'Documents'] as $stepIndex => $stepTitle): ?><div class="student-wizard-indicator rounded-lg border px-3 py-2 text-center text-xs font-semibold" data-step-indicator="<?= $stepIndex + 1 ?>"><?= ($stepIndex + 1) . '. ' . View::escape($stepTitle) ?></div><?php endforeach; ?></div><?php endif; ?>
    <form id="student-registration-form" method="post" enctype="multipart/form-data" action="<?= Url::to($editing ? '/students/update' : '/students') ?>" data-no-ajax class="mt-7 space-y-5">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $student['id'] ?>"><?php endif; ?>
        <input type="hidden" name="academic_year_id" value="<?= (int) ($student['academic_year_id'] ?? ($year['id'] ?? 0)) ?>">
        <?php if (!$editing): ?><input type="hidden" name="_wizard_finish" id="student-wizard-finish" value="0"><?php endif; ?>
        <?php if (!$editing): ?><input type="hidden" name="status" value="active"><input type="hidden" name="guardian_mode" value="new"><?php endif; ?>

        <!-- Student Admission -->
        <section data-wizard-step="1" class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Student Admission</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-2 block text-sm font-medium">Admission No <span class="text-red-300">*</span></span><input readonly name="student_id" value="<?= View::escape((string) $fieldValue('student_id', $nextStudentId)) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2.5 text-sm text-slate-300"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Class <span class="text-red-300">*</span></span><select required id="student-class-field" name="class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" data-level="<?= (int) $class['numeric_level'] ?>" data-class-name="<?= View::escape($class['name']) ?>" data-shift="<?= View::escape((string) ($class['shift'] ?? 'Day')) ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>><?= View::escape($class['name']) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Section <span class="text-red-300">*</span></span><select required id="student-section-field" name="section_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($sections as $section): ?><option data-class-id="<?= (int) $section['class_id'] ?>" data-next-roll="<?= (int) ($section['next_roll'] ?? 1) ?>" data-next-roll-morning="<?= (int) ($section['next_roll_morning'] ?? 1) ?>" data-next-roll-day="<?= (int) ($section['next_roll_day'] ?? 1) ?>" value="<?= (int) $section['id'] ?>" <?= $selectedSectionId === (int) $section['id'] ? 'selected' : '' ?>><?= View::escape($section['class_name'] . ' · ' . $section['name']) ?></option><?php endforeach; ?></select></label>
                <label id="student-academic-group-wrap" class="block <?= $selectedClassHasAcademicGroup ? '' : 'hidden' ?>"><span class="mb-2 block text-sm font-medium">Academic Group <span class="text-red-300">*</span></span><select id="student-academic-group" name="academic_group" <?= $selectedClassHasAcademicGroup ? 'required' : '' ?> class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select group</option><?php foreach ($academicGroups as $group): ?><option value="<?= View::escape($group) ?>" <?= (string) $fieldValue('academic_group') === $group ? 'selected' : '' ?>><?= View::escape($group) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Shift</span><select id="student-shift-field" name="shift" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach (['Morning', 'Day'] as $shiftOpt): ?><option value="<?= $shiftOpt ?>" <?= $selectedShift === $shiftOpt ? 'selected' : '' ?>><?= $shiftOpt ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Roll Number <span class="text-red-300">*</span></span><input required min="1" max="65535" type="number" id="student-roll-field" data-auto-roll="<?= !$editing ? '1' : '0' ?>" name="roll_no" placeholder="Roll Number" value="<?= View::escape((string) $fieldValue('roll_no', $editing ? '' : $suggestedRoll)) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-400">Suggested from the next available roll for this class, section, and shift.</span></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Student Name <span class="text-red-300">*</span></span><input required maxlength="150" name="name" placeholder="Student Name" value="<?= View::escape((string) $fieldValue('name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Gender <span class="text-red-300">*</span></span><select required name="gender" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select Gender</option><?php foreach ($genderOptions as $gender): ?><option value="<?= $gender ?>" <?= $fieldValue('gender') === $gender ? 'selected' : '' ?>><?= $gender ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Date Of Birth</span><input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" value="<?= View::escape((string) $fieldValue('date_of_birth')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Birth Certificate / Local ID</span><input maxlength="50" name="birth_registration_no" value="<?= View::escape((string) $fieldValue('birth_registration_no')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <?php if ($editing): ?><label class="block"><span class="mb-2 block text-sm font-medium">Status</span><select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated'] as $statusValue => $statusLabel): ?><option value="<?= $statusValue ?>" <?= $fieldValue('status', 'active') === $statusValue ? 'selected' : '' ?>><?= $statusLabel ?></option><?php endforeach; ?></select></label><?php endif; ?>
                <label class="block"><span class="mb-2 block text-sm font-medium">Religion</span><select name="religion" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach (['Islam', 'Hinduism', 'Christianity', 'Buddhism', 'Other'] as $religionOpt): ?><option value="<?= $religionOpt ?>" <?= $fieldValue('religion') === $religionOpt ? 'selected' : '' ?>><?= $religionOpt ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Caste</span><input maxlength="100" name="caste" placeholder="Caste" value="<?= View::escape((string) $fieldValue('caste')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mobile Number</span><input type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." name="mobile_number" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" value="<?= View::escape((string) $fieldValue('mobile_number')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Email</span><input type="email" maxlength="190" name="email" placeholder="Email" value="<?= View::escape((string) $fieldValue('email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Admission Date</span><input type="date" name="admission_date" value="<?= View::escape((string) $fieldValue('admission_date', $editing ? '' : date('Y-m-d'))) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">B.Group</span><select name="blood_group" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?><option value="<?= $bg ?>" <?= $fieldValue('blood_group') === $bg ? 'selected' : '' ?>><?= $bg ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Student House</span><select name="house_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select House</option><?php foreach ($houses as $hid => $hname): ?><option value="<?= (int) $hid ?>" <?= (int) $fieldValue('house_id') === (int) $hid ? 'selected' : '' ?>><?= View::escape($hname) ?></option><?php endforeach; ?></select></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Height</span><input type="number" step="0.01" name="height" placeholder="Height (cm)" value="<?= View::escape((string) $fieldValue('height')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Weight</span><input type="number" step="0.01" name="weight" placeholder="Weight (kg)" value="<?= View::escape((string) $fieldValue('weight')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Measurement Date</span><input type="date" name="measurement_date" value="<?= View::escape((string) $fieldValue('measurement_date', $editing ? '' : date('Y-m-d'))) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <div class="block min-w-0">
                    <label for="student-photo-input" class="mb-2 block text-sm font-medium">Student Photo<?= $studentPhotoRequired ? ' <span class="text-red-300">*</span>' : '' ?></label>
                    <?php $photoPreviewPath = (string) ($wizardFiles['photo'] ?? ($student['photo'] ?? '')); ?>
                    <?php $photoPreviewUrl = ''; ?>
                    <?php if ($photoPreviewPath !== '' && !empty($wizardFiles['photo'])): ?>
                        <?php $photoUploadRoot = realpath(BASE_PATH . '/storage/uploads'); $photoPreviewFile = $photoUploadRoot !== false ? realpath($photoUploadRoot . DIRECTORY_SEPARATOR . $photoPreviewPath) : false; ?>
                        <?php if (str_starts_with($photoPreviewPath, 'student-photos/') && $photoPreviewFile !== false && str_starts_with($photoPreviewFile, $photoUploadRoot . DIRECTORY_SEPARATOR) && is_file($photoPreviewFile) && (new finfo(FILEINFO_MIME_TYPE))->file($photoPreviewFile) === 'image/jpeg'): ?>
                            <?php $photoPreviewBytes = file_get_contents($photoPreviewFile); $photoPreviewUrl = $photoPreviewBytes !== false ? 'data:image/jpeg;base64,' . base64_encode($photoPreviewBytes) : ''; ?>
                        <?php endif; ?>
                    <?php elseif (!empty($student['photo'])): ?>
                        <?php $photoPreviewUrl = Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])); ?>
                    <?php endif; ?>
                    <div class="flex min-w-0 items-center gap-2">
                        <input id="student-photo-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" <?= $studentPhotoRequired ? 'required' : '' ?> class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
                        <div id="student-photo-current" class="h-[38px] w-[38px] shrink-0 overflow-hidden rounded-lg border border-green-100/20 bg-slate-900 <?= $photoPreviewUrl === '' ? 'hidden' : '' ?>">
                            <img id="student-photo-preview" src="<?= View::escape($photoPreviewUrl) ?>" data-original-src="<?= View::escape($photoPreviewUrl) ?>" alt="Student photo thumbnail" class="h-full w-full object-cover">
                        </div>
                        <?php if (!empty($student['photo'])): ?>
                            <button type="button" id="student-photo-delete" class="student-photo-delete-icon grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-rose-300/25 text-rose-200 transition hover:border-rose-300/60 hover:bg-rose-400/10" title="Delete photo from server when saved" aria-label="Delete student photo from server when saved">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 2-.9 12H5.9L5 6m4 4v5m6-5v5"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                    <span class="mt-1 block text-xs text-slate-400">Max 300 × 300 px; saved as JPG.</span>
                    <?php if (!$editing && !empty($wizardFiles['photo'])): ?><span class="wizard-file-saved mt-1 block text-xs text-green-300">Photo saved for this admission.</span><?php endif; ?>
                    <?php if (!empty($student['photo'])): ?>
                        <input type="checkbox" id="student-photo-remove" name="remove_photo" value="1" class="hidden">
                        <p id="student-photo-status" class="mt-1 text-xs text-amber-200" role="status" aria-live="polite"></p>
                    <?php endif; ?>
                </div>
                <label class="block xl:col-span-2"><span class="mb-2 block text-sm font-medium">Medical History</span><textarea name="medical_history" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Medical History"><?= View::escape((string) $fieldValue('medical_history')) ?></textarea></label>
                <label id="student-behaviour-field" class="block xl:col-span-2 <?= $selectedClassLevel === 9 ? 'student-behaviour-class-nine' : '' ?>"><span class="mb-2 block text-sm font-medium">Student Behaviour</span><textarea name="student_behaviour" maxlength="5000" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Record relevant behaviour notes"><?= View::escape((string) $fieldValue('student_behaviour')) ?></textarea></label>
            </div>
        </section>

        <!-- Parent / Guardian -->
        <section data-wizard-step="2" class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Parent / Guardian Detail</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Name</span><input maxlength="150" name="father_name" placeholder="Father Name" value="<?= View::escape((string) $fieldValue('father_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Phone</span><input type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." name="father_phone" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" value="<?= View::escape((string) $fieldValue('father_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Occupation</span><input maxlength="100" name="father_occupation" placeholder="Father Occupation" value="<?= View::escape((string) $fieldValue('father_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Father Photo</span><input type="file" name="father_photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-400">Max 300 × 300 px; saved as JPG.</span><?php if (!$editing && !empty($wizardFiles['father_photo'])): ?><span class="mt-1 block text-xs text-green-300">Photo saved for this admission.</span><?php endif; ?><?php if (!empty($student['father_photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['father_photo'])) ?>">View current photo</a><label class="mt-2 flex items-center gap-2 text-xs text-rose-200"><input type="checkbox" name="remove_father_photo" value="1"> Delete photo from server</label><?php endif; ?></label>

                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Name</span><input maxlength="150" name="mother_name" placeholder="Mother Name" value="<?= View::escape((string) $fieldValue('mother_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Phone</span><input type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." name="mother_phone" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" value="<?= View::escape((string) $fieldValue('mother_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Occupation</span><input maxlength="100" name="mother_occupation" placeholder="Mother Occupation" value="<?= View::escape((string) $fieldValue('mother_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Mother Photo</span><input type="file" name="mother_photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-400">Max 300 × 300 px; saved as JPG.</span><?php if (!$editing && !empty($wizardFiles['mother_photo'])): ?><span class="mt-1 block text-xs text-green-300">Photo saved for this admission.</span><?php endif; ?><?php if (!empty($student['mother_photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['mother_photo'])) ?>">View current photo</a><label class="mt-2 flex items-center gap-2 text-xs text-rose-200"><input type="checkbox" name="remove_mother_photo" value="1"> Delete photo from server</label><?php endif; ?></label>
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
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Phone</span><input type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." placeholder="01XXXXXXXXX or +8801XXXXXXXXX" name="guardian_phone" value="<?= View::escape((string) $fieldValue('guardian_phone', $guardian['phone'])) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Occupation</span><input maxlength="100" name="guardian_occupation" value="<?= View::escape((string) $fieldValue('guardian_occupation', $guardian['occupation'])) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Photo</span><input type="file" name="guardian_photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-400">Max 300 × 300 px; saved as JPG.</span><?php if (!empty($guardian['photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $guardian['photo'])) ?>">View current photo</a><label class="mt-2 flex items-center gap-2 text-xs text-rose-200"><input type="checkbox" name="remove_guardian_photo" value="1"> Delete photo from server</label><?php endif; ?></label>
                    <label class="block sm:col-span-2 xl:col-span-4"><span class="mb-2 block text-sm font-medium">Guardian Address</span><textarea name="guardian_address" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?= View::escape((string) $fieldValue('guardian_address', $guardian['address'])) ?></textarea></label>
                </div>
                <?php if (count($linkedGuardians ?? []) > 1): ?><p class="mt-3 text-xs text-slate-400">Other linked guardians: <?= View::escape(implode(', ', array_map(static fn (array $item): string => (string) $item['name'], array_filter($linkedGuardians, static fn (array $item): bool => (int) $item['id'] !== (int) ($guardian['id'] ?? 0)))) ) ?></p><?php endif; ?>
                <?php else: ?>
                    <p class="mt-5 text-sm text-slate-400">No guardian is linked to this student.</p>
                    <label class="mt-3 flex items-center gap-2 text-sm text-slate-300"><input id="create-guardian-toggle" type="checkbox" name="create_guardian" value="1" <?= $fieldValue('create_guardian') === '1' ? 'checked' : '' ?>> Add a guardian while editing</label>
                    <div id="edit-new-guardian" class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-4 <?= $fieldValue('create_guardian') === '1' ? '' : 'hidden' ?>">
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Name <span class="text-red-300">*</span></span><input maxlength="150" name="guardian_name" value="<?= View::escape((string) $fieldValue('guardian_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Relation</span><select name="guardian_relation" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($relationOptions as $relation): ?><option value="<?= $relation ?>" <?= $fieldValue('guardian_relation', 'Father') === $relation ? 'selected' : '' ?>><?= $relation ?></option><?php endforeach; ?></select></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Phone <span class="text-red-300">*</span></span><input type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." placeholder="01XXXXXXXXX or +8801XXXXXXXXX" name="guardian_phone" value="<?= View::escape((string) $fieldValue('guardian_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Email</span><input type="email" maxlength="190" name="guardian_email" value="<?= View::escape((string) $fieldValue('guardian_email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Occupation</span><input maxlength="100" name="guardian_occupation" value="<?= View::escape((string) $fieldValue('guardian_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                        <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Photo</span><input type="file" name="guardian_photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-400">Max 300 × 300 px; saved as JPG.</span></label>
                        <label class="block sm:col-span-2 xl:col-span-4"><span class="mb-2 block text-sm font-medium">Guardian Address</span><textarea name="guardian_address" rows="2" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?= View::escape((string) $fieldValue('guardian_address')) ?></textarea></label>
                    </div>
                <?php endif; ?>
            <?php else: ?>
            <div class="mt-6 grid gap-5 border-t border-green-900/50 pt-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Name <span class="text-red-300">*</span></span><input required maxlength="150" name="guardian_name" placeholder="Guardian Name" value="<?= View::escape((string) $fieldValue('guardian_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Relation</span><select name="guardian_relation" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($relationOptions as $relation): ?><option value="<?= $relation ?>" <?= $fieldValue('guardian_relation', 'Father') === $relation ? 'selected' : '' ?>><?= $relation ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Email</span><input type="email" maxlength="190" name="guardian_email" placeholder="Guardian Email" value="<?= View::escape((string) $fieldValue('guardian_email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Photo</span><input type="file" name="guardian_photo" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-400">Max 300 × 300 px; saved as JPG.</span></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Phone <span class="text-red-300">*</span></span><input required type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." name="guardian_phone" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" value="<?= View::escape((string) $fieldValue('guardian_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian Occupation</span><input maxlength="100" name="guardian_occupation" placeholder="Guardian Occupation" value="<?= View::escape((string) $fieldValue('guardian_occupation')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block sm:col-span-2"><span class="mb-2 block text-sm font-medium">Guardian Address</span><input maxlength="10000" name="guardian_address" placeholder="Guardian Address" value="<?= View::escape((string) $fieldValue('guardian_address')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
            </div>
            <?php endif; ?>
        </section>

        <!-- Add More Details -->
        <details data-wizard-step="3" open class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
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
        <section data-wizard-step="3" class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
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
        <section data-wizard-step="3" class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Hostel Details</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-medium">Hostel Room</span><select name="hostel_room_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Select</option><?php foreach ($hostelRooms as $hid => $hname): ?><option value="<?= (int) $hid ?>" <?= (int) $fieldValue('hostel_room_id') === (int) $hid ? 'selected' : '' ?>><?= View::escape($hname) ?></option><?php endforeach; ?></select></label>
            </div>
        </section>
        <?php endif; ?>

        <!-- Documents -->
        <section data-wizard-step="4" class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Upload Documents</h2>
            <div id="student-document-rows" class="mt-5 grid gap-4">
                <?php $documentIndex = 0; foreach (($documents ?? []) as $document): ?>
                    <?php if (in_array((int) $document['id'], $removedDocumentIds, true)): ?><input type="hidden" name="document_remove_ids[]" value="<?= (int) $document['id'] ?>"><?php continue; endif; ?>
                    <?php $documentOriginalName = trim((string) ($document['original_name'] ?? '')) ?: basename((string) $document['file_path']); ?>
                <div class="student-document-row grid gap-3 sm:grid-cols-[1fr_2fr_auto]" data-existing="1">
                    <input type="hidden" class="student-document-id" name="document_id_<?= $documentIndex ?>" value="<?= (int) $document['id'] ?>">
                    <input class="student-document-title w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" name="document_title_<?= $documentIndex ?>" placeholder="Title" value="<?= View::escape((string) ($old['document_title_' . $documentIndex] ?? $document['title'])) ?>">
                    <div class="flex min-w-0 items-center rounded-lg border border-green-100/15 bg-emerald-950/45 px-3 py-2.5"><span class="student-document-file-name min-w-0 truncate text-sm text-slate-300" data-current-name="<?= View::escape($documentOriginalName) ?>" title="<?= View::escape($documentOriginalName) ?>"><?= View::escape($documentOriginalName) ?></span></div>
                    <button type="button" class="student-document-remove-row grid h-11 w-11 place-items-center rounded-lg border border-rose-300/20 text-rose-200 transition hover:border-rose-300/60 hover:bg-rose-400/10" title="Remove document" aria-label="Remove document"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-.9 14H5.9L5 6m4 4v6m6-6v6"/></svg></button>
                </div>
                <?php $documentIndex++; endforeach; ?>
                <?php foreach ($removedDocumentIds as $removedId): ?><input type="hidden" name="document_remove_ids[]" value="<?= (int) $removedId ?>"><?php endforeach; ?>
                <?php $newDocumentIndex = $documentIndex; ?>
                <?php $wizardDocumentName = (string) ($wizardDocumentNames['document_file_' . $newDocumentIndex] ?? ''); ?>
                <?php $newDocumentTitle = (string) $fieldValue('document_title_' . $newDocumentIndex); ?>
                <?php $showInitialDocumentRow = !$editing || $newDocumentIndex === 0 || (int) $fieldValue('document_count', 0) > $newDocumentIndex || trim($newDocumentTitle) !== '' || $wizardDocumentName !== ''; ?>
                <?php if ($showInitialDocumentRow): ?>
                <div class="student-document-row grid gap-3 sm:grid-cols-[1fr_2fr_auto]" data-existing="0">
                    <input class="student-document-title w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" name="document_title_<?= (int) $newDocumentIndex ?>" placeholder="Title" value="<?= View::escape((string) $fieldValue('document_title_' . $newDocumentIndex)) ?>">
                    <div class="flex min-w-0 items-center rounded-lg border border-green-100/15 bg-emerald-950/45 px-2"><input type="file" accept="application/pdf,.pdf" class="student-document-file min-w-0 flex-1 basis-1/2 bg-transparent py-2 text-sm" name="document_file_<?= (int) $newDocumentIndex ?>"><span class="student-document-file-name min-w-0 flex-1 basis-1/2 truncate px-2 text-xs <?= $wizardDocumentName !== '' ? 'text-green-300' : 'text-slate-400' ?>" data-current-name="<?= View::escape($wizardDocumentName) ?>" title="<?= View::escape($wizardDocumentName) ?>"><?= View::escape($wizardDocumentName) ?></span></div>
                    <?php if ($newDocumentIndex > 0): ?><button type="button" class="student-document-remove-row grid h-11 w-11 place-items-center rounded-lg border border-rose-300/20 text-rose-200 transition hover:border-rose-300/60 hover:bg-rose-400/10" title="Remove document" aria-label="Remove document"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-.9 14H5.9L5 6m4 4v6m6-6v6"/></svg></button><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <button type="button" id="add-student-document" class="mt-4 rounded-lg border border-green-100/20 px-4 py-2 text-sm text-teal-200 hover:border-teal-300">+ Add another document</button>
            <input type="hidden" id="student-document-count" name="document_count" value="<?= (int) $newDocumentIndex + ($showInitialDocumentRow ? 1 : 0) ?>">
        </section>

        <div class="flex flex-wrap justify-between gap-3 border-t border-green-900/60 pt-5">
            <?php if (!$editing): ?><button type="button" id="student-wizard-back" class="hidden rounded-lg border border-green-100/20 px-5 py-2.5 text-sm font-semibold text-white hover:border-green-300">Back</button><span id="student-wizard-spacer" class="flex-1"></span><button type="button" id="student-wizard-next" class="rounded-lg bg-teal-400 px-6 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save and continue</button><?php endif; ?>
            <a href="<?= Url::to('/students') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a>
            <button id="student-wizard-submit" <?= !$year ? 'disabled' : '' ?> class="<?= !$editing ? 'hidden ' : '' ?>rounded-lg bg-teal-400 px-6 py-2.5 text-sm font-semibold text-slate-950 hover:bg-teal-300">Save</button>
        </div>
    </form>
</div>
<script>
(() => {
  const admissionForm = document.getElementById('student-registration-form');
  const nextStepButton = document.getElementById('student-wizard-next');
  const wizardBackButton = document.getElementById('student-wizard-back');
  const finishField = document.getElementById('student-wizard-finish');
  const stepIndicators = [...document.querySelectorAll('[data-step-indicator]')];
  const wizardSections = [...document.querySelectorAll('[data-wizard-step]')];
  if (admissionForm && nextStepButton && wizardSections.length) {
    const maxStep = 4;
    let step = <?= (int) ($wizardStep ?? 1) ?>;
    const showStep = value => {
      step = Math.max(1, Math.min(maxStep, value));
      wizardSections.forEach(section => { section.hidden = Number(section.dataset.wizardStep) !== step; });
      stepIndicators.forEach(indicator => {
        const active = Number(indicator.dataset.stepIndicator) === step;
        indicator.className = `student-wizard-indicator rounded-lg border px-3 py-2 text-center text-xs font-semibold ${active ? 'border-emerald-300 bg-emerald-400/15 text-emerald-100' : 'border-green-900/60 text-slate-400'}`;
      });
      wizardBackButton.classList.toggle('hidden', step === 1);
      nextStepButton.textContent = step === maxStep ? 'Save and finish admission' : 'Save and continue';
      document.getElementById('student-wizard-submit').classList.toggle('hidden', true);
    };
    const showStepError = (message, target = null, invalidFields = []) => {
      const section = wizardSections.find(item => Number(item.dataset.wizardStep) === step);
      if (!section) return;
      let feedback = section.querySelector('.student-wizard-error');
      if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'student-wizard-error mb-5 flex gap-3 rounded-xl border border-amber-300/25 bg-gradient-to-r from-amber-950/75 to-rose-950/60 p-4 text-sm text-amber-50 shadow-lg shadow-black/10';
        feedback.setAttribute('role', 'alert');
        section.insertBefore(feedback, section.firstChild);
      }
      feedback.replaceChildren();
      const icon = document.createElement('span');
      icon.className = 'grid h-9 w-9 shrink-0 place-items-center rounded-full border border-amber-200/20 bg-amber-300/10 font-bold text-amber-200';
      icon.setAttribute('aria-hidden', 'true');
      icon.textContent = '!';
      const content = document.createElement('div');
      content.className = 'min-w-0 flex-1';
      const fieldEntries = [];
      if (Array.isArray(message)) {
        const heading = document.createElement('div');
        heading.className = 'flex flex-wrap items-center gap-2';
        const title = document.createElement('p');
        title.className = 'student-wizard-error-title font-semibold text-amber-100';
        title.textContent = 'A few details need your attention';
        const count = document.createElement('span');
        count.className = 'student-wizard-error-count rounded-full border border-amber-200/15 bg-amber-200/10 px-2 py-0.5 text-xs font-medium text-amber-100/80';
        count.textContent = `${message.length} ${message.length === 1 ? 'field' : 'fields'}`;
        heading.append(title, count);
        const hint = document.createElement('p');
        hint.className = 'mt-0.5 text-xs text-amber-100/65';
        hint.textContent = 'Complete these before continuing.';
        const list = document.createElement('ul');
        list.className = 'mt-3 grid gap-2 sm:grid-cols-2';
        message.forEach((item, index) => {
          const entry = document.createElement('li');
          entry.className = 'flex items-start gap-2 rounded-lg border border-amber-100/10 bg-black/10 px-3 py-2 text-[13px] leading-5';
          const mark = document.createElement('span');
          mark.className = 'mt-0.5 text-amber-300';
          mark.setAttribute('aria-hidden', 'true');
          mark.textContent = '•';
          const text = document.createElement('span');
          text.textContent = item;
          entry.append(mark, text);
          list.append(entry);
          fieldEntries.push({ field: invalidFields[index], entry });
        });
        content.append(heading, hint, list);
      } else {
        const title = document.createElement('p');
        title.className = 'font-semibold text-amber-100';
        title.textContent = 'Could not continue';
        const detail = document.createElement('p');
        detail.className = 'mt-1 text-amber-100/80';
        detail.textContent = message;
        content.append(title, detail);
      }
      feedback.append(icon, content);
      feedback._fieldEntries = fieldEntries;
      invalidFields.forEach(field => field.setAttribute('aria-invalid', 'true'));
      if (target) {
        target.setAttribute('aria-invalid', 'true');
        target.focus({ preventScroll: true });
      }
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    const refreshStepError = () => {
      const section = wizardSections.find(item => Number(item.dataset.wizardStep) === step);
      const feedback = section?.querySelector('.student-wizard-error');
      if (!feedback) return;
      if (!Array.isArray(feedback._fieldEntries)) {
        feedback.remove();
        return;
      }
      feedback._fieldEntries = feedback._fieldEntries.filter(({ field, entry }) => {
        if (!field.isConnected || field.validity.valid) {
          field.removeAttribute('aria-invalid');
          entry.remove();
          return false;
        }
        return true;
      });
      const remaining = feedback._fieldEntries.length;
      if (!remaining) {
        feedback.remove();
        return;
      }
      const count = feedback.querySelector('.student-wizard-error-count');
      if (count) count.textContent = `${remaining} ${remaining === 1 ? 'field' : 'fields'}`;
      const title = feedback.querySelector('.student-wizard-error-title');
      if (title) title.textContent = remaining === 1 ? 'One detail needs your attention' : 'A few details need your attention';
    };
    const clearStepError = () => wizardSections
      .filter(section => Number(section.dataset.wizardStep) === step)
      .forEach(section => section.querySelector('.student-wizard-error')?.remove());
    const setSpecificFieldMessage = field => {
      if (/(phone|mobile)/i.test(field.name) && field.validity.patternMismatch) {
        field.setCustomValidity('Enter a valid Bangladeshi mobile number (01XXXXXXXXX or +8801XXXXXXXXX).');
        return;
      }
      if (!field.required || !field.validity.valueMissing) return;
      const label = field.labels?.[0];
      const labelText = label?.querySelector('span')?.textContent || label?.textContent || field.name;
      const fieldName = labelText.replace(/\([^)]*\)/g, '').replace(/\*/g, '').replace(/\s+/g, ' ').trim() || field.name.replace(/[_-]+/g, ' ');
      if (field instanceof HTMLSelectElement) field.setCustomValidity(`Choose an option for ${fieldName}.`);
      else if (field instanceof HTMLInputElement && field.type === 'file') field.setCustomValidity(`Choose a file for ${fieldName}.`);
      else field.setCustomValidity(`Enter ${fieldName}.`);
    };
    const saveStep = async () => {
      clearStepError();
      const activeSections = wizardSections.filter(section => Number(section.dataset.wizardStep) === step);
      const invalidFields = activeSections.flatMap(section => [...section.querySelectorAll(':invalid')]);
      if (invalidFields.length) {
        invalidFields.forEach(setSpecificFieldMessage);
        const messages = invalidFields.map(field => field.validationMessage || 'Please complete this required field.');
        showStepError(messages, invalidFields[0], invalidFields);
        return false;
      }
      nextStepButton.disabled = true;
      nextStepButton.textContent = 'Saving…';
      const formData = new FormData(admissionForm);
      formData.set('_wizard_step', String(step));
      try {
        const response = await fetch('<?= Url::to('/students/wizard-save') ?>', { method: 'POST', body: formData, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const responseText = await response.text();
        let result;
        try {
          result = JSON.parse(responseText);
        } catch {
          const serverMessage = responseText.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, ' ').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
          throw new Error(serverMessage || 'The server returned an invalid response (HTTP ' + response.status + ').');
        }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Could not save this step.');
        admissionForm.querySelectorAll('input[type="file"]').forEach(input => {
          const selectedFileName = input.files[0]?.name || '';
          if (input.files.length) input.value = '';
          const saved = result.savedFiles?.includes(input.name);
          if (input.classList.contains('student-document-file')) {
            const nameDisplay = input.parentElement.querySelector('.student-document-file-name');
            if (nameDisplay && saved && selectedFileName) {
              nameDisplay.dataset.currentName = selectedFileName;
              nameDisplay.textContent = selectedFileName;
              nameDisplay.title = selectedFileName;
              nameDisplay.classList.remove('text-slate-400');
              nameDisplay.classList.add('text-green-300');
            }
            return;
          }
          if (input.name === 'photo') {
            let status = input.parentElement.querySelector('.wizard-file-saved');
            if (saved && !status) {
              status = document.createElement('span');
              status.className = 'wizard-file-saved mt-1 block text-xs text-green-300';
              input.parentElement.append(status);
            }
            if (saved && status) status.textContent = 'Photo saved for this admission.';
            return;
          }
          let status = input.parentElement.querySelector('.wizard-file-saved');
          if (saved && !status) {
            status = document.createElement('span');
            status.className = 'wizard-file-saved mt-1 block text-xs text-green-300';
            status.textContent = 'File saved for this admission.';
            input.insertAdjacentElement('afterend', status);
          }
          if (!saved) status?.remove();
        });
        if (step < maxStep) {
          showStep(step + 1);
          window.scrollTo({ top: admissionForm.getBoundingClientRect().top + window.scrollY - 24, behavior: 'smooth' });
        } else {
          finishField.value = '1';
          HTMLFormElement.prototype.submit.call(admissionForm);
        }
        return true;
      } catch (error) {
        showStepError(error.message || 'Could not save this step. Please try again.');
        return false;
      } finally {
        nextStepButton.disabled = false;
        if (step < maxStep) nextStepButton.textContent = 'Save and continue';
      }
    };
    nextStepButton.addEventListener('click', saveStep);
    wizardBackButton.addEventListener('click', () => showStep(step - 1));
    admissionForm.addEventListener('input', event => {
      event.target.setCustomValidity?.('');
      if (event.target.matches('[aria-invalid="true"]') && event.target.validity.valid) event.target.removeAttribute('aria-invalid');
      refreshStepError();
    });
    admissionForm.addEventListener('change', event => {
      event.target.setCustomValidity?.('');
      if (event.target.matches('[aria-invalid="true"]') && event.target.validity.valid) event.target.removeAttribute('aria-invalid');
      refreshStepError();
    });
    showStep(step);
  }

  const classField = document.getElementById('student-class-field');
  const sectionField = document.getElementById('student-section-field');
  const rollField = document.getElementById('student-roll-field');
  const academicGroupWrap = document.getElementById('student-academic-group-wrap');
  const academicGroupField = document.getElementById('student-academic-group');
  const studentBehaviourField = document.getElementById('student-behaviour-field');
  const syncAcademicGroup = () => {
    if (!classField || !academicGroupWrap || !academicGroupField) return;
    const classOption = classField.selectedOptions[0];
    const className = (classOption?.dataset.className || classOption?.textContent || '').replace(/[^a-z0-9]/gi, '').toLowerCase().replace(/^class/, '');
    const namedLevels = { '1': 1, one: 1, '2': 2, two: 2, '3': 3, three: 3, '4': 4, four: 4, '5': 5, five: 5, '6': 6, six: 6, '7': 7, seven: 7, '8': 8, eight: 8, '9': 9, nine: 9, '10': 10, ten: 10 };
    const hasNamedLevel = Object.prototype.hasOwnProperty.call(namedLevels, className);
    const hasAcademicGroup = hasNamedLevel ? [9, 10].includes(namedLevels[className]) : ['9', '10'].includes(classOption?.dataset.level || '');
    academicGroupWrap.classList.toggle('hidden', !hasAcademicGroup);
    academicGroupField.required = hasAcademicGroup;
    studentBehaviourField?.classList.toggle('student-behaviour-class-nine', hasAcademicGroup);
    if (!hasAcademicGroup) academicGroupField.value = '';
  };
  if (classField && sectionField) {
    const syncSuggestedRoll = () => {
      const selected = sectionField.selectedOptions[0];
      const shiftValue = document.getElementById('student-shift-field')?.value || '';
      const suffix = shiftValue === 'Morning' ? 'Morning' : (shiftValue === 'Day' ? 'Day' : '');
      if (rollField?.dataset.autoRoll === '1' && selected) rollField.value = selected.dataset[`nextRoll${suffix}`] || '1';
    };
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
      syncAcademicGroup();
      syncSuggestedRoll();
    };
    classField.addEventListener('change', () => {
      const configuredShift = classField.selectedOptions[0]?.dataset.shift;
      if (['Morning', 'Day'].includes(configuredShift)) document.getElementById('student-shift-field').value = configuredShift;
      syncSections();
    });
    sectionField.addEventListener('change', syncSuggestedRoll);
    document.getElementById('student-shift-field')?.addEventListener('change', syncSuggestedRoll);
    syncSections();
  } else {
    syncAcademicGroup();
  }

  const createGuardianToggle = document.getElementById('create-guardian-toggle');
  const editNewGuardian = document.getElementById('edit-new-guardian');
  createGuardianToggle?.addEventListener('change', () => {
    editNewGuardian?.classList.toggle('hidden', !createGuardianToggle.checked);
  });

  const studentPhotoInput = document.getElementById('student-photo-input');
  const studentPhotoDeleteButton = document.getElementById('student-photo-delete');
  const studentPhotoRemoveField = document.getElementById('student-photo-remove');
  const studentPhotoPreview = document.getElementById('student-photo-preview');
  const studentPhotoPreviewFrame = studentPhotoPreview?.parentElement;
  const studentPhotoStatus = document.getElementById('student-photo-status');
  if (studentPhotoInput) studentPhotoInput.dataset.initialRequired = studentPhotoInput.required ? '1' : '0';
  let temporaryStudentPhotoUrl = '';
  const renderStudentPhotoState = () => {
    if (!studentPhotoInput || !studentPhotoPreview) return;
    const replacementSelected = studentPhotoInput.files.length > 0;
    const removalPending = Boolean(studentPhotoRemoveField?.checked);
    if (removalPending && !replacementSelected) {
      studentPhotoPreviewFrame?.classList.add('hidden');
      studentPhotoInput.required = true;
      if (studentPhotoStatus) studentPhotoStatus.textContent = 'Photo marked for deletion when saved. Upload a replacement photo before saving.';
      if (studentPhotoDeleteButton) {
        studentPhotoDeleteButton.title = 'Undo photo deletion';
        studentPhotoDeleteButton.setAttribute('aria-label', 'Undo photo deletion');
        studentPhotoDeleteButton.innerHTML = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.64-6.36L3 8m0-5v5h5"/><path d="M12 7v5l3 2"/></svg>';
      }
      return;
    }

    studentPhotoInput.required = studentPhotoInput.dataset.initialRequired === '1';
    if (replacementSelected) {
      if (temporaryStudentPhotoUrl) URL.revokeObjectURL(temporaryStudentPhotoUrl);
      temporaryStudentPhotoUrl = URL.createObjectURL(studentPhotoInput.files[0]);
      studentPhotoPreview.src = temporaryStudentPhotoUrl;
      studentPhotoPreviewFrame?.classList.remove('hidden');
      if (studentPhotoStatus) studentPhotoStatus.textContent = '';
    } else {
      const originalSrc = studentPhotoPreview.dataset.originalSrc || '';
      if (originalSrc) {
        studentPhotoPreview.src = originalSrc;
        studentPhotoPreviewFrame?.classList.remove('hidden');
      } else {
        studentPhotoPreview.removeAttribute('src');
        studentPhotoPreviewFrame?.classList.add('hidden');
      }
      if (studentPhotoStatus) studentPhotoStatus.textContent = '';
    }
    if (studentPhotoDeleteButton) {
      studentPhotoDeleteButton.title = 'Delete photo from server when saved';
      studentPhotoDeleteButton.setAttribute('aria-label', 'Delete student photo from server when saved');
      studentPhotoDeleteButton.innerHTML = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 2-.9 12H5.9L5 6m4 4v5m6-5v5"/></svg>';
    }
  };
  studentPhotoDeleteButton?.addEventListener('click', () => {
    if (!studentPhotoRemoveField) return;
    studentPhotoRemoveField.checked = !studentPhotoRemoveField.checked;
    renderStudentPhotoState();
  });
  studentPhotoInput?.addEventListener('change', () => {
    if (studentPhotoInput.files.length && studentPhotoRemoveField) studentPhotoRemoveField.checked = false;
    renderStudentPhotoState();
  });

  const documentRows = document.getElementById('student-document-rows');
  const addDocumentButton = document.getElementById('add-student-document');
  const documentCount = document.getElementById('student-document-count');
  const reindexDocuments = () => {
    const rows = [...documentRows.querySelectorAll('.student-document-row')].filter(row => !row.hidden);
    rows.forEach((row, index) => {
      row.querySelector('.student-document-title').name = `document_title_${index}`;
      const file = row.querySelector('.student-document-file');
      if (file) file.name = 'document_file_' + index;
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
    const removingSavedDocument = row.dataset.existing === '1';
    if (removingSavedDocument) {
      const idField = row.querySelector('.student-document-id');
      if (!idField) return;
      const removal = document.createElement('input');
      removal.type = 'hidden';
      removal.name = 'document_remove_ids[]';
      removal.value = idField.value;
      row.append(removal);
      row.querySelectorAll('.student-document-id, .student-document-title, .student-document-file').forEach(field => { field.disabled = true; });
      row.classList.add('hidden');
      row.hidden = true;
    } else if (documentRows.querySelectorAll('.student-document-row[data-existing="0"]').length > 1) {
      row.remove();
    }
    if (removingSavedDocument && ![...documentRows.querySelectorAll('.student-document-row[data-existing="0"]')].some(documentRow => !documentRow.hidden)) {
      addDocumentButton.click();
    }
    reindexDocuments();
  });

  documentRows.addEventListener('change', event => {
    const fileInput = event.target.closest('.student-document-file');
    if (!fileInput) return;
    const nameDisplay = fileInput.parentElement.querySelector('.student-document-file-name');
    if (!nameDisplay) return;
    const selectedName = fileInput.files[0]?.name || nameDisplay.dataset.currentName || '';
    nameDisplay.textContent = selectedName;
    nameDisplay.title = selectedName;
  });

  addDocumentButton?.addEventListener('click', () => {
    const index = [...documentRows.querySelectorAll('.student-document-row')].filter(row => !row.hidden).length;
    if (index >= 20) {
      addDocumentButton.disabled = true;
      return;
    }

    const row = document.createElement('div');
    row.className = 'student-document-row grid gap-3 sm:grid-cols-[1fr_2fr_auto]';
    row.dataset.existing = '0';

    const title = document.createElement('input');
    title.name = `document_title_${index}`;
    title.placeholder = 'Title';
    title.className = 'student-document-title w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm';

    const file = document.createElement('input');
    file.type = 'file';
    file.accept = 'application/pdf,.pdf';
    file.name = `document_file_${index}`;
    file.className = 'student-document-file min-w-0 flex-1 basis-1/2 bg-transparent py-2 text-sm';
    const fileName = document.createElement('span');
    fileName.className = 'student-document-file-name min-w-0 flex-1 basis-1/2 truncate px-2 text-xs text-slate-400';
    const fileWrap = document.createElement('div');
    fileWrap.className = 'flex min-w-0 items-center rounded-lg border border-green-100/15 bg-emerald-950/45 px-2';
    fileWrap.append(file, fileName);

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'student-document-remove-row grid h-11 w-11 place-items-center rounded-lg border border-rose-300/20 text-rose-200 transition hover:border-rose-300/60 hover:bg-rose-400/10';
    remove.title = 'Remove document';
    remove.setAttribute('aria-label', 'Remove document');
    remove.innerHTML = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m3 0-.9 14H5.9L5 6m4 4v6m6-6v6"/></svg>';
    row.append(title, fileWrap, remove);
    documentRows.append(row);
    reindexDocuments();
  });
  reindexDocuments();
})();

</script>
