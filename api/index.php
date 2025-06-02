<?php
// api/index.php - API para buscar scripts (gratuita, sin API Key)

// --- Configuración ---
$scripts_data_file = 'scripts.json'; // Archivo con los datos de los scripts (relativo a este archivo)

// --- Configurar encabezados de respuesta (default JSON, puede ser sobreescrito para HTML) ---
header('Content-Type: application/json');
// Configurar CORS (Permitir solicitudes desde cualquier origen - AJUSTAR EN PRODUCCIÓN)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS'); // Solo GET y OPTIONS son relevantes ahora
header('Access-Control-Allow-Headers: Content-Type'); // Headers que la API espera

// Manejar solicitudes OPTIONS para CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// --- Función de respuesta de error ---
function send_error($message, $status_code) {
    // Asegurar que el header sea JSON si no se ha enviado o sobreescrito
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code($status_code);
    echo json_encode(['error' => $message]);
    exit;
}

// --- Determinar la acción ---
$action = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['q'])) {
        $action = 'search_scripts';
    } else {
        $action = 'serve_html_page'; // Acción por defecto para GET
    }
}
// Aquí podrías añadir lógica para otros métodos HTTP como POST si fuera necesario

// --- Ejecutar acción ---
switch ($action) {
    case 'search_scripts':
        // La validación de API Key se ha eliminado.
        // --- Leer datos de los scripts ---
        $scripts = [];
        if (file_exists($scripts_data_file)) {
            $scripts_json = file_get_contents($scripts_data_file);
            if ($scripts_json !== false) {
                $scripts = json_decode($scripts_json, true);
                if ($scripts === null && json_last_error() !== JSON_ERROR_NONE) {
                     send_error('Error al leer los datos de los scripts: ' . json_last_error_msg(), 500);
                }
                if (!is_array($scripts)) { $scripts = []; } // Asegurar que $scripts sea un array
            }
        }

        // --- Lógica de búsqueda ---
        $search_query = strtolower(trim($_GET['q']));
        $results = [];
        if (!empty($search_query)) {
            foreach ($scripts as $script) {
                $title = strtolower($script['title'] ?? '');
                $description = strtolower($script['description'] ?? '');
                if (strpos($title, $search_query) !== false || strpos($description, $search_query) !== false) {
                    $results[] = $script;
                }
            }
        }
        echo json_encode(['query' => $_GET['q'], 'results' => $results]);
        exit;

    case 'serve_html_page':
        // Cambiamos esto para que devuelva JSON en lugar de la página HTML de documentación.
        // El Content-Type ya está configurado como application/json por defecto al inicio del script.
        http_response_code(200);
        echo json_encode([
            'api_name' => 'StarLuck Scripts API',
            'message' => 'Bienvenido a la API gratuita de StarLuck Scripts. Para buscar scripts, usa el parámetro GET "q". Ejemplo: ?q=nombre_script',
            'status' => 'ok'
        ]);
        exit;

    default:
        // Si $action es null (ej. método POST no manejado explícitamente) o una acción desconocida
        send_error('Método no permitido o acción no especificada.', 405); // 405 Method Not Allowed
        // send_error ya incluye exit, por lo que un exit adicional aquí es redundante.
}

?>
