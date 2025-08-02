
<style>
@media print {
  @page {
    size: landscape;
    margin: 1cm;
  } 

  html, body  {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: visible;
    background: white !important;
    color: black;

  }

  table{
        background: white !important;
        color: black;
  }
  body {
    display: block;
    box-sizing: border-box;
    transform: none;
  }

  .container, .main-content, .wrapper, .report-table {
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box;
    width: 100% !important;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    /* padding: 8px; */
    border: 1px solid #000;
    text-align: right;
  }

  .no-print {
    display: none !important;
  }
}
</style>
<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Get filter parameters
$mail_type = $_GET['mail_type'] ?? 'out'; // 'in' or 'out'
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$branch_id = $_GET['branch_id'] ?? '';
$sender_id = $_GET['sender_id'] ?? '';
$receiver_id = $_GET['receiver_id'] ?? '';

// Fetch filter options
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
$senders = $pdo->query("SELECT * FROM persons ORDER BY name")->fetchAll();
$receivers = $pdo->query("SELECT * FROM persons ORDER BY name")->fetchAll();

// Build query based on filters
$query = "";
$params = [];
$conditions = [];

if ($mail_type === 'in') {
    $query = "SELECT mi.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
              FROM mail_in mi
              JOIN branches b ON mi.from_branch_id = b.id
              JOIN persons s ON mi.sender_id = s.id
              JOIN persons r ON mi.receiver_id = r.id";
} else {
    $query = "SELECT mo.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
              FROM mail_out mo
              JOIN branches b ON mo.to_branch_id = b.id
              JOIN persons s ON mo.sender_id = s.id
              JOIN persons r ON mo.receiver_id = r.id";
}

// Add date filter
if (!empty($from_date)) {
    $conditions[] = "date >= ?";
    $params[] = $from_date;
}

if (!empty($to_date)) {
    $conditions[] = "date <= ?";
    $params[] = $to_date;
}

// Add branch filter
if (!empty($branch_id)) {
    if ($mail_type === 'in') {
        $conditions[] = "from_branch_id = ?";
    } else {
        $conditions[] = "to_branch_id = ?";
    }
    $params[] = $branch_id;
}

// Add sender filter
if (!empty($sender_id)) {
    $conditions[] = "sender_id = ?";
    $params[] = $sender_id;
}

// Add receiver filter
if (!empty($receiver_id)) {
    $conditions[] = "receiver_id = ?";
    $params[] = $receiver_id;
}

// Combine conditions
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY date DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$mail_items = $stmt->fetchAll();
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">تقرير البريد</h1>
    
    <!-- Filter Form -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols- gap-4">
            <div>
                <label for="mail_type" class="block mb-2 font-medium">نوع البريد</label>
                <select id="mail_type" name="mail_type" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                    <option value="">اختر</option>
                    <option value="out" <?= $mail_type === 'out' ? 'selected' : '' ?>>صادر</option>
                    <option value="in" <?= $mail_type === 'in' ? 'selected' : '' ?>>وارد</option>
                </select>
            </div>
            
            <div>
                <label for="from_date" class="block mb-2 font-medium">من تاريخ</label>
                <input type="date" id="from_date" name="from_date" value="<?= htmlspecialchars($from_date) ?>"
                       class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
            </div>
            
            <div>
                <label for="to_date" class="block mb-2 font-medium">إلى تاريخ</label>
                <input type="date" id="to_date" name="to_date" value="<?= htmlspecialchars($to_date) ?>"
                       class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
            </div>
            
            <div>
                <label for="branch_id" class="block mb-2 font-medium">الفرع</label>
                <select id="branch_id" name="branch_id" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                    <option value="">الكل</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch['id'] ?>" <?= $branch_id == $branch['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($branch['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="sender_id" class="block mb-2 font-medium">المرسل</label>
                <select id="sender_id" name="sender_id" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                    <option value="">الكل</option>
                    <?php foreach ($senders as $sender): ?>
                        <option value="<?= $sender['id'] ?>" <?= $sender_id == $sender['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sender['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="receiver_id" class="block mb-2 font-medium">المستلم</label>
                <select id="receiver_id" name="receiver_id" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                    <option value="">الكل</option>
                    <?php foreach ($receivers as $receiver): ?>
                        <option value="<?= $receiver['id'] ?>" <?= $receiver_id == $receiver['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($receiver['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:col-span-3 flex justify-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    تصفية
                </button>
                <!-- <button type="button" onclick="window.print()" 
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                    طباعة
                </button> -->
    <button type="button" onclick="openPrintWindow()" 
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
        طباعة
    </button>
            </div>
        </form>
    </div>
    
    <!-- Report Results -->
    <div class="print:p-0">
        <!-- Print Header -->
        <div class="hidden print:block text-center mb-4">
            <h1 class="text-xl font-bold">تقرير البريد <?= $mail_type === 'in' ? 'الوارد' : 'الصادر' ?></h1>
            <p class="text-lg">
                من <?= $from_date ?: 'بداية السجلات' ?> إلى <?= $to_date ?: 'آخر تاريخ' ?>
            </p>
            <p class="text-sm">تاريخ الطباعة: <?= date('Y-m-d H:i') ?></p>
        </div>
        
        <!-- Mail Table -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md overflow-x-auto print:p-0 print:shadow-none">
            <table class="w-full print:border print:border-gray-300">
                <thead class="print:table-header-group">
                    <tr class="bg-gray-200 dark:bg-gray-700 print:bg-gray-200">
                        <th class="p-3 text-right">التاريخ</th>
                        <th class="p-3 text-right"><?= $mail_type === 'in' ? 'الفرع المرسل' : 'الفرع المستلم' ?></th>
                        <th class="p-3 text-right">الكود</th>
                        <th class="p-3 text-right">المرسل</th>
                        <th class="p-3 text-right">المستلم</th>
                        <th class="p-3 text-right">تم التسليم إلى</th>
                        <th class="p-3 text-right">الوصف</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mail_items)): ?>
                        <tr>
                            <td colspan="7" class="p-3 text-center">لا توجد نتائج</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mail_items as $item): ?>
                        <tr class="border-b border-gray-200 dark:border-gray-700 print:border-b print:border-gray-300">
                            <td class="p-3"><?= $item['date'] ?></td>
                            <td class="p-3"><?= $item['branch_name'] ?></td>
                            <td class="p-3"><?= $item['code'] ?></td>
                            <td class="p-3"><?= $item['sender_name'] ?></td>
                            <td class="p-3"><?= $item['receiver_name'] ?></td>
                            <td class="p-3"><?= $item['handed_to'] ?></td>
                            <td class="p-3"><?= $item['description'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openPrintWindow() {
    // Get all form values
    const form = document.querySelector('form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();
    
    // Open new window with all filter parameters
    window.open(`report_new.php?${params}`, '_blank');
}
</script>


<?php require_once '../includes/footer.php'; ?>