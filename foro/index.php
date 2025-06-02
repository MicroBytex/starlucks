<?php
// Configuración
$data_file = 'posts.txt'; // Archivo para guardar las publicaciones
$upload_dir = 'uploads/'; // Directorio para subir imágenes
$max_file_size = 2 * 1024 * 1024; // Tamaño máximo de archivo: 2MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif']; // Tipos de archivo permitidos

// --- Manejar el envío de nuevas publicaciones ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usamos un nombre de usuario simple "Anónimo" ya que no hay sistema de usuarios
    $username = 'Anónimo';
    // Obtenemos el mensaje y lo sanitizamos para evitar XSS básico
    $message = $_POST['message'] ?? '';
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $image_path = '';

    // Manejar la subida de imagen
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $file_name = basename($file['name']);
        $file_type = $file['type'];
        $file_size = $file['size'];
        $temp_path = $file['tmp_name'];

        // Validar el archivo (tamaño y tipo)
        if ($file_size <= $max_file_size && in_array($file_type, $allowed_types)) {
            // Crear el directorio de subidas si no existe
            if (!is_dir($upload_dir)) {
                // Intentar crear el directorio con permisos de escritura para el servidor web
                // Nota: 0777 es permisivo, considera usar 0755 si es posible y seguro
                mkdir($upload_dir, 0777, true);
            }

            // Generar un nombre de archivo único para evitar colisiones y problemas de seguridad
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '.' . $extension;
            $destination_path = $upload_dir . $new_file_name;

            // Mover el archivo subido al directorio de destino
            if (move_uploaded_file($temp_path, $destination_path)) {
                $image_path = $destination_path;
            } else {
                // Manejar error de subida
                echo "<p style='color: red;'>Error al subir la imagen.</p>";
            }
        } else {
            // Manejar error de validación
             echo "<p style='color: red;'>Error: Archivo no válido o demasiado grande.</p>";
        }
    }

    // Preparar los datos de la publicación (formato simple: timestamp|username|message|image_path)
    $timestamp = date('Y-m-d H:i:s');
    // Usamos | como separador. Asegúrate de que los datos no contengan | sin escapar si fuera necesario.
    // En este ejemplo simple, htmlspecialchars ya debería manejar la mayoría de los caracteres problemáticos en el mensaje.
    $post_data = "$timestamp|$username|$message|$image_path\n";

    // Añadir al archivo (usando file_put_contents con FILE_APPEND y LOCK_EX para seguridad básica)
    // LOCK_EX ayuda a prevenir problemas si varios procesos escriben al mismo tiempo, pero no es una solución robusta.
    if (file_put_contents($data_file, $post_data, FILE_APPEND | LOCK_EX) === false) {
        echo "<p style='color: red;'>Error al guardar el mensaje.</p>";
    }

    // Redirigir para evitar que se reenvíe el formulario al actualizar la página
    header('Location: index.php');
    exit;
}

// --- Leer publicaciones existentes ---
$posts = [];
if (file_exists($data_file)) {
    // Leer el archivo línea por línea, ignorando líneas vacías
    $lines = file($data_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            // Dividir la línea en partes usando | como separador
            // Limitamos a 4 partes para manejar posibles | dentro del mensaje
            $parts = explode('|', $line, 4);
            if (count($parts) === 4) {
                $posts[] = [
                    'timestamp' => htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8'), // Sanitizar al leer también
                    'username' => htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8'),   // Sanitizar al leer también
                    'message' => htmlspecialchars($parts[2], ENT_QUOTES, 'UTF-8'),    // Sanitizar al leer también
                    'image_path' => htmlspecialchars($parts[3], ENT_QUOTES, 'UTF-8')  // Sanitizar al leer también
                ];
            }
        }
    }
}

// Invertir el array para mostrar las publicaciones más recientes primero
$posts = array_reverse($posts);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro - StarLuck</title>
    <!-- Ajusta la ruta del manifest.json si es necesario -->
    <link rel="manifest" href="../manifest.json">
    <link rel="icon" href="https://raw.githubusercontent.com/KaitoNekox/StarLuck/refs/heads/main/file_00000000663461f78983b0756bc1f1d0.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /*
        Aquí copiamos los estilos principales de tu sitio para mantener la consistencia.
        Idealmente, estos estilos estarían en un archivo CSS separado y compartido.
        */
        :root {
            --primary-color: #238636;
            --secondary-color: #2ea043;
            --background-color: #0d1117;
            --text-color: #c9d1d9;
            --border-color: #30363d;
            --card-color: #161b22;
            --hover-color: #1f6feb;
        }

        .light-theme { /* Asumiendo que la clase light-theme se aplica al html o body */
            --primary-color: #1f883d;
            --secondary-color: #2da44e;
            --background-color: #ffffff;
            --text-color: #24292f;
            --border-color: #d0d7de;
            --card-color: #f6f8fa;
            --hover-color: #0969da;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Estilos del encabezado (similar a otras páginas) */
        .forum-header {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .forum-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .forum-logo i {
            font-size: 24px;
        }

        .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 0 24px;
        }

        /* Estilos del contenido principal */
        .forum-container {
            padding: 24px;
            flex: 1;
            max-width: 800px; /* Limita el ancho para mejor legibilidad */
            margin: 0 auto; /* Centra el contenedor */
            width: 100%;
        }

        .forum-title {
            font-size: 28px;
            margin-bottom: 24px;
            color: var(--text-color);
            text-align: center;
        }

        /* Formulario para nueva publicación */
        .new-post-form {
            background-color: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .new-post-form h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .new-post-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
            color: var(--text-color);
            resize: vertical; /* Permite redimensionar verticalmente */
            min-height: 100px;
            font-size: 14px;
        }

        .new-post-form input[type="file"] {
            margin-bottom: 15px;
            color: var(--text-color);
            font-size: 14px;
        }

        .new-post-form button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .new-post-form button:hover {
            background-color: var(--secondary-color);
        }

        /* Lista de publicaciones */
        .posts-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .post {
            background-color: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
        }

        .post-meta {
            font-size: 12px;
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 10px;
        }

        .post-meta strong {
            color: var(--primary-color);
        }

        .post-content {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 10px;
            word-wrap: break-word; /* Evita que palabras largas desborden */
            white-space: pre-wrap; /* Mantiene saltos de línea del textarea */
        }

        .post-image {
            max-width: 100%; /* Asegura que la imagen no desborde el contenedor */
            height: auto;
            margin-top: 10px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            display: block; /* Evita espacio extra debajo de la imagen */
        }

        /* Estilos del pie de página */
        .forum-footer {
            text-align: center;
            padding: 24px;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
            background-color: var(--background-color);
        }

        /* Ajustes responsivos */
        @media (max-width: 600px) {
            .forum-container {
                padding: 16px;
            }

            .forum-title {
                font-size: 24px;
            }

            .new-post-form {
                padding: 15px;
            }

            .post {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="forum-header">
        <div class="forum-logo">
            <i class="fas fa-clover"></i>
            <span>StarLuck</span>
        </div>
    </div>
    <div class="divider"></div>

    <!-- Contenido Principal del Foro -->
    <div class="forum-container">
        <h1 class="forum-title">Foro de StarLuck</h1>

        <!-- Formulario para Nueva Publicación -->
        <div class="new-post-form">
            <h3>Crear Nueva Publicación</h3>
            <!-- El formulario envía los datos a sí mismo (index.php) -->
            <form action="index.php" method="post" enctype="multipart/form-data">
                <!-- Podrías añadir un campo para el nombre de usuario aquí si no quieres que sea "Anónimo" -->
                <textarea name="message" placeholder="Escribe tu mensaje aquí..." required></textarea>
                <!-- Campo para subir imagen -->
                <input type="file" name="image" accept="image/*">
                <button type="submit">Publicar</button>
            </form>
        </div>

        <!-- Lista de Publicaciones -->
        <div class="posts-list">
            <?php if (empty($posts)): ?>
                <p style="text-align: center; opacity: 0.8;">Aún no hay publicaciones. ¡Sé el primero en comentar!</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-meta">
                            Publicado por <strong><?= $post['username'] ?></strong> el <?= $post['timestamp'] ?>
                        </div>
                        <div class="post-content">
                            <?= nl2br($post['message']) ?> <!-- nl2br() convierte saltos de línea en <br> -->
                        </div>
                        <?php
                        // Mostrar la imagen si existe y el archivo existe
                        if (!empty($post['image_path']) && file_exists($post['image_path'])):
                        ?>
                            <img src="<?= $post['image_path'] ?>" alt="Imagen de la publicación" class="post-image">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pie de página -->
    <footer class="forum-footer">
        <p>&copy; StarLuck. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Lógica básica para aplicar el tema (puede expandirse)
        // Esto asume que la clase 'light-theme' se aplica al elemento <html>
        function applyTheme(theme) {
            if (theme === 'light') {
                document.documentElement.classList.add('light-theme');
            } else {
                document.documentElement.classList.remove('light-theme');
            }
        }

        // Verificar si hay una preferencia de tema guardada al cargar la página
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            applyTheme(savedTheme);
        } else {
            // Tema por defecto si no hay preferencia guardada
            applyTheme('dark');
        }

        // Nota: Una aplicación real necesitaría una gestión de temas más sofisticada
        // para sincronizar con el modal de configuración de la página principal.
    </script>
</body>
</html>