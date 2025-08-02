<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة البريد</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <script src="/bohy/assets/tailwind.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <script src="/bohy/assets/chart.js"></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">     -->
    <link rel="stylesheet" href="/bohy/assets/all.min.css">
    <!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css"> -->
    <link rel="stylesheet" href="/bohy/assets/jquery-ui.css">
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script src="/bohy/assets/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script> -->
    <script src="/bohy/assets/jquery-ui.min.js"></script>





    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                fontFamily: {
                    sans: ['Tajawal', 'sans-serif'],
                },
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        body {
            font-family: 'Tajawal', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <nav class="bg-blue-600 dark:bg-blue-800 text-white p-4 shadow-md sticky top-0 z-50"> <!-- Sticky top-0 z-50 for fixed top navbar-->
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">نظام إدارة البريد</h1>
            <div class="flex items-center space-x-6 space-x-reverse">
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-blue-700 dark:hover:bg-blue-600">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <a href="/bohy/index.php" class="px-3 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">الرئيسية</a>
                <a href="/bohy/mail/in.php" class="px-3 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">البريد الوارد</a>
                <a href="/bohy/mail/out.php" class="px-3 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">البريد الصادر</a>
                <a href="/bohy/manage/report.php" class="px-3 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">التقارير</a>
                <a href="/bohy/manage/persons.php" class="px-3 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">الأفراد</a>
                <a href="/bohy/manage/branches.php" class="px-3 py-2 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">الإعدادات</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
        <!-- 01005005191 -->