<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection test
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=mail_management;charset=utf8mb4', 'root', '');
    $db->setAttribute(3, 2);
    $db_status = "✅ Connected successfully";
} catch (PDOException $e) {
    $db_status = "❌ Connection failed: " . $e->getMessage();
}

// Get server information
$server_info = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'],
    'Server Name' => $_SERVER['SERVER_NAME'],
    'Document Root' => $_SERVER['DOCUMENT_ROOT'],
    'Current User' => get_current_user(),
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time')
];

// Test database queries
$test_queries = [
    'mail_in' => "SELECT COUNT(*) as count FROM mail_in",
    'mail_out' => "SELECT COUNT(*) as count FROM mail_out",
    'branches' => "SELECT COUNT(*) as count FROM branches",
    'persons' => "SELECT COUNT(*) as count FROM persons"
];

$query_results = [];
foreach ($test_queries as $name => $query) {
    try {
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $query_results[$name] = $result['count'] ?? 'N/A';
    } catch (PDOException $e) {
        $query_results[$name] = "Error: " . $e->getMessage();
    }
}

// Session information (if applicable)
$session_info = isset($_SESSION) ? $_SESSION : 'No session data available';
?>

<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">System Debug Information</h1>
    
    <!-- Database Connection Status -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Database Connection</h2>
        <p><?= $db_status ?></p>
    </div>
    
    <!-- Server Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Server Information</h2>
        <table class="min-w-full">
            <?php foreach ($server_info as $key => $value): ?>
                <tr class="border-b">
                    <td class="py-2 font-medium"><?= $key ?></td>
                    <td class="py-2"><?= $value ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- Database Tables Status -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Database Tables</h2>
        <table class="min-w-full">
            <?php foreach ($query_results as $table => $count): ?>
                <tr class="border-b">
                    <td class="py-2 font-medium"><?= ucfirst($table) ?></td>
                    <td class="py-2"><?= $count ?> records</td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- Session Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Session Data</h2>
        <pre class="bg-gray-100 p-4 rounded overflow-x-auto"><?= print_r($session_info, true) ?></pre>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>