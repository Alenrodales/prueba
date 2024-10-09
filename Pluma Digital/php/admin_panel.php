<?php
session_start();
require 'C:/xampp/htdocs/Pluma Digital/php/config.php'; 

if (!isset($_SESSION['nombre_usuario']) || empty($_SESSION['nombre_usuario'])) {
    echo "No has iniciado sesión. <a href='login.php'>Inicia sesión</a>";
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$username = $_SESSION['nombre_usuario'];

$sql = "SELECT u.*, r.nombre AS rol_nombre FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE u.nombre_usuario = :username";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Usuario no encontrado.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control del Administrador</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>
    <div class="sidebar">
        <div class="profile-info">
            <div class="profile-circle" style="background-image: url('<?php echo htmlspecialchars($user['foto_perfil']); ?>');"></div>
            <div class="user-details">
                <p class="username"><?php echo htmlspecialchars($user['nombre_usuario']); ?></p>
                <p class="rank"><?php echo htmlspecialchars($user['rol_nombre']); ?></p>
            </div>
        </div>
        <ul>
            <li><a href="#gestion-usuarios" onclick="showSection('gestion-usuarios')">Gestión de Usuarios</a></li>
            <li><a href="#moderacion-contenidos" onclick="showSection('moderacion-contenidos')">Moderación de Contenidos</a></li>
            <li><a href="#configuracion" onclick="showSection('configuracion')">Configuración</a></li>
            <li><a href="#estadisticas" onclick="showSection('estadisticas')">Estadísticas y Análisis</a></li>
            <li><a href="#roles-permisos" onclick="showSection('roles-permisos')">Roles y Permisos</a></li>
            <li><a href="#soporte" onclick="showSection('soporte')">Soporte y Asistencia</a></li>
            <li><a href="#seguridad" onclick="showSection('seguridad')">Seguridad</a></li>
            <li><a href="#publicidad" onclick="showSection('publicidad')">Administración de Publicidad</a></li>
            <li><a href="#backups" onclick="showSection('backups')">Backups y Restauraciones</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <section id="gestion-usuarios" class="content-section">
            <h2>Gestión de Usuarios</h2>
            <input type="text" id="searchUser" placeholder="Buscar usuario..." onkeyup="searchUser()">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Fecha de Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM usuarios";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        foreach ($result as $row) {
                            echo "<tr>";
                            echo "<td><img src='" . htmlspecialchars($row["foto_perfil"]) . "' alt='Foto de usuario' width='50'></td>";
                            echo "<td>" . htmlspecialchars($row["nombre"]) . " " . htmlspecialchars($row["apellido"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["nombre_usuario"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["fecha_registro"]) . "</td>";
                            echo "<td>
                                    <button class='edit-button' data-user-id='" . htmlspecialchars($row["id"]) . "'>Editar</button>
                                    <button onclick=\"suspendUser('" . htmlspecialchars($row["nombre_usuario"]) . "')\">Suspender</button>
                                    <button onclick=\"deleteUser('" . htmlspecialchars($row["nombre_usuario"]) . "')\">Eliminar</button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No se encontraron usuarios.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Editar Usuario</h2>
            <form id="editForm" method="POST" action="update_user.php">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="form-group">
                    <label for="newPassword">Nueva Contraseña:</label>
                    <input type="password" name="new_password" id="newPassword" placeholder="Ingrese nueva contraseña">
                </div>
                <div class="form-group">
                    <label for="deleteProfilePic">Eliminar Foto de Perfil:</label>
                    <input type="checkbox" name="delete_profile_pic" id="deleteProfilePic">
                </div>
                <div class="form-group">
                    <label for="role">Cambiar Rol:</label>
                    <select name="role" id="role">
                        <option value="">Seleccionar rol</option>
                        <option value="1">Owner</option>
                        <option value="2">Administrador</option>
                        <option value="3">Registrado</option>
                        <option value="4">Invitado</option>
                    </select>
                </div>
                <button type="submit" class="submit-button">Actualizar</button>
            </form>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('update') === 'success') {
            alert('Perfil actualizado');
        }
    </script>
    <script src="script.js"></script>
</body>
</html>
