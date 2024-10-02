<?php
include_once 'api.php';

$query = $_GET['q'] ?? '';
if (strlen($query) > 2) {
    $postAPI = new PostAPI();
    $conn = $postAPI->getConnection();

    $stmt = $conn->prepare("SELECT nombre_usuario, foto_perfil FROM usuarios WHERE nombre_usuario LIKE :query LIMIT 10");
    $stmt->bindValue(':query', "%$query%");
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
}
?>
