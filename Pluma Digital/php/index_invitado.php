<?php
session_start();

require 'api.php'; 

if (isset($_GET['logout'])) {
    eliminarUsuarioInvitado();
    
    session_unset();
    session_destroy();
    
    header("Location: login.php");
    exit();
}

function eliminarUsuarioInvitado() {
    global $conn;
    
    $sql_delete = "DELETE FROM usuarios WHERE nombre_usuario LIKE 'invitado%'";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->execute();
}

$postAPI = new PostAPI();
$posts = $postAPI->getPosts(); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="icon" href="icono5.ico" type="image/x-icon">
    <title>Pluma Digital</title>
    <style>
        .timer {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 18px;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="timer" id="timer">01:30</div>
    <div class="header">
        <div class="usuario">
            <span class="usuario1"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></span>
        </div>
        <div class="logocuadroinvitado">
            <a href="index.php">
                <img src="../logo.png" alt="Logo" class="logo">
            </a>
            <span class="nombreweb">Pluma Digital</span>
        </div>
        <div class="cerrar-sesion">
            <a href="?logout=1">Cerrar Sesión</a>
        </div>
    </div>

    <h2>Publicaciones</h2>

    <?php if (!empty($posts)): ?>
        <div class="publicaciones">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <hr>
                    <h3><?php echo htmlspecialchars($post['nombre_usuario']); ?></h3>
                    <p><?php echo htmlspecialchars($post['contenido']); ?></p>
                    <small><?php echo htmlspecialchars($post['fecha_publicacion']); ?></small>
                    <hr>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay publicaciones disponibles.</p>
    <?php endif; ?>

    <footer>
        <p>© 2024 RedRocket. Todos los derechos reservados.</p>
    </footer>
    <script>
        let timer = document.getElementById('timer');
        let timeLeft = 90; 
        function updateTimer() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            if (seconds < 10) {
                seconds = '0' + seconds;
            }
            timer.textContent = minutes + ':' + seconds;
            timeLeft--;

            if (timeLeft < 0) {
                window.location.href = 'login.php';
            }
        }

        setInterval(updateTimer, 1000);
    </script>
</body>
</html>
