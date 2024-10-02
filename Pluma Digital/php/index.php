<?php
session_start();
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: login.php");
    exit();
}

include_once 'api.php';

$postAPI = new PostAPI();
$posts = $postAPI->getPosts();
$conn = $postAPI->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['createPost']) && isset($_SESSION['nombre_usuario'])) {
        $contenido = $_POST['contenido'];
        $usuario_id = getUserProfile($conn, $_SESSION['nombre_usuario'])['id'];

        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagenTmpName = $_FILES['imagen']['tmp_name'];
            $imagenName = basename($_FILES['imagen']['name']);
            $imagenPath = '../uploads/' . $imagenName;

            if (move_uploaded_file($imagenTmpName, $imagenPath)) {
                $imagen = $imagenPath;
            } else {
                echo "Error al subir la imagen.";
            }
        }

        if ($postAPI->createPost($contenido, $usuario_id, $imagen)) {
            header("Location: index.php");
            exit();
        } else {
            echo "Error al crear la publicación.";
        }
    }
}

function getUserProfile($conn, $nombre_usuario) {
    $query = "SELECT id, nombre_usuario, foto_perfil, rol_id FROM usuarios WHERE nombre_usuario = :nombre_usuario";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":nombre_usuario", $nombre_usuario);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$userProfile = getUserProfile($conn, $_SESSION['nombre_usuario']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link rel="stylesheet" href="styles.css">
    <style>.icon {
    cursor: pointer;
}
</style>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadButton = document.getElementById('upload-button');
    const fileInput = document.getElementById('file-input');

    uploadButton.addEventListener('click', function () {
        fileInput.click();
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            console.log('Archivo seleccionado: ' + fileName);
        }
    });
});
</script>

    <script src="../js/editar_perfil.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    searchInput.addEventListener('input', function () {
        const query = searchInput.value;
        if (query.length > 2) { 
            fetch(`search_users.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(user => {
                            const resultItem = document.createElement('div');
                            resultItem.classList.add('result-item');
                            
                            const profilePic = document.createElement('img');
                            profilePic.src = user.foto_perfil;
                            profilePic.classList.add('result-profile-pic');

                            const userName = document.createElement('span');
                            userName.textContent = user.nombre_usuario;
                            userName.classList.add('result-username');

                            resultItem.appendChild(profilePic);
                            resultItem.appendChild(userName);

                            resultItem.addEventListener('click', () => {
                                window.location.href = `perfil.php?usuario=${encodeURIComponent(user.nombre_usuario)}`;
                            });

                            searchResults.appendChild(resultItem);
                        });
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.style.display = 'none';
                    }
                });
        } else {
            searchResults.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!searchBar.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
});
    </script>
</head>
<body>
<header>
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
                <img src="../icon2.png" alt="Icono 2" class="icon">
                <div class="despegables">
                    <ul>
                        <?php if ($_SESSION['nombre_usuario'] === 'RedRocket'): ?>
                            <li><a href="perfil_redrocket.php">Perfil de RedRocket</a></li>
                        <?php else: ?>
                            <li><a href="perfil.php">Perfil</a></li>
                        <?php endif; ?>
                        <li><a href="../html/enconstruccion.html">Ajustes</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                        <?php if ($userProfile['rol_id'] == 1 || $userProfile['rol_id'] == 2): ?>
                            <li><a href="admin_panel.php">Panel de Administración</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<main>
<br>
        <aside class="left-sidebar">
    <br>
    <div class="sidebar-content"> 
        <div class="profile">
            <div class="profile-picture" style="background-image: url('<?php echo htmlspecialchars($userProfile['foto_perfil']); ?>');"></div>
            <div class="profile-text"><?php echo htmlspecialchars($userProfile['nombre_usuario']); ?></div>
        </div>
        <nav class="nav-menu">
    <a href="../html/enconstruccion.html" class="nav-item">
        <button>
            <img src="../icons/address-book-solid.svg" alt="Portafolio Icono" class="nav-icon">
            Portafolio
        </button>
    </a>
    <a href="../html/enconstruccion.html" class="nav-item">
        <button>
            <img src="../icons/book-bookmark-solid.svg" alt="Biblioteca Icono" class="nav-icon">
            Biblioteca
        </button>
    </a>
    <a href="../html/enconstruccion.html" class="nav-item">
        <button>
            <img src="../icons/calendar-plus-solid.svg" alt="Eventos Icono" class="nav-icon">
            Eventos
        </button>
    </a>
    <a href="chat.php" class="nav-item">
    <button>
        <img src="../icons/message-regular.svg" alt="Chats Icono" class="nav-icon">
        Chats
    </button>
</a>

    <a href="../html/enconstruccion.html" class="nav-item">
        <button>
            <img src="../icons/users-rectangle-solid.svg" alt="Grupos Icono" class="nav-icon">
            Grupos
        </button>
    </a>
</nav>



        <br>
        <hr> 
        <br>
        <div class="additional-info">
            <p>Accesos Directos</p>
            <br>
            <div class="info-item">
                <div class="info-icon"></div>
                <div class="info-text">Texto</div>
            </div>
            <div class="info-item">
                <div class="info-icon"></div>
                <div class="info-text">Texto</div>
            </div>

        </div>
    </div>
</aside>

<section class="main-content">
    <br>
    <form method="POST" action="" enctype="multipart/form-data" class="crear-publicacion-form">
        <textarea name="contenido" required placeholder="Escribe tu publicación aquí..."></textarea>
        <input type="file" id="file-input" name="imagen" accept="image/*" style="display: none;">
        <div class="form-bottom">
            <img src="../icons/image-regular.svg" alt="Imagen" class="icon" id="upload-button">
            <button type="submit" name="createPost">Crear Publicación</button>
        </div>
                        </form>
        
        <div class="publicaciones">
            <?php foreach ($posts as $post): ?>
                <div class="tweet-card">
                    <div class="tweet-header">
                        <img src="<?php echo htmlspecialchars($post['foto_perfil']); ?>" alt="User" class="user-icon">
                        <div class="user-info">
                            <span class="user-handle"><?php echo htmlspecialchars($post['nombre_usuario']); ?> · <?php echo htmlspecialchars(date('d M Y', strtotime($post['fecha_publicacion']))); ?></span>
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
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <aside class="right-sidebar">
    <br>
    <div class="sidebar-content">
        <div class="sidebar-text">Texto</div>
        <div class="sidebar-menu">
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
            <div class="menu-item">
                <div class="menu-icon"></div>
                <div class="menu-text">Texto</div>
            </div>
          
    
            
            <br>

        </div>
    </div>
</aside>
</section>


</main>
</body>
</html>
