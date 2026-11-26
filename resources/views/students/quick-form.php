<?php
/** @var array<int, array<string, mixed>> $classes */
/** @var array<int, array<string, mixed>> $sections */
/** @var array<string, mixed> $year */
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$old = $_SESSION['_student_old'] ?? [];
$selectedClassId = (int) ($old['class_id'] ?? ($classes[0]['id'] ?? 0));
$selectedClassLevel = 0;
$selectedClassHasAcademicGroup = false;
$selectedClassShift = 'Day';
foreach ($classes as $classOption) {
    if ((int) $classOption['id'] === $selectedClassId) {
        $selectedClassLevel = (int) $classOption['numeric_level'];
        $normalizedClassName = strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string) $classOption['name']) ?? '');
        $normalizedClassName = preg_replace('/^CLASS/', '', $normalizedClassName) ?? $normalizedClassName;
        $knownClassNames = ['1', 'ONE', '2', 'TWO', '3', 'THREE', '4', 'FOUR', '5', 'FIVE', '6', 'SIX', '7', 'SEVEN', '8', 'EIGHT', '9', 'NINE', '10', 'TEN'];
        $selectedClassHasAcademicGroup = in_array($normalizedClassName, $knownClassNames, true)
            ? in_array($normalizedClassName, ['9', 'NINE', '10', 'TEN'], true)
            : in_array($selectedClassLevel, [9, 10], true);
        $selectedClassShift = in_array($classOption['shift'] ?? '', ['Morning', 'Day'], true) ? (string) $classOption['shift'] : 'Day';
        break;
    }
}
$selectedShift = (string) ($old['shift'] ?? $selectedClassShift);
$classSections = array_values(array_filter($sections, static fn (array $section): bool => (int) $section['class_id'] === $selectedClassId));
$selectedSectionId = (int) ($old['section_id'] ?? ($classSections[0]['id'] ?? 0));
$selectedSection = null;
foreach ($classSections as $section) {
    if ((int) $section['id'] === $selectedSectionId) { $selectedSection = $section; break; }
}
$suggestedRoll = $selectedSection === null ? 1 : (int) ($selectedSection['next_roll_' . strtolower($selectedShift)] ?? $selectedSection['next_roll'] ?? 1);
?>
<div class="w-full min-w-0">
    <a href="<?= Url::to('/students') ?>" class="text-sm text-green-300 hover:text-green-200">← Student register</a>
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold">Quick student admission</h1>
            <p class="mt-1 text-sm text-white">Register the essentials now. Complete the full profile later.</p>
        </div>
        <a href="<?= Url::to('/students/new') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-100 hover:border-green-300">Full admission form</a>
    </div>

    <form id="quick-admission-form" method="post" enctype="multipart/form-data" action="<?= Url::to('/students/quick-admit') ?>" class="mt-6 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-5 sm:p-7">
        <?= Csrf::field() ?>
        <input type="hidden" name="_quick_admission" value="1">
        <input type="hidden" name="academic_year_id" value="<?= (int) $year['id'] ?>">
        <input type="hidden" id="guardian-mode" name="guardian_mode" value="none">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-green-800/70 bg-[#062f2d] px-4 py-3">
            <span class="text-sm text-slate-300">Academic year <strong class="ml-2 text-green-100"><?= View::escape($year['name']) ?></strong></span>
            <span class="text-sm text-slate-300">Student ID <strong class="ml-2 text-green-100"><?= View::escape($nextStudentId) ?></strong></span>
        </div>

        <section>
            <h2 class="text-lg font-semibold">Student and class</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Student name <span class="text-rose-300">*</span></span>
                    <input autofocus required maxlength="150" name="name" value="<?= View::escape((string) ($old['name'] ?? '')) ?>" placeholder="Enter student's full name" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Class <span class="text-rose-300">*</span></span>
                    <select id="quick-class" required name="class_id" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>" data-level="<?= (int) $class['numeric_level'] ?>" data-class-name="<?= View::escape($class['name']) ?>" data-shift="<?= View::escape((string) ($class['shift'] ?? 'Day')) ?>" <?= $selectedClassId === (int) $class['id'] ? 'selected' : '' ?>><?= View::escape($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label id="quick-academic-group-wrap" class="block <?= $selectedClassHasAcademicGroup ? '' : 'hidden' ?>">
                    <span class="mb-1.5 block text-sm text-slate-300">Academic group <span class="text-rose-300">*</span></span>
                    <select id="quick-academic-group" name="academic_group" <?= $selectedClassHasAcademicGroup ? 'required' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                        <option value="">Select group</option>
                        <?php foreach ($academicGroups as $group): ?><option value="<?= View::escape($group) ?>" <?= ($old['academic_group'] ?? '') === $group ? 'selected' : '' ?>><?= View::escape($group) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Section <span class="text-rose-300">*</span></span>
                    <select id="quick-section" required name="section_id" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= (int) $section['id'] ?>" data-class-id="<?= (int) $section['class_id'] ?>" data-next-roll="<?= (int) $section['next_roll'] ?>" data-next-roll-morning="<?= (int) $section['next_roll_morning'] ?>" data-next-roll-day="<?= (int) $section['next_roll_day'] ?>" <?= $selectedSectionId === (int) $section['id'] ? 'selected' : '' ?>><?= View::escape($section['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Shift</span>
                    <select id="quick-shift" name="shift" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                        <?php foreach (['Morning', 'Day'] as $shift): ?><option value="<?= $shift ?>" <?= $selectedShift === $shift ? 'selected' : '' ?>><?= $shift ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Roll number <span class="text-rose-300">*</span></span>
                    <input id="quick-roll" required type="number" min="1" max="65535" name="roll_no" value="<?= (int) ($old['roll_no'] ?? $suggestedRoll) ?>"<?= isset($old['roll_no']) ? ' data-edited="1"' : '' ?> class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                    <span class="mt-1 block text-xs text-slate-500">Suggested from the next available roll for this class, section, and shift.</span>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Gender</span>
                    <select name="gender" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                        <option value="">Select Gender</option>
                        <?php foreach (['Male', 'Female', 'Other'] as $gender): ?>
                            <option value="<?= $gender ?>" <?= ($old['gender'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Date of birth</span>
                    <input type="date" name="date_of_birth" max="<?= date('Y-m-d') ?>" value="<?= View::escape((string) ($old['date_of_birth'] ?? '')) ?>" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Birth registration number</span>
                    <input maxlength="50" name="birth_registration_no" value="<?= View::escape((string) ($old['birth_registration_no'] ?? '')) ?>" placeholder="Birth registration number" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                </label>
                <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2 xl:col-span-4 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-1.5 block text-sm text-slate-300">Admission date</span>
                        <input type="date" name="admission_date" value="<?= View::escape((string) ($old['admission_date'] ?? date('Y-m-d'))) ?>" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                    </label>
                    <label class="block sm:col-span-2 xl:col-span-2">
                        <span class="mb-1.5 block text-sm text-slate-300">Current Address</span>
                        <textarea name="current_address" rows="2" placeholder="Current address" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400"><?= View::escape((string) ($old['current_address'] ?? '')) ?></textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-sm text-slate-300">Status</span>
                        <select name="status" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                            <?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated'] as $statusValue => $statusLabel): ?>
                                <option value="<?= $statusValue ?>" <?= ($old['status'] ?? 'active') === $statusValue ? 'selected' : '' ?>><?= $statusLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <section class="mt-7 border-t border-green-900/60 pt-6">
            <h2 class="text-lg font-semibold">Parent or guardian <span class="text-sm font-normal text-slate-400">(optional)</span></h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Guardian name <span class="text-rose-300">*</span></span>
                    <input id="quick-guardian-name" required maxlength="150" name="guardian_name" value="<?= View::escape((string) ($old['guardian_name'] ?? '')) ?>" placeholder="Parent or guardian" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Relation</span>
                    <select name="guardian_relation" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                        <?php foreach (['Guardian', 'Father', 'Mother', 'Grandparent', 'Sibling', 'Other'] as $relation): ?>
                            <option value="<?= $relation ?>" <?= ($old['guardian_relation'] ?? 'Guardian') === $relation ? 'selected' : '' ?>><?= $relation ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm text-slate-300">Guardian phone <span class="text-rose-300">*</span></span>
                    <input id="quick-guardian-phone" required type="tel" inputmode="tel" maxlength="14" pattern="(?:\+?88)?01[3-9][0-9]{8}" title="Enter a valid Bangladesh mobile number, e.g. 01712345678 or +8801712345678." name="guardian_phone" value="<?= View::escape((string) ($old['guardian_phone'] ?? '')) ?>" placeholder="01XXXXXXXXX or +8801XXXXXXXXX" class="w-full rounded-lg border border-green-800/80 bg-[#062f2d] px-3 py-2.5 text-sm outline-none focus:border-emerald-400">
                </label>
            </div>
            <p class="mt-2 text-xs text-slate-500">A guardian name and a valid Bangladesh mobile number are required to link the guardian to this student.</p>
        </section>

        <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-green-900/60 pt-5">
            <a href="<?= Url::to('/students') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-100 hover:border-green-300">Cancel</a>
            <button class="form-action-button">Admit student</button>
        </div>
    </form>
</div>
<script>
(() => {
  const form = document.getElementById('quick-admission-form');
  const classField = document.getElementById('quick-class');
  const sectionField = document.getElementById('quick-section');
  const shiftField = document.getElementById('quick-shift');
  const academicGroupWrap = document.getElementById('quick-academic-group-wrap');
  const academicGroupField = document.getElementById('quick-academic-group');
  const rollField = document.getElementById('quick-roll');
  const guardianName = document.getElementById('quick-guardian-name');
  const guardianPhone = document.getElementById('quick-guardian-phone');
  const guardianMode = document.getElementById('guardian-mode');
  if (!form || !classField || !sectionField || !shiftField || !rollField) return;
  const syncAcademicGroup = () => {
    const classOption = classField.selectedOptions[0];
    const className = (classOption?.dataset.className || classOption?.textContent || '').replace(/[^a-z0-9]/gi, '').toLowerCase().replace(/^class/, '');
    const namedLevels = { '1': 1, one: 1, '2': 2, two: 2, '3': 3, three: 3, '4': 4, four: 4, '5': 5, five: 5, '6': 6, six: 6, '7': 7, seven: 7, '8': 8, eight: 8, '9': 9, nine: 9, '10': 10, ten: 10 };
    const hasNamedLevel = Object.prototype.hasOwnProperty.call(namedLevels, className);
    const hasAcademicGroup = hasNamedLevel ? [9, 10].includes(namedLevels[className]) : ['9', '10'].includes(classOption?.dataset.level || '');
    if (academicGroupWrap) academicGroupWrap.classList.toggle('hidden', !hasAcademicGroup);
    if (academicGroupField) {
      academicGroupField.required = hasAcademicGroup;
      if (!hasAcademicGroup) academicGroupField.value = '';
    }
  };
  const syncSuggestedRoll = () => {
    const selected = sectionField.selectedOptions[0];
    const key = shiftField.value.toLowerCase();
    if (selected) rollField.value = selected.dataset[`nextRoll${key.charAt(0).toUpperCase()}${key.slice(1)}`] || selected.dataset.nextRoll || '1';
  };
  const syncSections = (updateRoll = false) => {
    let first = null;
    for (const option of sectionField.options) {
      const visible = option.dataset.classId === classField.value;
      option.hidden = !visible;
      if (visible && !first) first = option;
    }
    if (!sectionField.selectedOptions[0] || sectionField.selectedOptions[0].dataset.classId !== classField.value) {
      if (first) sectionField.value = first.value;
    }
    const selected = sectionField.selectedOptions[0];
    if (selected && updateRoll) syncSuggestedRoll();
    syncAcademicGroup();
  };
  classField.addEventListener('change', () => {
    const configuredShift = classField.selectedOptions[0]?.dataset.shift;
    shiftField.value = ['Morning', 'Day'].includes(configuredShift) ? configuredShift : 'Day';
    syncSections(true);
  });
  sectionField.addEventListener('change', () => syncSections(true));
  shiftField.addEventListener('change', syncSuggestedRoll);
  form.addEventListener('submit', () => {
    guardianMode.value = guardianName.value.trim() || guardianPhone.value.trim() ? 'new' : 'none';
  });
  syncSections();
})();
</script>
