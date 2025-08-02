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
                    <td class="p-3"><?= $person['name'] ?></td>
                    <td class="p-3"><?= $person['created_at'] ?></td>
                    <td class="p-3">
                        <a href="?delete=<?= $person['id'] ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('هل أنت متأكد من حذف هذا الاسم؟')">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>