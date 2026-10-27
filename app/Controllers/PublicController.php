<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\BranchContext;
use App\Core\Request;
use App\Core\View;

/**
 * The public facing website.
 *
 * Anyone may look at this page, so it never touches the signed in user or
 * any of the administration data.
 */
final class PublicController
{
    public function home(Request $request): void
    {
        $db = Database::connection();
        $branch = BranchContext::id();

        $settings = [];
        foreach ($db->query('SELECT `key`, value FROM school_settings WHERE branch_id=' . $branch)->fetchAll() as $row) {
            $settings[(string) $row['key']] = (string) $row['value'];
        }

        $notices = $db->query(
            'SELECT title, content, type, published_date FROM notices WHERE branch_id=' . $branch . ' AND is_published = 1'
            . ' AND (expires_date IS NULL OR expires_date >= CURDATE())'
            . ' ORDER BY published_date DESC LIMIT 6'
        )->fetchAll();

        $events = $db->query(
            'SELECT title, description, start_date, end_date, color FROM events WHERE branch_id=' . $branch . ' AND start_date >= CURDATE()'
            . ' ORDER BY start_date ASC LIMIT 6'
        )->fetchAll();

        $holidays = $db->query('SELECT title, date FROM holidays WHERE branch_id=' . $branch . ' AND date >= CURDATE() ORDER BY date ASC LIMIT 6')->fetchAll();

        $counts = [
            'students' => (int) $db->query('SELECT COUNT(*) FROM students WHERE branch_id=' . $branch . ' AND status = "active"')->fetchColumn(),
            'teachers' => (int) $db->query('SELECT COUNT(*) FROM teachers WHERE branch_id=' . $branch)->fetchColumn(),
            'classes' => (int) $db->query('SELECT COUNT(*) FROM classes WHERE branch_id=' . $branch)->fetchColumn(),
        ];

        View::render('public/home', [
            'title' => $settings['school_name'] ?? 'Welcome',
            'publicPage' => true,
            'settings' => $settings,
            'notices' => $notices,
            'events' => $events,
            'holidays' => $holidays,
            'counts' => $counts,
        ]);
    }
}
