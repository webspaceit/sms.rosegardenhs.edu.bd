<?php
use App\Core\Csrf;
use App\Core\Url;
use App\Core\View;

$paginationUrl = static fn (int $targetPage): string => Url::to('/students') . '?' . http_build_query([
    'q' => $search,
    'class_id' => $selectedClass,
    'group' => $selectedAcademicGroup,
    'status' => $selectedStatus,
    'year_id' => $selectedYear,
    'per_page' => $perPage,
    'page' => $targetPage,
]);
$firstStudent = $totalStudents === 0 ? 0 : (($page - 1) * $perPage + 1);
$lastStudent = min($page * $perPage, $totalStudents);
$showArchiveReasonColumn = $selectedStatus === 'inactive';
$columnCount = 12 + ($canDeleteStudents ? 1 : 0) + ($showAcademicGroupFilter ? 1 : 0) + ($showArchiveReasonColumn ? 1 : 0);
?>
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="<?= Url::to('/dashboard') ?>" class="text-sm text-green-300 hover:text-green-200">← Dashboard</a>
            <h1 class="mt-2 text-3xl font-semibold">Students</h1>
            <p class="mt-1 text-sm text-white">Admissions, class placement, and student status</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= Url::to('/students/export?' . http_build_query(['q' => $search, 'class_id' => $selectedClass, 'group' => $selectedAcademicGroup, 'status' => $selectedStatus, 'year_id' => $selectedYear])) ?>" class="form-action-button">Export CSV</a>
            <a href="<?= Url::to('/students/new') ?>" class="rounded-lg border border-green-100/20 px-4 py-2.5 text-sm text-green-100 hover:border-green-300">Full admission</a>
            <a href="<?= Url::to('/students/quick-admit') ?>" class="form-action-button">Quick admit</a>
        </div>
    </header>

    <form id="student-filters" method="get" action="<?= Url::to('/students') ?>" class="mt-7 grid min-w-0 gap-2 rounded-2xl border border-green-900/60 bg-[#0b3425]/80 p-3 sm:grid-cols-2 <?= $showAcademicGroupFilter ? 'xl:grid-cols-[minmax(140px,1.4fr)_minmax(105px,.95fr)_minmax(105px,.95fr)_minmax(105px,.95fr)_minmax(115px,1fr)_auto_auto]' : 'xl:grid-cols-[minmax(140px,1.4fr)_minmax(110px,1fr)_minmax(105px,.95fr)_minmax(115px,1fr)_auto_auto]' ?>">
        <label class="sr-only" for="student-search">Search students</label>
        <input id="student-search" name="q" value="<?= View::escape($search) ?>" placeholder="Search name, ID, or roll" class="h-11 min-w-0 rounded-lg border border-green-800/80 bg-[#062f2d] px-2.5 py-2 text-xs text-slate-100 shadow-inner shadow-black/10 transition placeholder:text-slate-500 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/15">
        <label class="sr-only" for="student-class">Class</label>
        <select id="student-class" name="class_id" class="h-11 min-w-0 rounded-lg border border-green-800/80 bg-[#062f2d] px-2 py-2 text-xs text-slate-100 shadow-inner shadow-black/10 transition hover:border-green-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/15">
            <option value="0">All classes</option>
            <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" data-numeric-level="<?= (int) $class['numeric_level'] ?>" <?= $selectedClass === (int) $class['id'] ? 'selected' : '' ?>><?= View::escape($class['name']) ?></option><?php endforeach; ?>
        </select>
        <label id="student-group-filter-wrap" class="<?= $showAcademicGroupFilter ? '' : 'hidden' ?> min-w-0"><span class="sr-only">Academic group</span><select id="student-group" name="group" class="h-11 w-full min-w-0 rounded-lg border border-green-800/80 bg-[#062f2d] px-2 py-2 text-xs text-slate-100 shadow-inner shadow-black/10 transition hover:border-green-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/15"><option value="">All groups</option><?php foreach ($academicGroups as $group): ?><option value="<?= View::escape($group) ?>" <?= $selectedAcademicGroup === $group ? 'selected' : '' ?>><?= View::escape($group) ?></option><?php endforeach; ?></select></label>
        <label class="sr-only" for="student-status">Status</label>
        <select id="student-status" name="status" class="h-11 min-w-0 rounded-lg border border-green-800/80 bg-[#062f2d] px-2 py-2 text-xs text-slate-100 shadow-inner shadow-black/10 transition hover:border-green-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/15">
            <option value="">All statuses</option>
            <?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'graduated' => 'Graduated'] as $value => $label): ?><option value="<?= $value ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
        </select>
        <label class="sr-only" for="student-year">Academic year</label>
        <select id="student-year" name="year_id" class="h-11 min-w-0 rounded-lg border border-green-800/80 bg-[#062f2d] px-2 py-2 text-xs text-slate-100 shadow-inner shadow-black/10 transition hover:border-green-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/15">
            <option value="0" <?= $selectedYear === 0 ? 'selected' : '' ?>>All academic years</option>
            <?php foreach ($academicYears as $academicYear): ?><option value="<?= (int) $academicYear['id'] ?>" <?= $selectedYear === (int) $academicYear['id'] ? 'selected' : '' ?>><?= View::escape($academicYear['name']) ?><?= $academicYear['is_current'] ? ' · Current' : '' ?></option><?php endforeach; ?>
        </select>
        <button class="h-11 rounded-lg border border-slate-700 px-4 text-sm font-medium hover:border-green-400">Filter</button>
        <a href="<?= Url::to('/students') ?>" class="inline-flex h-11 items-center justify-center rounded-lg px-4 text-center text-sm text-white hover:text-white">Clear</a>
    </form>

    <section class="mt-5 overflow-hidden rounded-2xl border border-green-900/60 bg-[#0b3425]/80">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-green-900/60 px-5 py-4">
            <div>
                <h2 class="font-semibold">Student register</h2>
                <p class="mt-1 text-xs text-slate-400"><?= $totalStudents ?> total records</p>
            </div>
            <?php if ($canDeleteStudents): ?>
                <form id="student-bulk-delete" method="post" action="<?= Url::to('/students/delete-selected') ?>" data-no-ajax data-confirm="Permanently delete the selected student records and their related attendance, fee, exam, guardian-link, and document records? This cannot be undone." class="flex flex-wrap items-center gap-3">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="q" value="<?= View::escape($search) ?>">
                    <input type="hidden" name="class_id" value="<?= $selectedClass ?>">
                    <input type="hidden" name="group" value="<?= View::escape($selectedAcademicGroup) ?>">
                    <input type="hidden" name="status" value="<?= View::escape($selectedStatus) ?>">
                    <input type="hidden" name="year_id" value="<?= $selectedYear ?>">
                    <input type="hidden" name="per_page" value="<?= $perPage ?>">
                    <input type="hidden" name="page" value="<?= $page ?>">
                    <span id="student-selection-count" class="text-sm text-slate-300" aria-live="polite">0 selected</span>
                    <button id="student-delete-selected" type="submit" disabled class="rounded-lg border border-red-300/30 px-4 py-2 text-sm font-semibold text-red-100 transition hover:border-red-300 hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-40">Delete selected</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="admit-list-table student-list-table w-full min-w-[1280px] text-left text-sm">
                <thead class="bg-green-950/40 text-sm uppercase tracking-wide text-slate-300">
                    <tr>
                        <?php if ($canDeleteStudents): ?><th class="w-12 px-3 py-3 text-center font-medium"><input type="checkbox" data-select-all-students aria-label="Select all students on this page" class="h-4 w-4 accent-emerald-400"></th><?php endif; ?>
                        <th class="w-12 px-2 py-3 font-medium">Sl.</th>
                        <th class="px-3 py-3 font-medium">Student ID</th>
                        <th class="px-3 py-3 font-medium">Name</th>
                        <th class="px-3 py-3 font-medium">Roll</th>
                        <th class="px-3 py-3 font-medium">Mobile No</th>
                        <th class="px-3 py-3 font-medium">Class</th>
                        <th class="px-3 py-3 font-medium">Section</th>
                        <?php if ($showAcademicGroupFilter): ?><th class="px-3 py-3 font-medium">Group</th><?php endif; ?>
                        <th class="px-3 py-3 font-medium">Shift</th>
                        <th class="px-3 py-3 font-medium">Session</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <?php if ($showArchiveReasonColumn): ?><th class="px-5 py-3 font-medium">Archive reason</th><?php endif; ?>
                        <th class="px-3 py-3 text-center font-medium">Image</th>
                        <th class="px-3 py-3 text-center font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-900/50">
                    <?php if ($students === []): ?>
                        <tr><td colspan="<?= $columnCount ?>" class="px-5 py-12 text-center"><p class="font-medium text-slate-300">No students match these filters</p><p class="mt-1 text-sm text-slate-500">Try changing the search or admit a student.</p></td></tr>
                    <?php else: foreach (array_values($students) as $index => $student): ?>
                        <?php $isArchived = $student['status'] === 'inactive' && !empty($student['archive_reason']); ?>
                        <tr class="hover:bg-slate-800/30"<?php if ((int) $student['class_level'] === 9 && !empty($student['academic_group'])): ?> data-academic-group="<?= View::escape($student['academic_group']) ?>"<?php endif; ?>>
                            <?php if ($canDeleteStudents): ?><td class="px-3 py-2 text-center"><input type="checkbox" form="student-bulk-delete" name="student_ids[]" value="<?= (int) $student['id'] ?>" data-student-select class="h-4 w-4 accent-emerald-400" aria-label="Select <?= View::escape($student['name']) ?>"></td><?php endif; ?>
                            <td class="w-12 px-2 py-2 text-white"><?= ($page - 1) * $perPage + $index + 1 ?></td>
                            <td class="px-3 py-2 text-white"><?= View::escape($student['student_id']) ?></td>
                            <td class="px-3 py-2"><p class="font-medium"><?= View::escape($student['name']) ?></p></td>
                            <td class="px-3 py-2 text-white"><?= (int) $student['roll_no'] ?></td>
                            <td class="px-3 py-2 text-white"><?= View::escape($student['mobile_number'] ?: '—') ?></td>
                            <td class="whitespace-nowrap px-3 py-2 text-slate-300"><?= View::escape($student['class_name']) ?></td>
                            <td class="px-3 py-2 text-slate-300"><?= View::escape($student['section_name']) ?></td>
                            <?php if ($showAcademicGroupFilter): ?><td class="px-3 py-2 text-slate-300"><?= View::escape($student['academic_group'] ?: '—') ?></td><?php endif; ?>
                            <td class="px-3 py-2 text-slate-300"><?= View::escape($student['shift'] ?: '—') ?></td>
                            <td class="px-3 py-2 text-white"><?= View::escape($student['year_name']) ?></td>
                            <td class="px-3 py-2"><span class="rounded-full px-2.5 py-1 text-xs <?= $student['status'] === 'active' ? 'bg-green-400/10 text-green-200' : 'bg-slate-700 text-slate-300' ?>"><?= View::escape(ucfirst($student['status'])) ?></span></td>
                            <?php if ($showArchiveReasonColumn): ?><td class="px-5 py-2 text-xs text-amber-100/90"><?php if ($isArchived): ?><span class="block max-w-64 whitespace-normal"><?= nl2br(View::escape($student['archive_reason'])) ?></span><?php else: ?><span class="text-slate-500">—</span><?php endif; ?></td><?php endif; ?>
                            <td class="px-3 py-2 text-center"><?php if (!empty($student['photo'])): ?><img src="<?= Url::to('/uploads/file?path=' . rawurlencode((string) $student['photo'])) ?>" alt="<?= View::escape($student['name']) ?>" loading="lazy" class="mx-auto h-9 w-9 rounded-md border border-green-100/20 object-cover"><?php else: ?><span class="text-slate-500">—</span><?php endif; ?></td>
                            <td class="px-3 py-2 text-center">
                                <details class="student-actions group inline-flex items-center justify-center" aria-label="Student actions">
                                    <summary class="grid h-9 w-9 place-items-center rounded-lg border border-green-100/20 text-green-100/75 transition hover:bg-green-100/10 hover:text-white" aria-label="Show student actions" title="Student actions"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"></circle><circle cx="12" cy="12" r="1.7"></circle><circle cx="19" cy="12" r="1.7"></circle></svg></summary>
                                    <div class="student-actions-controls hidden flex items-center gap-2">
                                        <a href="<?= Url::to('/students/view?id=' . (int) $student['id']) ?>" class="rounded-md border border-green-100/20 px-3 py-1.5 text-xs hover:border-green-300">View</a>
                                        <a href="<?= Url::to('/students/edit?id=' . (int) $student['id']) ?>" class="rounded-md border border-slate-700 px-3 py-1.5 text-xs hover:border-green-400">Edit</a>
                                        <?php if ($student['status'] === 'active'): ?><button type="submit" form="archive-student-<?= (int) $student['id'] ?>" formaction="<?= Url::to('/students/archive') ?>" formmethod="post" class="rounded-md border border-slate-700 px-3 py-1.5 text-xs text-red-200 hover:border-red-400">Archive</button><?php endif; ?>
                                    </div>
                                </details>
                                <?php if ($student['status'] === 'active'): ?><form id="archive-student-<?= (int) $student['id'] ?>" method="post" action="<?= Url::to('/students/archive') ?>" data-no-ajax data-confirm="Archive this student? The record will be marked inactive." data-confirm-reason="archive_reason" data-confirm-reason-label="Why is this student being archived?" data-confirm-reason-maxlength="500"><?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $student['id'] ?>"><input type="hidden" name="archive_reason" value=""></form><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-green-900/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <p class="text-sm text-white">Showing <?= $firstStudent ?>–<?= $lastStudent ?> of <?= $totalStudents ?> students</p>
                <label class="flex items-center gap-2 text-xs text-white" for="student-per-page">Per page
                    <select id="student-per-page" form="student-filters" name="per_page" onchange="this.form.requestSubmit()" class="rounded-lg border border-green-800/80 bg-[#062f2d] px-2.5 py-2 text-sm text-slate-100">
                        <?php foreach ([10, 25, 50, 100] as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <nav class="flex flex-wrap items-center gap-1.5" aria-label="Student pages">
                <?php if ($page > 1): ?><a href="<?= View::escape($paginationUrl($page - 1)) ?>" class="rounded-lg border border-green-800/80 px-3 py-2 text-sm text-green-100 transition hover:border-emerald-400 hover:bg-emerald-400/10">Previous</a><?php else: ?><span class="rounded-lg border border-green-900/50 px-3 py-2 text-sm text-white">Previous</span><?php endif; ?>
                <?php for ($number = max(1, $page - 2); $number <= min($totalPages, $page + 2); $number++): ?><?php if ($number === $page): ?><span class="grid h-9 min-w-9 place-items-center rounded-lg border border-emerald-400/60 bg-emerald-400/15 px-2 text-sm font-semibold text-emerald-100" aria-current="page"><?= $number ?></span><?php else: ?><a href="<?= View::escape($paginationUrl($number)) ?>" class="grid h-9 min-w-9 place-items-center rounded-lg border border-green-900/60 px-2 text-sm text-slate-300 transition hover:border-emerald-400 hover:bg-emerald-400/10"><?= $number ?></a><?php endif; ?><?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="<?= View::escape($paginationUrl($page + 1)) ?>" class="rounded-lg border border-green-800/80 px-3 py-2 text-sm text-green-100 transition hover:border-emerald-400 hover:bg-emerald-400/10">Next</a><?php else: ?><span class="rounded-lg border border-green-900/50 px-3 py-2 text-sm text-white">Next</span><?php endif; ?>
            </nav>
        </div>
    </section>

    <script>
    (() => {
        const table = document.querySelector('table.student-list-table');
        if (!table) return;

        const tooltip = document.createElement('div');
        tooltip.setAttribute('role', 'tooltip');
        tooltip.style.cssText = 'position:fixed;display:none;z-index:9999;pointer-events:none;min-width:150px;padding:10px 13px;border:1px solid rgba(110,231,183,.36);border-radius:12px;background:linear-gradient(145deg,#064e3b,#052e2b);color:#ecfdf5;box-shadow:0 12px 30px rgba(0,0,0,.34);font:500 13px/1.35 system-ui,sans-serif;';
        const caption = document.createElement('span');
        caption.textContent = 'CLASS 9 · ACADEMIC GROUP';
        caption.style.cssText = 'display:block;margin-bottom:4px;color:#6ee7b7;font-size:9px;font-weight:700;letter-spacing:.12em;';
        const value = document.createElement('strong');
        value.style.cssText = 'font-size:14px;font-weight:650;';
        tooltip.append(caption, value);
        document.body.appendChild(tooltip);

        let activeRow = null;
        const position = (event) => {
            const gap = 14;
            const left = Math.min(event.clientX + gap, window.innerWidth - tooltip.offsetWidth - 10);
            const top = Math.min(event.clientY + gap, window.innerHeight - tooltip.offsetHeight - 10);
            tooltip.style.left = Math.max(10, left) + 'px';
            tooltip.style.top = Math.max(10, top) + 'px';
        };

        table.addEventListener('pointerover', (event) => {
            if (!(event.target instanceof Element)) return;
            const row = event.target.closest('tr[data-academic-group]');
            if (!row) return;
            activeRow = row;
            value.textContent = row.dataset.academicGroup || '';
            tooltip.style.display = 'block';
            position(event);
        });
        table.addEventListener('pointermove', (event) => {
            if (activeRow && event.target instanceof Element && activeRow.contains(event.target)) position(event);
        });
        table.addEventListener('pointerout', (event) => {
            if (activeRow && !activeRow.contains(event.relatedTarget)) {
                activeRow = null;
                tooltip.style.display = 'none';
            }
        });
    })();
    </script>

    <script>
    (() => {
        const filterForm = document.getElementById('student-filters');
        const classSelect = document.getElementById('student-class');
        const groupWrap = document.getElementById('student-group-filter-wrap');
        const groupSelect = document.getElementById('student-group');
        if (!filterForm || !classSelect || !groupWrap || !groupSelect) return;

        const columnsWithoutGroup = 'minmax(140px,1.4fr) minmax(110px,1fr) minmax(105px,.95fr) minmax(115px,1fr) auto auto';
        const columnsWithGroup = 'minmax(140px,1.4fr) minmax(105px,.95fr) minmax(105px,.95fr) minmax(105px,.95fr) minmax(115px,1fr) auto auto';
        const syncGroupFilter = () => {
            const isClassNine = classSelect.selectedOptions[0]?.dataset.numericLevel === '9';
            groupWrap.classList.toggle('hidden', !isClassNine);
            if (!isClassNine) groupSelect.value = '';
            filterForm.style.gridTemplateColumns = window.matchMedia('(min-width: 1280px)').matches
                ? (isClassNine ? columnsWithGroup : columnsWithoutGroup)
                : '';
        };

        classSelect.addEventListener('change', syncGroupFilter);
        window.addEventListener('resize', syncGroupFilter);
        syncGroupFilter();
    })();
    </script>

    <?php if ($canDeleteStudents): ?>
        <script>
        (() => {
            const selectAll = document.querySelector('[data-select-all-students]');
            const checkboxes = Array.from(document.querySelectorAll('[data-student-select]'));
            const deleteButton = document.getElementById('student-delete-selected');
            const countLabel = document.getElementById('student-selection-count');
            if (!selectAll || !deleteButton || !countLabel) return;
            const updateSelection = () => {
                const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
                deleteButton.disabled = selected === 0;
                countLabel.textContent = selected + ' selected';
                selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
                selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
            };
            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
                updateSelection();
            });
            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
            updateSelection();
        })();
        </script>
    <?php endif; ?>
