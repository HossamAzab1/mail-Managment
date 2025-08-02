<?php

require_once '../config/db.php';

// Get filter parameters
$mail_type = $_GET['mail_type'] ?? 'out';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$branch_id = $_GET['branch_id'] ?? '';
$sender_id = $_GET['sender_id'] ?? '';
$receiver_id = $_GET['receiver_id'] ?? '';

// Build base query
if ($mail_type === 'in') {
    $query = "SELECT mi.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
              FROM mail_in mi
              JOIN branches b ON mi.from_branch_id = b.id
              JOIN persons s ON mi.sender_id = s.id
              JOIN persons r ON mi.receiver_id = r.id
              WHERE 1";
} else {
    $query = "SELECT mo.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
              FROM mail_out mo
              JOIN branches b ON mo.to_branch_id = b.id
              JOIN persons s ON mo.sender_id = s.id
              JOIN persons r ON mo.receiver_id = r.id
              WHERE 1";
}

// Add filters
$params = [];
if ($from_date) {
    $query .= " AND mo.date >= :from_date";
    $params['from_date'] = $from_date;
}
if ($to_date) {
    $query .= " AND mo.date <= :to_date";
    $params['to_date'] = $to_date;
}
if ($branch_id) {
    $query .= " AND b.id = :branch_id";
    $params['branch_id'] = $branch_id;
}
if ($sender_id) {
    $query .= " AND s.id = :sender_id";
    $params['sender_id'] = $sender_id;
}
if ($receiver_id) {
    $query .= " AND r.id = :receiver_id";
    $params['receiver_id'] = $receiver_id;
}

// Prepare and execute
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>Raw Mail Report</title>
</head>
<body onload="window.print()">
          <div class="hidden print:block text-center mb-4">
            <h1 class="text-lg font-bold">تقرير البريد <?= $mail_type === 'in' ? 'الوارد' : 'الصادر' ?></h1>
            <p class="text-sm">
                من <?= $from_date ?: 'بداية السجلات' ?> إلى <?= $to_date ?: 'آخر تاريخ' ?>
            </p>
            <p class="text-sm">تاريخ الطباعة: <?= date('Y-m-d H:i') ?></p>
        </div>

  <table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
      <tr>
        <th>التاريخ</th>
        <th>الجهة</th>
        <th>الرقم</th>
        <th>الراسل</th>
        <th>المرسل اليه</th>
        <th>المستلم</th>
        <th>الوصف</th>
        <th>التوقيع</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $row): ?>
      <tr>
    <td><?= htmlspecialchars($row['date']) ?></td>
    <td><?= htmlspecialchars($row['branch_name']) ?></td>
    <td><?= htmlspecialchars($row['code']) ?></td>
    <td><?= htmlspecialchars($row['sender_name']) ?></td>
    <td><?= htmlspecialchars($row['receiver_name']) ?></td>
    <td><?= htmlspecialchars($row['handed_to']) ?></td>
    <td><?= htmlspecialchars($row['description']) ?></td>
  <td></td>

      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
