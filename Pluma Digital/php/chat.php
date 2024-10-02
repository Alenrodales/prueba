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

function getMessages($conn) {
    $sql = "SELECT m.contenido, m.fecha_envio, u.nombre_usuario, u.foto_perfil 
            FROM mensajes m 
            JOIN usuarios u ON m.usuario_id = u.id 
            ORDER BY m.fecha_envio ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserProfile($conn, $nombre_usuario) {
    $query = "SELECT id, nombre_usuario, foto_perfil, rol_id FROM usuarios WHERE nombre_usuario = :nombre_usuario";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":nombre_usuario", $nombre_usuario);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getContacts($conn, $currentUserId) {
    $sql = "SELECT id, nombre_usuario, foto_perfil FROM usuarios WHERE id != :current_user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':current_user_id', $currentUserId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$contacts = getContacts($conn, $userProfile['id']);


function getOtherUsers($conn, $current_user_id) {
    $query = "SELECT id, nombre_usuario, foto_perfil FROM usuarios WHERE id != :current_user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":current_user_id", $current_user_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getChatWithUser($conn, $current_user_id, $other_user_id) {
    $sql = "SELECT m.contenido, m.fecha_envio, u.nombre_usuario, u.foto_perfil 
            FROM mensajes m 
            JOIN usuarios u ON m.usuario_id = u.id 
            WHERE (m.usuario_id = :current_user_id AND m.destinatario_id = :other_user_id) 
            OR (m.usuario_id = :other_user_id AND m.destinatario_id = :current_user_id)
            ORDER BY m.fecha_envio ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':current_user_id', $current_user_id);
    $stmt->bindParam(':other_user_id', $other_user_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$messages = getMessages($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message']) && !empty($_POST['message']) && isset($_POST['destinatario_id'])) {
        $message = $_POST['message'];
        $usuario_id = $userProfile['id'];
        $destinatario_id = $_POST['destinatario_id'];

        $sql = "INSERT INTO mensajes (contenido, usuario_id, destinatario_id) VALUES (:contenido, :usuario_id, :destinatario_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':contenido', $message);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':destinatario_id', $destinatario_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Mensaje enviado con éxito']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje']);
            exit();
        }
    }
}

// Endpoint para obtener mensajes en formato JSON
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'getMessages') {
        $messages = getMessages($conn);
        echo json_encode($messages);
        exit();
    } elseif ($_GET['action'] == 'getOtherUsers') {
        $otherUsers = getOtherUsers($conn, $userProfile['id']);
        echo json_encode($otherUsers);
        exit();
    } elseif ($_GET['action'] == 'getChat') {
        $other_user_id = $_GET['other_user_id'];
        $chatMessages = getChatWithUser($conn, $userProfile['id'], $other_user_id);
        echo json_encode($chatMessages);
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="css/styles2.css">
    <style>
        /* Estilos del chat */
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
               /* Aquí puedes agregar los estilos CSS anteriores */
        /* Añadimos estilos específicos para la lista de contactos */
        .user-list {
            display: flex;
            flex-direction: column;
            padding: 10px;
        }

        .user-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .user-item .circle {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            margin-right: 10px;
            background-size: cover;
            background-position: center;
        }

        .user-item span {
            font-size: 1em;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatBox = document.getElementById('chat-box');
            const messageInput = document.getElementById('message-input');
            const chatForm = document.getElementById('chat-form');
            const userList = document.getElementById('user-list');
            let currentChatUserId = null;

            function fetchMessages() {
                if (currentChatUserId === null) return;

                fetch(`chat.php?action=getChat&other_user_id=${currentChatUserId}`)
                    .then(response => response.json())
                    .then(messages => {
                        chatBox.innerHTML = ''; // Limpiar el chat
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
                        chatBox.scrollTop = chatBox.scrollHeight; // Desplazar hacia abajo
                    })
                    .catch(error => console.error('Error al obtener mensajes:', error));
            }

            function fetchOtherUsers() {
                fetch('chat.php?action=getOtherUsers')
                    .then(response => response.json())
                    .then(users => {
                        userList.innerHTML = ''; // Limpiar la lista de usuarios
                        users.forEach(user => {
                            const userItem = document.createElement('div');
                            userItem.className = 'user-item';
                            userItem.innerHTML = `
                                <div class="circle" style="background-image: url('${user.foto_perfil}');"></div>
                                <span>${user.nombre_usuario}</span>
                            `;
                            userItem.addEventListener('click', function () {
                                currentChatUserId = user.id;
                                fetchMessages(); // Cargar mensajes con el usuario seleccionado
                            });
                            userList.appendChild(userItem);
                        });
                    })
                    .catch(error => console.error('Error al obtener otros usuarios:', error));
            }

            chatForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (currentChatUserId === null) {
                    console.error('Selecciona un contacto para chatear.');
                    return;
                }

                const message = messageInput.value;

                fetch('chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({ message: message, destinatario_id: currentChatUserId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = ''; // Limpiar el campo de entrada
                        fetchMessages(); // Actualizar los mensajes
                    } else {
                        console.error('Error:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error de red:', error);
                });
            });

            // Actualizar mensajes cada 5 segundos
            setInterval(fetchMessages, 5000);

            // Inicializar mensajes y lista de contactos al cargar
            fetchMessages();
            fetchOtherUsers();
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="user-header">
                <div class="circle" style="background-image: url('<?php echo htmlspecialchars($userProfile['foto_perfil']); ?>');"></div>
                <span><?php echo htmlspecialchars($userProfile['nombre_usuario']); ?></span>
            </div>
            <div class="messages" id="chat-box">
                <!-- Los mensajes se cargarán aquí -->
            </div>
            <form id="chat-form" method="POST" action="">
                <div class="message-input">
                    <div class="file-icon"></div>
                    <textarea name="message" id="message-input" placeholder="Escribe un mensaje..."></textarea>
                    <button type="submit" class="send-icon">Enviar</button>
                </div>
            </form>
        </div>
        <div class="chat-panel">
            <input type="text" placeholder="Buscar contacto...">
            <div class="recent">
    <span>Contactos recientes</span>
    <?php foreach ($contacts as $contact): ?>
        <a href="chat_with_user.php?user_id=<?php echo htmlspecialchars($contact['id']); ?>">
            <div class="recent-circle" style="background-image: url('<?php echo htmlspecialchars($contact['foto_perfil']); ?>');"></div>
        </a>
    <?php endforeach; ?>
</div>

        </div>
    </div>
</body>
</html>
