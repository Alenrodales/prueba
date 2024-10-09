<?php 
session_start();
require 'config.php';

if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

include_once 'api.php';

$postAPI = new PostAPI();
$conn = $postAPI->getConnection();

$userProfile = getUserProfile($conn, $_SESSION['nombre_usuario']);

function getMessagesWithUser($conn, $currentUserId, $contactUserId)
{
    $sql = "SELECT m.contenido, m.fecha_envio, u.nombre_usuario, u.foto_perfil, m.imagen 
            FROM mensajes m 
            JOIN usuarios u ON m.usuario_id = u.id 
            WHERE (m.usuario_id = :current_user_id AND m.receptor_id = :contact_user_id) 
               OR (m.usuario_id = :contact_user_id AND m.receptor_id = :current_user_id) 
            ORDER BY m.fecha_envio ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':current_user_id', $currentUserId);
    $stmt->bindParam(':contact_user_id', $contactUserId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserProfile($conn, $nombre_usuario)
{
    $query = "SELECT id, nombre_usuario, foto_perfil FROM usuarios WHERE nombre_usuario = :nombre_usuario";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":nombre_usuario", $nombre_usuario);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getContactProfile($conn, $contactUserId)
{
    $query = "SELECT nombre_usuario, foto_perfil FROM usuarios WHERE id = :contact_user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":contact_user_id", $contactUserId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$contactUserId = $_GET['user_id'];
$contactProfile = getContactProfile($conn, $contactUserId);
$messages = getMessagesWithUser($conn, $userProfile['id'], $contactUserId);

// Procesamiento del envío del mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message']) && !empty($_POST['message'])) {
        $message = $_POST['message'];
        $usuario_id = $userProfile['id'];
        $contactUserId = $_POST['contacto_id'];
        $imagePath = null;

        // Manejo de la imagen
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $targetDir = "../uploads/"; // Modificado para usar la carpeta plumadigital/uploads
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $targetFile = $targetDir . uniqid() . '.' . $imageFileType;

            // Validar el tipo de archivo (opcional)
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = $targetFile; // Guardar la ruta de la imagen
                } else {
                    echo "<script>alert('Error al subir la imagen');</script>";
                }
            } else {
                echo "<script>alert('Tipo de archivo no permitido');</script>";
            }
        }

        $sql = "INSERT INTO mensajes (contenido, usuario_id, receptor_id, imagen) VALUES (:contenido, :usuario_id, :receptor_id, :imagen)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':contenido', $message);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':receptor_id', $contactUserId);
        $stmt->bindParam(':imagen', $imagePath);

        if ($stmt->execute()) {
            echo "<script>window.location.href = 'chat_with_user.php?user_id=$contactUserId';</script>";
            exit();
        } else {
            echo "<script>alert('Error al enviar el mensaje');</script>";
        }
    } else {
        echo "<script>alert('El mensaje no puede estar vacío');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con <?php echo htmlspecialchars($contactProfile['nombre_usuario']); ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            background-color: #f0f0f0;
        }

        .user-header {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #fff;
            border-bottom: 1px solid #ccc;
        }

        .user-header .circle {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            margin-right: 10px;
            background-size: cover;
            background-position: center;
        }

        .messages {
            padding: 10px;
            flex: 1;
            overflow-y: auto;
            background-color: #fff;
        }

        .message {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .message .circle {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            margin-right: 10px;
            background-size: cover;
            background-position: center;
        }

        .message-content {
            display: flex;
            flex-direction: column;
        }

        .message-content strong {
            font-weight: bold;
        }

        .message-content p {
            margin: 5px 0;
        }

        .timestamp {
            font-size: 0.8em;
            color: #999;
        }

        .message-input {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #fff;
            border-top: 1px solid #ccc;
        }

        .message-input textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
        }

        .message-input input[type="file"] {
            display: none; /* Ocultar el input de tipo file */
        }

        .message-input .image-icon {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            cursor: pointer;
        }

        .message-input button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 5px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatBox = document.getElementById('chat-box');

            // Función para hacer scroll hasta abajo
            function scrollToBottom() {
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            // Mantener el scroll al final si no se ha movido manualmente
            let autoScroll = true;

            chatBox.addEventListener('scroll', function () {
                // Calcular la distancia entre el scroll actual y el fondo
                const distanceFromBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight;
                
                // Si la distancia es mayor que un pequeño margen, significa que el usuario ha interactuado con el scroll
                autoScroll = distanceFromBottom < 50; // 50px de margen
            });

            // Llamar al scroll a abajo al cargar la página
            scrollToBottom();

            // Al enviar un mensaje
            const form = document.getElementById('chat-form');
            form.addEventListener('submit', function () {
                setTimeout(() => {
                    if (autoScroll) {
                        scrollToBottom(); // Si el usuario no ha interactuado, hacer scroll abajo
                    }
                }, 100); // Asegurar que el DOM se actualice antes de hacer scroll
            });

            // Desplazar al final también cuando se cargan mensajes nuevos (si no se interactuó con el scroll)
            const observer = new MutationObserver(() => {
                if (autoScroll) {
                    scrollToBottom();
                }
            });

            observer.observe(chatBox, { childList: true }); // Observar si hay nuevos mensajes en el contenedor
        });

        // Función para abrir el selector de archivos
        function openFileSelector() {
            document.getElementById('file-input').click();
        }

        // Cambiar el ícono de imagen al seleccionar un archivo
        function updateImageIcon() {
            const fileInput = document.getElementById('file-input');
            const imageIcon = document.getElementById('image-icon');

            if (fileInput.files.length > 0) {
                imageIcon.src = '../imagenes.png';
            } else {
                imageIcon.src = '../imagenes2.png';
            }
        }
    </script>
</head>
<body>
    <div class="container">
    <div class="sidebar">
    <div class="user-header">
        <a href="chat.php">
            <img src="../flecha.png" alt="Volver" style="width: 30px; height: 30px; margin-right: 10px; cursor: pointer;">
        </a>
        <!-- Cambiar a `$contactProfile` para mostrar los datos del contacto -->
        <div class="circle" style="background-image: url('<?php echo htmlspecialchars($contactProfile['foto_perfil']); ?>');"></div>
        <h3><?php echo htmlspecialchars($contactProfile['nombre_usuario']); ?></h3>
    </div>
</div>

        <div class="messages" id="chat-box">
            <?php foreach ($messages as $message): ?>
                <div class="message">
                    <div class="circle" style="background-image: url('<?php echo htmlspecialchars($message['foto_perfil']); ?>');"></div>
                    <div class="message-content">
                        <strong><?php echo htmlspecialchars($message['nombre_usuario']); ?></strong>
                        <p><?php echo htmlspecialchars($message['contenido']); ?></p>
                        <?php if ($message['imagen']): ?>
                            <img src="<?php echo htmlspecialchars($message['imagen']); ?>" class="image-preview" alt="Imagen del mensaje">
                        <?php endif; ?>
                        <span class="timestamp"><?php echo date('d/m/Y H:i', strtotime($message['fecha_envio'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <form id="chat-form" class="message-input" method="POST" enctype="multipart/form-data">
            <img src="../imagenes2.png" class="image-icon" onclick="openFileSelector()" id="image-icon" alt="Subir imagen">
            <input id="file-input" type="file" name="image" accept="image/*" onchange="updateImageIcon()">
            <textarea name="message" rows="1" placeholder="Escribe tu mensaje..."></textarea>
            <input type="hidden" name="contacto_id" value="<?php echo htmlspecialchars($contactUserId); ?>">
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>
