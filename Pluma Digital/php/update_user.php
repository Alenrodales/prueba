<?php
session_start();
require 'C:/xampp/htdocs/Pluma Digital/php/config.php'; 

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $newPassword = $_POST['new_password'];
    $deleteProfilePic = isset($_POST['delete_profile_pic']) ? 1 : 0;
    $role = $_POST['role'];

    try {
        $conn->beginTransaction();

        if (!empty($newPassword)) {
            $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', password_hash($newPassword, PASSWORD_DEFAULT));
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        }

        if ($deleteProfilePic) {
            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = NULL WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        }

        if (!empty($role)) {
            $stmt = $conn->prepare("UPDATE usuarios SET rol_id = :role WHERE id = :user_id");
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        }

        $conn->commit();

        header('Location: admin_panel_updated.php?update=success');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
