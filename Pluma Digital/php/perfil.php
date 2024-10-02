<?php
session_start();
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "plumadigital1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$usuario_nombre = isset($_GET['usuario']) ? $_GET['usuario'] : $_SESSION['nombre_usuario'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil'])) {
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES["foto_perfil"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["foto_perfil"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "El archivo no es una imagen.";
        $uploadOk = 0;
    }

    if ($_FILES["foto_perfil"]["size"] > 500000) {
        echo "Lo siento, tu archivo es demasiado grande.";
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif") {
        echo "Lo siento, solo se permiten archivos JPG, JPEG, PNG y GIF.";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        echo "Lo siento, tu archivo no fue subido.";
    } else {
        if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $target_file)) {
            $sql = "UPDATE usuarios SET foto_perfil = ? WHERE nombre_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $target_file, $usuario_nombre);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Lo siento, hubo un error al subir tu archivo.";
        }
    }
}

if (isset($_POST['descripcion'])) {
    $descripcion = $_POST['descripcion'];
    $sql = "UPDATE usuarios SET descripcion = ? WHERE nombre_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $descripcion, $usuario_nombre);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT nombre, apellido, nombre_usuario, email, fecha_nacimiento, genero, foto_perfil, descripcion, id FROM usuarios WHERE nombre_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario_nombre);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    echo "Usuario no encontrado.";
    exit();
}

$sql_posts = "SELECT * FROM publicaciones WHERE usuario_id = ?";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $usuario['id']);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

$posts = [];
if ($result_posts->num_rows > 0) {
    while ($row = $result_posts->fetch_assoc()) {
        $posts[] = $row;
    }
}

$stmt->close();
$stmt_posts->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../js/editar_perfil.js"></script>
    <link rel="stylesheet" href="../css/perfil.css">
    <title>Perfil de Usuario</title>
</head>
<body>
<div class="header">
    <div class="usuario">
        <span class="usuario1"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></span>
    </div>
    <div class="logocuadro">
        <a href="index.php">
            <img src="../logo.png" alt="Logo" class="logo">
        </a>
        <span class="nombreweb">Pluma Digital</span>
    </div>
    <div class="menus">
            <div class="menusdespegables">
                <img src="../icon1.png" alt="Icono 1" class="icon">
                <div class="despegables">
                    <ul>
                        <li><a href="#">Biblioteca</a></li>
                        <li><a href="#">Portafolio</a></li>
                        <li><a href="#">Eventos</a></li>
                    </ul>
                </div>
            </div>
            <div class="menusdespegables">
                <img src="../icon2.png" alt="Icono 2" class="icon">
                <div class="despegables">
                    <ul>
                        <li><a href="perfil.php">Perfil</a></li>
                        <li><a href="ajustes.php">Ajustes</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
</div>

<div class="secciondeperfil">
    <div class="perfilcontenido">
        <div class="fotoperfil">
            <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de Perfil">
        </div>
        <div class="informaciondeperfil">
            <div class="nombreperfil">
                <h2><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h2>
                <?php if ($usuario_nombre == $_SESSION['nombre_usuario']): ?>
                    <div class="botones">
                        <button class="editar">Editar</button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="grupodebotones">
                <div class="botones">
                    <button class="editar">N° Seguidores</button>
                </div>
                <div class="botones">
                    <button class="editar">N° Seguidos</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="informacionusuario">
        <h2>Información Personal</h2>
        <ul class="informacionpersonal">
            <li><p>Nombre y Apellido: <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></p></li>
            <li><p>Fecha de nacimiento: <?php echo htmlspecialchars($usuario['fecha_nacimiento']); ?></p></li>
            <li><p>Género: <?php echo htmlspecialchars($usuario['genero']); ?></p></li>
        </ul>
    </div>

    <div class="descripcion">
        <h2>Descripción Personal</h2>
        <?php if (isset($usuario['descripcion'])): ?>
            <p><?php echo htmlspecialchars($usuario['descripcion']); ?></p>
        <?php endif; ?>
    </div>

</div>

<div class="posts">
    <h2>Posts de <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h2>
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h3><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h3>
                <p><?php echo htmlspecialchars($post['contenido']); ?></p>
                <small><?php echo htmlspecialchars($post['fecha_publicacion']); ?></small>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay posts disponibles.</p>
    <?php endif; ?>
</div>

<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Editar Perfil</h2>
        <button id="editarFotoBtn">Editar Foto de Perfil</button>
        <button id="editarDescripcionBtn">Editar Descripción Personal</button>
    </div>
</div>

<div id="editarFotoModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Editar Foto de Perfil</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="foto_perfil">Foto de Perfil:</label>
                <input type="file" id="foto_perfil" name="foto_perfil">
            </div>
            <div class="form-group">
                <input type="submit" value="Guardar Cambio">
            </div>
        </form>
    </div>
</div>

<div id="editarDescripcionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Editar Descripción Personal</h2>
        <form method="post">
            <div class="form-group">
                <label for="descripcion">Descripción Personal:</label>
                <textarea id="descripcion" name="descripcion"><?php echo htmlspecialchars($usuario['descripcion']); ?></textarea>
            </div>
            <div class="form-group">
                <input type="submit" value="Guardar Cambio">
            </div>
        </form>
    </div>
</div>

<footer>
    &copy; 2024 RedRocket. Todos los derechos reservados.
</footer>
</body>
</html>
