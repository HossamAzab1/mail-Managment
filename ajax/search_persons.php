<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? $_GET['term'] : '';

try {
    $stmt = $pdo->prepare("SELECT id, name FROM persons WHERE name LIKE :term ORDER BY name");
    $stmt->execute(['term' => '%' . $term . '%']);
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($persons);
} catch (PDOException $e) {
    echo json_encode([]);
}


?>
