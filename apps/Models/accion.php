<?php
class accion
{
	public $IdAccion;
	public $tipo;
	public $descripcion;
	public $fecha;
	public $IdUsuario;
	public $IdUsuarioAdministrador;

	// Enum-like: lista canónica de tipos aceptados a nivel de modelo
	protected static $TIPOS_CANONICOS = array(
		'baneo', 'desbaneo', 'eliminar_usuario', 'editar_perfil',
		'cambiar_gmail', 'cambiar_contrasenia'
	);

	public function __construct($IdAccion, $tipo, $descripcion, $fecha, $IdUsuario, $IdUsuarioAdministrador) {
		$this->IdAccion = $IdAccion;
		$this->tipo = $tipo;
		$this->descripcion = $descripcion;
		$this->fecha = $fecha;
		$this->IdUsuario = $IdUsuario;
		$this->IdUsuarioAdministrador = $IdUsuarioAdministrador;
	}

	public function getIdAccion() { return $this->IdAccion; }

	public function getTipo() { return $this->tipo; }
	public function setTipo($tipo) {
		$tipoNorm = self::normalizeTipo($tipo);
		if (self::isValidTipo($tipoNorm)) {
			$this->tipo = $tipoNorm;
		} else {
			error_log("accion::setTipo - tipo inválido recibido: $tipo; usando 'baneo' como fallback");
			$this->tipo = 'baneo';
		}
	}

	/**
	 * Normaliza un token de acción recibido (acepta camelCase, variantes con/without tildes, etc)
	 * y lo convierte a un token canónico manejado por el modelo.
	 * @param string $tipo
	 * @return string tipo normalizado
	 */
	public static function normalizeTipo($tipo) {
		if (!is_string($tipo)) $tipo = (string)$tipo;
		$tipo = trim($tipo);
		// Convertir CamelCase a snake_case
		$tipoSnake = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $tipo));
		$tipoSnake = preg_replace('/[\s\-]+/', '_', $tipoSnake);
		$lookup = str_replace('_', '', $tipoSnake);
			// Mapa de alias a tokens canónicos (solo a los tipos canónicos definidos arriba)
			$aliasMap = array(
				'banear' => 'baneo',
				'baneo' => 'baneo',
				'desbanear' => 'desbaneo',
				'desbaneo' => 'desbaneo',
				'eliminar' => 'eliminar_usuario',
				'eliminar_usuario' => 'eliminar_usuario',
				'editar' => 'editar_perfil',
				'editar_perfil' => 'editar_perfil',
				'cambiarcontrasena' => 'cambiar_contrasenia',
				'cambiar_contrasena' => 'cambiar_contrasenia',
				'cambiar_contrasenia' => 'cambiar_contrasenia',
				'cambiaremail' => 'cambiar_gmail',
				'cambiar_email' => 'cambiar_gmail',
				'cambiar_gmail' => 'cambiar_gmail'
			);
		if (isset($aliasMap[$lookup])) return $aliasMap[$lookup];
		if (isset($aliasMap[$tipo])) return $aliasMap[$tipo];
		// Por defecto devolver el snake case
		return $tipoSnake;
	}

	/**
	 * Devuelve la lista de tipos canónicos conocidos por el modelo
	 * @return array
	 */
	public static function getTiposCanonicos() {
		return self::$TIPOS_CANONICOS;
	}

	/**
	 * Valida que el tipo esté en la lista canónica del modelo
	 * @param string $tipo
	 * @return bool
	 */
	public static function isValidTipo($tipo) {
		return in_array($tipo, self::getTiposCanonicos(), true);
	}

	public function getDescripcion() { return $this->descripcion; }
	public function setDescripcion($descripcion) { $this->descripcion = $descripcion; }

	public function getFecha() { return $this->fecha; }
	public function setFecha($fecha) { $this->fecha = $fecha; }

	public function getIdUsuario() { return $this->IdUsuario; }
	public function setIdUsuario($IdUsuario) { $this->IdUsuario = $IdUsuario; }

	public function getIdUsuarioAdministrador() { return $this->IdUsuarioAdministrador; }
	public function setIdUsuarioAdministrador($IdUsuarioAdministrador) { $this->IdUsuarioAdministrador = $IdUsuarioAdministrador; }

	/**
	 * Crear una nueva fila en la tabla Accion.
	 * @param string $tipo
	 * @param string|null $descripcion
	 * @param int|null $idUsuario
	 * @param int|null $idUsuarioAdministrador
	 * @return int|false Devuelve el IdAccion insertado o false en error
	 */
	public static function crear($tipo, $descripcion = null, $idUsuario = null, $idUsuarioAdministrador = null) {
		require_once __DIR__ . '/ConexionDB.php';
		try {
			// Normalizar el token de acción usando el enum-like del modelo
			$tipo = self::normalizeTipo($tipo);
			$db = new ConexionDB();
			$conn = $db->getConexion();
			// Obtener los valores permitidos del ENUM 'tipo' en la tabla 'accion'
			try {
				$schema = '';
				// Intentar obtener el nombre de la base de datos conectada
				$resDb = $conn->query("SELECT DATABASE() AS dbname");
				if ($resDb) {
					$rdb = $resDb->fetch_assoc();
					$schema = $rdb['dbname'] ?? '';
					$resDb->free_result();
				}
				$allowed = array();
				if (!empty($schema)) {
					$q = $conn->prepare("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'accion' AND COLUMN_NAME = 'tipo' LIMIT 1");
					if ($q) {
						$q->bind_param('s', $schema);
						$q->execute();
						$res = $q->get_result();
						if ($res && ($row = $res->fetch_assoc())) {
							$colType = $row['COLUMN_TYPE']; // e.g. enum('a','b')
							// Extraer valores entre comillas
							if (preg_match_all("/'([^']*)'/", $colType, $m)) {
								$allowed = $m[1];
							}
						}
						$q->close();
					}
				}
				// Si el tipo deseado no está en los permitidos, intentar mapear a tokens compatibles del DB
				if (!empty($allowed) && !in_array($tipo, $allowed, true)) {
					// Mapa explícito de compatibilidad modelo -> valor real en BD
					$compatibleMap = array(
						'banear' => 'baneo',
						'desbanear' => 'desbaneo',
						'editar_perfil' => 'editar_perfil',
						'cambiar_contrasena' => 'cambiar_contrasenia',
						'cambiar_email' => 'cambiar_gmail'
					);
					if (isset($compatibleMap[$tipo]) && in_array($compatibleMap[$tipo], $allowed, true)) {
						$tipo = $compatibleMap[$tipo];
						error_log("accion::crear - mapeado tipo '$tipo' a valor compatible en DB");
					} else {
						// Evitar mapear silenciosamente a un token no relacionado.
						if (in_array('baneo', $allowed, true)) {
							$tipo = 'baneo';
							error_log("accion::crear - tipo no está en ENUM; usando 'baneo' como fallback");
						} else {
							error_log("accion::crear - tipo '$tipo' no está en ENUM y no hay fallback disponible; abortando insert");
							return false;
						}
					}
				}
			} catch (Exception $e) {
				// No fatal: continuamos e intentamos el INSERT; si falla lo logueamos más abajo
				error_log('accion::crear warning al obtener ENUM: ' . $e->getMessage());
			}
			$fecha = date('Y-m-d H:i:s');
			$idUsuarioInt = $idUsuario ? intval($idUsuario) : 0;
			$idAdminInt = $idUsuarioAdministrador ? intval($idUsuarioAdministrador) : 0;
			$stmt = $conn->prepare("INSERT INTO Accion (tipo, descripcion, fecha, IdUsuario, IdUsuarioAdministrador) VALUES (?, ?, ?, ?, ?)");
			if (!$stmt) {
				error_log('accion::crear prepare failed: ' . $conn->error);
				return false;
			}
			$stmt->bind_param('sssii', $tipo, $descripcion, $fecha, $idUsuarioInt, $idAdminInt);
			$ok = $stmt->execute();
			if (!$ok) { $stmt->close(); return false; }
			$insertId = $conn->insert_id;
			$stmt->close();
			return $insertId;
		} catch (Exception $e) {
			error_log('accion::crear error: ' . $e->getMessage());
			return false;
		}
	}
}
?>