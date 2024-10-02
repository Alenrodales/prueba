<?php
include_once 'config.php';

class AdminAPI {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getUserByUsername($username) {
        $query = "SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.nombre_usuario = :nombre_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre_usuario", $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class PostAPI {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getPosts() {
        $query = "SELECT p.id, p.contenido, p.imagen, p.fecha_publicacion, u.nombre_usuario, u.foto_perfil
                  FROM publicaciones p
                  JOIN usuarios u ON p.usuario_id = u.id
                  ORDER BY p.fecha_publicacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPost($contenido, $usuario_id, $imagen = null) {
        $query = "INSERT INTO publicaciones (contenido, fecha_publicacion, usuario_id, imagen) VALUES (:contenido, NOW(), :usuario_id, :imagen)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":contenido", $contenido);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":imagen", $imagen);
        return $stmt->execute();
    }

    public function getUserPosts($usuario_id) {
        $query = "SELECT p.id, p.contenido, p.fecha_publicacion, u.nombre_usuario, p.imagen
                  FROM publicaciones p 
                  JOIN usuarios u ON p.usuario_id = u.id 
                  WHERE p.usuario_id = :usuario_id 
                  ORDER BY p.fecha_publicacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
