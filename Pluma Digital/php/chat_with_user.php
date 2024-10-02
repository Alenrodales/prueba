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

function getMessagesWithUser($conn, $currentUserId, $contactUserId) {
    $sql = "SELECT m.contenido, m.fecha_envio, u.nombre_usuario, u.foto_perfil 
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

function getUserProfile($conn, $nombre_usuario) {
    $query = "SELECT id, nombre_usuario, foto_perfil FROM usuarios WHERE nombre_usuario = :nombre_usuario";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":nombre_usuario", $nombre_usuario);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$contactUserId = $_GET['user_id'];
$messages = getMessagesWithUser($conn, $userProfile['id'], $contactUserId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message']) && !empty($_POST['message'])) {
        $message = $_POST['message'];
        $usuario_id = $userProfile['id'];
        $contactUserId = $_POST['contacto_id'];

        $sql = "INSERT INTO mensajes (contenido, usuario_id, receptor_id) VALUES (:contenido, :usuario_id, :receptor_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':contenido', $message);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':receptor_id', $contactUserId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Mensaje enviado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje']);
        }
        exit(); 
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'getMessages') {
    $messages = getMessagesWithUser($conn, $userProfile['id'], $contactUserId);
    echo json_encode($messages);
    exit(); 
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con <?php echo htmlspecialchars($contactUserId); ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        .sidebar {
            width: 75%;
            background-color: #f0f0f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        .message-input .file-icon, .message-input .send-icon {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 5px;
            margin-right: 10px;
            cursor: pointer;
        }

        .message-input textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
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

        .chat-panel {
            width: 25%;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            padding: 10px;
            border-left: 1px solid #ccc;
        }

        .chat-panel input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .recent {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .recent span {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .recent-circle {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            margin-bottom: 10px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatBox = document.getElementById('chat-box');
            const messageInput = document.getElementById('message-input');
            const chatForm = document.getElementById('chat-form');

            function fetchMessages() {
                fetch('chat_with_user.php?action=getMessages&user_id=<?php echo htmlspecialchars($contactUserId); ?>')
                    .then(response => response.json())
                    .then(messages => {
                        chatBox.innerHTML = ''; 
                        messages.forEach(message => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'message';
                            messageDiv.innerHTML = `
                                <div class="circle" style="background-image: url('${message.foto_perfil}');"></div>
                                <div class="message-content">
                                    <strong>${message.nombre_usuario}</strong>
                                    <p>${message.contenido}</p>
                                    <span class="timestamp">${new Date(message.fecha_envio).toLocaleString()}</span>
                                </div>
                            `;
                            chatBox.appendChild(messageDiv);
                        });
                        chatBox.scrollTop = chatBox.scrollHeight; 
                    })
                    .catch(error => {
                        console.error('Error al cargar los mensajes:', error);
                    });
            }

            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                fetch('chat_with_user.php', {
                    method: 'POST',
                    body: formData
                })
                
            });

            setInterval(fetchMessages, 1000);
            fetchMessages(); 
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="user-header">
                <div class="circle" style="background-image: url('<?php echo htmlspecialchars($userProfile['foto_perfil']); ?>');"></div>
                <div><?php echo htmlspecialchars($userProfile['nombre_usuario']); ?></div>
            </div>
            <div class="messages" id="chat-box">
            </div>
            <form id="chat-form">
                <input type="hidden" name="contacto_id" value="<?php echo htmlspecialchars($contactUserId); ?>">
                <div class="message-input">
                    <textarea id="message-input" name="message" placeholder="Escribe tu mensaje..."></textarea>
                    <button type="submit">Enviar</button>
                </div>
            </form>
        </div>
        <div class="chat-panel">
            <div class="recent">
                <span>Contactos recientes</span>
            </div>
        </div>
    </div>
</body>
</html>
