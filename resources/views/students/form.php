<?php
use App\Core\Url;
use App\Core\View;

$editing = $student !== null;
$old = $_SESSION['_student_old'] ?? [];
$fieldValue = static fn (string $key, mixed $fallback = ''): mixed => $old[$key] ?? ($student[$key] ?? $fallback);
$selectedClassId = (int) $fieldValue('class_id', $classes[0]['id'] ?? 0);
?>
<div class="mx-auto max-w-4xl">
    <a href="<?= Url::to('/students') ?>" class="text-sm text-green-300 hover:text-green-200">← Student register</a>
    <h1 class="mt-2 text-3xl font-semibold"><?= $editing ? 'Edit student' : 'Admit a student' ?></h1>
    <p class="mt-1 text-sm text-white"><?= $editing ? 'Update the student and admission record.' : 'Enter student details, placement and a guardian contact.' ?></p>

    <form id="student-registration-form" method="post" enctype="multipart/form-data" action="<?= Url::to($editing ? '/students/update' : '/students') ?>" class="mt-7 space-y-5">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $student['id'] ?>"><?php endif; ?>
        <input type="hidden" name="academic_year_id" value="<?= (int) ($student['academic_year_id'] ?? ($year['id'] ?? 0)) ?>">

        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Student information</h2>
            <p class="mb-5 mt-1 text-sm text-slate-400">Basic identity and contact details.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block sm:col-span-2"><span class="mb-2 block text-sm font-medium">Student name <span class="text-red-300">*</span></span><input required maxlength="150" name="name" value="<?= View::escape((string) $fieldValue('name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Full name in English"></label>
                <label class="block sm:col-span-2"><span class="mb-2 block text-sm font-medium">Student name in Bangla</span><input maxlength="150" name="name_bn" value="<?= View::escape((string) $fieldValue('name_bn')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="বাংলায় নাম"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Date of birth</span><input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" value="<?= View::escape((string) $fieldValue('date_of_birth')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-500">Optional. Cannot be a future date.</span></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Birth registration number</span><input maxlength="50" name="birth_registration_no" value="<?= View::escape((string) $fieldValue('birth_registration_no')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="As shown on the certificate"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Gender</span><select name="gender" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><option value="">Not specified</option><?php foreach (['Male', 'Female', 'Other'] as $gender): ?><option value="<?= $gender ?>" <?= $fieldValue('gender') === $gender ? 'selected' : '' ?>><?= $gender ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Student photo</span><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php if (!empty($student['photo'])): ?><a class="mt-1 block text-xs text-green-300" target="_blank" href="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>">View current photo</a><?php endif; ?></label>
                <label class="block sm:col-span-2"><span class="mb-2 block text-sm font-medium">Address</span><textarea name="address" rows="3" maxlength="10000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Current residential address"><?= View::escape((string) $fieldValue('address')) ?></textarea></label>
            </div>
        </section>

        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Admission and placement</h2>
            <p class="mb-5 mt-1 text-sm text-slate-400">Choose the academic placement and record admission history.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block"><span class="mb-2 block text-sm font-medium">Class <span class="text-red-300">*</span></span><select required id="student-class-field" name="class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>><?= View::escape($class['name']) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Section <span class="text-red-300">*</span></span><select required id="student-section-field" name="section_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach ($sections as $section): ?><option data-class-id="<?= (int) $section['class_id'] ?>" value="<?= (int) $section['id'] ?>" <?= (int) $fieldValue('section_id', $student['section_id'] ?? 0) === (int) $section['id'] ? 'selected' : '' ?>><?= View::escape($section['class_name'] . ' · ' . $section['name']) ?></option><?php endforeach; ?></select><span class="mt-1 block text-xs text-slate-500">Sections are filtered by the selected class.</span></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Roll number <span class="text-red-300">*</span></span><input required min="1" max="65535" type="number" name="roll_no" value="<?= View::escape((string) $fieldValue('roll_no')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Admission date</span><input type="date" name="admission_date" value="<?= View::escape((string) $fieldValue('admission_date', $editing ? '' : date('Y-m-d'))) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Previous school</span><input maxlength="190" name="previous_school" value="<?= View::escape((string) $fieldValue('previous_school')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="If applicable"></label>
                <div><span class="mb-2 block text-sm font-medium">Academic year</span><div class="rounded-lg border px-3 py-2.5 text-sm" style="background-color:#062f2d;border-color:rgba(22,101,52,.8);color:#f1f5f9"><?= View::escape($year['name'] ?? 'Not configured') ?></div></div>
                <?php if ($editing): ?><label class="block"><span class="mb-2 block text-sm font-medium">Status</span><select name="status" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated'] as $value => $label): ?><option value="<?= $value ?>" <?= $student['status'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><?php else: ?><input type="hidden" name="status" value="active"><?php endif; ?>
            </div>
        </section>

        <?php if (!$editing): ?>
        <section class="rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
            <h2 class="text-lg font-semibold">Parent or guardian</h2>
            <p class="mb-5 mt-1 text-sm text-slate-400">Link an existing contact or create one while admitting this student.</p>
            <label class="flex flex-col gap-2 text-sm font-medium sm:flex-row sm:items-center"><span class="sm:mr-3">Guardian option</span><select id="guardian-mode" name="guardian_mode" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 sm:max-w-md"><option value="none" <?= $fieldValue('guardian_mode', 'new') === 'none' ? 'selected' : '' ?>>Add later</option><option value="existing" <?= $fieldValue('guardian_mode') === 'existing' ? 'selected' : '' ?> <?= $guardians === [] ? 'disabled' : '' ?>>Link existing guardian</option><option value="new" <?= $fieldValue('guardian_mode', 'new') === 'new' ? 'selected' : '' ?>>Add a new guardian</option></select></label>
            <div id="existing-guardian-fields" class="mt-4 <?= $fieldValue('guardian_mode') === 'existing' ? '' : 'hidden' ?>"><label class="block text-sm font-medium">Select guardian<select name="guardian_id" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5"><?php if ($guardians === []): ?><option value="">No guardians have been added yet</option><?php else: foreach ($guardians as $guardian): ?><option value="<?= (int) $guardian['id'] ?>" <?= (string) $fieldValue('guardian_id') === (string) $guardian['id'] ? 'selected' : '' ?>><?= View::escape($guardian['name'] . ' · ' . $guardian['relation'] . ' · ' . $guardian['phone']) ?></option><?php endforeach; endif; ?></select></label></div>
            <div id="new-guardian-fields" class="mt-4 grid gap-4 sm:grid-cols-2 <?= $fieldValue('guardian_mode', 'new') === 'new' ? '' : 'hidden' ?>">
                <label class="block"><span class="mb-2 block text-sm font-medium">Guardian name <span class="text-red-300">*</span></span><input maxlength="150" name="guardian_name" value="<?= View::escape((string) $fieldValue('guardian_name')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Relation</span><select name="guardian_relation" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"><?php foreach (['Father', 'Mother', 'Guardian', 'Grandparent', 'Sibling', 'Other'] as $relation): ?><option value="<?= $relation ?>" <?= $fieldValue('guardian_relation', 'Father') === $relation ? 'selected' : '' ?>><?= $relation ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Phone number <span class="text-red-300">*</span></span><input maxlength="20" name="guardian_phone" value="<?= View::escape((string) $fieldValue('guardian_phone')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm" placeholder="Primary contact"></label>
                <label class="block"><span class="mb-2 block text-sm font-medium">Email (optional)</span><input type="email" maxlength="190" name="guardian_email" value="<?= View::escape((string) $fieldValue('guardian_email')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
                <label class="block sm:col-span-2"><span class="mb-2 block text-sm font-medium">Guardian name in Bangla (optional)</span><input maxlength="150" name="guardian_name_bn" value="<?= View::escape((string) $fieldValue('guardian_name_bn')) ?>" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm"></label>
            </div>
        </section>
        <?php endif; ?>

        <div class="flex flex-wrap justify-end gap-3 border-t border-green-900/60 pt-5"><a href="<?= Url::to('/students') ?>" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:border-slate-500">Cancel</a><button <?= !$year ? 'disabled' : '' ?> class="form-action-button"><?= $editing ? 'Save changes' : 'Review admission' ?></button></div>
    </form>
</div>
<?php if (!$editing): ?>
<dialog id="student-admission-review" class="w-[min(92vw,38rem)] rounded-2xl border border-green-200/20 bg-[#073d30] p-0 text-slate-100 shadow-2xl backdrop:bg-black/70">
    <div class="border-b border-green-100/15 p-5"><h2 class="text-xl font-semibold">Review student admission</h2><p class="mt-1 text-sm text-slate-300">Check these details before saving the student record.</p></div>
    <div id="student-admission-summary" class="grid gap-3 p-5 sm:grid-cols-2"></div>
    <div class="flex justify-end gap-3 border-t border-green-100/15 p-5"><button type="button" id="edit-admission-details" class="rounded-lg border border-slate-400/30 px-4 py-2.5 text-sm">Go back</button><button type="button" id="confirm-admission" class="form-action-button">Confirm admission</button></div>
</dialog>
<?php endif; ?>
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
  const guardianMode = document.getElementById('guardian-mode');
  const existingFields = document.getElementById('existing-guardian-fields');
  const newFields = document.getElementById('new-guardian-fields');
  const syncGuardian = () => {
    if (!guardianMode) return;
    existingFields?.classList.toggle('hidden', guardianMode.value !== 'existing');
    newFields?.classList.toggle('hidden', guardianMode.value !== 'new');
    if (newFields) newFields.querySelectorAll('input').forEach(input => input.required = guardianMode.value === 'new' && ['guardian_name', 'guardian_phone'].includes(input.name));
  };
  guardianMode?.addEventListener('change', syncGuardian);
  syncGuardian();
  const registrationForm = document.getElementById('student-registration-form');
  const reviewDialog = document.getElementById('student-admission-review');
  if (registrationForm && reviewDialog) {
    const summary = document.getElementById('student-admission-summary');
    let confirmed = false;
    const value = name => registrationForm.elements.namedItem(name)?.value?.trim() || 'Not provided';
    const selectedLabel = name => {
      const select = registrationForm.elements.namedItem(name);
      return select?.selectedOptions?.[0]?.textContent?.trim() || 'Not selected';
    };
    const addSummary = (label, content) => {
      const item = document.createElement('div');
      item.className = 'rounded-xl border border-green-100/10 bg-emerald-950/20 p-3';
      const heading = document.createElement('p');
      heading.className = 'text-xs text-slate-400';
      heading.textContent = label;
      const text = document.createElement('p');
      text.className = 'mt-1 break-words text-sm font-medium';
      text.textContent = content;
      item.append(heading, text);
      summary.append(item);
    };
    registrationForm.addEventListener('submit', event => {
      if (confirmed) { confirmed = false; return; }
      event.preventDefault();
      summary.replaceChildren();
      addSummary('Student', value('name'));
      addSummary('Name in Bangla', value('name_bn'));
      addSummary('Class and section', selectedLabel('class_id') + ' · ' + selectedLabel('section_id'));
      addSummary('Roll number', value('roll_no'));
      addSummary('Gender', value('gender'));
      addSummary('Date of birth', value('date_of_birth'));
      addSummary('Birth registration number', value('birth_registration_no'));
      addSummary('Admission date', value('admission_date'));
      addSummary('Previous school', value('previous_school'));
      addSummary('Address', value('address'));
      const mode = value('guardian_mode');
      const guardian = mode === 'new' ? value('guardian_name') + ' · ' + value('guardian_relation') + ' · ' + value('guardian_phone') : mode === 'existing' ? selectedLabel('guardian_id') : 'Add later';
      addSummary('Guardian contact', guardian);
      reviewDialog.showModal();
    });
    document.getElementById('edit-admission-details')?.addEventListener('click', () => reviewDialog.close());
    document.getElementById('confirm-admission')?.addEventListener('click', () => {
      confirmed = true;
      reviewDialog.close();
      registrationForm.requestSubmit();
    });
  }
})();
</script>
