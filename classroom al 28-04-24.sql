-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 28-04-2024 a las 21:43:18
-- Versión del servidor: 8.0.31
-- Versión de PHP: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `classroom`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivoslecciones`
--

DROP TABLE IF EXISTS `archivoslecciones`;
CREATE TABLE IF NOT EXISTS `archivoslecciones` (
  `idArchivoLeccion` int NOT NULL AUTO_INCREMENT,
  `id_leccion` int NOT NULL,
  `tipoArchivo` varchar(10) NOT NULL,
  `urlArchivo` varchar(100) NOT NULL,
  PRIMARY KEY (`idArchivoLeccion`),
  UNIQUE KEY `id_leccion_2` (`id_leccion`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacioncursos`
--

DROP TABLE IF EXISTS `asignacioncursos`;
CREATE TABLE IF NOT EXISTS `asignacioncursos` (
  `idAsignacion` int NOT NULL AUTO_INCREMENT,
  `id_estudiante` int NOT NULL,
  `id_seccion` int NOT NULL,
  PRIMARY KEY (`idAsignacion`),
  KEY `id_estudiante` (`id_estudiante`,`id_seccion`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

DROP TABLE IF EXISTS `calificaciones`;
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `idCalificacion` int NOT NULL AUTO_INCREMENT,
  `id_estudiante` int NOT NULL,
  `id_seccion` int NOT NULL,
  `id_modulo` int NOT NULL,
  `id_curso` int NOT NULL,
  `calificacion` int NOT NULL,
  PRIMARY KEY (`idCalificacion`),
  KEY `id_estudiante` (`id_estudiante`,`id_seccion`,`id_modulo`,`id_curso`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

DROP TABLE IF EXISTS `cursos`;
CREATE TABLE IF NOT EXISTS `cursos` (
  `idCurso` int NOT NULL AUTO_INCREMENT,
  `nombreCurso` char(20) NOT NULL,
  `contenidoCurso` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `estado` char(15) NOT NULL,
  `fechaInicioCurso` date NOT NULL,
  `fechaFinCurso` date DEFAULT NULL,
  `horarioCurso` time DEFAULT NULL,
  PRIMARY KEY (`idCurso`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`idCurso`, `nombreCurso`, `contenidoCurso`, `estado`, `fechaInicioCurso`, `fechaFinCurso`, `horarioCurso`) VALUES
(1, 'Programacion', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec vel egestas dolor, nec dignissim metus. Donec augue elit, rhoncus ac sodales id, porttitor vitae est. Donec laoreet rutrum libero sed pharetra.', 'Programado', '2023-08-11', '2024-01-25', '20:00:00'),
(2, 'Excel Basico', 'Curso básico para operador de excel. Incluye plantillas básicas', 'En Curso', '2023-10-02', '2024-03-30', '10:30:00'),
(3, 'Matemática', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec vel egestas dolor, nec dignissim metus.', 'En curso', '2023-10-02', '2023-11-27', '13:30:00'),
(4, 'Excel Intermedio', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec vel egestas dolor,', 'Programado', '2023-10-12', '2024-04-23', '13:30:00'),
(12, 'PFS Martes 20hs', 'Programación web fullstack nivel intermedio', 'Programado', '2023-10-30', '2024-01-29', '20:00:00'),
(5, 'Prueba', 'Lorem Ipsim', 'Programado', '2023-10-18', '2023-12-18', '16:30:00'),
(6, 'Prueba 2', '', 'Programado', '2023-11-01', '2023-11-30', '17:00:00'),
(7, 'Prueba 2', '', 'Programado', '2023-11-01', '2023-11-30', '17:00:00'),
(8, 'Prueba 2', '', 'Programado', '2023-10-23', '2023-11-16', '17:00:00'),
(9, 'Prueba 3', '', 'En Curso', '2023-10-02', '2023-10-25', '15:30:00'),
(10, 'Prueba 3', '', 'Cancelado', '2023-08-01', '2023-10-27', '16:30:00'),
(11, 'Prueba 6', '', 'Finalizado', '2023-05-02', '2023-10-02', '16:24:00'),
(13, 'Discord Finalizado', 'Lorem ipsum', 'Finalizado', '2023-10-31', '2024-01-09', '20:00:00'),
(14, 'PFS Sábados', 'Curso de programación web', 'Programado', '2023-11-24', '2023-01-24', '11:00:00'),
(15, 'SAC Sáb 13hs', 'Curso de secretariado', 'Programado', '2023-12-16', '2024-04-20', '13:00:00'),
(16, 'Programador Jueves 9', 'Zarazaza', 'En Curso', '2024-02-21', '2024-03-08', '09:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lecciones`
--

DROP TABLE IF EXISTS `lecciones`;
CREATE TABLE IF NOT EXISTS `lecciones` (
  `idLeccion` int NOT NULL AUTO_INCREMENT,
  `nombreLeccion` char(30) NOT NULL,
  `contenidoLeccion` tinytext NOT NULL,
  `id_modulo` int NOT NULL,
  PRIMARY KEY (`idLeccion`),
  KEY `id_modulo` (`id_modulo`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

DROP TABLE IF EXISTS `mensajes`;
CREATE TABLE IF NOT EXISTS `mensajes` (
  `idMensaje` int NOT NULL AUTO_INCREMENT,
  `id_remitente` int NOT NULL,
  `id_destinatario` int NOT NULL,
  `contenidoMensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `fechaMensaje` timestamp NOT NULL,
  PRIMARY KEY (`idMensaje`),
  KEY `id_remitente` (`id_remitente`,`id_destinatario`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`idMensaje`, `id_remitente`, `id_destinatario`, `contenidoMensaje`, `fechaMensaje`) VALUES
(1, 1, 2, 'Hola, ¿cómo estás?', '2023-10-11 11:00:00'),
(2, 2, 1, '¡Hola! Estoy bien, ¿y tú?', '2023-10-11 11:05:00'),
(3, 3, 4, 'Buenos días, ¿puedes ayudarme con algo?', '2023-10-11 12:00:00'),
(4, 4, 3, 'Claro, ¿en qué necesitas ayuda?', '2023-10-11 12:05:00'),
(5, 1, 3, '¡Hola! ¿Qué tal?', '2023-10-11 13:00:00'),
(6, 3, 1, 'Hola, todo bien. Gracias por preguntar.', '2023-10-11 13:05:00'),
(7, 5, 6, '¡Feliz cumpleaños!', '2023-10-11 14:00:00'),
(8, 6, 5, '¡Gracias! ¿Quieres venir a la celebración?\r\nMauris tincidunt auctor orci, a commodo massa rutrum eu. Ut sollicitudin arcu at ante convallis, eget ornare justo ultrices. Integer vitae suscipit magna. Duis condimentum ante risus, a egestas nibh malesuada', '2023-10-19 00:51:06'),
(9, 8, 10, 'Hola, necesitamos discutir el proyecto.', '2023-10-11 17:00:00'),
(10, 10, 8, 'Por supuesto, ¿a qué hora te viene bien?', '2023-10-11 17:05:00'),
(11, 9, 7, '¡Qué bueno verte ayer en la reunión!', '2023-10-11 18:00:00'),
(12, 7, 9, 'Sí, fue genial. Hablamos pronto.', '2023-10-11 18:05:00'),
(13, 4, 5, 'A que hora venis?', '2023-10-19 00:42:58'),
(14, 4, 5, 'Te paso el link https://adminlte.io/themes/v3/pages/widgets.html', '2023-10-19 01:11:43'),
(15, 5, 11, ' Mensaje desde la APP', '2023-10-20 17:14:14'),
(17, 5, 11, ' Nuevo mensaje desde la App con fecha y hora', '2023-10-20 17:14:14'),
(18, 5, 11, ' Otro mensaje con hora acutalizada', '2023-10-20 17:14:14'),
(19, 5, 7, ' Holas', '2023-10-20 17:12:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles`
--

DROP TABLE IF EXISTS `perfiles`;
CREATE TABLE IF NOT EXISTS `perfiles` (
  `idPerfil` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `dniPerfil` int DEFAULT NULL,
  `telefonoPerfil` char(20) DEFAULT NULL,
  `fnacPerfil` date DEFAULT NULL,
  `domicilioPerfil` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `provinciaPerfil` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `contenidoPerfil` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  PRIMARY KEY (`idPerfil`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `perfiles`
--

INSERT INTO `perfiles` (`idPerfil`, `id_usuario`, `dniPerfil`, `telefonoPerfil`, `fnacPerfil`, `domicilioPerfil`, `provinciaPerfil`, `contenidoPerfil`) VALUES
(1, 1, NULL, NULL, '1990-01-01', 'Calle 123, Ciudad A', '', 'Este es el contenido del perfil para el usuario 1.'),
(2, 2, NULL, NULL, '1985-05-15', 'Avenida XYZ, Ciudad B', '', 'Perfil para el usuario 2 con información aleatoria.'),
(3, 3, NULL, NULL, '1992-08-20', 'Calle Principal, Ciudad C', '', 'Contenido del perfil para el usuario 3.'),
(4, 4, NULL, NULL, '1988-11-10', 'Calle 456, Ciudad D', '', 'Información del perfil para el usuario 4.'),
(5, 5, NULL, NULL, '1960-11-17', 'Calle Falsa 123', '', '                                      Datos perfil 5  actualizado                          '),
(9, 48, 35123456, '2612223344', '1991-08-31', 'Los Pimientos 856 - Las Heras', 'Mendoza', NULL),
(11, 50, 14197120, '2612223344', '1960-12-28', 'Av San Martin 123 - Ciudad', 'La Pampa', NULL),
(12, 51, 14197120, '2619999999', '1960-12-20', 'Los Pimientos 856 - Las Heras', 'Mendoza', NULL),
(13, 52, 13429334, '2619998888', '1959-06-26', 'Los Pinos 1235', 'Mendoza', NULL),
(14, 53, 13429335, '2612225555', '1960-06-26', 'Av Siempre Viva 732', 'Mendoza', NULL),
(15, 64, 13429334, '2612223344', '1959-06-28', 'Los Pimientos 856 - Las Heras', 'Mendoza', NULL),
(16, 65, 26190480, '2615332049', '1977-12-23', 'Av Mitre 3168', 'Mendoza', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posteos`
--

DROP TABLE IF EXISTS `posteos`;
CREATE TABLE IF NOT EXISTS `posteos` (
  `idPosteo` int NOT NULL,
  `id_autor` int NOT NULL,
  `contenidoPosteo` tinytext NOT NULL,
  `fechaPosteo` timestamp NOT NULL,
  `id_curso` int NOT NULL,
  KEY `id_autor` (`id_autor`,`id_curso`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

DROP TABLE IF EXISTS `secciones`;
CREATE TABLE IF NOT EXISTS `secciones` (
  `idSeccion` int NOT NULL AUTO_INCREMENT,
  `tituloSeccion` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `contenidoSeccion` tinytext,
  `id_curso` int NOT NULL,
  `docente` int NOT NULL,
  `tutor` int DEFAULT NULL,
  PRIMARY KEY (`idSeccion`),
  KEY `id_curso` (`id_curso`,`docente`,`tutor`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `secciones`
--

INSERT INTO `secciones` (`idSeccion`, `tituloSeccion`, `contenidoSeccion`, `id_curso`, `docente`, `tutor`) VALUES
(20, 'HTML', 'Introducción a HTML, etiquetas básicas', 1, 55, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idUsuario` int NOT NULL AUTO_INCREMENT,
  `nombreUsuario` char(20) NOT NULL,
  `apellidoUsuario` char(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `pass` varchar(100) NOT NULL,
  `resetPass` tinyint(1) NOT NULL,
  `imgUsuario` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL,
  `rol` char(15) NOT NULL,
  PRIMARY KEY (`idUsuario`)
) ENGINE=MyISAM AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idUsuario`, `nombreUsuario`, `apellidoUsuario`, `email`, `pass`, `resetPass`, `imgUsuario`, `activo`, `rol`) VALUES
(54, 'Nombre111', 'Apellido11', 'nombre111.apellido11@example.com', '$2y$10$C2Rcd0nXs.nKZdkYIe6rTuJIKPDMJddrUYKLaCYCSF1om8kAR26za', 1, 'img1.jpg', 1, 'ADMINISTRADOR'),
(55, 'Nombre2', 'Apellido2', 'nombre2.apellido2@example.com', 'password2', 0, 'img2.jpg', 1, 'DOCENTE'),
(56, 'Nombre3', 'Apellido3', 'nombre3.apellido3@example.com', 'password3', 0, 'img3.jpg', 1, 'ESTUDIANTE'),
(57, 'Nombre4', 'Apellido4', 'nombre4.apellido4@example.com', 'password4', 0, 'img4.jpg', 1, 'GESTOR'),
(58, 'Nombre5', 'Apellido5', 'nombre5.apellido5@example.com', 'password5', 0, 'img5.jpg', 1, 'ADMINISTRADOR'),
(59, 'Nombre6', 'Apellido6', 'nombre6.apellido6@example.com', 'password6', 0, 'img6.jpg', 1, 'DOCENTE'),
(60, 'Nombre7', 'Apellido7', 'nombre7.apellido7@example.com', 'password7', 0, 'img7.jpg', 1, 'ESTUDIANTE'),
(61, 'Nombre8', 'Apellido8', 'nombre8.apellido8@example.com', 'password8', 0, 'img8.jpg', 1, 'GESTOR'),
(62, 'Nombre9', 'Apellido9', 'nombre9.apellido9@example.com', 'password9', 0, 'img9.jpg', 1, 'ADMINISTRADOR'),
(63, 'Nombre10', 'Apellido10', 'nombre10.apellido10@example.com', 'password10', 0, 'img10.jpg', 1, 'DOCENTE'),
(64, 'José', 'Pineda Ortega', 'jose@correo.com', '$2y$10$3mkrb3ZV1toHIbbzea7FMuCP2jtmYRnxyJbj7A7VDUYZaeuZ4VAB.', 1, '', 1, 'ESTUDIANTE'),
(65, 'Jorge', 'Flores', 'jorge@correo.com', '$2y$10$7C3t5Qop7ObynWwT8JF8seJ50dKYF3ihfznin7rSdRqBvE6QRZjKe', 1, '', 1, 'GESTOR');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
