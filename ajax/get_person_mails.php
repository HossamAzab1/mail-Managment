<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['person_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing person_id']);
    exit;
}

$person_id = $_GET['person_id'];

try {
    // Fetch incoming mail
    $stmtIn = $pdo->prepare("
        SELECT mi.date, b.name as branch_name, mi.code, mi.description, 'in' as mail_type
        FROM mail_in mi
        JOIN branches b ON mi.from_branch_id = b.id
        WHERE mi.receiver_id = ?
        ORDER BY mi.date DESC
        LIMIT 100
    ");
    $stmtIn->execute([$person_id]);
    $mailIn = $stmtIn->fetchAll(PDO::FETCH_ASSOC);

    // Fetch outgoing mail
    $stmtOut = $pdo->prepare("
        SELECT mo.date, b.name as branch_name, mo.code, mo.description, 'out' as mail_type
        FROM mail_out mo
        JOIN branches b ON mo.to_branch_id = b.id
        WHERE mo.sender_id = ?
        ORDER BY mo.date DESC
        LIMIT 100
    ");
    $stmtOut->execute([$person_id]);
    $mailOut = $stmtOut->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'mail_in' => $mailIn,
        'mail_out' => $mailOut
    ]);
} catch (PDOException $e) {
    error_log('Mail fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'mail_in' => [],
        'mail_out' => [],
        'error' => 'Database error.'
    ]);
}
