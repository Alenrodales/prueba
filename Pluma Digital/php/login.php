    <?php
    session_start();
    require 'config.php';

    function generarNombreUsuarioInvitado() {
        return 'invitado_' . time();
    }

    function crearUsuarioInvitado($conn) {
        $sql_delete_posts = "DELETE FROM publicaciones WHERE usuario_id IN (SELECT id FROM usuarios WHERE nombre_usuario LIKE 'invitado%')";
        $stmt_delete_posts = $conn->prepare($sql_delete_posts);
        $stmt_delete_posts->execute();

        $sql_delete_users = "DELETE FROM usuarios WHERE nombre_usuario LIKE 'invitado%'";
        $stmt_delete_users = $conn->prepare($sql_delete_users);
        $stmt_delete_users->execute();

        $nombre_usuario = generarNombreUsuarioInvitado();
        $rol = 4; 

        $sql_insert = "INSERT INTO usuarios (nombre_usuario, rol_id) VALUES (:nombre_usuario, :rol_id)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':nombre_usuario', $nombre_usuario);
        $stmt_insert->bindParam(':rol_id', $rol);

        if ($stmt_insert->execute()) {
            $_SESSION['nombre_usuario'] = $nombre_usuario; 
            $_SESSION['rol_id'] = $rol; 
            header("Location: index_invitado.php");
            exit();
        } else {
            echo "Error al crear el usuario invitado: " . $stmt_insert->errorInfo()[2];
        }
    }

    if (isset($_GET['invitado'])) {
        crearUsuarioInvitado($conn);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT id, nombre_usuario, rol_id, password FROM usuarios WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
            $_SESSION['rol_id'] = $user['rol_id'];

            if ($user['rol_id'] == 4) {
                header("Location: index_invitado.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "Correo o contraseña incorrecta";
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../css/login.css">
        <link rel="icon" href="icono5.ico" type="image/x-icon">
        <title>Pluma Digital</title>
    </head>
    <body>
        <div class="container">
            <div class="izquierda">
                <div class="logoytexto">
                    <img src="../logo.png" alt="Logo">
                    <h1>Pluma Digital</h1>
                </div>
                <p class="description">"Donde las palabras vuelan libres y la literatura cobra vida."</p>
            </div>
            <div class="derecha">
                <div class="logoytexto-responsive">
                    <img src="logo.png" alt="Logo">
                    <h1>Pluma Digital</h1>
                    <p class="description">"Donde las palabras vuelan libres y la literatura cobra vida."</p>
                </div>
                <div class="formulario">
                    <form action="" method="post">
                        <input type="email" name="email" placeholder="Correo Electrónico" required>
                        <input type="password" name="password" placeholder="Contraseña" required>
                        <button type="submit">Iniciar Sesión</button>
                    </form>
                    <a href="#">¿Has olvidado tu contraseña?</a>
                    <a href="registro.php">Registrarse</a>
                    <a href="?invitado=1">Iniciar Sesión como Invitado</a>
                </div>
                <a href="#">Ayuda Técnica</a>
            </div>
        </div>
        <footer>
            <p>© 2024 RedRocket. Todos los derechos reservados.</p>
        </footer>
    </body>
    </html>
