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
        $query = "SELECT p.id, p.contenido, p.imagen, p.fecha_publicacion, u.nombre_usuario, u.foto_perfil, COUNT(l.id) AS like_count
                  FROM publicaciones p
                  JOIN usuarios u ON p.usuario_id = u.id
                  LEFT JOIN likes l ON p.id = l.publicacion_id
                  GROUP BY p.id
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

class LikeAPI { 
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function likePost($usuario_id, $publicacion_id) {
        // Verifica si el usuario ya ha dado like a la publicación
        $query = "SELECT COUNT(*) FROM likes WHERE usuario_id = :usuario_id AND publicacion_id = :publicacion_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':publicacion_id', $publicacion_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // El usuario ya ha dado like a la publicación
            return false; // O maneja esto como quieras
        }
    
        // Si no ha dado like, insertar nuevo like
        $insertQuery = "INSERT INTO likes (usuario_id, publicacion_id) VALUES (:usuario_id, :publicacion_id)";
        $insertStmt = $this->conn->prepare($insertQuery);
        $insertStmt->bindParam(':usuario_id', $usuario_id);
        $insertStmt->bindParam(':publicacion_id', $publicacion_id);
        return $insertStmt->execute();
    }
    
    

    public function unlikePost($usuario_id, $publicacion_id) {
        $query = "DELETE FROM likes WHERE usuario_id = :usuario_id AND publicacion_id = :publicacion_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":publicacion_id", $publicacion_id);
        if ($stmt->execute()) {
            // Decrementar el conteo de likes
            $this->updateLikeCount($publicacion_id, -1);
            return true;
        } else {
            return false;
        }
    }

    private function updateLikeCount($publicacion_id, $value) {
        $query = "UPDATE publicaciones SET like_count = like_count + :value WHERE id = :publicacion_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":value", $value);
        $stmt->bindParam(":publicacion_id", $publicacion_id);
        if (!$stmt->execute()) {
            echo "Error al actualizar el conteo de likes: " . implode(", ", $stmt->errorInfo());
        }
    }
    

    public function userLikedPost($usuario_id, $publicacion_id) {
        $query = "SELECT COUNT(*) FROM likes WHERE usuario_id = :usuario_id AND publicacion_id = :publicacion_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->bindParam(":publicacion_id", $publicacion_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }


}

