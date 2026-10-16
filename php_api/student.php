<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/condb.php';

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
}

function readJsonBody(): ?array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : null;
}

function textLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    preg_match_all('/./us', $value, $matches);
    return count($matches[0]);
}

function validateStudentId($value)
{
    if (is_int($value)) {
        return $value > 0 ? $value : false;
    }
    if (!is_string($value) || !preg_match('/^[0-9]+$/', $value)) {
        return false;
    }

    $normalized = ltrim($value, '0');
    if ($normalized === '') {
        return false;
    }

    return filter_var($normalized, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

function validateStudentFields(array $data): ?array
{
    $firstName = trim((string) ($data['first_name'] ?? ''));
    $lastName = trim((string) ($data['last_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));

    if ($firstName === '' || $lastName === '') {
        return null;
    }
    if (textLength($firstName) > 50 || textLength($lastName) > 50 || textLength($phone) > 15 || textLength($email) > 100) {
        return null;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    return [
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':phone' => $phone === '' ? null : $phone,
        ':email' => $email === '' ? null : $email
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $conn->query(
            'SELECT student_id, first_name, last_name, phone, email
             FROM students
             ORDER BY student_id DESC'
        );
        respond(200, [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;
    }

    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        header('Allow: GET, POST, PUT, DELETE, OPTIONS');
        respond(405, ['success' => false, 'message' => 'ไม่รองรับคำขอนี้']);
        exit;
    }

    $data = readJsonBody();
    if ($data === null) {
        respond(400, ['success' => false, 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง']);
        exit;
    }

    if ($method === 'POST') {
        $values = validateStudentFields($data);
        if ($values === null) {
            respond(400, ['success' => false, 'message' => 'กรุณากรอกชื่อ นามสกุล และตรวจสอบอีเมล']);
            exit;
        }

        $stmt = $conn->prepare(
            'INSERT INTO students (first_name, last_name, phone, email)
             VALUES (:first_name, :last_name, :phone, :email)'
        );
        $stmt->execute($values);
        respond(201, ['success' => true, 'message' => 'เพิ่มข้อมูลนักศึกษาเรียบร้อย']);
        exit;
    }

    $id = validateStudentId($data['student_id'] ?? null);
    if ($id === false || $id === null) {
        respond(400, ['success' => false, 'message' => 'ไม่พบรหัสนักศึกษา']);
        exit;
    }

    if ($method === 'PUT') {
        $values = validateStudentFields($data);
        if ($values === null) {
            respond(400, ['success' => false, 'message' => 'กรุณากรอกชื่อ นามสกุล และตรวจสอบอีเมล']);
            exit;
        }

        $values[':student_id'] = $id;
        $stmt = $conn->prepare(
            'UPDATE students
             SET first_name = :first_name, last_name = :last_name, phone = :phone, email = :email
             WHERE student_id = :student_id'
        );
        $stmt->execute($values);
        if ($stmt->rowCount() === 0) {
            $check = $conn->prepare('SELECT 1 FROM students WHERE student_id = :student_id');
            $check->execute([':student_id' => $id]);
            if (!$check->fetchColumn()) {
                respond(404, ['success' => false, 'message' => 'ไม่พบข้อมูลนักศึกษาที่ต้องการแก้ไข']);
                exit;
            }
        }

        respond(200, ['success' => true, 'message' => 'แก้ไขข้อมูลนักศึกษาเรียบร้อย']);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM students WHERE student_id = :student_id');
    $stmt->execute([':student_id' => $id]);
    if ($stmt->rowCount() === 0) {
        respond(404, ['success' => false, 'message' => 'ไม่พบข้อมูล']);
        exit;
    }

    respond(200, ['success' => true, 'message' => 'ลบข้อมูลนักศึกษาเรียบร้อย']);
} catch (PDOException $e) {
    respond(500, ['success' => false, 'message' => 'เกิดข้อผิดพลาด']);
}
?>
