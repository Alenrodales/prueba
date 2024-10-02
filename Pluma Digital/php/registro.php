<?php
require 'config.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['genero'];

    if (!preg_match("/^[a-zA-Z]+$/", $nombre)) {
        $error_message = "El nombre solo puede contener letras.";
    } elseif (!preg_match("/^[a-zA-Z]+$/", $apellido)) {
        $error_message = "El apellido solo puede contener letras.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=(?:.*\d){3}).{8,20}$/", $password)) {
        $error_message = "La contraseña debe contener al menos una letra mayúscula y tres números.";
    } elseif (new DateTime($fecha_nacimiento) < new DateTime('1924-01-01')) {
        $error_message = "La fecha de nacimiento debe ser posterior a 1924.";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $sql_check = "SELECT * FROM usuarios WHERE email = :email OR nombre_usuario = :nombre_usuario";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':email', $email);
        $stmt_check->bindParam(':nombre_usuario', $nombre_usuario);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            $error_message = "El correo electrónico o nombre de usuario ya está en uso.";
        } else {
            $sql_insert = "INSERT INTO usuarios (nombre, apellido, nombre_usuario, email, password, fecha_nacimiento, genero) 
                           VALUES (:nombre, :apellido, :nombre_usuario, :email, :password, :fecha_nacimiento, :genero)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bindParam(':nombre', $nombre);
            $stmt_insert->bindParam(':apellido', $apellido);
            $stmt_insert->bindParam(':nombre_usuario', $nombre_usuario);
            $stmt_insert->bindParam(':email', $email);
            $stmt_insert->bindParam(':password', $password_hashed);
            $stmt_insert->bindParam(':fecha_nacimiento', $fecha_nacimiento);
            $stmt_insert->bindParam(':genero', $genero);

            if ($stmt_insert->execute()) {
                echo "Registro exitoso";
            } else {
                echo "Error en el registro";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/registro.css">
    <title>Página de Registro</title>
</head>
<body>
    <div class="container">
        <a href="login.php" class="regreso">
            <img src="../flecha.png" alt="Volver al Login">
        </a>
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
                <form action="registro.php" method="post">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <input type="text" name="apellido" placeholder="Apellido" required>
                    <input type="text" name="nombre_usuario" placeholder="Nombre de Usuario" required>
                    <input type="email" name="email" placeholder="Correo Electrónico" required>
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <input type="date" name="fecha_nacimiento" required>
                    <select name="genero" required>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otros">Otros</option>
                    </select>
                    <button type="submit">Registrarse</button>
                </form>
                <?php if ($error_message): ?>
                    <div class="mensajedeerror"><?php echo $error_message; ?></div>
                <?php endif; ?>
            </div>
            <a href="#">Ayuda Técnica</a>
        </div>
    </div>    
    <footer>
        <p>&copy 2024 RedRocket. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
