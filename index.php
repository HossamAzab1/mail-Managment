<?php
require_once 'includes/header.php';
?>

<div class="text-center py-12">
    <h1 class="text-4xl font-bold mb-6">مرحبًا بك في نظام إدارة البريد</h1>
    <p class="text-xl mb-8">نظام متكامل لإدارة البريد الوارد والصادر</p>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="mail/in.php" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="text-blue-600 dark:text-blue-400 text-3xl mb-3">
                <i class="fas fa-inbox"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">البريد الوارد</h3>
            <p class="text-gray-600 dark:text-gray-400">إضافة وعرض البريد الوارد</p>
        </a>
        
        <a href="mail/out.php" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="text-green-600 dark:text-green-400 text-3xl mb-3">
                <i class="fas fa-paper-plane"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">البريد الصادر</h3>
            <p class="text-gray-600 dark:text-gray-400">إضافة وعرض البريد الصادر</p>
        </a>
        
        <a href="manage/branches.php" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="text-purple-600 dark:text-purple-400 text-3xl mb-3">
                <i class="fas fa-code-branch"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">إدارة الفروع</h3>
            <p class="text-gray-600 dark:text-gray-400">إضافة وتعديل وحذف الفروع</p>
        </a>
                <a href="manage/persons.php" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="text-lime-600 dark:text-lime-400 text-3xl mb-3">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">إدارة الأفراد</h3>
            <p class="text-gray-600 dark:text-gray-400">إضافة وتعديل وحذف الأفراد</p>
        </a>
        

        <a href="manage/dashboard.php" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="text-yellow-600 dark:text-yellow-400 text-3xl mb-3">
            <i class="fas fa-water"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">لوحة التحكم</h3>
            <p class="text-gray-600 dark:text-gray-400">إحصائيات وتحليلات البريد</p>
        </a>

        <a href="manage/report.php" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <div class="text-yellow-600 dark:text-yellow-400 text-3xl mb-3">
                <i class="fas fa-file"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">التقارير</h3>
            <p class="text-gray-600 dark:text-gray-400">طباعة التقارير</p>
        </a>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>