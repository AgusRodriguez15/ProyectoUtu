<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Models/categoria.php';

// Verificar si se especificó una acción
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'obtenerTodas':
            $categoria = new Categoria();
            $categorias = $categoria->obtenerTodas();
            echo json_encode([
                'success' => true,
                'data' => $categorias
            ]);
            break;
        case 'obtenerTodasConConteo':
            // Devuelve todas las categorías junto con la cantidad de servicios que las usan
            $db = new ConexionDB();
            $conn = $db->getConexion();
            $sql = "SELECT c.IdCategoria, c.Nombre, c.Descripcion, COUNT(p.IdServicio) as serviciosCount
                    FROM Categoria c
                    LEFT JOIN Pertenece p ON c.IdCategoria = p.IdCategoria
                    GROUP BY c.IdCategoria
                    ORDER BY c.Nombre ASC";
            $res = $conn->query($sql);
            $out = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $out]);
            break;
        case 'crear':
            // Crea una nueva categoría (espera POST: nombre, descripcion)
            $nombre = $_POST['nombre'] ?? null;
            $descripcion = $_POST['descripcion'] ?? '';
            if (!$nombre) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nombre de categoría requerido']);
                break;
            }
            $categoria = new Categoria();
            try {
                $id = $categoria->crear(trim($nombre), trim($descripcion));
                echo json_encode(['success' => true, 'data' => ['IdCategoria' => $id, 'Nombre' => $nombre, 'Descripcion' => $descripcion]]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'eliminar':
            // Eliminar categoría por id (espera POST: id)
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Id inválido']);
                break;
            }

            // Verificar si la categoría está siendo usada
            $db = new ConexionDB();
            $conn = $db->getConexion();
            $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM Pertenece WHERE IdCategoria = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $cnt = (int)($row['cnt'] ?? 0);
                $stmt->close();
                if ($cnt > 0) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'No se puede eliminar la categoría: está asociada a ' . $cnt . ' servicio(s)']);
                    break;
                }
            }

            $categoria = new Categoria();
            try {
                $ok = $categoria->eliminar($id);
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Categoría eliminada']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la categoría']);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Acción no especificada o inválida'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
