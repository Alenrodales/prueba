<?php
session_start();
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "plumadigital1";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el nombre de usuario a ver o el logueado
$usuario_nombre = isset($_GET['usuario']) ? $_GET['usuario'] : $_SESSION['nombre_usuario'];

// Obtener el ID del usuario logueado
$sql = "SELECT id FROM usuarios WHERE nombre_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['nombre_usuario']);
$stmt->execute();
$result = $stmt->get_result();
$usuario_logueado = $result->fetch_assoc();
$usuario_logueado_id = $usuario_logueado['id'];

// Obtener el ID del usuario del perfil visto
$sql = "SELECT * FROM usuarios WHERE nombre_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario_nombre);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$usuario_visto_id = $usuario['id'];

// Verificar si el usuario logueado ya sigue al usuario del perfil
$sql = "SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_logueado_id, $usuario_visto_id);
$stmt->execute();
$result = $stmt->get_result();
$siguiendo = $result->num_rows > 0;

// Manejar la solicitud de seguir o dejar de seguir
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['seguir'])) {
        if (!$siguiendo) {
            $sql = "INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $usuario_logueado_id, $usuario_visto_id);
            $stmt->execute();
        }
    } elseif (isset($_POST['dejar_de_seguir'])) {
        if ($siguiendo) {
            $sql = "DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $usuario_logueado_id, $usuario_visto_id);
            $stmt->execute();
        }
    } elseif (isset($_POST['editar_perfil'])) {
        // Procesar la edición del perfil
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $email = $_POST['email'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $genero = $_POST['genero'];
        $descripcion = $_POST['descripcion'];

        // Procesar la subida de la foto de perfil
        $foto_perfil = $_FILES['foto_perfil']['name'];
        if ($foto_perfil) {
            $ruta_temp = $_FILES['foto_perfil']['tmp_name'];
            $ruta_destino = "../uploads/" . basename($foto_perfil);
            if (move_uploaded_file($ruta_temp, $ruta_destino)) {
                // Actualizar la base de datos con la nueva foto de perfil
                $sql = "UPDATE usuarios SET nombre=?, apellido=?, email=?, fecha_nacimiento=?, genero=?, descripcion=?, foto_perfil=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $nombre, $apellido, $email, $fecha_nacimiento, $genero, $descripcion, $ruta_destino, $usuario_visto_id);
            } else {
                echo "Error al subir la foto.";
            }
        } else {
            // Si no hay foto nueva, actualizar solo los demás campos
            $sql = "UPDATE usuarios SET nombre=?, apellido=?, email=?, fecha_nacimiento=?, genero=?, descripcion=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $fecha_nacimiento, $genero, $descripcion, $usuario_visto_id);
        }
        $stmt->execute();
    }

    // Redirigir al perfil después de seguir/dejar de seguir
    header("Location: perfil.php?usuario=" . urlencode($usuario_nombre));
    exit();
}

// Contar el número de seguidores
$sql = "SELECT COUNT(*) AS total_seguidores FROM seguidores WHERE seguido_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_visto_id);
$stmt->execute();
$result = $stmt->get_result();
$total_seguidores = $result->fetch_assoc()['total_seguidores'];

// Contar el número de seguidos
$sql = "SELECT COUNT(*) AS total_seguidos FROM seguidores WHERE seguidor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_visto_id);
$stmt->execute();
$result = $stmt->get_result();
$total_seguidos = $result->fetch_assoc()['total_seguidos'];

// Obtener información personal
$informacion_personal = [
    'nombre' => $usuario['nombre'],
    'apellido' => $usuario['apellido'],
    'email' => $usuario['email'],
    'fecha_nacimiento' => $usuario['fecha_nacimiento'],
    'genero' => $usuario['genero'],
    'descripcion' => $usuario['descripcion'],
];

// Obtener los posts del usuario
$sql = "SELECT * FROM publicaciones WHERE usuario_id = ? ORDER BY fecha_publicacion DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_visto_id);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);


$sql = "SELECT p.*, u.nombre AS nombre_usuario, u.apellido AS apellido_usuario, u.foto_perfil 
        FROM publicaciones p 
        JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.fecha_publicacion DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_visto_id);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
?>

<!-- Tu HTML aquí para mostrar la información del perfil -->


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <style>
        /* Estilos generales del cuerpo */
        /* Estilos generales del cuerpo */
        body {
            margin: 0;
            /* Eliminar márgenes del body */
            font-family: Arial, sans-serif;
            /* Fuente general para el documento */
        }

        /* Contenedor principal del perfil */
        .perfilcontenido {
            display: flex;
            flex-direction: column;
            align-items: center;
            /* Centrar el contenido */
            text-align: center;
            /* Centrar el texto */
            padding: 20px;
            /* Espacio interno del contenido del perfil */
            margin: 20px auto;
            /* Margen superior e inferior */
            max-width: 600px;
            /* Ancho máximo del contenedor del perfil */
            background-color: #f9f9f9;
            /* Color de fondo del contenido */
            border-radius: 10px;
            /* Bordes redondeados */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            /* Sombra para dar profundidad */
        }


        .fotoperfil img {
            width: 150px;
            /* Ancho fijo */
            height: 150px;
            /* Alto fijo para mantener la forma circular */
            border-radius: 50%;
            /* Bordes redondeados al 50% para hacerla circular */
            margin-bottom: 15px;
            /* Espacio inferior de la imagen */
            border: 3px solid blue;
            /* Borde azul */
            box-sizing: border-box;
            /* Asegurar que el padding y el borde se incluyan en el tamaño total */
            object-fit: cover;
            /* Asegura que la imagen cubra el área de la imagen */
        }



        /* Estilos para el nombre del perfil */
        .nombreperfil h2 {
            font-size: 28px;
            color: #333;
            margin: 10px 0;
        }

        .margin-izquierdo {
            margin-left: 140px;
            /* Aplica solo cuando se está viendo el perfil de otro usuario */
        }

        /* Estilos para la descripción del perfil */
        .nombreperfil p {
            color: #777;
            /* Color de texto secundario */
            font-size: 16px;
            /* Tamaño de fuente */
        }

        .nombreperfil {
            margin-bottom: 15px;
            /* Espacio inferior para separar de la descripción */
        }

        .nombreperfil div {
            display: flex;
            align-items: center;
            /* Centrar verticalmente */
        }


        .search-bar {
            position: relative;
            display: flex;
            align-items: center;
            width: 40%;
            margin: 0 20px;
            margin-right: 370px;
        }

        /* Estilos para la información del perfil */
        .informaciondeperfil p {
            margin: 5px 0;
            /* Espaciado entre párrafos */
            color: #555;
            /* Color de texto secundario */
        }

        /* Estilos para los botones */
        .botones form {
            display: inline-block;
            /* Mantener los botones en línea */
        }

        .botones {
            margin-left: 20px;
            /* Espacio entre el nombre y el botón */
        }


        .botones .editar {
            margin-left: 10px;
            padding: 5px 10px;
            /* Tamaño del botón */
            cursor: pointer;
            /* Cambia el cursor al pasar sobre el botón */
            background-color: #007bff;
            /* Color de fondo del botón */
            color: white;
            /* Color del texto del botón */
            border: none;
            /* Sin borde */
            border-radius: 5px;
            /* Bordes redondeados */
            transition: background-color 0.3s;
            /* Transición suave para el color de fondo */
        }

        .botones .editar:hover {
            background-color: #0056b3;
            /* Color de fondo al pasar el mouse */
        }


        .boton {
            padding: 10px 20px;
            /* Espaciado interno */
            border: none;
            /* Sin borde */
            border-radius: 5px;
            /* Bordes redondeados */
            cursor: pointer;
            /* Cambiar cursor al pasar */
            transition: background-color 0.3s;
            /* Transición suave */
        }

        /* Estilo para el botón de seguir/dejar de seguir */
        .boton.seguir {
            background-color: #007bff;
            /* Color azul */
            color: white;
            /* Texto blanco */
        }

        .boton.seguir:hover {
            background-color: #0056b3;
            /* Azul más oscuro al pasar el ratón */
        }

        /* Estilo para el botón de editar perfil */
        .editar {
            background-color: #28a745;
            /* Color verde */
            color: white;
            /* Texto blanco */
            border: 2px solid #218838;
            /* Borde verde oscuro */
            transition: background-color 0.3s, transform 0.3s;
            /* Transiciones suaves */
            font-weight: bold;
            /* Negrita */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            /* Sombra sutil */
            padding: 10px 20px;
            /* Espaciado interno del botón */
            cursor: pointer;
            /* Cambia el cursor al pasar sobre el botón */
            border-radius: 5px;
            /* Bordes redondeados */
        }

        .editar:hover {
            background-color: #218838;
            /* Verde más oscuro al pasar el ratón */
            transform: scale(1.05);
            /* Aumentar ligeramente el tamaño */
        }

        /* Estilos para los posts */
        .posts {
            margin-top: 30px;
            /* Espaciado superior */
        }

        .post {
            background-color: #f9f9f9;
            /* Fondo gris claro */
            border: 1px solid #e0e0e0;
            /* Borde gris */
            padding: 15px;
            /* Espaciado interno */
            border-radius: 5px;
            /* Bordes redondeados */
            margin-bottom: 15px;
            /* Espaciado inferior */
        }

        .modal {
            display: none;
            /* Oculto por defecto */
            position: fixed;
            /* Fijo en la pantalla */
            z-index: 1000;
            /* Asegura que esté sobre otros elementos */
            left: 0;
            top: 0;
            width: 100%;
            /* Ancho completo */
            height: 100%;
            /* Alto completo */
            overflow: auto;
            /* Permitir scroll si es necesario */
            background-color: rgba(0, 0, 0, 0.6);
            /* Fondo oscuro con opacidad */
        }

        .modal-content {
            background-color: white;
            /* Fondo blanco */
            padding: 20px;
            /* Espaciado interno */
            border: 1px solid #ccc;
            /* Borde gris */
            border-radius: 10px;
            /* Bordes redondeados */
            width: 90%;
            /* Ancho del modal */
            max-width: 500px;
            /* Ancho máximo */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            /* Sombra */
            position: fixed;
            /* Fijar en el viewport */
            top: 20%;
            /* Posición más arriba (ajustable) */
            left: 50%;
            /* Centrar horizontalmente */
            transform: translate(-50%, -20%);
            /* Alinea horizontal y ajusta verticalmente */
        }


        .close {
            color: #aaa;
            /* Color del texto del botón de cerrar */
            float: right;
            /* Alinear a la derecha */
            font-size: 28px;
            /* Tamaño del texto */
            font-weight: bold;
            /* Negrita */
        }

        .close:hover,
        .close:focus {
            color: black;
            /* Color del texto al pasar el mouse o enfocar */
            text-decoration: none;
            /* Sin subrayado */
            cursor: pointer;
            /* Cursor pointer */
        }

        h2 {
            margin-top: 0;
            /* Sin margen superior */
            text-align: center;
            /* Centramos el título */
        }

        form {
            display: flex;
            /* Usamos flexbox para los elementos del formulario */
            flex-direction: column;
            /* Colocamos los elementos en columna */
            gap: 15px;
            /* Espacio entre los elementos */
            margin-right: 15px;
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        select,
        textarea {
            padding: 10px;
            /* Espaciado interno */
            border: 1px solid #ccc;
            /* Borde gris claro */
            border-radius: 5px;
            /* Bordes redondeados */
            font-size: 16px;
            /* Tamaño del texto */
            width: 95%;
        }


        /* Estilo para el botón de seguir/dejar de seguir */
        .boton.seguir {
            background-color: #007bff;
            /* Color azul */
            color: white;
            /* Texto blanco */
            border: 2px solid #0056b3;
            /* Borde azul oscuro */
            transition: background-color 0.3s, transform 0.3s;
            /* Transiciones suaves */
            font-weight: bold;
            /* Negrita */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            /* Sombra sutil */
        }

        .boton.seguir:hover {
            background-color: #0056b3;
            /* Azul más oscuro al pasar el ratón */
            transform: scale(1.05);
            /* Aumentar ligeramente el tamaño */
        }

        /* Estilo para el botón de dejar de seguir */
        .boton.dejar-seguir {
            background-color: #dc3545;
            /* Color rojo */
            color: white;
            /* Texto blanco */
            border: 2px solid #c82333;
            /* Borde rojo oscuro */
            transition: background-color 0.3s, transform 0.3s;
            /* Transiciones suaves */
            font-weight: bold;
            /* Negrita */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            /* Sombra sutil */
        }

        .boton.dejar-seguir:hover {
            background-color: #c82333;
            /* Rojo más oscuro al pasar el ratón */
            transform: scale(1.05);
            /* Aumentar ligeramente el tamaño */
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Estilo para el textarea */
        textarea {
            width: 100%;
            /* Ancho completo */
            height: 100px;
            /* Alto fijo */
            margin: 10px 0;
            /* Espaciado vertical */
            padding: 10px;
            /* Espaciado interno */
            border-radius: 5px;
            /* Bordes redondeados */
            border: 1px solid #ccc;
            /* Borde gris */
            resize: none;
            /* Desactivar redimensionamiento */
        }

        /* Estilo para los inputs */
        input[type="text"],
        input[type="email"],
        input[type="date"],
        select {
            width: 100%;
            /* Ancho completo */
            padding: 10px;
            /* Espaciado interno */
            margin: 10px 0;
            /* Espaciado vertical */
            border-radius: 5px;
            /* Bordes redondeados */
            border: 1px solid #ccc;
            /* Borde gris */
        }

        /* Estilos para el header */
        .header {
            background-color: white;
            /* Color de fondo del header */
            padding: 15px;
            /* Espacio interno del header */
            color: black;
            /* Cambié a negro para que el texto sea visible sobre el fondo blanco */
            display: flex;
            /* Usar flexbox para organizar los elementos */
            justify-content: space-between;
            /* Espacio entre los elementos del header */
            align-items: center;
            /* Centrar verticalmente los elementos */
            position: relative;
            /* Posicionamiento relativo para asegurar que no se superponga */
            z-index: 1;
            /* Asegurarse de que el header esté por encima del contenido */
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.5);
            /* Sombreado negro en la parte inferior */
        }


        .logocuadro {
            display: flex;
            align-items: center;
        }

        .menus {
            display: flex;
            align-items: center;
        }

        .despegables {
            display: none;
            position: absolute;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 1000;
            padding: 10px;
            left: 0;
            right: auto;
        }

        .despegables ul {
            list-style: none;
            padding: 10px;
            margin: 0;
        }

        .despegables ul li {
            padding: 10px 20px;
        }

        .despegables ul li a {
            text-decoration: none;
            color: #000;
        }

        .menusdespegables {
            position: relative;
            margin-left: 20px;
        }

        .menusdespegables:hover .despegables,
        .despegables:hover {
            display: block;
        }

        .menusdespegables:hover .despegables {
            left: auto;
            right: 0;
        }

        .menusdespegables .despegables {
            position: absolute;
            top: 100%;
            left: 0;
        }

        .logocuadro {
            display: flex;
            align-items: center;
            flex: 1;
            /* Permitir que ocupe el espacio disponible */
        }

        /* Icono debe estar a la izquierda */
        .icon2 {
            width: 30px;
            height: auto;
            /* Ancho del icono */
            cursor: pointer;
            /* Cambiar cursor al pasar */
        }

        /* Logo debe estar a la derecha */
        .logo {
            width: 50px;
            /* Ancho del logo */
            margin-left: 15px;
            /* Empujar hacia la derecha */
            margin-right: 10px;
        }

        /* Estilo para el contenedor de seguidores y seguidos */
        .seguidores-seguidos {
            display: flex;
            /* Usar flexbox para alinearlos */
            justify-content: flex-start;
            /* Alinear los botones a la izquierda */
            margin-top: 20px;
            /* Espacio superior */
        }

        /* Estilo para los botones */
        .boton-seguidores,
        .boton-seguidos {
            padding: 10px 20px;
            /* Espaciado interno */
            border: none;
            /* Sin borde */
            border-radius: 5px;
            /* Bordes redondeados */
            color: white;
            /* Color del texto */
            cursor: pointer;
            /* Cambiar cursor al pasar */
            font-size: 16px;
            /* Tamaño de fuente */
            margin: 0 5px;
            /* Margen lateral para separación ligera */
        }

        /* Estilo para el botón de seguidores */
        .boton-seguidores {
            background-color: #28a745;
            /* Color verde */
            margin-left: 50px;
        }

        /* Estilo para el botón de seguidos */
        .boton-seguidos {
            background-color: #007bff;
            /* Color azul */
            margin-right: 35px;
        }

        .informacion-perfil {
            margin-top: 15px;
            /* Margen superior para separación */
            text-align: left;
            /* Alinear texto a la izquierda */
        }

        .informacion-perfil p {
            margin: 10px 0;
            /* Espacio entre los párrafos */
            font-size: 16px;
            /* Tamaño de la fuente */
            color: #333;
            /* Color del texto */
        }

        .informacion-perfil strong {
            color: #007bff;
            /* Color del texto en negrita */
        }

        .nombreweb {
            color: black;
        }

        .publicaciones {
            width: 46%;
            /* Adjust the width to a smaller percentage */
            margin: 10px auto;
            /* Reduce top and bottom margins */
        }

        .tweet-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            /* Slightly smaller radius for a compact look */
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 10px 0;
            /* Reduce vertical margin */
            padding: 5px;
            /* Add padding for better spacing */
        }

        .tweet-header {
            display: flex;
            align-items: center;
            padding: 5px;
            /* Reduce padding for a compact header */
            border-bottom: 1px solid #ccc;
        }

        .user-icon {
            width: 40px;
            /* Slightly smaller user icon */
            height: 40px;
            /* Adjust height to match width */
            border-radius: 50%;
            margin-right: 8px;
            /* Decrease right margin */
        }

        .tweet-content {
            padding: 10px;
            display: flex;
            /* Make the container a flex container */
            flex-direction: column;
            /* Align items in a column */
            align-items: center;
            /* Center items horizontally */
        }

        .tweet-image {
            width: 100%;
            /* Ensure the image takes full width of its container */
            max-width: 500px;
            /* Optional: Set a maximum width for larger screens */
            border-radius: 10px;
            /* Maintain rounded corners if desired */
            margin-top: 10px;
            /* Space above the image */
        }


        .tweet-content p {
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 90ch;
            /* Adjust max-width for compact text */
        }

        .tweet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
            /* Reduce footer padding */
            border-top: 1px solid #ccc;
        }

        .icon-section {
            display: flex;
            justify-content: space-between;
            width: 60%;
            /* Adjust width to fit better */
        }

        .icon {
            display: flex;
            align-items: center;
            width: 25px;
            /* Adjust width for compact icons */
            cursor: pointer;
        }

        .icon img {
            width: 15px;
            /* Slightly smaller icon size */
            height: 15px;
            /* Match height to width */
            margin-right: 3px;
            /* Reduce right margin */
        }

        .right-icons {
            display: flex;
            align-items: center;
        }

        .right-icon {
            display: flex;
            align-items: center;
            margin-left: 5px;
            /* Reduce left margin */
            font-size: 12px;
        }

        .right-icon img {
            width: 14px;
            /* Adjust size for right icons */
            height: 14px;
            /* Match height to width */
        }

        .titulo-publicaciones {
            text-align: center;
            /* Center the title */
            margin: 20px 0;
            /* Space above and below */
        }

        .titulo-publicaciones h3 {
            font-size: 24px;
            /* Font size for the title */
            font-weight: bold;
            /* Make the title bold */
            color: #333;
            /* Darker color for the text */
            border-bottom: 2px solid #ccc;
            /* Underline for elegance */
            padding-bottom: 10px;
            /* Space below the title */
        }

        .perfil-container {
            display: flex;
            flex-direction: column;
            /* Coloca los elementos en una columna */
            align-items: center;
            /* Centra todos los elementos horizontalmente */
        }

        .nombre-recuadro {
            width: 150px;
            /* Ajusta el ancho según sea necesario */
            height: 50px;
            /* Ajusta la altura según sea necesario */
            display: flex;
            justify-content: center;
            /* Centra el texto horizontalmente */
            align-items: center;
            /* Centra el texto verticalmente */
            border: 2px solid #000;
            /* Borde del recuadro */
            text-align: center;
            /* Centra el texto */
            overflow: hidden;
            /* Oculta el texto que excede el límite */
            white-space: nowrap;
            /* Evita el salto de línea */
            font-size: 16px;
            /* Tamaño de la fuente */
            padding: 5px;
            /* Espaciado interno */
            box-sizing: border-box;
            /* Incluye padding y border en el tamaño total */
            margin-bottom: 10px;
            /* Espacio entre el nombre y el botón */
        }
    </style>
    <script>
        function mostrarModal() {
            document.getElementById("modalEditarPerfil").style.display = "block";
        }
        function cerrarModal() {
            document.getElementById("modalEditarPerfil").style.display = "none";
        }
    </script>
</head>

<body>
    <div class="header">
        <div class="logocuadro">
            <a href="index.php">
                <img src="../logo.png" alt="Logo" class="logo">
            </a>
            <span class="nombreweb">Pluma Digital</span>
        </div>
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Buscar usuarios...">
            <div id="search-results" class="search-results"></div>
        </div>

        <div class="menus">
            <div class="menusdespegables">
                <img src="../icon2.png" alt="Icono 2" class="icon2">
                <div class="despegables">
                    <ul>
                        <?php if ($_SESSION['nombre_usuario'] === 'RedRocket'): ?>
                            <li><a href="perfil_redrocket.php">Perfil de RedRocket</a></li>
                        <?php else: ?>
                            <li><a href="perfil.php">Perfil</a></li>
                        <?php endif; ?>
                        <li><a href="../html/enconstruccion.html">Ajustes</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li>

                        <!-- Verificación de rol del usuario logueado -->
                        <?php if (isset($_SESSION['rol_id']) && ($_SESSION['rol_id'] == 1 || $_SESSION['rol_id'] == 2)): ?>
                            <li><a href="admin_panel.php">Panel de Administración</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </div>
    </div>
    <div class="perfilcontenido">
        <div class="fotoperfil">
            <img src="<?php echo $usuario['foto_perfil'] ?: 'ruta/default.jpg'; ?>" alt="Foto de perfil">
        </div>

        <div class="nombreperfil">
            <div
                style="display: flex; align-items: center; justify-content: <?php echo ($usuario_logueado_id !== $usuario_visto_id) ? 'center' : 'flex-start'; ?>;">
                <h2 class="<?php echo ($usuario_logueado_id !== $usuario_visto_id) ? 'margin-izquierdo' : ''; ?>"
                    style="margin-right: 5px;">
                    <div class="nombre-recuadro">
                        <div class="perfil-container">
                            <div class="nombre-recuadro">
                                <?php echo substr($usuario['nombre'] . ' ' . $usuario['apellido'], 0, 15); ?>
                            </div>
                        </div>
                    </div>
                </h2>
                <?php if ($usuario_logueado_id === $usuario_visto_id): // Mostrar botón solo si es su propio perfil ?>
                <?php endif; ?>
            </div>

            <?php
            // Mostrar el botón "Editar perfil" solo si el usuario está viendo su propio perfil
            if ($usuario_logueado_id === $usuario_visto_id) {
                ?>
                <div class="botones">
                    <button class="editar" onclick="mostrarModal()">Editar perfil</button>
                </div>
                <?php
            }
            ?>
            <p><?php echo $usuario['descripcion']; ?></p>
        </div>

        <div class="informacion-perfil">
            <p><strong>Email:</strong> <?php echo $usuario['email']; ?></p>
            <p><strong>Fecha de nacimiento:</strong> <?php echo $usuario['fecha_nacimiento']; ?></p>
            <p><strong>Género:</strong> <?php echo $usuario['genero']; ?></p>
        </div>

        <!-- Grupo de botones de seguir / dejar de seguir -->
        <div class="grupodebotones" style="margin-top: 10px;">
            <div class="botones">
                <?php if ($usuario_logueado_id !== $usuario_visto_id): ?>
                    <form method="POST">
                        <?php if (!$siguiendo): ?>
                            <button type="submit" name="seguir" class="boton seguir">Seguir</button>
                        <?php else: ?>
                            <button type="submit" name="dejar_de_seguir" class="boton dejar-seguir">Dejar de seguir</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sección de seguidores y seguidos -->
        <div class="seguidores-seguidos">
            <button class="boton-seguidores">Seguidores: <?php echo $total_seguidores; ?></button>
            <button class="boton-seguidos">Seguidos: <?php echo $total_seguidos; ?></button>
        </div>


    </div>


    <!-- Modal para editar perfil -->
    <div id="modalEditarPerfil" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Editar Perfil</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="nombre" value="<?php echo $usuario['nombre']; ?>" required>
                <input type="text" name="apellido" value="<?php echo $usuario['apellido']; ?>" required>
                <input type="email" name="email" value="<?php echo $usuario['email']; ?>" required>
                <input type="date" name="fecha_nacimiento" value="<?php echo $usuario['fecha_nacimiento']; ?>" required>
                <select name="genero" required>
                    <option value="Masculino" <?php echo ($usuario['genero'] == 'Masculino') ? 'selected' : ''; ?>>
                        Masculino</option>
                    <option value="Femenino" <?php echo ($usuario['genero'] == 'Femenino') ? 'selected' : ''; ?>>Femenino
                    </option>
                    <option value="Otro" <?php echo ($usuario['genero'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                </select>
                <textarea name="descripcion" required><?php echo $usuario['descripcion']; ?></textarea>
                <input type="file" name="foto_perfil" accept="image/*">
                <button type="submit" name="editar_perfil">Guardar cambios</button>
            </form>
        </div>
    </div>

    <!-- New Section Title for Publicaciones -->
    <div class="titulo-publicaciones">
        <h3>Publicaciones:</h3>
    </div>

    <div class="publicaciones">
        <?php foreach ($posts as $post): ?>
            <div class="tweet-card">
                <div class="tweet-header">
                    <img src="<?php echo htmlspecialchars($post['foto_perfil']); ?>" alt="User" class="user-icon">
                    <div class="user-info">
                        <span class="user-handle">
                            <?php
                            // Verifica si existe 'nombre_usuario' en el post
                            echo isset($post['nombre_usuario']) ? htmlspecialchars($post['nombre_usuario'] . ' ' . $post['apellido_usuario']) : 'Usuario desconocido';
                            ?> ·
                            <?php echo htmlspecialchars(date('d M Y', strtotime($post['fecha_publicacion']))); ?>
                        </span>
                    </div>
                </div>
                <div class="tweet-content">
                    <p><?php echo nl2br(htmlspecialchars($post['contenido'])); ?></p>
                    <?php if (!empty($post['imagen'])): ?>
                        <img src="<?php echo htmlspecialchars($post['imagen']); ?>" alt="Imagen" class="tweet-image">
                    <?php endif; ?>
                </div>
                <div class="tweet-footer">
                    <div class="icon-section">
                        <!-- Puedes agregar íconos de interacciones aquí -->
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


</body>

</html>

<?php
$conn->close();
?>