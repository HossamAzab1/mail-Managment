<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Set default year to current year
$current_year = date('Y');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Get counts for the selected year
$mail_in_count = $pdo->prepare("SELECT COUNT(*) FROM mail_in WHERE YEAR(date) = ?");
$mail_in_count->execute([$selected_year]);
$mail_in_count = $mail_in_count->fetchColumn();

$mail_out_count = $pdo->prepare("SELECT COUNT(*) FROM mail_out WHERE YEAR(date) = ?");
$mail_out_count->execute([$selected_year]);
$mail_out_count = $mail_out_count->fetchColumn();

// Get highest month (both in and out)
$highest_month = $pdo->prepare("
    SELECT month, MAX(total) as max_count FROM (
        SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as total 
        FROM mail_in 
        WHERE YEAR(date) = ?
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        
        UNION ALL
        
        SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as total 
        FROM mail_out 
        WHERE YEAR(date) = ?
        GROUP BY DATE_FORMAT(date, '%Y-%m')
    ) as combined
    GROUP BY month
    ORDER BY max_count DESC
    LIMIT 1
");
$highest_month->execute([$selected_year, $selected_year]);
$highest_month = $highest_month->fetch();

// Get top senders (in only)
$top_senders_in = $pdo->prepare("
    SELECT p.name, COUNT(*) as count 
    FROM mail_in mi
    JOIN persons p ON mi.sender_id = p.id
    WHERE YEAR(mi.date) = ?
    GROUP BY p.name
    ORDER BY count DESC
    LIMIT 5
");
$top_senders_in->execute([$selected_year]);
$top_senders_in = $top_senders_in->fetchAll();

// Get top senders (out only)
$top_senders_out = $pdo->prepare("
    SELECT p.name, COUNT(*) as count 
    FROM mail_out mo
    JOIN persons p ON mo.sender_id = p.id
    WHERE YEAR(mo.date) = ?
    GROUP BY p.name
    ORDER BY count DESC
    LIMIT 5
");
$top_senders_out->execute([$selected_year]);
$top_senders_out = $top_senders_out->fetchAll();

// Get top receivers (in only)
$top_receivers_in = $pdo->prepare("
    SELECT p.name, COUNT(*) as count 
    FROM mail_in mi
    JOIN persons p ON mi.receiver_id = p.id
    WHERE YEAR(mi.date) = ?
    GROUP BY p.name
    ORDER BY count DESC
    LIMIT 5
");
$top_receivers_in->execute([$selected_year]);
$top_receivers_in = $top_receivers_in->fetchAll();

// Get top receivers (out only)
$top_receivers_out = $pdo->prepare("
    SELECT p.name, COUNT(*) as count 
    FROM mail_out mo
    JOIN persons p ON mo.receiver_id = p.id
    WHERE YEAR(mo.date) = ?
    GROUP BY p.name
    ORDER BY count DESC
    LIMIT 5
");
$top_receivers_out->execute([$selected_year]);
$top_receivers_out = $top_receivers_out->fetchAll();

// Get mail by month (in and out separately)
$mail_in_by_month = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as count
    FROM mail_in
    WHERE YEAR(date) = ?
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month
");
$mail_in_by_month->execute([$selected_year]);
$mail_in_by_month = $mail_in_by_month->fetchAll();

$mail_out_by_month = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as count
    FROM mail_out
    WHERE YEAR(date) = ?
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month
");
$mail_out_by_month->execute([$selected_year]);
$mail_out_by_month = $mail_out_by_month->fetchAll();

// Get mail by branch (in and out)
$mail_by_branch_in = $pdo->prepare("
    SELECT b.name, COUNT(*) as count
    FROM mail_in mi
    JOIN branches b ON mi.from_branch_id = b.id
    WHERE YEAR(mi.date) = ?
    GROUP BY b.name
    ORDER BY count DESC
");
$mail_by_branch_in->execute([$selected_year]);
$mail_by_branch_in = $mail_by_branch_in->fetchAll();

$mail_by_branch_out = $pdo->prepare("
    SELECT b.name, COUNT(*) as count
    FROM mail_out mo
    JOIN branches b ON mo.to_branch_id = b.id
    WHERE YEAR(mo.date) = ?
    GROUP BY b.name
    ORDER BY count DESC
");
$mail_by_branch_out->execute([$selected_year]);
$mail_by_branch_out = $mail_by_branch_out->fetchAll();

// Get branch-month matrix data
$branch_month_matrix = $pdo->prepare("
    SELECT 
        b.name as branch_name,
        DATE_FORMAT(m.date, '%Y-%m') as month,
        COUNT(*) as count
    FROM (
        SELECT from_branch_id as branch_id, date FROM mail_in WHERE YEAR(date) = ?
        UNION ALL
        SELECT to_branch_id as branch_id, date FROM mail_out WHERE YEAR(date) = ?
    ) as m
    JOIN branches b ON m.branch_id = b.id
    GROUP BY b.name, DATE_FORMAT(m.date, '%Y-%m')
    ORDER BY branch_name, month
");
$branch_month_matrix->execute([$selected_year, $selected_year]);
$branch_month_data = $branch_month_matrix->fetchAll();

// Prepare branch-month matrix for display
$branches = array_unique(array_column($branch_month_data, 'branch_name'));
$months = array_unique(array_column($branch_month_data, 'month'));
sort($months);

$matrix = [];
foreach ($branches as $branch) {
    $matrix[$branch] = [];
    foreach ($months as $month) {
        $matrix[$branch][$month] = 0;
    }
}

foreach ($branch_month_data as $row) {
    $matrix[$row['branch_name']][$row['month']] = $row['count'];
}
?>

<h2 class="text-2xl font-bold mb-6">لوحة التحكم</h2>

<!-- Year selector -->
<div class="mb-6">
    <form method="get" class="flex items-center gap-4">
        <select name="year" onchange="this.form.submit()" class="p-2 border rounded dark:bg-gray-800 dark:border-gray-700">
            <?php for ($year = $current_year; $year >= $current_year - 5; $year--): ?>
                <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>><?= $year ?></option>
            <?php endfor; ?>
        </select>
        <span>اختر السنة:</span>
    </form>
</div>

<!-- Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-blue-100 dark:bg-blue-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">البريد الوارد</h3>
        <p class="text-3xl font-bold"><?= $mail_in_count ?></p>
    </div>
    <div class="bg-green-100 dark:bg-green-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">البريد الصادر</h3>
        <p class="text-3xl font-bold"><?= $mail_out_count ?></p>
    </div>
    <div class="bg-yellow-100 dark:bg-yellow-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">أعلى شهر</h3>
        <p class="text-xl font-bold"><?= $highest_month['month'] ?? 'N/A' ?></p>
        <div class="grid grid-cols-2 gap-6">
        <p class="text-sm"><?= $highest_month['max_count'] ?? 0 ?> مراسلات</p>
        <!-- <p class="text-sm"><?= $highest_month_out['max_count'] ?? 0 ?> صادر</p> -->

        </div>
    </div>
    <div class="bg-purple-100 dark:bg-purple-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">أكثر المرسلين (وارد)</h3>
        <p class="text-xl font-bold"><?= $top_senders_in[0]['name'] ?? 'N/A' ?></p>
        <p class="text-sm"><?= $top_senders_in[0]['count'] ?? 0 ?> مراسلات</p>
    </div>
    <div class="bg-red-100 dark:bg-red-900 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-bold mb-2">أكثر المستلمين (صادر)</h3>
        <p class="text-xl font-bold"><?= $top_receivers_out[0]['name'] ?? 'N/A' ?></p>
        <p class="text-sm"><?= $top_receivers_out[0]['count'] ?? 0 ?> مراسلات</p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold mb-4">البريد حسب الشهر</h3>
        <canvas id="mailByMonthChart"></canvas>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold mb-4">البريد حسب الفرع</h3>
        <canvas id="mailByBranchChart"></canvas>
    </div>
</div>

<!-- Senders and Receivers Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Senders Tables -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">أكثر المرسلين (وارد)</h3>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-200 dark:bg-gray-700">
                        <th class="p-3 text-right">الاسم</th>
                        <th class="p-3 text-right">عدد المراسلات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_senders_in as $sender): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="p-3"><?= $sender['name'] ?></td>
                        <td class="p-3"><?= $sender['count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">أكثر المرسلين (صادر)</h3>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-200 dark:bg-gray-700">
                        <th class="p-3 text-right">الاسم</th>
                        <th class="p-3 text-right">عدد المراسلات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_senders_out as $sender): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="p-3"><?= $sender['name'] ?></td>
                        <td class="p-3"><?= $sender['count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Receivers Tables -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">أكثر المستلمين (وارد)</h3>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-200 dark:bg-gray-700">
                        <th class="p-3 text-right">الاسم</th>
                        <th class="p-3 text-right">عدد المراسلات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_receivers_in as $receiver): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="p-3"><?= $receiver['name'] ?></td>
                        <td class="p-3"><?= $receiver['count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">أكثر المستلمين (صادر)</h3>
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-200 dark:bg-gray-700">
                        <th class="p-3 text-right">الاسم</th>
                        <th class="p-3 text-right">عدد المراسلات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_receivers_out as $receiver): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="p-3"><?= $receiver['name'] ?></td>
                        <td class="p-3"><?= $receiver['count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Incoming Mail Branch-Month Matrix Table -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-8 overflow-x-auto">
    <h3 class="text-xl font-bold mb-4">إحصائيات البريد الوارد حسب الفرع والشهر لسنة <?= $selected_year ?></h3>
    <table class="w-full">
        <thead>
            <tr class="bg-gray-200 dark:bg-gray-700">
                <th class="p-3 text-right">الفرع</th>
                <?php foreach ($months as $month): ?>
                    <th class="p-3 text-right"><?= $month ?></th>
                <?php endforeach; ?>
                <th class="p-3 text-right">المجموع</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Get incoming mail data by branch and month
            $incoming_matrix = [];
            $incoming_data = $pdo->prepare("
                SELECT b.name as branch_name, 
                       DATE_FORMAT(mi.date, '%Y-%m') as month, 
                       COUNT(*) as count
                FROM mail_in mi
                JOIN branches b ON mi.from_branch_id = b.id
                WHERE YEAR(mi.date) = ?
                GROUP BY b.name, DATE_FORMAT(mi.date, '%Y-%m')
                ORDER BY branch_name, month
            ");
            $incoming_data->execute([$selected_year]);
            $incoming_results = $incoming_data->fetchAll();
            
            // Prepare incoming matrix
            foreach ($branches as $branch) {
                $incoming_matrix[$branch] = array_fill_keys($months, 0);
            }
            foreach ($incoming_results as $row) {
                $incoming_matrix[$row['branch_name']][$row['month']] = $row['count'];
            }
            
            foreach ($branches as $branch): 
                $branch_total = array_sum($incoming_matrix[$branch]);
            ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3 font-bold"><?= $branch ?></td>
                    <?php foreach ($months as $month): ?>
                        <td class="p-3"><?= $incoming_matrix[$branch][$month] ?></td>
                    <?php endforeach; ?>
                    <td class="p-3 font-bold"><?= $branch_total ?></td>
                </tr>
            <?php endforeach; ?>
            <!-- Totals row -->
            <tr class="bg-gray-200 dark:bg-gray-700 font-bold">
                <td class="p-3">المجموع</td>
                <?php 
                $incoming_month_totals = [];
                foreach ($months as $month) {
                    $month_total = 0;
                    foreach ($branches as $branch) {
                        $month_total += $incoming_matrix[$branch][$month];
                    }
                    $incoming_month_totals[$month] = $month_total;
                }
                ?>
                <?php foreach ($months as $month): ?>
                    <td class="p-3"><?= $incoming_month_totals[$month] ?></td>
                <?php endforeach; ?>
                <td class="p-3"><?= array_sum($incoming_month_totals) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Outgoing Mail Branch-Month Matrix Table -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-8 overflow-x-auto">
    <h3 class="text-xl font-bold mb-4">إحصائيات البريد الصادر حسب الفرع والشهر لسنة <?= $selected_year ?></h3>
    <table class="w-full">
        <thead>
            <tr class="bg-gray-200 dark:bg-gray-700">
                <th class="p-3 text-right">الفرع</th>
                <?php foreach ($months as $month): ?>
                    <th class="p-3 text-right"><?= $month ?></th>
                <?php endforeach; ?>
                <th class="p-3 text-right">المجموع</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Get outgoing mail data by branch and month
            $outgoing_matrix = [];
            $outgoing_data = $pdo->prepare("
                SELECT b.name as branch_name, 
                       DATE_FORMAT(mo.date, '%Y-%m') as month, 
                       COUNT(*) as count
                FROM mail_out mo
                JOIN branches b ON mo.to_branch_id = b.id
                WHERE YEAR(mo.date) = ?
                GROUP BY b.name, DATE_FORMAT(mo.date, '%Y-%m')
                ORDER BY branch_name, month
            ");
            $outgoing_data->execute([$selected_year]);
            $outgoing_results = $outgoing_data->fetchAll();
            
            // Prepare outgoing matrix
            foreach ($branches as $branch) {
                $outgoing_matrix[$branch] = array_fill_keys($months, 0);
            }
            foreach ($outgoing_results as $row) {
                $outgoing_matrix[$row['branch_name']][$row['month']] = $row['count'];
            }
            
            foreach ($branches as $branch): 
                $branch_total = array_sum($outgoing_matrix[$branch]);
            ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <td class="p-3 font-bold"><?= $branch ?></td>
                    <?php foreach ($months as $month): ?>
                        <td class="p-3"><?= $outgoing_matrix[$branch][$month] ?></td>
                    <?php endforeach; ?>
                    <td class="p-3 font-bold"><?= $branch_total ?></td>
                </tr>
            <?php endforeach; ?>
            <!-- Totals row -->
            <tr class="bg-gray-200 dark:bg-gray-700 font-bold">
                <td class="p-3">المجموع</td>
                <?php 
                $outgoing_month_totals = [];
                foreach ($months as $month) {
                    $month_total = 0;
                    foreach ($branches as $branch) {
                        $month_total += $outgoing_matrix[$branch][$month];
                    }
                    $outgoing_month_totals[$month] = $month_total;
                }
                ?>
                <?php foreach ($months as $month): ?>
                    <td class="p-3"><?= $outgoing_month_totals[$month] ?></td>
                <?php endforeach; ?>
                <td class="p-3"><?= array_sum($outgoing_month_totals) ?></td>
            </tr>
        </tbody>
    </table>
</div>


<?php
// echo '<pre>';
// print_r($branch_incoming_chart_data);
// echo '</pre>';
?>


<script>
    // Prepare month labels - get all unique months from both datasets
    const allMonths = Array.from(new Set([
        ...<?= json_encode(array_column($mail_in_by_month, 'month')) ?>,
        ...<?= json_encode(array_column($mail_out_by_month, 'month')) ?>
    ])).sort();

    // Create maps for quick lookup
    const mailInData = <?= json_encode(array_column($mail_in_by_month, 'count', 'month')) ?>;
    const mailOutData = <?= json_encode(array_column($mail_out_by_month, 'count', 'month')) ?>;

    // Prepare datasets
    const mailInCounts = allMonths.map(month => mailInData[month] || 0);
    const mailOutCounts = allMonths.map(month => mailOutData[month] || 0);

    // Mail by month chart (combined in and out)
    const mailByMonthCtx = document.getElementById('mailByMonthChart').getContext('2d');
    const mailByMonthChart = new Chart(mailByMonthCtx, {
        type: 'line',
        data: {
            labels: allMonths,
            datasets: [
                {
                    label: 'البريد الوارد',
                    data: mailInCounts,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'البريد الصادر',
                    data: mailOutCounts,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }
            ]
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


    // Prepare branch data for the chart
    <?php
    // Get all unique branch names from both datasets
    $branchNamesIn = array_column($mail_by_branch_in, 'name');
    $branchNamesOut = array_column($mail_by_branch_out, 'name');
    $allBranchNames = array_unique(array_merge($branchNamesIn, $branchNamesOut));
    $allBranchNames = array_values($allBranchNames); // Re-index the array
    ?>

    // Create lookup objects for branch counts
    const branchInData = <?= json_encode(array_column($mail_by_branch_in, 'count', 'name')) ?>;
    const branchOutData = <?= json_encode(array_column($mail_by_branch_out, 'count', 'name')) ?>;

    // Prepare datasets aligned with the labels
    const branchLabels = <?= json_encode($allBranchNames) ?>;
    const branchInCounts = branchLabels.map(branch => branchInData[branch] || 0);
    const branchOutCounts = branchLabels.map(branch => branchOutData[branch] || 0);

    // Mail by branch chart (combined in and out)
    const mailByBranchCtx = document.getElementById('mailByBranchChart').getContext('2d');
    const mailByBranchChart = new Chart(mailByBranchCtx, {
        type: 'bar',
        data: {
            labels: branchLabels,
            datasets: [
                {
                    label: 'البريد الوارد',
                    data: branchInCounts,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 0
                },
                {
                    label: 'البريد الصادر',
                    data: branchOutCounts,
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 0
                }
            ]
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