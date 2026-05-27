-- =========================================
-- Script SQL para crear tablas de Combos
-- Ejecutar en la base de datos minimarketsystem
-- =========================================

CREATE TABLE IF NOT EXISTS `combos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `precio_combo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_regular` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Suma de precios individuales',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = Activo, 0 = Inactivo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `combo_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `combo_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `combo_id` (`combo_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `combo_detalles_ibfk_1` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `combo_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- Si ya creaste las tablas sin el campo foto,
-- ejecuta este ALTER TABLE:
-- =========================================
ALTER TABLE `combos` ADD COLUMN `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `precio_regular`;
