-- Migration: modificar ENUM de Gestion.tipo a los valores solicitados
-- Objetivo: cambiar la columna tipo a ENUM('ver','editar','banear','desbanear','cambiar_contrasena','cambiar_email')
-- Estrategia segura:
--  1) crear columna temporal tipo_new con el nuevo ENUM permitido (nullable)
--  2) mapear valores existentes de `tipo` a los nuevos tokens (heurística por contenido)
--  3) asignar un valor por defecto para filas no mapeadas
--  4) reemplazar la columna antigua por la nueva con NOT NULL

START TRANSACTION;

-- 1) Añadir columna temporal con los nuevos valores
ALTER TABLE `Gestion` 
  ADD COLUMN `tipo_new` ENUM('ver','editar','banear','desbanear','cambiar_contrasena','cambiar_email') NULL;

-- 2) Mapear valores existentes a los nuevos valores (heurística basada en texto)
UPDATE `Gestion` SET `tipo_new` = CASE
  WHEN LOWER(tipo) LIKE '%ver%' THEN 'ver'
  WHEN LOWER(tipo) LIKE '%editar%' OR LOWER(tipo) LIKE '%perfil%' THEN 'editar'
  WHEN LOWER(tipo) LIKE '%bane%' OR LOWER(tipo) LIKE '%baneo%' THEN 'banear'
  WHEN LOWER(tipo) LIKE '%desban%' OR LOWER(tipo) LIKE '%desbane%' THEN 'desbanear'
  WHEN LOWER(tipo) LIKE '%contras%' OR LOWER(tipo) LIKE '%contrase%' OR LOWER(tipo) LIKE '%password%' THEN 'cambiar_contrasena'
  WHEN LOWER(tipo) LIKE '%email%' OR LOWER(tipo) LIKE '%gmail%' OR LOWER(tipo) LIKE '%mail%' THEN 'cambiar_email'
  ELSE NULL
END;

-- 3) Para filas no mapeadas, asignar un valor por defecto 'ver' (evita fallos al cambiar a NOT NULL)
UPDATE `Gestion` SET `tipo_new` = 'ver' WHERE `tipo_new` IS NULL;

-- 4) Reemplazar la columna antigua por la nueva
ALTER TABLE `Gestion` DROP COLUMN `tipo`;
ALTER TABLE `Gestion` CHANGE `tipo_new` `tipo` ENUM('ver','editar','banear','desbanear','cambiar_contrasena','cambiar_email') NOT NULL;

COMMIT;

-- Nota: Ejecutar este script solo después de realizar un backup completo de la base de datos.
-- Si preferís aplicar solo la línea ALTER TABLE directamente (más arriesgado), consultame antes — este script evita errores por valores no válidos.
