<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Database connection using PDO
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=mail_management;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get selected year from request or use current year
$selected_year = $_GET['year'] ?? date('Y');

// Get counts for dashboard cards using PDO
$incoming_mail_count = $db->query("SELECT COUNT(*) FROM mail_in")->fetchColumn();
$outgoing_mail_count = $db->query("SELECT COUNT(*) FROM mail_out")->fetchColumn();
$branches_count = $db->query("SELECT COUNT(*) FROM branches")->fetchColumn();
$persons_count = $db->query("SELECT COUNT(*) FROM persons")->fetchColumn();

// Get recent incoming mail with prepared statement
$stmt_incoming = $db->prepare("SELECT mail_in.*, branches.name as branch_name 
                              FROM mail_in 
                              JOIN branches ON mail_in.from_branch_id = branches.id 
                              ORDER BY date DESC LIMIT 5");
$stmt_incoming->execute();
$recent_incoming = $stmt_incoming->fetchAll();

// Get recent outgoing mail with prepared statement
$stmt_outgoing = $db->prepare("SELECT mail_out.*, branches.name as branch_name 
                              FROM mail_out 
                              JOIN branches ON mail_out.to_branch_id = branches.id 
                              ORDER BY date DESC LIMIT 5");
$stmt_outgoing->execute();
$recent_outgoing = $stmt_outgoing->fetchAll();

// Get mail statistics for charts with prepared statements
$stmt_monthly_incoming = $db->prepare("SELECT MONTH(date) as month, COUNT(*) as count 
                                     FROM mail_in 
                                     WHERE YEAR(date) = :year 
                                     GROUP BY MONTH(date)");
$stmt_monthly_incoming->execute([':year' => $selected_year]);
$monthly_incoming = $stmt_monthly_incoming->fetchAll();

$stmt_monthly_outgoing = $db->prepare("SELECT MONTH(date) as month, COUNT(*) as count 
                                     FROM mail_out 
                                     WHERE YEAR(date) = :year 
                                     GROUP BY MONTH(date)");
$stmt_monthly_outgoing->execute([':year' => $selected_year]);
$monthly_outgoing = $stmt_monthly_outgoing->fetchAll();

// Get branch-wise statistics for incoming mail
$stmt_branch_incoming = $db->prepare("SELECT 
    b.name as branch_name,
    MONTH(mi.date) as month,
    COUNT(*) as count
FROM mail_in mi
JOIN branches b ON mi.from_branch_id = b.id
WHERE YEAR(mi.date) = :year
GROUP BY b.name, MONTH(mi.date)
ORDER BY b.name, MONTH(mi.date)");
$stmt_branch_incoming->execute([':year' => $selected_year]);
$branch_incoming_data = $stmt_branch_incoming->fetchAll();

// Get branch-wise statistics for outgoing mail
$stmt_branch_outgoing = $db->prepare("SELECT 
    b.name as branch_name,
    MONTH(mo.date) as month,
    COUNT(*) as count
FROM mail_out mo
JOIN branches b ON mo.to_branch_id = b.id
WHERE YEAR(mo.date) = :year
GROUP BY b.name, MONTH(mo.date)
ORDER BY b.name, MONTH(mo.date)");
$stmt_branch_outgoing->execute([':year' => $selected_year]);
$branch_outgoing_data = $stmt_branch_outgoing->fetchAll();

// Prepare data for charts
$incoming_data = array_fill(0, 12, 0);
$outgoing_data = array_fill(0, 12, 0);

foreach ($monthly_incoming as $row) {
    $incoming_data[$row['month'] - 1] = $row['count'];
}

foreach ($monthly_outgoing as $row) {
    $outgoing_data[$row['month'] - 1] = $row['count'];
}


// Group incoming branch data
$branch_incoming_grouped = [];
foreach ($branch_incoming_data as $row) {
    $branch = $row['branch_name'];
    $month = $row['month'] - 1; // zero-indexed
    $count = $row['count'];
    if (!isset($branch_incoming_grouped[$branch])) {
        $branch_incoming_grouped[$branch] = array_fill(0, 12, 0);
    }
    $branch_incoming_grouped[$branch][$month] = $count;
}

// Group outgoing branch data
$branch_outgoing_grouped = [];
foreach ($branch_outgoing_data as $row) {
    $branch = $row['branch_name'];
    $month = $row['month'] - 1;
    $count = $row['count'];
    if (!isset($branch_outgoing_grouped[$branch])) {
        $branch_outgoing_grouped[$branch] = array_fill(0, 12, 0);
    }
    $branch_outgoing_grouped[$branch][$month] = $count;
}

$branch_incoming_datasets = [];
$branch_outgoing_datasets = [];
$color_palette = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];

// Prepare incoming branch datasets
$color_index = 0;
foreach ($branch_incoming_grouped as $branch => $monthly_data) {
    $branch_incoming_datasets[] = [
        'label' => $branch,
        'data' => array_values($monthly_data), // Ensure sequential array
        'backgroundColor' => $color_palette[$color_index % count($color_palette)],
        'borderColor' => $color_palette[$color_index % count($color_palette)],
        'borderWidth' => 1
    ];
    $color_index++;
}

// Prepare outgoing branch datasets
$color_index = 0;
foreach ($branch_outgoing_grouped as $branch => $monthly_data) {
    $branch_outgoing_datasets[] = [
        'label' => $branch,
        'data' => array_values($monthly_data), // Ensure sequential array
        'backgroundColor' => $color_palette[$color_index % count($color_palette)],
        'borderColor' => $color_palette[$color_index % count($color_palette)],
        'borderWidth' => 1
    ];
    $color_index++;
}

// Get available years for filter
$stmt_years = $db->query("SELECT DISTINCT YEAR(date) as year FROM mail_in UNION SELECT DISTINCT YEAR(date) as year FROM mail_out ORDER BY year DESC");
$available_years = $stmt_years->fetchAll();
?>
<style>
    .chart-container {
    overflow: hidden;
    margin-bottom: 20px;
}

</style>

<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-600 dark:text-blue-400">لوحة التحكم</h2>
        <form method="get" class="flex items-center">
            <select name="year" onchange="this.form.submit()" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($available_years as $year_row): ?>
                    <option value="<?= $year_row['year'] ?>" <?= $selected_year == $year_row['year'] ? 'selected' : '' ?>>
                        <?= $year_row['year'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="mr-2 text-sm">:اختر السنة</span>
        </form>
    </div>

    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">البريد الوارد</p>
                    <h3 class="text-2xl font-bold"><?= htmlspecialchars($incoming_mail_count) ?></h3>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                    <i class="fas fa-inbox text-blue-500 dark:text-blue-300 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">البريد الصادر</p>
                    <h3 class="text-2xl font-bold"><?= htmlspecialchars($outgoing_mail_count) ?></h3>
                </div>
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                    <i class="fas fa-paper-plane text-green-500 dark:text-green-300 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">الفروع</p>
                    <h3 class="text-2xl font-bold"><?= htmlspecialchars($branches_count) ?></h3>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full">
                    <i class="fas fa-code-branch text-purple-500 dark:text-purple-300 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 dark:text-gray-400">الأفراد</p>
                    <h3 class="text-2xl font-bold"><?= htmlspecialchars($persons_count) ?></h3>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                    <i class="fas fa-users text-yellow-500 dark:text-yellow-300 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">حركة البريد الوارد خلال السنة</h3>
                <canvas id="incomingChart" ></canvas>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">حركة البريد الصادر خلال السنة</h3>
                <canvas id="outgoingChart"></canvas>
        </div>
    </div>
    
    <!-- Branch-wise Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">توزيع البريد الوارد حسب الفروع</h3>
        <div class="chart-container" style="position: relative; height: 300px;">
            <canvas id="branchIncomingChart"></canvas>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">توزيع البريد الصادر حسب الفروع</h3>
        <div class="chart-container" style="position: relative; height: 300px;">
            <canvas id="branchOutgoingChart"></canvas>
        </div>
    </div>
</div>
    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Incoming Mail -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-600 dark:bg-blue-800 text-white">
                <h3 class="text-lg font-semibold">أحدث البريد الوارد</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-right">التاريخ</th>
                            <th class="px-4 py-2 text-right">الفرع</th>
                            <th class="px-4 py-2 text-right">الكود</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach($recent_incoming as $row): ?>
                        <tr>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['date']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['branch_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['code']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 text-right">
                <a href="/bohy/mail/in.php" class="text-blue-600 dark:text-blue-400 hover:underline">عرض الكل</a>
            </div>
        </div>
        
        <!-- Recent Outgoing Mail -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-green-600 dark:bg-green-800 text-white">
                <h3 class="text-lg font-semibold">أحدث البريد الصادر</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-right">التاريخ</th>
                            <th class="px-4 py-2 text-right">الفرع</th>
                            <th class="px-4 py-2 text-right">الكود</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach($recent_outgoing as $row): ?>
                        <tr>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['date']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['branch_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['code']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 text-right">
                <a href="/bohy/mail/out.php" class="text-green-600 dark:text-green-400 hover:underline">عرض الكل</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Incoming Mail Chart
    const incomingCtx = document.getElementById('incomingChart').getContext('2d');
    const incomingChart = new Chart(incomingCtx, {
        type: 'line',
        data: {
            labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
            datasets: [{
                label: 'البريد الوارد',
                data: <?= json_encode($incoming_data) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    rtl: true,
                    align: 'start',
                    labels: {
                        font: {
                            family: 'Tajawal'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Outgoing Mail Chart
    const outgoingCtx = document.getElementById('outgoingChart').getContext('2d');
    const outgoingChart = new Chart(outgoingCtx, {
        type: 'bar',
        data: {
            labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
            datasets: [{
                label: 'البريد الصادر',
                data: <?= json_encode($outgoing_data) ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    rtl: true,
                    align: 'start',
                    labels: {
                        font: {
                            family: 'Tajawal'
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Common months labels
    const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                   'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    // Branch Incoming Mail Chart
    const branchIncomingCtx = document.getElementById('branchIncomingChart');
    if (branchIncomingCtx) {
        new Chart(branchIncomingCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: <?= json_encode($branch_incoming_datasets) ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        rtl: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'Tajawal'
                            },
                            boxWidth: 12
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // Branch Outgoing Mail Chart
    const branchOutgoingCtx = document.getElementById('branchOutgoingChart');
    if (branchOutgoingCtx) {
        new Chart(branchOutgoingCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: <?= json_encode($branch_outgoing_datasets) ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        rtl: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'Tajawal'
                            },
                            boxWidth: 12
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }



</script>

<?php require_once '../includes/footer.php'; ?>