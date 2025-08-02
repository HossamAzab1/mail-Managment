<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Get counts
$mail_in_count = $pdo->query("SELECT COUNT(*) FROM mail_in")->fetchColumn();
$mail_out_count = $pdo->query("SELECT COUNT(*) FROM mail_out")->fetchColumn();

// Get top senders
$top_senders = $pdo->query("
    SELECT p.name, COUNT(*) as count 
    FROM mail_in mi
    JOIN persons p ON mi.sender_id = p.id
    GROUP BY p.name
    ORDER BY count DESC
    LIMIT 5
")->fetchAll();

// Get top receivers
$top_receivers = $pdo->query("
    SELECT p.name, COUNT(*) as count 
    FROM mail_in mi
    JOIN persons p ON mi.receiver_id = p.id
    GROUP BY p.name
    ORDER BY count DESC
    LIMIT 5
")->fetchAll();

// Get mail by month
$mail_by_month = $pdo->query("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as count
    FROM mail_in
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month
")->fetchAll();

// Get mail by branch (in)
$mail_by_branch_in = $pdo->query("
    SELECT b.name, COUNT(*) as count
    FROM mail_in mi
    JOIN branches b ON mi.from_branch_id = b.id
    GROUP BY b.name
    ORDER BY count DESC
")->fetchAll();

// Get mail by branch (out)
$mail_by_branch_out = $pdo->query("
    SELECT b.name, COUNT(*) as count
    FROM mail_out mo
    JOIN branches b ON mo.to_branch_id = b.id
    GROUP BY b.name
    ORDER BY count DESC
")->fetchAll();
?>

<h2 class="text-2xl font-bold mb-6">لوحة التحكم</h2>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-blue-100 dark:bg-blue-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">البريد الوارد</h3>
        <p class="text-3xl font-bold"><?= $mail_in_count ?></p>
    </div>
    <div class="bg-green-100 dark:bg-green-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">البريد الصادر</h3>
        <p class="text-3xl font-bold"><?= $mail_out_count ?></p>
    </div>
    <div class="bg-yellow-100 dark:bg-yellow-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">أكثر المرسلين</h3>
        <p class="text-xl font-bold"><?= $top_senders[0]['name'] ?? 'N/A' ?></p>
        <p class="text-sm"><?= $top_senders[0]['count'] ?? 0 ?> مراسلات</p>
    </div>
    <div class="bg-purple-100 dark:bg-purple-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">أكثر المستلمين</h3>
        <p class="text-xl font-bold"><?= $top_receivers[0]['name'] ?? 'N/A' ?></p>
        <p class="text-sm"><?= $top_receivers[0]['count'] ?? 0 ?> مراسلات</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold mb-4">البريد الوارد حسب الشهر</h3>
        <canvas id="mailByMonthChart"></canvas>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold mb-4">البريد الصادر حسب الفرع</h3>
        <canvas id="mailByBranchOutChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold mb-4">أكثر المرسلين</h3>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700">
                    <th class="p-3 text-right">الاسم</th>
                    <th class="p-3 text-right">عدد المراسلات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_senders as $sender): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3"><?= $sender['name'] ?></td>
                    <td class="p-3"><?= $sender['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold mb-4">أكثر المستلمين</h3>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700">
                    <th class="p-3 text-right">الاسم</th>
                    <th class="p-3 text-right">عدد المراسلات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_receivers as $receiver): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3"><?= $receiver['name'] ?></td>
                    <td class="p-3"><?= $receiver['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Mail by month chart
    const mailByMonthCtx = document.getElementById('mailByMonthChart').getContext('2d');
    const mailByMonthChart = new Chart(mailByMonthCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($mail_by_month, 'month')) ?>,
            datasets: [{
                label: 'البريد الوارد',
                data: <?= json_encode(array_column($mail_by_month, 'count')) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    rtl: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Mail by branch (out) chart
    const mailByBranchOutCtx = document.getElementById('mailByBranchOutChart').getContext('2d');
    const mailByBranchOutChart = new Chart(mailByBranchOutCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($mail_by_branch_out, 'name')) ?>,
            datasets: [{
                label: 'البريد الصادر',
                data: <?= json_encode(array_column($mail_by_branch_out, 'count')) ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    rtl: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>