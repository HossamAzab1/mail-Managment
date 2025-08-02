<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $from_branch_id = $_POST['from_branch_id'];
    $code = $_POST['code'];
    $description = $_POST['description'];
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $handed_to = $_POST['handed_to'];

    // Validate sender and receiver exist
    $stmt = $pdo->prepare("SELECT id FROM persons WHERE id = ?");
    $stmt->execute([$sender_id]);
    if (!$stmt->fetch()) {
        die('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">المرسل المحدد غير موجود</div>');
    }

    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        die('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">المستلم المحدد غير موجود</div>');
    }

    $stmt = $pdo->prepare("INSERT INTO mail_in (date, from_branch_id, code, description, sender_id, receiver_id, handed_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$date, $from_branch_id, $code, $description, $sender_id, $receiver_id, $handed_to])) {
        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">تم إضافة البريد الوارد بنجاح</div>';
    } else {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">حدث خطأ أثناء حفظ البيانات</div>';
    }
}

// Handle Excel export
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="mail_in_'.date('Y-m-d').'.xls"');
    
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    
    $query = "SELECT mi.date, b.name as branch_name, mi.code, 
                     s.name as sender_name, r.name as receiver_name, mi.handed_to, mi.description
              FROM mail_in mi
              JOIN branches b ON mi.from_branch_id = b.id
              JOIN persons s ON mi.sender_id = s.id
              JOIN persons r ON mi.receiver_id = r.id";
    
    $conditions = [];
    $params = [];
    
    if (!empty($from_date)) {
        $conditions[] = "mi.date >= ?";
        $params[] = $from_date;
    }
    
    if (!empty($to_date)) {
        $conditions[] = "mi.date <= ?";
        $params[] = $to_date;
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY mi.date DESC, mi.id DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $mail_in = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr>
            <th>التاريخ</th>
            <th>الفرع المرسل</th>
            <th>الكود</th>
            <th>المرسل</th>
            <th>الوصف</th>
            <th>المرسل اليه</th>
            <th>تم التسليم إلى</th>
          </tr>";
    
    foreach ($mail_in as $item) {
        echo "<tr>
                <td>{$item['date']}</td>
                <td>{$item['branch_name']}</td>
                <td>{$item['code']}</td>
                <td>{$item['sender_name']}</td>
                <td>{$item['description']}</td>
                <td>{$item['receiver_name']}</td>
                <td>{$item['handed_to']}</td>
              </tr>";
    }
    
    echo "</table>";
    exit;
}


// Fetch branches
$branches = $pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();

// Get filter values
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Build query for mail_in with filters
$query = "SELECT mi.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
          FROM mail_in mi
          JOIN branches b ON mi.from_branch_id = b.id
          JOIN persons s ON mi.sender_id = s.id
          JOIN persons r ON mi.receiver_id = r.id";

$conditions = [];
$params = [];

if (!empty($from_date)) {
    $conditions[] = "mi.date >= ?";
    $params[] = $from_date;
}

if (!empty($to_date)) {
    $conditions[] = "mi.date <= ?";
    $params[] = $to_date;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY mi.date DESC, mi.id DESC LIMIT 500";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$mail_in = $stmt->fetchAll();

?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>

<h2 class="text-2xl font-bold mb-6">إضافة بريد وارد</h2>

<form method="POST" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="date" class="block mb-2 font-medium">التاريخ</label>
            <input type="date" id="date" name="date" required 
                   class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                   value="<?= date('Y-m-d') ?>">
        </div>
        <div>
            <label for="from_branch_id" class="block mb-2 font-medium">الفرع المرسل</label>
            <select id="from_branch_id" name="from_branch_id" required 
                    class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                <option value="">اختر الفرع</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= $branch['id'] ?>"><?= $branch['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="code" class="block mb-2 font-medium">كود البريد</label>
            <input type="text" id="code" name="code" required 
                   class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
        </div>
        <div>
            <label for="sender_name" class="block mb-2 font-medium">المرسل</label>
            <input type="text" id="sender_name" name="sender_name" required 
                   class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                   placeholder="ابدأ الكتابة للبحث...">
            <input type="hidden" id="sender_id" name="sender_id">
        </div>
        <div>
            <label for="receiver_name" class="block mb-2 font-medium">المستلم</label>
            <input type="text" id="receiver_name" name="receiver_name" required 
                   class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                   placeholder="ابدأ الكتابة للبحث...">
            <input type="hidden" id="receiver_id" name="receiver_id">
        </div>
        <div>
            <label for="handed_to" class="block mb-2 font-medium">تم التسليم إلى</label>
            <input type="text" id="handed_to" name="handed_to" 
                   class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
        </div>
        <div class="md:col-span-2">
            <label for="description" class="block mb-2 font-medium">الوصف</label>
            <textarea id="description" name="description" rows="3" 
                      class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"></textarea>
        </div>
    </div>
    <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">حفظ</button>
</form>

<script>
$(function() {
    // Auto-complete for sender
    $("#sender_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../ajax/search_persons.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $("#sender_id").val(ui.item.id);
            $("#sender_name").val(ui.item.name);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append(`<div>${item.name}</div>`)
            .appendTo(ul);
    };

    // Auto-complete for receiver
    $("#receiver_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../ajax/search_persons.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $("#receiver_id").val(ui.item.id);
            $("#receiver_name").val(ui.item.name);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append(`<div>${item.name}</div>`)
            .appendTo(ul);
    };
});
</script>

<h2 class="text-2xl font-bold mb-6">سجل البريد الوارد</h2>
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
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
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded w-full">
                تصفية
            </button>
        </div>
        <div class="flex gap-2">
            <a href="?export=1&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>"
               class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex-1 text-center">
                تصدير إكسل
            </a>
            <!-- <button onclick="window.print()" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded flex-1">
                طباعة
            </button> -->
            <a
                    class="text-xs bg-purple-600 hover:bg-purple-700 text-center text-white font-bold py-2 px-4 rounded flex-1">
                طباعة - تحت التطوير
            </a>
        </div>
    </form>
</div>

<!-- Print-only header (hidden on screen) -->
<div class="hidden print:block text-center mb-4">
    <h1 class="text-2xl font-bold">سجل البريد الوارد</h1>
    <?php if (!empty($from_date) || !empty($to_date)): ?>
    <p class="text-lg">
        من <?= $from_date ?> إلى <?= $to_date ?>
    </p>
    <?php endif; ?>
    <p class="text-sm">تاريخ الطباعة: <?= date('Y-m-d H:i') ?></p>
</div>

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md overflow-x-auto print:p-0 print:shadow-none">
    <table class="w-full print:border print:border-gray-300">
        <thead class="print:table-header-group">
            <tr class="bg-gray-200 dark:bg-gray-700 print:bg-gray-200">
                <th class="p-3 text-right">التاريخ</th>
                <th class="p-3 text-right">الفرع المرسل</th>
                <th class="p-3 text-right">الكود</th>
                <th class="p-3 text-right">المرسل</th>
                <th class="p-3 text-right">المستلم</th>
                <th class="p-3 text-right">تم التسليم إلى</th>
                <th class="p-3 text-right">إجراءات</th>
                
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mail_in as $item): ?>
            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 print:border-b print:border-gray-300">
                <td class="p-3"><?= $item['date'] ?></td>
                <td class="p-3"><?= $item['branch_name'] ?></td>
                <td class="p-3"><?= $item['code'] ?></td>
                <td class="p-3"><?= $item['sender_name'] ?></td>
                <td class="p-3"><?= $item['receiver_name'] ?></td>
                <td class="p-3"><?= $item['handed_to'] ?></td>
                                <td class="p-3 flex gap-2 justify-end">
    <!-- View Button -->
    <button onclick="showMailDetails(
        '<?= htmlspecialchars($item['date'], ENT_QUOTES) ?>',
        '<?= htmlspecialchars($item['branch_name'], ENT_QUOTES) ?>',
        '<?= htmlspecialchars($item['code'], ENT_QUOTES) ?>',
        '<?= htmlspecialchars($item['sender_name'], ENT_QUOTES) ?>',
        '<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>',
        '<?= htmlspecialchars($item['receiver_name'], ENT_QUOTES) ?>',
        '<?= htmlspecialchars($item['handed_to'], ENT_QUOTES) ?>'
    )" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-eye"></i> عرض
    </button>
    
    <!-- Delete Button -->
    <a href="delete_mail.php?id=<?= $item['id'] ?>&type=in" 
       onclick="return confirm('هل أنت متأكد من حذف هذا البريد؟')"
       class="text-red-600 hover:text-red-800">
        <i class="fas fa-trash"></i> حذف
    </a>
</td>


            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .print\:p-0, .print\:p-0 * {
            visibility: visible;
        }
        .print\:p-0 {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
    }
</style>
<!-- Mail Details Modal -->
<div id="mailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">تفاصيل البريد الوارد</h3>
            <button onclick="document.getElementById('mailModal').classList.add('hidden')" 
                    class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="font-medium">التاريخ:</label>
                    <p id="modal-date" class="mt-1"></p>
                </div>
                <div>
                    <label class="font-medium">الكود:</label>
                    <p id="modal-code" class="mt-1"></p>
                </div>
                <div>
                    <label class="font-medium">الجهة:</label>
                    <p id="modal-branch" class="mt-1"></p>
                </div>
                <div>
                    <label class="font-medium">المرسل:</label>
                    <p id="modal-sender" class="mt-1"></p>
                </div>
                <div>
                    <label class="font-medium">المستلم:</label>
                    <p id="modal-receiver" class="mt-1"></p>
                </div>
                <div>
                    <label class="font-medium">تم التسليم إلى:</label>
                    <p id="modal-handed" class="mt-1"></p>
                </div>
            </div>
            <div>
                <label class="font-medium">الوصف:</label>
                <p id="modal-description" class="mt-1 whitespace-pre-line"></p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button onclick="document.getElementById('mailModal').classList.add('hidden')" 
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                إغلاق
            </button>
        </div>
    </div>
</div>
<script>
// Show mail details in modal
function showMailDetails(date, branch, code, sender, description, receiver, handed) {
    document.getElementById('modal-date').textContent = date;
    document.getElementById('modal-branch').textContent = branch;
    document.getElementById('modal-code').textContent = code;
    document.getElementById('modal-sender').textContent = sender;
    document.getElementById('modal-receiver').textContent = receiver;
    document.getElementById('modal-handed').textContent = handed;
    document.getElementById('modal-description').textContent = description;
    
    document.getElementById('mailModal').classList.remove('hidden');
}

// Close modal when clicking outside
document.getElementById('mailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});
</script>


<?php require_once '../includes/footer.php'; ?>
