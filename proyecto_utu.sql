-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-10-2025 a las 03:43:56
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `proyecto_utu`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accion`
--

CREATE TABLE `accion` (
  `IdAccion` int(11) NOT NULL,
  `tipo` enum('borrar_comentario','editar_datos_servicio','desabilitar','cancelar_reseñas','borrar_servicio') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `IdUsuario` int(11) DEFAULT NULL,
  `IdUsuarioAdministrador` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `accion`
--

INSERT INTO `accion` (`IdAccion`, `tipo`, `descripcion`, `fecha`, `IdUsuario`, `IdUsuarioAdministrador`) VALUES
(1, '', 'Servicio creado por proveedor', '2025-09-14 20:23:55', 3, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administrador`
--

CREATE TABLE `administrador` (
  `IdUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administrador`
--

INSERT INTO `administrador` (`IdUsuario`) VALUES
(2),
(40),
(41);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `IdCategoria` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`IdCategoria`, `Nombre`, `Descripcion`) VALUES
(1, 'hogar', 'Servicios de mantenimiento del hogar'),
(2, 'jardiner??a', 'Cuidado de jardines y espacios verdes'),
(3, 'Carpinter??a', 'Trabajos en madera, muebles a medida, reparaciones y restauraciones'),
(4, 'Electricidad', 'Instalaciones el??ctricas, reparaciones y mantenimiento el??ctrico'),
(5, 'Plomer??a', 'Servicios de fontaner??a, instalaci??n y reparaci??n de ca??er??as'),
(6, 'Jardiner??a', 'Mantenimiento de jardines, poda, dise??o de paisajes'),
(7, 'Pintura', 'Servicios de pintura interior y exterior, decoraci??n'),
(8, 'Limpieza', 'Servicios de limpieza profesional para hogares y oficinas'),
(9, 'Inform??tica', 'Reparaci??n de computadoras, soporte t??cnico, instalaci??n de software'),
(10, 'Alba??iler??a', 'Construcci??n, reparaciones, reformas generales'),
(11, 'Cerrajer??a', 'Servicios de cerrajer??a, instalaci??n y reparaci??n de cerraduras'),
(12, 'Mec??nica', 'Reparaci??n y mantenimiento de veh??culos'),
(13, 'Dise??o Gr??fico', 'Servicios de dise??o gr??fico, logos, folletos'),
(14, 'Fotograf??a', 'Servicios fotogr??ficos profesionales'),
(15, 'Educaci??n', 'Clases particulares y tutor??as'),
(16, 'Belleza', 'Servicios de peluquer??a, manicura, maquillaje'),
(17, 'Transporte', 'Servicios de mudanza y transporte de carga');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `IdUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`IdUsuario`) VALUES
(2),
(18),
(21),
(23),
(31),
(32),
(38);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dato`
--

CREATE TABLE `dato` (
  `IdDato` int(11) NOT NULL,
  `IdUsuario` int(11) DEFAULT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  `Contacto` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dato`
--

INSERT INTO `dato` (`IdDato`, `IdUsuario`, `Tipo`, `Contacto`) VALUES
(5, 31, 'telfono', '092906813'),
(6, 31, 'telfono', '094389088'),
(7, 32, 'gmail', 'asddasdsas'),
(42, 34, 'telefono', '369258'),
(43, 34, 'gmail', '123@gmail.uy');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad`
--

CREATE TABLE `disponibilidad` (
  `IdDisponibilidad` int(11) NOT NULL,
  `IdServicio` int(11) DEFAULT NULL,
  `FechaInicio` datetime NOT NULL,
  `FechaFin` datetime NOT NULL,
  `Estado` enum('disponible','ocupado','no_disponible') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `disponibilidad`
--

INSERT INTO `disponibilidad` (`IdDisponibilidad`, `IdServicio`, `FechaInicio`, `FechaFin`, `Estado`) VALUES
(1, 1, '2025-09-14 20:24:51', '2025-09-14 22:24:51', 'ocupado'),
(2, 2, '2025-09-14 20:24:51', '2025-09-14 23:24:51', 'disponible'),
(5, 31, '2025-10-22 23:12:00', '2025-10-23 00:30:00', 'ocupado'),
(6, 31, '2025-10-30 22:12:00', '2025-10-31 23:12:00', 'ocupado'),
(7, 32, '2025-10-22 23:24:00', '2025-10-23 00:24:00', 'ocupado'),
(8, 32, '2025-10-31 10:07:00', '2025-10-31 11:00:00', 'disponible'),
(9, 33, '2025-10-24 22:44:00', '2025-10-24 23:44:00', 'ocupado'),
(10, 33, '2025-10-30 22:44:00', '2025-10-31 00:44:00', 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `foto`
--

CREATE TABLE `foto` (
  `IdFoto` int(11) NOT NULL,
  `IdServicio` int(11) DEFAULT NULL,
  `Foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `foto`
--

INSERT INTO `foto` (`IdFoto`, `IdServicio`, `Foto`) VALUES
(1, 1, 'grifos.jpg'),
(2, 2, 'jardin.jpg'),
(3, 8, 'sistemas.jpg'),
(8, 17, 'servicio_17_68efdc9c9b00c.png'),
(10, 18, 'servicio_18_68efebad6c920.png'),
(11, 19, 'servicio_19_68efed3cad0f4.png'),
(12, 19, 'servicio_19_1760663572_0.jpg'),
(13, 17, 'servicio_17_1760668331_0.jpg'),
(14, 20, 'servicio_20_68f1adfba0999.jpg'),
(15, 20, 'servicio_20_68f1adfbcc317.jpg'),
(16, 20, 'servicio_20_68f1adfbd85e3.jpg'),
(17, 20, 'servicio_20_68f1adfbe3801.jpg'),
(18, 20, 'servicio_20_68f1adfc0128e.jpg'),
(19, 21, 'servicio_21_68f2de2adef5a.jpg'),
(20, 21, 'servicio_21_68f2de2ae64e1.png'),
(21, 27, 'servicio_27_68f58c89a7871.jpg'),
(22, 28, 'servicio_28_68f6ee42e23c1.jpg'),
(23, 29, 'servicio_29_68f6f4684b6d6.jpg'),
(25, 31, 'servicio_31_68f82f7674dec.png'),
(26, 32, 'servicio_32_68f840915adb5.png'),
(27, 33, 'servicio_33_68fada2253e31.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestion`
--

CREATE TABLE `gestion` (
  `IdGestion` int(11) NOT NULL,
  `tipo` enum('baneo','desbaneo','eliminar_usuario','editar_perfil','cambiar_gmail','cambiar_contraseña') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `IdAdministrador` int(11) DEFAULT NULL,
  `IdServicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gestion`
--

INSERT INTO `gestion` (`IdGestion`, `tipo`, `descripcion`, `fecha`, `IdAdministrador`, `IdServicio`) VALUES
(1, 'baneo', 'Baneo de usuario IdUsuario=18 por administrador Id=41', '2025-10-29 14:11:02', 41, NULL),
(2, 'baneo', 'Baneo de usuario IdUsuario=3 por administrador Id=41. Motivo: tiempo de baneo completo', '2025-10-29 14:15:34', 41, NULL),
(3, 'desbaneo', 'Desbaneo de usuario IdUsuario=18 por administrador Id=41. Motivo: tiempo completo', '2025-10-29 14:15:53', 41, NULL),
(4, 'desbaneo', 'porque pinto', '2025-10-29 20:36:18', 41, NULL),
(5, 'baneo', 'se llama carlos', '2025-10-29 20:36:33', 41, NULL),
(6, '', 'aaa', '2025-10-29 21:08:50', 41, NULL),
(7, '', 'Usuario eliminado por administrador', '2025-10-29 23:45:02', 41, NULL),
(8, '', 'Usuario eliminado por administrador', '2025-10-29 23:45:32', 41, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habilidad`
--

CREATE TABLE `habilidad` (
  `IdHabilidad` int(11) NOT NULL,
  `IdUsuario` int(11) DEFAULT NULL,
  `Habilidad` varchar(100) DEFAULT NULL,
  `AniosExperiencia` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `habilidad`
--

INSERT INTO `habilidad` (`IdHabilidad`, `IdUsuario`, `Habilidad`, `AniosExperiencia`) VALUES
(1, 3, 'Plomer??a', 0),
(2, 3, 'Electricidad', 0),
(58, 34, 'carpintero', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensaje`
--

CREATE TABLE `mensaje` (
  `IdMensaje` int(11) NOT NULL,
  `Contenido` text NOT NULL,
  `Fecha` datetime NOT NULL,
  `Estado` enum('enviado','leido','eliminado') DEFAULT 'enviado',
  `IdUsuarioEmisor` int(11) DEFAULT NULL,
  `IdUsuarioReceptor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palabraclave`
--

CREATE TABLE `palabraclave` (
  `IdPalabraClave` int(11) NOT NULL,
  `Palabra` varchar(100) DEFAULT NULL,
  `IdServicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `palabraclave`
--

INSERT INTO `palabraclave` (`IdPalabraClave`, `Palabra`, `IdServicio`) VALUES
(1, 'grifos', 1),
(2, 'corte de c??sped', 2),
(43, 'pelo', 19),
(44, 'tijera', 19),
(45, 'hogar', 19),
(46, 'madera', 17),
(47, 'metal', 17),
(48, 'perro', 17),
(49, 'ajedrez', 18),
(50, 'tablero', 18),
(51, 'yo', 18),
(52, 'pasto', 20),
(53, 'cortar', 20),
(54, 'corta', 20),
(55, 'casa', 20),
(56, 'adsa', 21),
(57, 'sd', 21),
(58, 's', 21),
(59, 'd', 21),
(60, 'w', 21),
(81, 'matematica', 27),
(82, 'quimica', 27),
(83, 'historia', 27),
(84, 'economia', 27),
(85, 'fr', 28),
(86, 'x', 28),
(87, 'z', 28),
(88, 'a', 28),
(89, 'q', 28),
(111, 'limpio', 29),
(112, 'sucio', 29),
(113, 'escoba', 29),
(119, 'kulik', 31),
(120, 'nahiara', 31),
(121, 'agustin', 31),
(122, 'pablo', 31),
(123, 'clase', 33),
(124, 'educacion', 33);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pertenece`
--

CREATE TABLE `pertenece` (
  `IdServicio` int(11) NOT NULL,
  `IdCategoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pertenece`
--

INSERT INTO `pertenece` (`IdServicio`, `IdCategoria`) VALUES
(1, 1),
(2, 2),
(17, 1),
(17, 4),
(17, 10),
(18, 1),
(18, 14),
(18, 15),
(19, 9),
(19, 12),
(20, 1),
(20, 10),
(21, 5),
(21, 7),
(21, 11),
(27, 10),
(27, 15),
(28, 4),
(28, 8),
(28, 10),
(29, 8),
(29, 16),
(31, 3),
(31, 11),
(32, 1),
(32, 15),
(33, 1),
(33, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `IdUsuario` int(11) NOT NULL,
  `AniosExperiencia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`IdUsuario`, `AniosExperiencia`) VALUES
(3, 5),
(22, 0),
(30, 0),
(33, 0),
(34, 0),
(35, 0),
(36, 0),
(37, 0),
(39, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenia`
--

CREATE TABLE `resenia` (
  `IdResenia` int(11) NOT NULL,
  `Comentario` text DEFAULT NULL,
  `Puntuacion` int(11) DEFAULT NULL CHECK (`Puntuacion` between 1 and 5),
  `Fecha` datetime DEFAULT NULL,
  `IdUsuario` int(11) DEFAULT NULL,
  `IdServicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resenia`
--

INSERT INTO `resenia` (`IdResenia`, `Comentario`, `Puntuacion`, `Fecha`, `IdUsuario`, `IdServicio`) VALUES
(14, 'servicio muy malo', 2, '2025-10-19 19:34:07', 18, 21),
(15, 'muy bueno, mejor que el nuestro', 5, '2025-10-24 03:45:56', 18, 33);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

CREATE TABLE `reserva` (
  `IdReserva` int(11) NOT NULL,
  `IdDisponibilidad` int(11) DEFAULT NULL,
  `Estado` enum('pendiente','confirmada','cancelada','finalizada') DEFAULT 'pendiente',
  `Observacion` text DEFAULT NULL,
  `IdUsuario` int(11) DEFAULT NULL,
  `IdServicio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`IdReserva`, `IdDisponibilidad`, `Estado`, `Observacion`, `IdUsuario`, `IdServicio`) VALUES
(2, NULL, 'pendiente', NULL, 2, 2),
(5, NULL, 'pendiente', 'sauce', 18, 31),
(6, NULL, 'pendiente', 'ahora', 38, 31),
(7, 7, 'finalizada', '', 18, 32),
(8, 9, 'confirmada', 'en Montevideo', 18, 33);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicio`
--

CREATE TABLE `servicio` (
  `IdServicio` int(11) NOT NULL,
  `Nombre` varchar(150) NOT NULL,
  `Descripcion` text DEFAULT NULL,
  `Precio` decimal(10,2) NOT NULL,
  `Divisa` char(3) NOT NULL,
  `FechaPublicacion` datetime NOT NULL,
  `Estado` enum('DISPONIBLE','NO_DISPONIBLE') NOT NULL,
  `IdProveedor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicio`
--

INSERT INTO `servicio` (`IdServicio`, `Nombre`, `Descripcion`, `Precio`, `Divisa`, `FechaPublicacion`, `Estado`, `IdProveedor`) VALUES
(1, 'Reparaci??n de grifos', 'Reparaci??n r??pida y efectiva de grifos', 0.00, '', '2025-09-14 20:23:54', 'DISPONIBLE', 3),
(2, 'Mantenimiento de jard??n', 'Corte de c??sped y poda de ??rboles', 0.00, '', '2025-09-14 20:23:54', 'DISPONIBLE', 3),
(8, 'cortar so', 'asdsadsadsadsads', 0.00, '', '2025-09-16 12:27:05', 'DISPONIBLE', 3),
(17, 'ferreter??a de metal', 'trabajo con cobre', 200.00, 'EUR', '2025-10-15 14:40:44', 'NO_DISPONIBLE', 34),
(18, 'ajedrez', 'lllllllllllllllllllllllfkgh', 150.00, 'UYU', '2025-10-15 15:45:00', 'DISPONIBLE', 34),
(19, 'peluquero', 'servicio', 100.00, 'EUR', '2025-10-15 15:51:40', 'NO_DISPONIBLE', 34),
(20, 'cortar pasto', 'corto pasta', 200.00, 'USD', '2025-10-16 23:46:19', 'DISPONIBLE', 34),
(21, 'adadasd', 'asdadsdaddas', 500.00, 'USD', '2025-10-17 21:24:10', 'DISPONIBLE', 34),
(27, 'clases de matematica', 'asdasasdda', 75.00, 'EUR', '2025-10-19 22:12:41', 'DISPONIBLE', 34),
(28, 'asasasas', '1qw23e4r5t', 457.00, 'EUR', '2025-10-20 23:21:54', 'DISPONIBLE', 34),
(29, 'limpieza', 'limpiando', 2000.00, 'UYU', '2025-10-20 23:48:07', 'DISPONIBLE', 37),
(31, 'nicolas', 'asdsd', 15.00, 'USD', '2025-10-21 22:12:21', 'DISPONIBLE', 37),
(32, 'clases de quimica', 'asdasdasdasdadsadadasdlllllllllllllllllllll', 48.00, 'EUR', '2025-10-21 23:25:20', 'DISPONIBLE', 37),
(33, 'clases de sitemas operativos', 'doy clases', 50.00, 'USD', '2025-10-23 22:45:05', 'DISPONIBLE', 39);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicioubicacion`
--

CREATE TABLE `servicioubicacion` (
  `IdServicio` int(11) NOT NULL,
  `IdUbicacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicioubicacion`
--

INSERT INTO `servicioubicacion` (`IdServicio`, `IdUbicacion`) VALUES
(1, 1),
(1, 2),
(2, 2),
(2, 3),
(28, 5),
(29, 6),
(29, 7),
(31, 10),
(31, 11),
(32, 12),
(32, 13),
(33, 14),
(33, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion`
--

CREATE TABLE `ubicacion` (
  `IdUbicacion` int(11) NOT NULL,
  `Pais` varchar(100) NOT NULL,
  `Ciudad` varchar(100) DEFAULT NULL,
  `Calle` varchar(150) DEFAULT NULL,
  `Numero` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ubicacion`
--

INSERT INTO `ubicacion` (`IdUbicacion`, `Pais`, `Ciudad`, `Calle`, `Numero`) VALUES
(1, 'Uruguay', 'Montevideo', 'Av. 18 de Julio', 1000),
(2, 'Uruguay', 'Montevideo', 'Bulevar Artigas', 200),
(3, 'Uruguay', 'Punta del Este', 'Rambla Claudio Williman', 50),
(4, 'argentina', 'cordoba', '18 de mayo', 1234),
(5, 'ecuador', 'guayaquil', 'la avenida', NULL),
(6, 'Uruguay', 'Sauce', 'Camino Berruti Mevir3 Casa 32732', 32732),
(7, 'argentina', 'buenos aires', NULL, NULL),
(8, 'chile', NULL, NULL, NULL),
(9, 'canada', 'vancover', 'av canada 5', 5),
(10, 'Uruguay', 'artiga', NULL, NULL),
(11, 'colombia', NULL, NULL, NULL),
(12, 'republica dominicana', 'quito', 'av italia', NULL),
(13, 'laos', NULL, NULL, NULL),
(14, 'uruguay', 'montevideo', NULL, NULL),
(15, 'uruguay', 'canelones', NULL, NULL),
(16, 'argentina', 'cordoba', '18 de mayo', 1234);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `IdUsuario` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `ContrasenaHash` varchar(255) NOT NULL,
  `FotoPerfil` varchar(255) DEFAULT NULL,
  `Descripcion` text DEFAULT NULL,
  `FechaRegistro` datetime NOT NULL,
  `EstadoCuenta` enum('ACTIVO','INACTIVO','BANEADO') NOT NULL,
  `UltimoAcceso` datetime DEFAULT NULL,
  `IdUbicacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`IdUsuario`, `Nombre`, `Apellido`, `Email`, `ContrasenaHash`, `FotoPerfil`, `Descripcion`, `FechaRegistro`, `EstadoCuenta`, `UltimoAcceso`, `IdUbicacion`) VALUES
(2, 'Ana', 'Gomez', 'ana@example.com', 'hash2', NULL, NULL, '2025-09-14 20:23:53', 'INACTIVO', NULL, NULL),
(3, 'Carlos', 'Lopez', 'carlos@example.com', 'hash3', NULL, NULL, '2025-09-14 20:23:53', 'BANEADO', NULL, NULL),
(18, 'Mario', 'Martello', 'benjaminmartello1509@gmail.com', '$2y$10$pfE9RIt7okmphpliYMICgeK8bTeHMQxGluje4aS2Xxulr8OvTiD2m', 'WhatsApp Image 2025-10-29 at 16.06.26.jpeg', 'asdasdsad', '2025-09-15 09:37:18', 'INACTIVO', NULL, 16),
(21, 'Mario', 'Martello', 'inmartello1509@gmail.com', '$2y$10$TcekCHbD0sh54k52GQuLTO9eH3JHW/VQFQDX0BfUfJ4kDJ1zVT/IW', 'der.png', '', '2025-09-17 18:39:03', 'INACTIVO', NULL, NULL),
(22, 'Mario', 'garcia', 'benjamin@gmail.com', '$2y$10$olJKRo9UA5oN62UZrRMQ/.kRUca51YsVcuUkleJwoV7wBlTxjjkfu', NULL, '', '2025-09-22 01:49:51', 'INACTIVO', NULL, NULL),
(23, 'jajjsada', 'daiiaddiasd', 'enjaminmartello1509@gmail.com', '$2y$10$yDz4JKUDgyDw6zQ4NJO5ROSfJ4AAdIZLHuRKSrd14ynQ0q.wrZdZy', 'WhatsApp Image 2025-05-20 at 01.24.13.jpeg', '', '2025-10-02 12:30:36', 'INACTIVO', NULL, NULL),
(24, 'Mario', 'Martello', 'benjaminmartello@gmail.com', '$2y$10$aR.ut0O.5jWYTO1Vk4gLQO4PeOr4.nhy7kiTSNe7ysaGfGp7Ii//.', NULL, NULL, '2025-10-09 09:29:54', 'INACTIVO', NULL, NULL),
(25, 'Benjam??n', 'Martello', '12@hotmail.com', '$2y$10$ntbbpQ8C300bbWNs.zJZY.CNKCea8SB.MbiDPIiMAmXOnh0aseXFW', NULL, NULL, '2025-10-10 09:31:51', 'INACTIVO', NULL, NULL),
(26, 'Benjam??n', 'Martello', '21@gmail.com', '$2y$10$tA/KyJNdu9fXDOXXNI3UKuuOmTy5cNkgoS/bnyopell5nTfcbxu.q', NULL, NULL, '2025-10-10 09:54:40', 'INACTIVO', NULL, NULL),
(28, 'Benjam??n', 'Martello', '22@gmail.com', '$2y$10$3mEw1bRMofT18bRoK2u05OWTvf1avsLEHfdSxQjui.5yOk6LSGfGq', NULL, NULL, '2025-10-10 10:00:00', 'INACTIVO', NULL, NULL),
(29, 'Benjam??n', 'Martello', '29@gmail.com', '$2y$10$KrS66iPC9D9vMPuvD87Y8eY42Mcrr5RAicBTc8GW08lGcecZfllAq', NULL, NULL, '2025-10-10 15:11:45', 'INACTIVO', NULL, NULL),
(30, 'asdsad', 'asdasd', 'asdads@adsasdasda.com', '$2y$10$nbuDuvrQCqOjpsBfSIIrFuLVGYrnjVwwwEKnDro6BQsRta0pt1MJa', NULL, NULL, '2025-10-10 15:41:27', 'INACTIVO', NULL, NULL),
(31, 'aa', 'aa', 'aa@gmail.com', '$2y$10$zImlrbS4qK92IYefomEleuhYdtgnxdAuBdcw5P0ZZ/.M68d9uc7GK', NULL, '', '2025-10-10 15:45:24', 'INACTIVO', NULL, NULL),
(32, 'Benjam??n', 'Martello', '451509@gmail.com', '$2y$10$o3AMqgI1FrPYyAZSUCmXFe2fGqQBI67l6kmKyKG.QDgVMsg4chh0q', 'der.png', '', '2025-10-12 03:59:40', 'INACTIVO', NULL, NULL),
(33, 'Benjam??n', 'Martello', '5656@gmail.com.uy', '$2y$10$OG2RmjexZELuhJYfPVfxeupnPLQXTO1Te1UnqRbq4JrLzwGsNqetG', NULL, NULL, '2025-10-14 13:03:47', 'INACTIVO', NULL, NULL),
(34, 'adsd', 'asdsa', 'yo@gmail.com', '$2y$10$UVutYKmsczIrmPJpzXoAXusXrqUg9oqh4hS052BBkHAKgTZQ7Rfa2', 'WhatsApp Image 2025-05-23 at 18.00.44.jpeg', '', '2025-10-14 13:54:55', 'INACTIVO', NULL, NULL),
(35, 'Mario', 'Martello', 'ben@gmail.com', '$2y$10$rfwyiCnFmBi/MnLcaMXQYuvVDbXbneOl8Yma0jH8JVxoxQeuDYMSS', NULL, NULL, '2025-10-14 19:23:14', 'INACTIVO', NULL, NULL),
(36, 'Benjam??n', 'Martello', 'aaa@gmail.com', '$2y$10$yS6kPaayMyfCoG/r12vCBe6PPx138PI9dm8Svc4eRtXo7S26.mRKO', NULL, NULL, '2025-10-15 15:34:07', 'INACTIVO', NULL, NULL),
(37, 'alicia', 'lopez', 'ella@gmail.com', '$2y$10$kZeBpOL4qun10URlxdqiqe3ukIckDFWll2HEf/R3TR9f1niLU40qm', 'estado.png', '', '2025-10-21 04:46:47', 'INACTIVO', NULL, NULL),
(38, 'nicolas', 'kulik', 'el@gmail.com', '$2y$10$3TCppXUyZ35ZN.VEjBJX2eUC9wkUllwy0gvEiHpVQzpLm4L39cPVG', NULL, NULL, '2025-10-22 03:13:45', 'INACTIVO', NULL, NULL),
(39, 'pablo', 'martinez', 'pablo@gmail.com.uy', '$2y$10$quM0LvWNVij9/PnBXsrHkuY3ytz4f2.LnMlvaDXssZnsgxzh.PjN6', NULL, NULL, '2025-10-24 03:42:57', 'INACTIVO', NULL, NULL),
(40, 'admin', 'admin', 'admin@gmail.com', '123123', NULL, NULL, '2025-10-28 22:43:58', 'ACTIVO', NULL, NULL),
(41, 'Administrador', 'Sistema', 'admin@ejemplo.com', '$2y$10$.iLE6/oW7rtX3cfnXSGoluFXVtGTDgARYQ/r0ljhX31SNBhitq2Ju', NULL, NULL, '2025-10-29 02:14:25', 'ACTIVO', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accion`
--
ALTER TABLE `accion`
  ADD PRIMARY KEY (`IdAccion`),
  ADD KEY `IdUsuario` (`IdUsuario`),
  ADD KEY `IdUsuarioAdministrador` (`IdUsuarioAdministrador`);

--
-- Indices de la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD PRIMARY KEY (`IdUsuario`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`IdCategoria`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`IdUsuario`);

--
-- Indices de la tabla `dato`
--
ALTER TABLE `dato`
  ADD PRIMARY KEY (`IdDato`),
  ADD KEY `IdUsuario` (`IdUsuario`);

--
-- Indices de la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  ADD PRIMARY KEY (`IdDisponibilidad`),
  ADD KEY `IdServicio` (`IdServicio`);

--
-- Indices de la tabla `foto`
--
ALTER TABLE `foto`
  ADD PRIMARY KEY (`IdFoto`),
  ADD KEY `IdServicio` (`IdServicio`);

--
-- Indices de la tabla `gestion`
--
ALTER TABLE `gestion`
  ADD PRIMARY KEY (`IdGestion`),
  ADD KEY `IdAdministrador` (`IdAdministrador`),
  ADD KEY `IdServicio` (`IdServicio`);

--
-- Indices de la tabla `habilidad`
--
ALTER TABLE `habilidad`
  ADD PRIMARY KEY (`IdHabilidad`),
  ADD KEY `IdUsuario` (`IdUsuario`);

--
-- Indices de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`IdMensaje`),
  ADD KEY `IdUsuarioEmisor` (`IdUsuarioEmisor`),
  ADD KEY `IdUsuarioReceptor` (`IdUsuarioReceptor`);

--
-- Indices de la tabla `palabraclave`
--
ALTER TABLE `palabraclave`
  ADD PRIMARY KEY (`IdPalabraClave`),
  ADD KEY `IdServicio` (`IdServicio`);

--
-- Indices de la tabla `pertenece`
--
ALTER TABLE `pertenece`
  ADD PRIMARY KEY (`IdServicio`,`IdCategoria`),
  ADD KEY `IdCategoria` (`IdCategoria`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`IdUsuario`);

--
-- Indices de la tabla `resenia`
--
ALTER TABLE `resenia`
  ADD PRIMARY KEY (`IdResenia`),
  ADD KEY `IdUsuario` (`IdUsuario`),
  ADD KEY `IdServicio` (`IdServicio`);

--
-- Indices de la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`IdReserva`),
  ADD KEY `IdUsuario` (`IdUsuario`),
  ADD KEY `IdServicio` (`IdServicio`),
  ADD KEY `fk_reserva_disponibilidad` (`IdDisponibilidad`);

--
-- Indices de la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD PRIMARY KEY (`IdServicio`),
  ADD KEY `IdProveedor` (`IdProveedor`);

--
-- Indices de la tabla `servicioubicacion`
--
ALTER TABLE `servicioubicacion`
  ADD PRIMARY KEY (`IdServicio`,`IdUbicacion`),
  ADD KEY `IdUbicacion` (`IdUbicacion`);

--
-- Indices de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  ADD PRIMARY KEY (`IdUbicacion`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`IdUsuario`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `IdUbicacion` (`IdUbicacion`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accion`
--
ALTER TABLE `accion`
  MODIFY `IdAccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `IdCategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `dato`
--
ALTER TABLE `dato`
  MODIFY `IdDato` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  MODIFY `IdDisponibilidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `foto`
--
ALTER TABLE `foto`
  MODIFY `IdFoto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `gestion`
--
ALTER TABLE `gestion`
  MODIFY `IdGestion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `habilidad`
--
ALTER TABLE `habilidad`
  MODIFY `IdHabilidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  MODIFY `IdMensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `palabraclave`
--
ALTER TABLE `palabraclave`
  MODIFY `IdPalabraClave` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de la tabla `resenia`
--
ALTER TABLE `resenia`
  MODIFY `IdResenia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `reserva`
--
ALTER TABLE `reserva`
  MODIFY `IdReserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `servicio`
--
ALTER TABLE `servicio`
  MODIFY `IdServicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  MODIFY `IdUbicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `IdUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `accion`
--
ALTER TABLE `accion`
  ADD CONSTRAINT `accion_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`),
  ADD CONSTRAINT `accion_ibfk_2` FOREIGN KEY (`IdUsuarioAdministrador`) REFERENCES `administrador` (`IdUsuario`);

--
-- Filtros para la tabla `administrador`
--
ALTER TABLE `administrador`
  ADD CONSTRAINT `administrador_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `dato`
--
ALTER TABLE `dato`
  ADD CONSTRAINT `dato_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `disponibilidad`
--
ALTER TABLE `disponibilidad`
  ADD CONSTRAINT `disponibilidad_ibfk_1` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`);

--
-- Filtros para la tabla `foto`
--
ALTER TABLE `foto`
  ADD CONSTRAINT `foto_ibfk_1` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`);

--
-- Filtros para la tabla `gestion`
--
ALTER TABLE `gestion`
  ADD CONSTRAINT `gestion_ibfk_1` FOREIGN KEY (`IdAdministrador`) REFERENCES `administrador` (`IdUsuario`),
  ADD CONSTRAINT `gestion_ibfk_2` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`);

--
-- Filtros para la tabla `habilidad`
--
ALTER TABLE `habilidad`
  ADD CONSTRAINT `habilidad_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD CONSTRAINT `mensaje_ibfk_1` FOREIGN KEY (`IdUsuarioEmisor`) REFERENCES `usuario` (`IdUsuario`),
  ADD CONSTRAINT `mensaje_ibfk_2` FOREIGN KEY (`IdUsuarioReceptor`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `palabraclave`
--
ALTER TABLE `palabraclave`
  ADD CONSTRAINT `palabraclave_ibfk_1` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`);

--
-- Filtros para la tabla `pertenece`
--
ALTER TABLE `pertenece`
  ADD CONSTRAINT `pertenece_ibfk_1` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`),
  ADD CONSTRAINT `pertenece_ibfk_2` FOREIGN KEY (`IdCategoria`) REFERENCES `categoria` (`IdCategoria`);

--
-- Filtros para la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD CONSTRAINT `proveedor_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `resenia`
--
ALTER TABLE `resenia`
  ADD CONSTRAINT `resenia_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`),
  ADD CONSTRAINT `resenia_ibfk_2` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`);

--
-- Filtros para la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_disponibilidad` FOREIGN KEY (`IdDisponibilidad`) REFERENCES `disponibilidad` (`IdDisponibilidad`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`),
  ADD CONSTRAINT `reserva_ibfk_2` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`);

--
-- Filtros para la tabla `servicio`
--
ALTER TABLE `servicio`
  ADD CONSTRAINT `servicio_ibfk_1` FOREIGN KEY (`IdProveedor`) REFERENCES `proveedor` (`IdUsuario`);

--
-- Filtros para la tabla `servicioubicacion`
--
ALTER TABLE `servicioubicacion`
  ADD CONSTRAINT `servicioubicacion_ibfk_1` FOREIGN KEY (`IdServicio`) REFERENCES `servicio` (`IdServicio`),
  ADD CONSTRAINT `servicioubicacion_ibfk_2` FOREIGN KEY (`IdUbicacion`) REFERENCES `ubicacion` (`IdUbicacion`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`IdUbicacion`) REFERENCES `ubicacion` (`IdUbicacion`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
