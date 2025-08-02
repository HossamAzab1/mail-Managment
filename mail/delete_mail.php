<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Check if required parameters exist
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    header('Location: out.php'); // Default fallback
    exit;
}

$id = $_GET['id'];
$type = $_GET['type'];

// Validate and sanitize the type
$validTypes = ['in', 'out'];
if (!in_array($type, $validTypes)) {
    $_SESSION['error'] = "نوع البريد غير صحيح";
    header('Location: out.php');
    exit;
}

try {
    // Determine which table to use based on mail type
    $table = ($type === 'in') ? 'mail_in' : 'mail_out';
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = ($type === 'in') 
        ? "تم حذف البريد الوارد بنجاح" 
        : "تم حذف البريد الصادر بنجاح";
        
} catch (PDOException $e) {
    $_SESSION['error'] = "حدث خطأ أثناء حذف البريد: " . $e->getMessage();
}

// Redirect to the appropriate page based on mail type
$redirectPage = ($type === 'in') ? 'in.php' : 'out.php';
header("Location: $redirectPage");
exit;
?>