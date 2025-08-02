<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id'])) {
        // Handle edit form submission
        $id = $_POST['edit_id'];
        $date = $_POST['date'];
        $from_branch_id = $_POST['from_branch_id'];
        $code = $_POST['code'];
        $description = $_POST['description'];
        $sender_id = $_POST['sender_id'];
        $receiver_id = $_POST['receiver_id'];
        $handed_to = $_POST['handed_to'];

        try {
            $stmt = $pdo->prepare("UPDATE mail_in SET date = ?, from_branch_id = ?, code = ?, description = ?, sender_id = ?, receiver_id = ?, handed_to = ? WHERE id = ?");
            if ($stmt->execute([$date, $from_branch_id, $code, $description, $sender_id, $receiver_id, $handed_to, $id])) {
                $_SESSION['success'] = "تم تحديث البريد الوارد بنجاح";
                header('Location: in.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث البيانات: " . $e->getMessage();
            header('Location: in.php');
            exit;
        }
    } else {
        // Handle new form submission
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
            $_SESSION['error'] = "المرسل المحدد غير موجود";
            header('Location: in.php');
            exit;
        }

        $stmt->execute([$receiver_id]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "المستلم المحدد غير موجود";
            header('Location: in.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO mail_in (date, from_branch_id, code, description, sender_id, receiver_id, handed_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$date, $from_branch_id, $code, $description, $sender_id, $receiver_id, $handed_to])) {
                $_SESSION['success'] = "تم إضافة البريد الوارد بنجاح";
                header('Location: in.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
            header('Location: in.php');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM mail_in WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "تم حذف البريد الوارد بنجاح";
            header('Location: in.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "حدث خطأ أثناء حذف البيانات: " . $e->getMessage();
        header('Location: in.php');
        exit;
    }
}

// Handle Excel export
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="mail_in_'.date('Y-m-d').'.xls"');
    
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $search = $_GET['search'] ?? '';
    
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
    
    if (!empty($search)) {
        $conditions[] = "(b.name LIKE ? OR mi.code LIKE ? OR s.name LIKE ? OR r.name LIKE ? OR mi.handed_to LIKE ? OR mi.description LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, array_fill(0, 6, $searchTerm));
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
$search = $_GET['search'] ?? '';

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

if (!empty($search)) {
    $conditions[] = "(b.name LIKE ? OR mi.code LIKE ? OR s.name LIKE ? OR r.name LIKE ? OR mi.handed_to LIKE ? OR mi.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, array_fill(0, 6, $searchTerm));
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY mi.date DESC, mi.id DESC LIMIT 500";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$mail_in = $stmt->fetchAll();
?>

<!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script> -->

<div class="container mx-auto px-4 py-6">
    <!-- Page Header with Add Button -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">سجل البريد الوارد</h2>
        <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i> إضافة بريد وارد
        </button>
    </div>

    <!-- Search Section -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label for="search" class="block mb-2 font-medium">بحث</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                       class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700" 
                       placeholder="ابحث في البريد الوارد...">
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    بحث
                </button>
                <a href="in.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    إعادة تعيين
                </a>
            </div>
        </form>
    </div>

    <!-- Filter Section -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
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
                <a href="?export=1&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&search=<?= urlencode($search) ?>"
                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex-1 text-center">
                    <i class="fas fa-file-excel mr-2"></i> تصدير إكسل
                </a>
                <button onclick="window.print()" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded flex-1">
                    <i class="fas fa-print mr-2"></i> طباعة
                </button>
            </div>
        </form>
    </div>

    <!-- Mail Records Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700">
                    <th class="p-3 text-right">التاريخ</th>
                    <th class="p-3 text-right">الفرع المرسل</th>
                    <th class="p-3 text-right">الكود</th>
                    <th class="p-3 text-right">مرسل من</th>
                    <th class="p-3 text-right">مرسل الى</th>
                    <th class="p-3 text-right">تم التسليم إلى</th>
                    <th class="p-3 text-right">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mail_in as $item): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3"><?= $item['date'] ?></td>
                    <td class="p-3"><?= $item['branch_name'] ?></td>
                    <td class="p-3"><?= $item['code'] ?></td>
                    <td class="p-3"><?= $item['sender_name'] ?></td>
                    <td class="p-3"><?= $item['receiver_name'] ?></td>
                    <td class="p-3"><?= $item['handed_to'] ?></td>
                    <td class="p-3 flex gap-2 text-center">
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
                        <button onclick="openEditModal(
                            <?= $item['id'] ?>,
                            '<?= $item['date'] ?>',
                            <?= $item['from_branch_id'] ?>,
                            '<?= $item['code'] ?>',
                            '<?= addslashes($item['description']) ?>',
                            <?= $item['sender_id'] ?>,
                            '<?= addslashes($item['sender_name']) ?>',
                            <?= $item['receiver_id'] ?>,
                            '<?= addslashes($item['receiver_name']) ?>',
                            '<?= addslashes($item['handed_to']) ?>'
                        )" class="text-yellow-600 hover:text-yellow-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="if(confirm('هل أنت متأكد من حذف هذا البريد؟')) window.location.href='?delete_id=<?= $item['id'] ?>'" 
                           class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
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
            <h3 class="text-xl font-bold">إضافة بريد وارد</h3>
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

<!-- Edit Mail Modal -->
<div id="editMailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">تعديل بريد وارد</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit_date" class="block mb-2 font-medium">التاريخ</label>
                    <input type="date" id="edit_date" name="date" required 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                </div>
                <div>
                    <label for="edit_from_branch_id" class="block mb-2 font-medium">الفرع المرسل</label>
                    <select id="edit_from_branch_id" name="from_branch_id" required 
                            class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                        <option value="">اختر الفرع</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>"><?= $branch['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="edit_code" class="block mb-2 font-medium">كود البريد</label>
                    <input type="text" id="edit_code" name="code" required 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                </div>
                <div>
                    <label for="edit_sender_name" class="block mb-2 font-medium">المرسل</label>
                    <input type="text" id="edit_sender_name" name="sender_name" required 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                           placeholder="ابدأ الكتابة للبحث...">
                    <input type="hidden" id="edit_sender_id" name="sender_id">
                </div>
                <div>
                    <label for="edit_receiver_name" class="block mb-2 font-medium">المستلم</label>
                    <input type="text" id="edit_receiver_name" name="receiver_name" required 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"
                           placeholder="ابدأ الكتابة للبحث...">
                    <input type="hidden" id="edit_receiver_id" name="receiver_id">
                </div>
                <div>
                    <label for="edit_handed_to" class="block mb-2 font-medium">تم التسليم إلى</label>
                    <input type="text" id="edit_handed_to" name="handed_to" 
                           class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                </div>
                <div class="md:col-span-2">
                    <label for="edit_description" class="block mb-2 font-medium">الوصف</label>
                    <textarea id="edit_description" name="description" rows="3" 
                              class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    إلغاء
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

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
// Modal functions
function openAddModal() {
    document.getElementById('addMailModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addMailModal').classList.add('hidden');
}

function openEditModal(id, date, from_branch_id, code, description, sender_id, sender_name, receiver_id, receiver_name, handed_to) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_from_branch_id').value = from_branch_id;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_sender_id').value = sender_id;
    document.getElementById('edit_sender_name').value = sender_name;
    document.getElementById('edit_receiver_id').value = receiver_id;
    document.getElementById('edit_receiver_name').value = receiver_name;
    document.getElementById('edit_handed_to').value = handed_to;
    
    document.getElementById('editMailModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editMailModal').classList.add('hidden');
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

document.getElementById('editMailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('mailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Auto-complete for sender and receiver
$(function() {
    $("#sender_name, #edit_sender_name").autocomplete({
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
            $(this).val(ui.item.name);
            $(this).parent().find('input[type="hidden"]').val(ui.item.id);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append(`<div>${item.name}</div>`)
            .appendTo(ul);
    };

    $("#receiver_name, #edit_receiver_name").autocomplete({
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
            $(this).val(ui.item.name);
            $(this).parent().find('input[type="hidden"]').val(ui.item.id);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
            .append(`<div>${item.name}</div>`)
            .appendTo(ul);
    };
});
</script>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .container, .container * {
            visibility: visible;
        }
        .container {
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

<?php require_once '../includes/footer.php'; ?>