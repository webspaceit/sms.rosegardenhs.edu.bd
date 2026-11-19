<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BranchContext;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Env;
use App\Core\Guard;
use App\Core\PhoneNumber;
use App\Core\Request;
use App\Core\Toast;
use App\Core\Url;
use App\Core\View;

final class SmsController
{
    public function index(Request $request): void
    {
        Guard::requirePermission('settings.manage');
        $database = Database::read();
        $branch = BranchContext::id();
        $classes = $database->query('SELECT id, name FROM classes WHERE branch_id=' . $branch . ' ORDER BY numeric_level')->fetchAll();
        $gateway = $database->query('SELECT id, provider, api_key, sender_type, sender_id, api_base_url, is_active FROM sms_settings ORDER BY is_active DESC, id DESC LIMIT 1')->fetch() ?: null;
        View::render('sms/index', [
            'title' => 'SMS Center', 'user' => Auth::user(), 'gateway' => $gateway,
            'classes' => $classes, 'academicYear' => \App\Core\AcademicYearContext::selected(),
        ]);
    }

    public function previewRecipients(Request $request): void
    {
        Guard::requirePermission('settings.manage');
        try {
            $audience = (string) $request->input('audience', '');
            if (!in_array($audience, ['students', 'guardians', 'teachers'], true)) {
                $this->json(['numbers' => [], 'count' => 0]);
            }
            $numbers = $this->recipients($request, $audience);
            $this->json(['numbers' => $numbers, 'count' => count($numbers)]);
        } catch (\Throwable $error) {
            error_log((string) $error);
            $this->json(['error' => 'Could not load recipient mobile numbers.'], 500);
        }
    }

    public function send(Request $request): void
    {
        Guard::requirePermission('settings.manage');
        if (!Csrf::verify($request)) {
            Toast::flash('Your session expired. Please try again.', 'error');
            $this->redirect();
        }
        if ($request->input('confirm_cost') !== '1') {
            Toast::flash('Confirm that you have reviewed the recipients before sending.', 'error');
            $this->redirect();
        }
        $audience = (string) $request->input('audience', 'custom');
        $message = trim((string) $request->input('message', ''));
        if ($message === '' || mb_strlen($message) > 1000) {
            Toast::flash('Enter a message between 1 and 1,000 characters.', 'error');
            $this->redirect();
        }
        $numbers = $this->recipients($request, $audience);
        if ($numbers === []) {
            Toast::flash('No valid Bangladeshi mobile numbers were found for this recipient selection.', 'error');
            $this->redirect();
        }
        if (count($numbers) > 500) {
            Toast::flash('A single send is limited to 500 recipients. Narrow the class or send in smaller batches.', 'error');
            $this->redirect();
        }
        $result = $this->submit($numbers, $message);
        if ($result['ok']) {
            Toast::flash('SMS accepted by the gateway for ' . count($numbers) . ' recipient(s). Delivery depends on the mobile network.');
        } else {
            Toast::flash('SMS was not accepted: ' . $result['message'], 'error');
        }
        $this->redirect();
    }

    public function submitFromCenter(Request $request): void
    {
        if ($request->input('test_number') !== null) {
            $this->test($request);
            return;
        }

        $this->send($request);
    }

    public function test(Request $request): void
    {
        Guard::requirePermission('settings.manage');
        if (!Csrf::verify($request)) {
            Toast::flash('Your session expired. Please try again.', 'error');
            $this->redirect();
        }
        $number = $this->normalize((string) $request->input('test_number', ''));
        if ($number === null) {
            Toast::flash(PhoneNumber::ERROR, 'error');
            $this->redirect();
        }
        $result = $this->submit([$number], 'WSIT School SMS integration test.');
        Toast::flash($result['ok'] ? 'Test SMS accepted by the gateway. Delivery depends on the mobile network.' : 'Test SMS failed: ' . $result['message'], $result['ok'] ? 'success' : 'error');
        $this->redirect();
    }

    /** @return array<int, string> */
    private function recipients(Request $request, string $audience): array
    {
        $branch = BranchContext::id();
        $yearId = (int) (\App\Core\AcademicYearContext::selected()['id'] ?? 0);
        $classId = max(0, (int) $request->input('class_id', 0));
        $database = Database::read();
        if ($audience === 'students' || $audience === 'guardians') {
            if ($yearId < 1) return [];
            if ($audience === 'students') {
                $statement = $database->prepare('SELECT mobile_number AS phone FROM students WHERE branch_id=:branch AND academic_year_id=:year AND status=\'active\' AND mobile_number IS NOT NULL AND mobile_number<>\'\'' . ($classId ? ' AND class_id=:class' : ''));
            } else {
                $statement = $database->prepare('SELECT DISTINCT g.phone FROM student_guardian sg JOIN guardians g ON g.id=sg.guardian_id WHERE sg.branch_id=:link_branch AND g.branch_id=:guardian_branch AND EXISTS (SELECT 1 FROM students s WHERE s.id=sg.student_id AND s.branch_id=:student_branch AND s.academic_year_id=:year AND s.status=\'active\'' . ($classId ? ' AND s.class_id=:class' : '') . ')');
            }
            $bindings = ['branch' => $branch, 'year' => $yearId];
            if ($audience === 'guardians') {
                $bindings = ['link_branch' => $branch, 'guardian_branch' => $branch, 'student_branch' => $branch, 'year' => $yearId];
            }
            if ($classId) $bindings['class'] = $classId;
            $statement->execute($bindings);
            $raw = array_column($statement->fetchAll(), 'phone');
        } elseif ($audience === 'teachers') {
            $raw = array_column($database->query('SELECT phone FROM teachers WHERE branch_id=' . $branch . ' AND phone IS NOT NULL AND phone<>\'\'')->fetchAll(), 'phone');
        } elseif ($audience === 'custom') {
            $raw = preg_split('/[\\s,;]+/', trim((string) $request->input('numbers', '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        } else {
            return [];
        }
        $valid = [];
        foreach ($raw as $number) {
            $normalized = $this->normalize((string) $number);
            if ($normalized !== null) $valid[$normalized] = $normalized;
        }
        return array_values($valid);
    }

    private function normalize(string $number): ?string
    {
        $number = preg_replace('/[\\s().-]+/', '', trim($number)) ?? '';
        if (!PhoneNumber::isBangladeshiMobile($number)) return null;
        $digits = ltrim($number, '+');
        return str_starts_with($digits, '01') ? '88' . $digits : $digits;
    }

    /** @param array<int, string> $numbers @return array{ok:bool,message:string} */
    private function submit(array $numbers, string $message): array
    {
        $gateway = Database::connection()->query('SELECT provider, api_key, sender_id, api_base_url FROM sms_settings WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetch();
        if (!$gateway || trim((string) ($gateway['api_key'] ?? '')) === '' || trim((string) ($gateway['sender_id'] ?? '')) === '') {
            return ['ok' => false, 'message' => 'Configure and activate a gateway with an API key and sender ID in SMS Integration settings.'];
        }
        $provider = strtolower(trim((string) $gateway['provider']));
        $isMram = str_contains($provider, 'mram') || str_contains($provider, 'mram sms');
        $isBulkSmsBd = str_contains($provider, 'bulksmsbd');
        if (!$isMram && !$isBulkSmsBd) {
            return ['ok' => false, 'message' => 'The send adapter supports MRAM SMS and BulkSMSBD. Set the provider name accordingly.'];
        }
        $endpoint = trim((string) ($gateway['api_base_url'] ?? '')) ?: ($isMram ? 'https://sms.mram.com.bd/smsapi' : 'https://bulksmsbd.net/api/smsapi');
        $url = parse_url($endpoint);
        $allowedHosts = $isMram ? ['sms.mram.com.bd'] : ['bulksmsbd.net', 'www.bulksmsbd.net'];
        if (!is_array($url) || strtolower((string) ($url['scheme'] ?? '')) !== 'https' || !in_array(strtolower((string) ($url['host'] ?? '')), $allowedHosts, true) || isset($url['user']) || isset($url['pass'])) {
            return ['ok' => false, 'message' => $isMram ? 'Use the official HTTPS MRAM SMS endpoint (sms.mram.com.bd).' : 'Use the official HTTPS BulkSMSBD endpoint (bulksmsbd.net).'];
        }
        if (!function_exists('curl_init')) return ['ok' => false, 'message' => 'PHP cURL is required on the server to connect to the SMS gateway.'];
        $curl = curl_init($endpoint);
        $payload = $isMram ? [
            'api_key' => (string) $gateway['api_key'],
            'type' => preg_match('/[^\x00-\x7F]/', $message) === 1 ? 'unicode' : 'text',
            'contacts' => implode(',', $numbers),
            'senderid' => (string) $gateway['sender_id'],
            'msg' => $message,
            'label' => 'transactional',
        ] : [
            'api_key' => (string) $gateway['api_key'],
            'type' => 'text',
            'number' => implode(',', $numbers),
            'senderid' => (string) $gateway['sender_id'],
            'message' => $message,
        ];
        $caBundle = trim((string) Env::get('SMS_CA_BUNDLE', ''));
        if ($caBundle === '') {
            $caBundle = trim((string) (ini_get('curl.cainfo') ?: ini_get('openssl.cafile') ?: getenv('CURL_CA_BUNDLE') ?: getenv('SSL_CERT_FILE') ?: ''));
        }
        if ($caBundle !== '' && !is_file($caBundle)) {
            return ['ok' => false, 'message' => 'The configured SMS_CA_BUNDLE certificate file is unavailable.'];
        }
        $curlOptions = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ];
        if ($caBundle !== '') $curlOptions[CURLOPT_CAINFO] = $caBundle;
        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($response === false) return ['ok' => false, 'message' => $curlError !== '' ? $curlError : 'Could not connect to the SMS gateway.'];
        $responseText = trim((string) $response);
        $decoded = json_decode($responseText, true);
        $accepted = $httpCode >= 200 && $httpCode < 300 && ($isMram
            ? $responseText !== '' && preg_match('/^10(?:0[2-9]|1[0-9])$/', $responseText) !== 1
            : ((is_array($decoded) && (int) ($decoded['response_code'] ?? $decoded['code'] ?? 0) === 202)
                || preg_match('/(?:^|\\D)202(?:\\D|$)/', $responseText) === 1));
        if (!$accepted) {
            $safeResponse = $responseText !== '' ? str_replace((string) $gateway['api_key'], '[redacted]', mb_substr($responseText, 0, 220)) : 'HTTP ' . $httpCode;
            return ['ok' => false, 'message' => 'Gateway response ' . $safeResponse];
        }
        return ['ok' => true, 'message' => 'accepted'];
    }

    private function redirect(): never
    {
        header('Location: ' . Url::to('/sms-center'));
        exit;
    }

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
