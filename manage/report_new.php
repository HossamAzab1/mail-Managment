<?php
require_once '../config/db.php';

// Get all filter parameters
$mail_type = $_GET['mail_type'] ?? 'out';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$branch_id = $_GET['branch_id'] ?? '';
$sender_id = $_GET['sender_id'] ?? '';
$receiver_id = $_GET['receiver_id'] ?? '';

// Build the query
if ($mail_type === 'in') {
    $query = "SELECT mi.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
              FROM mail_in mi
              JOIN branches b ON mi.from_branch_id = b.id
              JOIN persons s ON mi.sender_id = s.id
              JOIN persons r ON mi.receiver_id = r.id
              WHERE 1=1";
} else {
    $query = "SELECT mo.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
              FROM mail_out mo
              JOIN branches b ON mo.to_branch_id = b.id
              JOIN persons s ON mo.sender_id = s.id
              JOIN persons r ON mo.receiver_id = r.id
              WHERE 1=1";
}

// Add conditions based on filters
$conditions = [];
$params = [];

if (!empty($from_date)) {
    $query .= " AND date >= ?";
    $params[] = $from_date;
}

if (!empty($to_date)) {
    $query .= " AND date <= ?";
    $params[] = $to_date;
}

if (!empty($branch_id)) {
    if ($mail_type === 'in') {
        $query .= " AND from_branch_id = ?";
    } else {
        $query .= " AND to_branch_id = ?";
    }
    $params[] = $branch_id;
}

if (!empty($sender_id)) {
    $query .= " AND sender_id = ?";
    $params[] = $sender_id;
}

if (!empty($receiver_id)) {
    $query .= " AND receiver_id = ?";
    $params[] = $receiver_id;
}

$query .= " ORDER BY date DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تقرير البريد</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 1cm;
    }
    body {
      font-family: 'Arial', sans-serif;
      margin: 0;
      padding: 20px;
      background: white;
      color: black;
      display: flex;
      justify-content: center;
    }
    .container {
      max-width: 100%;
      width: 100%;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      font-size: 12px;
      margin: 0 auto
    }
    th, td {
      border: 1px solid #000;
      padding: 4px;
      text-align: right;
    }
    th {
      background-color: #f2f2f2;
    }
    h1, h2, p {
      text-align: center;
      margin: 5px 0;
    }
    h1 {
      font-size: 18px;
      margin-bottom: 10px;
    }
    h2 {
      font-size: 16px;
    }
    p {
      font-size: 14px;
    }
    .print-button {
      display: none;
    }
    @media print {
      body {
        padding: 0;
        display: flex;
        justify-content: center;
      }
      .container {
        width: 100%;
        margin: 0 auto;
      }
      .print-button {
        display: none !important;
      }
    }
  </style>

</head>
<body onload="window.print()">
  <div class="container">
  <h1>تقرير البريد <?= $mail_type === 'in' ? 'الوارد' : 'الصادر' ?></h1>
  <h2>من <?= htmlspecialchars($from_date ?: 'بداية السجلات') ?> إلى <?= htmlspecialchars($to_date ?: 'آخر تاريخ') ?></h2>
  <table>
    <thead>
      <tr>
        <th>م</th>
        <th>التاريخ</th>
        <th><?= $mail_type === 'in' ? 'الفرع المرسل' : 'الفرع المستلم' ?></th>
        <th>الكود</th>
        <th>مرسل من</th>
        <th>مرسل الى</th>
        <th>تم التسليم إلى</th>
        <th>الوصف</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($results)): ?>
        <tr>
          <td colspan="8" style="text-align: center;">لا توجد نتائج</td>
        </tr>
      <?php else: ?>
        <?php foreach ($results as $index => $row): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['branch_name']) ?></td>
            <td><?= htmlspecialchars($row['code']) ?></td>
            <td><?= htmlspecialchars($row['sender_name']) ?></td>
            <td><?= htmlspecialchars($row['receiver_name']) ?></td>
            <td><?= htmlspecialchars($row['handed_to']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot class="text-center"><tr>
      <td colspan="8">
        أية مراسلات غير مضمنة داخل الجدول ليس للبريد علاقة بها ولم يتم ارسالها من خلالنا - تاريخ الطباعة: <?= date('Y-m-d H:i') ?>

      </td>
    </tr></tfoot>
  </table>

  <div class="print-button" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
      طباعة التقرير
    </button>
  </div>
        </div>
</body>
</html>