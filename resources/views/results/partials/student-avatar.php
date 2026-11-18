<?php
$avatarGender = strtolower(trim((string) ($avatarGender ?? '')));
$avatarName = trim((string) ($avatarName ?? 'Student')) ?: 'Student';
$avatarClass = trim((string) ($avatarClass ?? ''));
$femaleAvatar = $avatarGender === 'female';
?>
<div class="admit-student-avatar <?= \App\Core\View::escape($avatarClass) ?>" role="img" aria-label="<?= \App\Core\View::escape(ucfirst($avatarGender ?: 'student') . ' avatar for ' . $avatarName) ?>">
    <svg viewBox="0 0 100 120" aria-hidden="true" focusable="false">
        <rect width="100" height="120" rx="5" fill="#eaf2f8"/>
        <?php if ($femaleAvatar): ?>
            <path d="M19 53c-5-25 7-42 31-43 25-1 36 17 31 44l-4 35H23z" fill="#4b352e"/>
            <path d="M26 91c2-17 12-25 24-25s22 8 24 25l12 29H14z" fill="#8b5cf6"/>
            <path d="M42 63h16v14H42z" fill="#d99a73"/>
            <ellipse cx="50" cy="41" rx="19" ry="23" fill="#f2c6a6"/>
            <path d="M31 38c1-18 10-27 22-27 14 0 23 11 20 27-5-5-8-13-9-17-7 7-19 11-33 10z" fill="#4b352e"/>
            <path d="M31 38c-3 13-2 27 2 36l-8-2c-4-18-2-35 6-44zm38-2c6 12 6 26 1 40l7-3c6-18 3-34-5-43z" fill="#4b352e"/>
        <?php else: ?>
            <path d="M24 95c2-17 12-27 26-27s24 10 26 27l10 25H14z" fill="#2563a6"/>
            <path d="M42 63h16v16H42z" fill="#d99a73"/>
            <ellipse cx="50" cy="42" rx="19" ry="23" fill="#f2c6a6"/>
            <path d="M31 38c0-17 8-27 20-27 13 0 20 9 19 25-5-2-9-7-11-12-7 7-17 11-28 12z" fill="#30343b"/>
        <?php endif; ?>
    </svg>
</div>
