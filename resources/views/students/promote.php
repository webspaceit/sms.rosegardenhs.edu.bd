<?php
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;
?>
<header class="flex flex-wrap items-end justify-between gap-4"><div><a href="<?= Url::to('/students') ?>" class="text-sm text-green-300 hover:text-green-200">← Students</a><h1 class="mt-2 text-3xl font-semibold">Promote students</h1><p class="mt-1 text-sm text-white">Move an active class cohort into the next academic year and preserve the previous enrollment.</p></div></header>
<section class="mt-7 max-w-4xl rounded-2xl border border-green-900/60 bg-gradient-to-br from-[#0b3425] to-[#071d19] p-6">
  <form method="post" action="<?= Url::to('/students/promote') ?>" data-confirm="Promote all active students in this source class? Their destination rolls will be assigned in order.">
    <?= Csrf::field() ?>
    <div class="grid gap-5 sm:grid-cols-2">
      <label class="text-sm text-slate-200">Current academic year<select required name="source_year_id" class="mt-2 w-full rounded-lg border border-green-800 bg-[#062f2d] px-3 py-2.5 text-slate-100"><option value="">Choose year</option><?php foreach ($years as $year): ?><option value="<?= (int) $year['id'] ?>" <?= (int) $year['is_current'] === 1 ? 'selected' : '' ?>><?= View::escape($year['name']) ?></option><?php endforeach; ?></select></label>
      <label class="text-sm text-slate-200">Source class<select required name="source_class_id" class="mt-2 w-full rounded-lg border border-green-800 bg-[#062f2d] px-3 py-2.5 text-slate-100"><option value="">Choose class</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"><?= View::escape($class['name']) ?></option><?php endforeach; ?></select></label>
      <label class="text-sm text-slate-200">New academic year<select required name="target_year_id" class="mt-2 w-full rounded-lg border border-green-800 bg-[#062f2d] px-3 py-2.5 text-slate-100"><option value="">Choose year</option><?php foreach ($years as $year): ?><option value="<?= (int) $year['id'] ?>"><?= View::escape($year['name']) ?></option><?php endforeach; ?></select></label>
      <label class="text-sm text-slate-200">Destination class<select required name="target_class_id" id="promotion-class" class="mt-2 w-full rounded-lg border border-green-800 bg-[#062f2d] px-3 py-2.5 text-slate-100"><option value="">Choose class</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>"><?= View::escape($class['name']) ?></option><?php endforeach; ?></select></label>
      <label class="text-sm text-slate-200 sm:col-span-2">Destination section<select required name="target_section_id" id="promotion-section" class="mt-2 w-full rounded-lg border border-green-800 bg-[#062f2d] px-3 py-2.5 text-slate-100"><option value="">Choose destination class first</option><?php foreach ($sections as $section): ?><option data-class="<?= (int) $section['class_id'] ?>" value="<?= (int) $section['id'] ?>"><?= View::escape($section['name']) ?></option><?php endforeach; ?></select></label>
    </div>
    <p class="mt-5 rounded-lg border border-amber-300/20 bg-amber-300/5 p-3 text-sm text-amber-100">All active students in the selected source class will be moved together. Roll numbers are assigned after the last roll already present in the destination section.</p>
    <div class="mt-5 flex justify-end"><button class="form-action-button">Promote class</button></div>
  </form>
</section>
<script>
(() => {
  const classSelect = document.getElementById('promotion-class');
  const sectionSelect = document.getElementById('promotion-section');
  if (!classSelect || !sectionSelect) return;
  const update = () => {
    const classId = classSelect.value;
    for (const option of sectionSelect.options) {
      if (!option.value) continue;
      option.hidden = option.dataset.class !== classId;
      if (option.hidden && option.selected) sectionSelect.value = '';
    }
  };
  classSelect.addEventListener('change', update);
  update();
})();
</script>
