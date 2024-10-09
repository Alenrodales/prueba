<?php
include 'C:/xampp/htdocs/Pluma Digital/php/config.php';

$database = new Database();
$conn = $database->getConnection();

$sql = "SELECT id, nombre FROM roles";
$stmt = $conn->prepare($sql);
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($roles);
?>
