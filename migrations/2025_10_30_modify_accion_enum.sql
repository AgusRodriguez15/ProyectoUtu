-- Migration: ampliar ENUM de accion.tipo para incluir tokens administrativos nuevos
-- Añade: 'editar','banear','desbanear','cambiar_contrasena','cambiar_email'
-- NOTA: Este script sólo cambia el tipo ENUM. Ejecutar tras backup completo.

START TRANSACTION;

-- Modificar la columna tipo de la tabla 'accion' para incluir los nuevos tokens
ALTER TABLE `accion` 
  MODIFY COLUMN `tipo` ENUM(
    'borrar_comentario',
    'editar_datos_servicio',
    'desabilitar',
    'cancelar_reseñas',
    'borrar_servicio',
    'editar',
    'banear',
    'desbanear',
    'cambiar_contrasena',
    'cambiar_email'
  ) NOT NULL;

COMMIT;

-- Si la ejecución falla por valores no válidos existentes, revisar y normalizar los datos
-- previo a volver a correr el ALTER (por ejemplo con UPDATE para mapear valores antiguos a nuevos).
