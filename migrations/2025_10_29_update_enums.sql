-- Migration: actualizar ENUMs para gestion.tipo y accion.tipo
-- 1) Crear columna temporal tipo_new en Gestion con los nuevos valores
-- 2) Mapear valores existentes a los nuevos valores
-- 3) Reemplazar la columna tipo por la nueva ENUM
-- 4) Asegurar que accion.tipo tenga los valores correctos

START TRANSACTION;

-- 1) Añadir columna temporal
ALTER TABLE `Gestion` ADD COLUMN `tipo_new` ENUM('baneo','desbaneo','eliminar_usuario','editar_perfil','cambiar_gmail','cambiar_contraseña','habilitar') NULL;

-- 2) Mapear valores existentes a los nuevos valores (heurística)
UPDATE `Gestion` SET `tipo_new` = CASE
  WHEN LOWER(tipo) LIKE '%bane%' THEN 'baneo'
  WHEN LOWER(tipo) LIKE '%desbane%' THEN 'desbaneo'
  WHEN LOWER(tipo) LIKE '%contras%' OR LOWER(tipo) LIKE '%contrase%' THEN 'cambiar_contraseña'
  WHEN LOWER(tipo) LIKE '%gmail%' OR LOWER(tipo) LIKE '%email%' OR LOWER(tipo) LIKE '%mail%' THEN 'cambiar_gmail'
  WHEN LOWER(tipo) LIKE '%editar%' OR LOWER(tipo) LIKE '%perfil%' THEN 'editar_perfil'
  WHEN LOWER(tipo) LIKE '%habil%' THEN 'habilitar'
  WHEN LOWER(tipo) LIKE '%eliminar%' OR LOWER(tipo) LIKE '%borrar%' THEN 'eliminar_usuario'
  ELSE NULL
END;

-- 3) Para cualquier fila que no se mapeara, poner un valor por defecto 'editar_perfil' (evita fallos al cambiar a NOT NULL)
UPDATE `Gestion` SET `tipo_new` = 'editar_perfil' WHERE `tipo_new` IS NULL;

-- 4) Eliminar la columna antigua y renombrar la nueva
ALTER TABLE `Gestion` DROP COLUMN `tipo`;
ALTER TABLE `Gestion` CHANGE `tipo_new` `tipo` ENUM('baneo','desbaneo','eliminar_usuario','editar_perfil','cambiar_gmail','cambiar_contraseña','habilitar') NOT NULL;

-- 5) Asegurar que la tabla accion tenga el ENUM exacto
ALTER TABLE `accion` MODIFY COLUMN `tipo` ENUM('borrar_comentario','editar_datos_servicio','desabilitar','cancelar_reseñas','borrar_servicio') NOT NULL;

COMMIT;

-- Fin de migración
