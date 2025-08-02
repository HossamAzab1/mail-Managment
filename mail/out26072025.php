<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $to_branch_id = $_POST['to_branch_id'];
    $code = $_POST['code'];
    $description = $_POST['description'];
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $handed_to = $_POST['handed_to'];

    // Check if code already exists
    $stmt = $pdo->prepare("SELECT id FROM mail_out WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "كود البريد موجود مسبقاً، يرجى استخدام كود آخر";
        header('Location: out.php');
        exit;
    }

    // Validate sender and receiver exist
    $stmt = $pdo->prepare("SELECT id FROM persons WHERE id = ?");
    $stmt->execute([$sender_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "مرسل من المحدد غير موجود";
        header('Location: out.php');
        exit;
    }

    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "مرسل الى المحدد غير موجود";
        header('Location: out.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO mail_out (date, to_branch_id, code, description, sender_id, receiver_id, handed_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$date, $to_branch_id, $code, $description, $sender_id, $receiver_id, $handed_to])) {
            $_SESSION['success'] = "تم إضافة البريد الصادر بنجاح";
            header('Location: out.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
        header('Location: out.php');
        exit;
    }
}

// Handle Excel export
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="بريد صادر-'.date('Y-m-d').'.xls"');
    
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    
    $query = "SELECT mo.date, b.name as branch_name, mo.code, 
                     s.name as sender_name, r.name as receiver_name, mo.handed_to, mo.description
              FROM mail_out mo
              JOIN branches b ON mo.to_branch_id = b.id
              JOIN persons s ON mo.sender_id = s.id
              JOIN persons r ON mo.receiver_id = r.id";
    
    $conditions = [];
    $params = [];
    
    if (!empty($from_date)) {
        $conditions[] = "mo.date >= ?";
        $params[] = $from_date;
    }
    
    if (!empty($to_date)) {
        $conditions[] = "mo.date <= ?";
        $params[] = $to_date;
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY mo.date DESC, mo.code ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $mail_out = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr>
            <th>التاريخ</th>
            <th>الفرع مرسل من</th>
            <th>الكود</th>
            <th>مرسل من</th>
            <th>الوصف</th>
            <th>مرسل من اليه</th>
            <th>تم التسليم إلى</th>
          </tr>";
    
    foreach ($mail_out as $item) {
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

// Build query for mail_out with filters
$query = "SELECT mo.*, b.name as branch_name, s.name as sender_name, r.name as receiver_name 
          FROM mail_out mo
          JOIN branches b ON mo.to_branch_id = b.id
          JOIN persons s ON mo.sender_id = s.id
          JOIN persons r ON mo.receiver_id = r.id";

$conditions = [];
$params = [];

if (!empty($from_date)) {
    $conditions[] = "mo.date >= ?";
    $params[] = $from_date;
}

if (!empty($to_date)) {
    $conditions[] = "mo.date <= ?";
    $params[] = $to_date;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY mo.date DESC, mo.code ASC LIMIT 500";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$mail_out = $stmt->fetchAll();

// Get the maximum code value from the database
$max_code = $pdo->query("SELECT MAX(code) FROM mail_out")->fetchColumn();
$next_code = $max_code + 1;
?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>

<div class="container mx-auto px-4 py-6">
    <!-- Page Header with Add Button -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">سجل البريد الصادر</h2>
        <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i> إضافة بريد صادر
        </button>
    </div>

    <!-- Filter Section -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-6">
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
                    <i class="fas fa-file-excel mr-2"></i> تصدير إكسل
                </a>
                <button 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded flex-1">
                    <i class="fas fa-print mr-2"></i> طباعة
                </button>
                <!-- <button onclick="window.print()" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded flex-1">
                    <i class="fas fa-print mr-2"></i> طباعة
                </button> -->
            </div>
        </form>
    </div>

    <!-- Mail Records Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700">
                    <th class="p-3 text-right">التاريخ</th>
                    <th class="p-3 text-right">الجهة</th>
                    <th class="p-3 text-right">الكود</th>
                    <th class="p-3 text-right">مرسل من</th>
                    <th class="p-3 text-right">الوصف</th>
                    <th class="p-3 text-right">مرسل الى</th>
                    <th class="p-3 text-right">تم التسليم إلى</th>
                    <th class="p-3 text-right">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mail_out as $item): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3"><?= $item['date'] ?></td>
                    <td class="p-3"><?= $item['branch_name'] ?></td>
                    <td class="p-3"><?= $item['code'] ?></td>
                    <td class="p-3"><?= $item['sender_name'] ?></td>
                    <td class="p-3"><?= $item['description'] ?></td>
                    <td class="p-3"><?= $item['receiver_name'] ?></td>
                    <td class="p-3"><?= $item['handed_to'] ?></td>
                    <td class="p-3 flex gap-2 justify-end">
                        <button onclick="showMailDetails(
                            '<?= htmlspecialchars($item['date'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($item['branch_name'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($item['code'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($item['sender_name'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($item['receiver_name'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($item['handed_to'], ENT_QUOTES) ?>'
                        )" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="delete_mail.php?id=<?= $item['id'] ?>&type=out" 
                           onclick="return confirm('هل أنت متأكد من حذف هذا البريد؟')"
                           class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Mail Modal -->
<div id="addMailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">إضافة بريد صادر</h3>
            <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block mb-2 font-medium">التاريخ</label>
                    <input type="date" id="date" name="date" required 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="to_branch_id" class="block mb-2 font-medium">الجهة</label>
                    <select id="to_branch_id" name="to_branch_id" required 
                            class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                        <option value="">اختر الفرع</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>"><?= $branch['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="code" class="block mb-2 font-medium">كود البريد</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="code" name="code" required 
                               value="<?= htmlspecialchars($next_code) ?>"
                               class="flex-1 p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            (آخر كود: <?= htmlspecialchars($max_code) ?>)
                        </span>
                    </div>
                </div>
                <div>
                    <label for="sender_name" class="block mb-2 font-medium">مرسل من</label>
                    <input type="text" id="sender_name" name="sender_name" required 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                           placeholder="ابدأ الكتابة للبحث...">
                    <input type="hidden" id="sender_id" name="sender_id">
                </div>
                <div>
                    <label for="receiver_name" class="block mb-2 font-medium">مرسل الى</label>
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
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    إلغاء
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    حفظ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Mail Details Modal -->
<div id="mailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">تفاصيل البريد الصادر</h3>
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
                    <label class="font-medium">مرسل من:</label>
                    <p id="modal-sender" class="mt-1"></p>
                </div>
                <div>
                    <label class="font-medium">مرسل الى:</label>
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
// Modal functions
function openAddModal() {
    document.getElementById('addMailModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addMailModal').classList.add('hidden');
}

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
document.getElementById('addMailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddModal();
    }
});

document.getElementById('mailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Auto-complete for sender and receiver
$(function() {
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

</style>

<?php require_once '../includes/footer.php'; ?>