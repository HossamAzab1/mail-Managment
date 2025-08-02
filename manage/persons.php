<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Handle add person
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $name = $_POST['name'];
    $stmt = $pdo->prepare("INSERT INTO persons (name) VALUES (?)");
    $stmt->execute([$name]);
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">تم إضافة الشخص بنجاح</div>';
}

// Handle edit person
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_person'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $stmt = $pdo->prepare("UPDATE persons SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">تم تحديث الشخص بنجاح</div>';
}

// Handle delete person
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM persons WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">تم حذف الشخص بنجاح</div>';
}

// Fetch all persons
$persons = $pdo->query("SELECT * FROM persons ORDER BY name")->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6">إدارة الأسماء</h2>

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold mb-4">إضافة اسم جديد</h3>
    <form method="POST" class="flex gap-4">
        <input type="text" name="name" placeholder="اسم الشخص" required class="flex-grow p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
        <button type="submit" name="add_person" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">إضافة</button>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold mb-4">قائمة الأسماء</h3>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700">
                    <th class="p-3 text-right">#</th>
                    <th class="p-3 text-right">الاسم</th>
                    <th class="p-3 text-right">تاريخ الإضافة</th>
                    <th class="p-3 text-right">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($persons as $person): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3"><?= $person['id'] ?></td>
                    <td class="p-3">
                        <a href="#" onclick="showPersonMails(<?= $person['id'] ?>, '<?= htmlspecialchars($person['name'], ENT_QUOTES) ?>')" class="text-blue-600 hover:text-blue-800">
                            <?= $person['name'] ?>
                        </a>
                    </td>
                    <td class="p-3"><?= $person['created_at'] ?></td>
                    <td class="p-3 flex gap-2">
                        <button onclick="openEditModal(<?= $person['id'] ?>, '<?= htmlspecialchars($person['name'], ENT_QUOTES) ?>')" class="text-yellow-600 hover:text-yellow-800">
                            تعديل
                        </button>
                        <span>|</span>
                        <a href="?delete=<?= $person['id'] ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('هل أنت متأكد من حذف هذا الاسم؟')">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Person Modal -->
<div id="editPersonModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">تعديل اسم الشخص</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" id="edit_person_id">
            <div>
                <label for="edit_person_name" class="block mb-2 font-medium">الاسم</label>
                <input type="text" id="edit_person_name" name="name" required 
                       class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                    إلغاء
                </button>
                <button type="submit" name="edit_person" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Person Mails Modal -->
<div id="personMailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold" id="personMailsTitle">بريد الشخص</h3>
            <button onclick="document.getElementById('personMailsModal').classList.add('hidden')" 
                    class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-6">
            <div>
                <h4 class="text-lg font-semibold mb-2">البريد الوارد</h4>
                <div id="mailInTable" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-200 dark:bg-gray-700">
                                <th class="p-3 text-right">التاريخ</th>
                                <th class="p-3 text-right">الفرع المرسل</th>
                                <th class="p-3 text-right">الكود</th>
                                <th class="p-3 text-right">الوصف</th>
                            </tr>
                        </thead>
                        <tbody id="mailInBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Filled by JavaScript -->
                        </tbody>
                    </table>
                    <p id="noMailIn" class="text-center py-4 text-gray-500">جاري التحميل...</p>
                </div>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold mb-2">البريد الصادر</h4>
                <div id="mailOutTable" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-200 dark:bg-gray-700">
                                <th class="p-3 text-right">التاريخ</th>
                                <th class="p-3 text-right">الفرع المستقبل</th>
                                <th class="p-3 text-right">الكود</th>
                                <th class="p-3 text-right">الوصف</th>
                            </tr>
                        </thead>
                        <tbody id="mailOutBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Filled by JavaScript -->
                        </tbody>
                    </table>
                    <p id="noMailOut" class="text-center py-4 text-gray-500">جاري التحميل...</p>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button onclick="document.getElementById('personMailsModal').classList.add('hidden')" 
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                إغلاق
            </button>
        </div>
    </div>
</div>
<script>
// Modal functions for editing person
function openEditModal(id, name) {
    document.getElementById('edit_person_id').value = id;
    document.getElementById('edit_person_name').value = name;
    document.getElementById('editPersonModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editPersonModal').classList.add('hidden');
}

// Show person's mails
function showPersonMails(personId, personName) {
    document.getElementById('personMailsTitle').textContent = `بريد ${personName}`;
    document.getElementById('personMailsModal').classList.remove('hidden');
    
    // Show loading messages
    document.getElementById('noMailIn').textContent = 'جاري التحميل...';
    document.getElementById('noMailIn').style.display = 'block';
    document.getElementById('noMailOut').textContent = 'جاري التحميل...';
    document.getElementById('noMailOut').style.display = 'block';
    
    // Clear previous results
    document.getElementById('mailInBody').innerHTML = '';
    document.getElementById('mailOutBody').innerHTML = '';

    // Fetch mail data
    fetch(`../ajax/get_person_mails.php?person_id=${personId}`)
        .then(response => response.json())
        .then(data => {
            // Handle mail in
            const mailInBody = document.getElementById('mailInBody');
            if (data.mail_in && data.mail_in.length > 0) {
                data.mail_in.forEach(item => {
                    const row = document.createElement('tr');
                    row.className = 'border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700';
                    row.innerHTML = `
                        <td class="p-3">${item.date}</td>
                        <td class="p-3">${item.branch_name}</td>
                        <td class="p-3">${item.code}</td>
                        <td class="p-3">${item.description}</td>
                    `;
                    mailInBody.appendChild(row);
                });
                document.getElementById('noMailIn').style.display = 'none';
            } else {
                document.getElementById('noMailIn').textContent = 'لا يوجد بريد وارد لهذا الشخص';
                document.getElementById('noMailIn').style.display = 'block';
            }

            // Handle mail out
            const mailOutBody = document.getElementById('mailOutBody');
            if (data.mail_out && data.mail_out.length > 0) {
                data.mail_out.forEach(item => {
                    const row = document.createElement('tr');
                    row.className = 'border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700';
                    row.innerHTML = `
                        <td class="p-3">${item.date}</td>
                        <td class="p-3">${item.branch_name}</td>
                        <td class="p-3">${item.code}</td>
                        <td class="p-3">${item.description}</td>
                    `;
                    mailOutBody.appendChild(row);
                });
                document.getElementById('noMailOut').style.display = 'none';
            } else {
                document.getElementById('noMailOut').textContent = 'لا يوجد بريد صادر لهذا الشخص';
                document.getElementById('noMailOut').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('noMailIn').textContent = 'حدث خطأ أثناء جلب البيانات';
            document.getElementById('noMailOut').textContent = 'حدث خطأ أثناء جلب البيانات';
        });
}

// Close modal when clicking outside
document.getElementById('editPersonModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('personMailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>