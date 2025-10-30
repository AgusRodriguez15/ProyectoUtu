<?php
// Desactivar display de errores para que no interfiera con JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Models/servicio.php';
require_once __DIR__ . '/../Models/usuario.php';
require_once __DIR__ . '/../Models/dato.php';
require_once __DIR__ . '/../Models/habilidad.php';
require_once __DIR__ . '/../Models/proveedor.php';
require_once __DIR__ . '/../Models/palabraClave.php';
require_once __DIR__ . '/../Models/foto.php';
require_once __DIR__ . '/../Models/ubicacion.php';
require_once __DIR__ . '/../Models/accion.php';

session_start();

// Si se solicita una acción vía GET (por ejemplo desde el panel admin)
if (isset($_GET['action']) && $_GET['action'] === 'listarTodos') {
    try {
        // Devolver todos los servicios (incluye proveedor y estado)
        require_once __DIR__ . '/../Models/usuario.php';
        $db = new ConexionDB();
        $conn = $db->getConexion();
        $sql = "SELECT s.IdServicio, s.Nombre, s.Descripcion, s.Estado, s.IdProveedor, u.Nombre as ProveedorNombre, u.Apellido as ProveedorApellido, s.Precio, s.Divisa
                FROM Servicio s LEFT JOIN Usuario u ON s.IdProveedor = u.IdUsuario
                ORDER BY s.FechaPublicacion DESC";
        $res = $conn->query($sql);
        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'IdServicio' => $row['IdServicio'],
                    'Nombre' => $row['Nombre'],
                    'Descripcion' => $row['Descripcion'],
                    'Estado' => $row['Estado'],
                    'IdProveedor' => $row['IdProveedor'],
                    'ProveedorNombre' => trim(($row['ProveedorNombre'] ?? '') . ' ' . ($row['ProveedorApellido'] ?? '')),
                    'Precio' => $row['Precio'],
                    'Divisa' => $row['Divisa']
                ];
            }
        }
        echo json_encode($out);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

try {
    // Actualizar un servicio existente
    if (isset($_POST['actualizar']) && isset($_POST['id'])) {
        if (!isset($_SESSION['IdUsuario'])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        $idServicio = intval($_POST['id']);
        $servicio = Servicio::obtenerPorId($idServicio);
        if (!$servicio) {
            echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
            exit;
        }

        // Validar propietario
        $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
        if (!$proveedor || $servicio->IdProveedor !== $proveedor->getIdUsuario()) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar este servicio']);
            exit;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = $_POST['estado'] ?? 'DISPONIBLE';
        $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0.0;
        $divisa = $_POST['divisa'] ?? 'UYU';

        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
            exit;
        }

        if (!isset($_POST['precio'])) {
            echo json_encode(['success' => false, 'message' => 'El precio es obligatorio']);
            exit;
        }

        // Actualizar datos básicos
        $ok = Servicio::actualizarBasico($idServicio, $proveedor->getIdUsuario(), $nombre, $descripcion, $precio, $divisa, $estado);
        
        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el servicio']);
            exit;
        }

        // Manejar eliminación de fotos
        if (isset($_POST['fotosAEliminar'])) {
            $fotosAEliminar = json_decode($_POST['fotosAEliminar'], true);
            if (is_array($fotosAEliminar)) {
                foreach ($fotosAEliminar as $fotoUrl) {
                    // Extraer nombre del archivo de la URL
                    $nombreArchivo = basename($fotoUrl);
                    Foto::eliminarPorNombre($idServicio, $nombreArchivo);
                }
            }
        }

        // Manejar nuevas fotos
        if (isset($_FILES['nuevasFotos']) && !empty($_FILES['nuevasFotos']['name'][0])) {
            $uploadDir = '../../public/recursos/imagenes/servicios/';
            
            // Crear directorio si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['nuevasFotos']['name'] as $key => $nombreOriginal) {
                if ($_FILES['nuevasFotos']['error'][$key] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($extension, $extensionesPermitidas)) {
                        // Generar nombre único
                        $nombreArchivo = 'servicio_' . $idServicio . '_' . time() . '_' . $key . '.' . $extension;
                        $rutaCompleta = $uploadDir . $nombreArchivo;
                        
                        if (move_uploaded_file($_FILES['nuevasFotos']['tmp_name'][$key], $rutaCompleta)) {
                            // Guardar en base de datos
                            Foto::crear($idServicio, $nombreArchivo);
                        }
                    }
                }
            }
        }

        // Manejar palabras clave
        if (isset($_POST['palabrasClave'])) {
            $palabrasClave = json_decode($_POST['palabrasClave'], true);
            if (is_array($palabrasClave)) {
                try {
                    PalabraClave::actualizarPorServicio($idServicio, $palabrasClave);
                } catch (Exception $e) {
                    error_log("Error al actualizar palabras clave: " . $e->getMessage());
                }
            }
        } else {
            // Si no se enviaron palabras clave, eliminar todas las existentes
            try {
                PalabraClave::eliminarPorServicio($idServicio);
            } catch (Exception $e) {
                error_log("Error al eliminar palabras clave: " . $e->getMessage());
            }
        }
        
        // Manejar ubicaciones a eliminar
        if (isset($_POST['ubicacionesAEliminar'])) {
            $ubicacionesAEliminar = json_decode($_POST['ubicacionesAEliminar'], true);
            if (is_array($ubicacionesAEliminar) && !empty($ubicacionesAEliminar)) {
                error_log("Ubicaciones a eliminar: " . implode(', ', $ubicacionesAEliminar));
                foreach ($ubicacionesAEliminar as $idUbicacion) {
                    try {
                        ubicacion::eliminarDeServicio($idServicio, intval($idUbicacion));
                        error_log("Ubicación {$idUbicacion} eliminada del servicio {$idServicio}");
                    } catch (Exception $e) {
                        error_log("Error al eliminar ubicación {$idUbicacion}: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Manejar nuevas ubicaciones
        error_log("🔍 POST nuevasUbicaciones isset: " . (isset($_POST['nuevasUbicaciones']) ? 'SI' : 'NO'));
        if (isset($_POST['nuevasUbicaciones'])) {
            error_log("📦 POST nuevasUbicaciones RAW: " . $_POST['nuevasUbicaciones']);
            $nuevasUbicaciones = json_decode($_POST['nuevasUbicaciones'], true);
            error_log("🔄 JSON decode result: " . (is_array($nuevasUbicaciones) ? 'ES ARRAY' : 'NO ES ARRAY'));
            if (is_array($nuevasUbicaciones) && !empty($nuevasUbicaciones)) {
                error_log("✅ Nuevas ubicaciones a agregar: " . count($nuevasUbicaciones));
                error_log("📍 Datos ubicaciones: " . print_r($nuevasUbicaciones, true));
                foreach ($nuevasUbicaciones as $index => $ubicacion) {
                    try {
                        $resultado = ubicacion::crearYAsociarAServicio($idServicio, $ubicacion);
                        if ($resultado !== false) {
                            error_log("Nueva ubicación #{$index} agregada con ID: {$resultado}");
                        } else {
                            error_log("No se pudo agregar la ubicación #{$index}");
                        }
                    } catch (Exception $e) {
                        error_log("Error al agregar ubicación #{$index}: " . $e->getMessage());
                    }
                }
            }
        }

        // Manejar disponibilidades a eliminar
        if (isset($_POST['disponibilidadesAEliminar'])) {
            $disponibilidadesAEliminar = json_decode($_POST['disponibilidadesAEliminar'], true);
            if (is_array($disponibilidadesAEliminar) && !empty($disponibilidadesAEliminar)) {
                error_log("Disponibilidades a eliminar: " . implode(', ', $disponibilidadesAEliminar));
                foreach ($disponibilidadesAEliminar as $idDisponibilidad) {
                    try {
                        // Aquí iría la lógica de eliminación si tuvieras un método para ello
                        error_log("Eliminando disponibilidad ID: {$idDisponibilidad}");
                        // disponibilidad::eliminar($idDisponibilidad);
                    } catch (Exception $e) {
                        error_log("Error al eliminar disponibilidad {$idDisponibilidad}: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Manejar nuevas disponibilidades
        if (isset($_POST['nuevasDisponibilidades'])) {
            $nuevasDisponibilidades = json_decode($_POST['nuevasDisponibilidades'], true);
            if (is_array($nuevasDisponibilidades) && !empty($nuevasDisponibilidades)) {
                error_log("Nuevas disponibilidades a agregar: " . count($nuevasDisponibilidades));
                foreach ($nuevasDisponibilidades as $index => $disponibilidad) {
                    try {
                        $resultado = disponibilidad::crearParaServicio($idServicio, $disponibilidad);
                        if ($resultado !== false) {
                            error_log("Nueva disponibilidad #{$index} agregada con ID: {$resultado}");
                        } else {
                            error_log("No se pudo agregar la disponibilidad #{$index}");
                        }
                    } catch (Exception $e) {
                        error_log("Error al agregar disponibilidad #{$index}: " . $e->getMessage());
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Servicio actualizado correctamente']);
        exit;
    }
    // Si se solicita eliminar un servicio
    if (isset($_POST['eliminar']) && isset($_POST['idServicio'])) {
        if (!isset($_SESSION['IdUsuario'])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
        
        $idServicio = intval($_POST['idServicio']);
        $servicio = Servicio::obtenerPorId($idServicio);
        
        if (!$servicio) {
            echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
            exit;
        }
        
        // Verificar que el servicio pertenece al usuario actual
        $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
        
        if (!$proveedor || $servicio->IdProveedor !== $proveedor->getIdUsuario()) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este servicio']);
            exit;
        }
        
        // Eliminar el servicio y todas sus dependencias
        $eliminado = Servicio::eliminar($idServicio, $proveedor->getIdUsuario());
        
        if (!$eliminado) {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el servicio']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente']);
        exit;
    }
    // Eliminación por administrador (permitir borrar servicio aun si no es propietario)
    if (isset($_POST['eliminarAdmin']) && isset($_POST['idServicio'])) {
        // Verificar rol admin
        require_once __DIR__ . '/../Models/usuario.php';
        $usuarioSesion = null;
        if (isset($_SESSION['IdUsuario'])) {
            $usuarioSesion = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
        }
        if (!$usuarioSesion || $usuarioSesion->getRol() !== usuario::ROL_ADMIN) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        $idServicio = intval($_POST['idServicio']);
        $servicio = Servicio::obtenerPorId($idServicio);
        if (!$servicio) {
            echo json_encode(['success' => false, 'message' => 'Servicio no encontrado']);
            exit;
        }

        $idProveedor = $servicio->IdProveedor;
        $eliminado = Servicio::eliminar($idServicio, $idProveedor);
        if (!$eliminado) {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el servicio']);
            exit;
        }

        // Registrar acción en la tabla Accion mediante el modelo
        try {
            $motivo = $_POST['motivo'] ?? '';
            // usar el modelo accion para insertar
            accion::crear('borrar_servicio', $motivo, $idProveedor, $_SESSION['IdUsuario']);
        } catch (Exception $e) {
            error_log('Error registrando Accion (borrar_servicio): ' . $e->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Servicio eliminado correctamente (admin)']);
        exit;
    }

    // Cambiar estado (admin)
    if (isset($_POST['cambiarEstado']) && isset($_POST['idServicio']) && isset($_POST['Estado'])) {
        require_once __DIR__ . '/../Models/usuario.php';
        $usuarioSesion = null;
        if (isset($_SESSION['IdUsuario'])) {
            $usuarioSesion = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
        }
        if (!$usuarioSesion || $usuarioSesion->getRol() !== usuario::ROL_ADMIN) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
        $idServicio = intval($_POST['idServicio']);
        $nuevoEstado = trim($_POST['Estado']);
        $db = new ConexionDB(); $conn = $db->getConexion();
        $stmt = $conn->prepare("UPDATE Servicio SET Estado = ? WHERE IdServicio = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error preparando consulta']); exit;
        }
        $stmt->bind_param('si', $nuevoEstado, $idServicio);
        $ok = $stmt->execute();
        $stmt->close();
        // Registrar en Accion si el cambio fue exitoso
        if ($ok) {
            try {
                $motivo = $_POST['motivo'] ?? '';
                // si el nuevo estado no es DISPONIBLE lo consideramos una deshabilitación
                $tipoAcc = (strtoupper($nuevoEstado) !== 'DISPONIBLE') ? 'desabilitar' : 'editar_datos_servicio';
                // obtener el proveedor para registrar como IdUsuario
                $serv = Servicio::obtenerPorId($idServicio);
                $idProv = $serv ? $serv->IdProveedor : null;
                accion::crear($tipoAcc, $motivo, $idProv ? intval($idProv) : 0, $_SESSION['IdUsuario']);
            } catch (Exception $e) {
                error_log('Error registrando Accion (cambiarEstado): ' . $e->getMessage());
            }
        }

        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Estado actualizado' : 'No se pudo actualizar estado']);
        exit;
    }

    // Editar datos del servicio por administrador (no borrar)
    if (isset($_POST['editarAdmin']) && isset($_POST['idServicio'])) {
        require_once __DIR__ . '/../Models/usuario.php';
        $usuarioSesion = null;
        if (isset($_SESSION['IdUsuario'])) {
            $usuarioSesion = usuario::obtenerPor('IdUsuario', $_SESSION['IdUsuario']);
        }
        if (!$usuarioSesion || $usuarioSesion->getRol() !== usuario::ROL_ADMIN) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        $idServicio = intval($_POST['idServicio']);
        $nombre = trim($_POST['Nombre'] ?? '');
        $descripcion = trim($_POST['Descripcion'] ?? '');
        $precio = isset($_POST['Precio']) ? floatval($_POST['Precio']) : null;
        $divisa = $_POST['Divisa'] ?? null;
        $estado = $_POST['Estado'] ?? null;

        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']); exit;
        }

        $db = new ConexionDB(); $conn = $db->getConexion();
        $sql = "UPDATE Servicio SET Nombre = ?, Descripcion = ?, Precio = ?, Divisa = ?, Estado = ? WHERE IdServicio = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Error preparar consulta']); exit; }
        $stmt->bind_param('ssdsis', $nombre, $descripcion, $precio, $divisa, $estado, $idServicio);
        $ok = $stmt->execute();
        $stmt->close();
        // Registrar en Accion la edición de datos por admin
        if ($ok) {
            try {
                $motivo = $_POST['motivo'] ?? '';
                $tipoAcc = 'editar_datos_servicio';
                $serv = Servicio::obtenerPorId($idServicio);
                $idProv = $serv ? $serv->IdProveedor : null;
                accion::crear($tipoAcc, $motivo, $idProv ? intval($idProv) : 0, $_SESSION['IdUsuario']);
            } catch (Exception $e) {
                error_log('Error registrando Accion (editarAdmin): ' . $e->getMessage());
            }
        }

        echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Servicio actualizado (admin)' : 'No se pudo actualizar']);
        exit;
    }
    
    // Si se solicitan los servicios del proveedor actual
    if (isset($_POST['misServicios'])) {
        if (!isset($_SESSION['IdUsuario'])) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        // Obtener el IdProveedor del usuario actual
        $proveedor = proveedor::obtenerPorIdUsuario($_SESSION['IdUsuario']);
        
        if (!$proveedor) {
            echo json_encode([]);
            exit;
        }
        
        $servicios = Servicio::obtenerPorProveedor($proveedor->getIdUsuario());
        
        // Armar respuesta
        $respuesta = array_map(function ($s) {
            $fotos = [];
            if (!empty($s->Fotos) && is_array($s->Fotos)) {
                foreach ($s->Fotos as $foto) {
                    // Las fotos ahora vienen como strings con rutas completas
                    $fotos[] = [
                        'Url' => $foto,
                        'Descripcion' => ''
                    ];
                }
            }
            
            return [
                'IdServicio' => $s->IdServicio,
                'Nombre' => $s->Nombre,
                'Descripcion' => $s->Descripcion,
                'FechaPublicacion' => $s->FechaPublicacion,
                'Estado' => $s->Estado,
                'Fotos' => $fotos
            ];
        }, $servicios);
        
        echo json_encode($respuesta);
        exit;
    }
    
    // Si se solicita un servicio por ID
    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        error_log("Buscando servicio con ID: " . $id);
        
        $servicio = Servicio::obtenerPorId($id);
        
        if (!$servicio) {
            echo json_encode(['error' => 'Servicio no encontrado']);
            exit;
        }
        
        error_log("Servicio encontrado: " . $servicio->Nombre);
        
        // Obtener información del proveedor
        $nombreProveedor = 'Información no disponible';
        $descripcionProveedor = '';
        $fotoProveedor = '';
        $contactos = [];
        $habilidades = [];
        
        if ($servicio->IdProveedor) {
            try {
                $proveedor = usuario::obtenerPor('IdUsuario', $servicio->IdProveedor);
                
                if ($proveedor) {
                    $nombreProveedor = $proveedor->getNombre() . ' ' . $proveedor->getApellido();
                    $descripcionProveedor = $proveedor->getDescripcion() ?: '';
                    $fotoProveedor = $proveedor->getFotoPerfil() ?: '';
                    
                    // Obtener datos de contacto del proveedor
                    try {
                        $datosContacto = dato::obtenerPorUsuario($servicio->IdProveedor);
                        error_log("Contactos obtenidos: " . count($datosContacto));
                        foreach ($datosContacto as $dato) {
                            error_log("Tipo: " . $dato->Tipo . ", Contacto: " . $dato->Contacto);
                            $contactos[] = [
                                'tipo' => $dato->Tipo,
                                'contacto' => $dato->Contacto
                            ];
                        }
                        error_log("Total contactos agregados: " . count($contactos));
                    } catch (Exception $e) {
                        error_log("Error al obtener contactos: " . $e->getMessage());
                    }
                    
                    // Obtener habilidades del proveedor
                    try {
                        $habilidadesData = habilidad::obtenerPorUsuario($servicio->IdProveedor);
                        error_log("Habilidades obtenidas: " . count($habilidadesData));
                        foreach ($habilidadesData as $hab) {
                            $habilidades[] = [
                                'habilidad' => is_array($hab) ? $hab['Habilidad'] : $hab->Habilidad,
                                'experiencia' => is_array($hab) ? $hab['AniosExperiencia'] : $hab->AniosExperiencia
                            ];
                        }
                        error_log("Total habilidades agregadas: " . count($habilidades));
                    } catch (Exception $e) {
                        error_log("Error al obtener habilidades: " . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log("Error al obtener proveedor: " . $e->getMessage());
            }
        }
        
        // Obtener fotos del servicio (ahora son rutas completas como strings)
        $fotos = [];
        if (is_array($servicio->Fotos) && count($servicio->Fotos) > 0) {
            foreach ($servicio->Fotos as $foto) {
                // Si viene como objeto y tiene getRutaFoto
                if (is_object($foto) && method_exists($foto, 'getRutaFoto')) {
                    $fotos[] = $foto->getRutaFoto();
                } elseif (is_string($foto)) {
                    // Si ya es una ruta string completa
                    $fotos[] = $foto;
                }
            }
        }
        
        // Obtener palabras clave del servicio
        $palabrasClave = [];
        try {
            $palabrasClave = PalabraClave::obtenerPorServicio($servicio->IdServicio);
        } catch (Exception $e) {
            error_log("Error al obtener palabras clave: " . $e->getMessage());
        }

        // Obtener ubicaciones del servicio
        $ubicaciones = [];
        try {
            $ubicacionesData = ubicacion::obtenerPorServicio($servicio->IdServicio);
            error_log("Ubicaciones obtenidas: " . count($ubicacionesData));
            foreach ($ubicacionesData as $ub) {
                // Construir dirección completa
                $direccion = '';
                $ciudad = '';
                
                if (isset($ub['calle']) && !empty($ub['calle'])) {
                    $direccion = $ub['calle'];
                    if (isset($ub['numero']) && !empty($ub['numero'])) {
                        $direccion .= ' ' . $ub['numero'];
                    }
                }
                
                if (isset($ub['ciudad']) && !empty($ub['ciudad'])) {
                    $ciudad = $ub['ciudad'];
                }
                
                if (isset($ub['pais']) && !empty($ub['pais'])) {
                    if (!empty($ciudad)) {
                        $ciudad .= ', ' . $ub['pais'];
                    } else {
                        $ciudad = $ub['pais'];
                    }
                }
                
                // Solo agregar si hay alguna información de ubicación
                if (!empty($direccion) || !empty($ciudad)) {
                    $ubicaciones[] = [
                        'idUbicacion' => $ub['idUbicacion'],
                        'direccion' => $direccion,
                        'ciudad' => $ciudad
                    ];
                }
            }
            error_log("Total ubicaciones agregadas: " . count($ubicaciones));
        } catch (Exception $e) {
            error_log("Error al obtener ubicaciones: " . $e->getMessage());
        }

        // Obtener disponibilidades del servicio
        $disponibilidades = [];
        try {
            require_once __DIR__ . '/../Models/disponibilidad.php';
            $disponibilidadesData = disponibilidad::obtenerPorServicio($servicio->IdServicio);
            error_log("Disponibilidades obtenidas: " . count($disponibilidadesData));
            foreach ($disponibilidadesData as $disp) {
                $disponibilidades[] = [
                    'idDisponibilidad' => $disp->getIdDisponibilidad(),
                    'fechaInicio' => $disp->getFechaInicio(),
                    'fechaFin' => $disp->getFechaFin(),
                    'estado' => $disp->getEstado()
                ];
            }
            error_log("Total disponibilidades agregadas: " . count($disponibilidades));
        } catch (Exception $e) {
            error_log("Error al obtener disponibilidades: " . $e->getMessage());
        }

        $respuesta = [
            'id' => $servicio->IdServicio,
            'nombre' => $servicio->Nombre,
            'descripcion' => $servicio->Descripcion,
            'precio' => property_exists($servicio, 'Precio') ? $servicio->Precio : null,
            'divisa' => property_exists($servicio, 'Divisa') ? $servicio->Divisa : 'UYU',
            'foto' => $servicio->getFotoServicio(),
            'fotos' => $fotos,
            'palabrasClave' => $palabrasClave,
            'fechaPublicacion' => $servicio->FechaPublicacion,
            'estado' => $servicio->Estado,
            'ubicaciones' => $ubicaciones,
            'disponibilidades' => $disponibilidades,
            'proveedor' => [
                'nombre' => $nombreProveedor,
                'descripcion' => $descripcionProveedor,
                'foto' => $fotoProveedor,
                'contactos' => $contactos,
                'habilidades' => $habilidades,
                'idUsuario' => $servicio->IdProveedor
            ]
        ];
        
        echo json_encode($respuesta);
        exit;
    }
    
    // Capturar término de búsqueda (búsqueda por defecto si no hay otro parámetro)
    $termino = isset($_POST['q']) ? trim($_POST['q']) : '';

    // Buscar servicios
    $servicios = Servicio::buscarPorCategoriaYTitulo($termino);

    // Armar respuesta para el front (solo datos necesarios)
    $respuesta = array_map(function ($s) {
        return [
            'id' => $s->IdServicio,
            'nombre' => $s->Nombre,
            'descripcion' => $s->Descripcion,
            'foto' => $s->getFotoServicio()
        ];
    }, $servicios);

    echo json_encode($respuesta);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
