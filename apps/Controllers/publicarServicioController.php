<?php

// Desactivar display_errors para que no contamine el JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Los errores se registrarán en el log de PHP pero no se mostrarán
ini_set('log_errors', 1);

require_once __DIR__ . '/../Models/servicio.php';
require_once __DIR__ . '/../Models/categoria.php';
require_once __DIR__ . '/../Models/foto.php';
require_once __DIR__ . '/../Models/palabraClave.php';

session_start();

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: ../../public/login.html");
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener y validar datos del POST
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
        $divisa = trim($_POST['divisa'] ?? 'UYU');
        
        // Validar campos obligatorios
        if (empty($titulo) || empty($descripcion)) {
            throw new Exception('El título y la descripción son obligatorios');
        }
        
        // Validar que el precio no sea negativo
        if ($precio < 0) {
            throw new Exception('El precio no puede ser negativo');
        }
        
        error_log("Datos recibidos: Titulo={$titulo}, Precio={$precio}, Divisa={$divisa}, IdProveedor={$_SESSION['IdUsuario']}");
        
        // Crear el servicio usando el método crear() del modelo
        $servicio = new Servicio();
        $idServicio = $servicio->crear([
            'nombre' => $titulo,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'divisa' => $divisa,
            'idProveedor' => $_SESSION['IdUsuario']
        ]);
        
        if (!$idServicio) {
            throw new Exception('Error al crear el servicio en la base de datos');
        }
        
        error_log("Servicio creado exitosamente con ID: {$idServicio}");
        
        // Guardar categorías
        if (isset($_POST['categoria']) && is_array($_POST['categoria'])) {
            $categoriasValidas = array_filter($_POST['categoria'], function($id) {
                return !empty($id) && is_numeric($id);
            });
            
            if (!empty($categoriasValidas)) {
                try {
                    Categoria::asociarCategoriasAServicio($idServicio, $categoriasValidas);
                    error_log("Categorías asociadas al servicio {$idServicio}: " . implode(', ', $categoriasValidas));
                } catch (Exception $e) {
                    error_log("Error al asociar categorías: " . $e->getMessage());
                    throw new Exception('Error al asociar las categorías: ' . $e->getMessage());
                }
            }
        }
        
        // Guardar palabras clave
        if (isset($_POST['palabrasClave']) && !empty($_POST['palabrasClave'])) {
            $palabras = explode(',', $_POST['palabrasClave']);
            $palabras = array_filter(array_map('trim', $palabras)); // Limpiar espacios y vacíos
            
            if (!empty($palabras)) {
                PalabraClave::guardarPalabrasClaveServicio($idServicio, $palabras);
                error_log("Palabras clave guardadas: " . implode(', ', $palabras));
            }
        }
        
        // Guardar fotos
        $fotosGuardadas = 0;
        if (isset($_FILES['fotos']) && isset($_FILES['fotos']['name'])) {
            $totalFotos = is_array($_FILES['fotos']['name']) ? count($_FILES['fotos']['name']) : 1;
            
            for ($i = 0; $i < $totalFotos && $fotosGuardadas < 5; $i++) {
                // Verificar si hay error en la carga
                $error = is_array($_FILES['fotos']['error']) ? $_FILES['fotos']['error'][$i] : $_FILES['fotos']['error'];
                
                if ($error === UPLOAD_ERR_OK) {
                    // Preparar array de foto en el formato esperado por el modelo
                    $archivoFoto = [
                        'tmp_name' => is_array($_FILES['fotos']['tmp_name']) ? $_FILES['fotos']['tmp_name'][$i] : $_FILES['fotos']['tmp_name'],
                        'name' => is_array($_FILES['fotos']['name']) ? $_FILES['fotos']['name'][$i] : $_FILES['fotos']['name']
                    ];
                    
                    try {
                        // El modelo se encarga de todo: mover archivo y guardar en BD
                        $rutaGuardada = Foto::guardarFoto($idServicio, $archivoFoto);
                        $fotosGuardadas++;
                    } catch (Exception $e) {
                        // Continuamos con las demás fotos
                    }
                }
            }
        }
        
        if ($fotosGuardadas === 0) {
            throw new Exception('Debe subir al menos una foto del servicio');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Servicio publicado exitosamente',
            'idServicio' => $idServicio,
            'fotosGuardadas' => $fotosGuardadas
        ]);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(400); // Bad Request en lugar de 500
    error_log("Error en publicarServicioController: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    http_response_code(500);
    error_log("Error fatal en publicarServicioController: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
    exit;
}

?>