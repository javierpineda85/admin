-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 12-10-2023 a las 01:32:53
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `contenidoCurso` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `estado` CHAR(15) NOT NULL,
  `fechaInicioCurso` date NOT NULL,
  `fechaFinCurso` date DEFAULT NULL,
  `horarioCurso` time DEFAULT NULL,
  PRIMARY KEY (`idCurso`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `contenidoMensaje` tinytext NOT NULL,
  `fechaMensaje` timestamp NOT NULL,
  PRIMARY KEY (`idMensaje`),
  KEY `id_remitente` (`id_remitente`,`id_destinatario`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles`
--

DROP TABLE IF EXISTS `perfiles`;
CREATE TABLE IF NOT EXISTS `perfiles` (
  `idPerfil` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fnacPerfil` date DEFAULT NULL,
  `domicilioPerfil` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `contenidoPerfil` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  PRIMARY KEY (`idPerfil`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `tituloSeccion` CHAR(10) NOT NULL,
  `contenidoSeccion` TINYTEXT NULL,
  `id_curso` int NOT NULL,
  `docente` int NOT NULL,
  `tutor` int DEFAULT NULL,
  PRIMARY KEY (`idSeccion`),
  KEY `id_curso` (`id_curso`,`docente`,`tutor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `imgUsuario` varchar(30) NOT NULL,
  `activo` tinyint(1) NOT NULL,
  `rol` char(15) NOT NULL,
  PRIMARY KEY (`idUsuario`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

--
-- INSERTANDO DATOS
--

-- Insertar 10 usuarios con roles específicos
INSERT INTO usuarios (nombreUsuario, apellidoUsuario, email, pass, resetPass, imgUsuario, activo, rol)
VALUES 
  ('Nombre1', 'Apellido1', 'nombre1.apellido1@example.com', 'password1', 0, 'img1.jpg', 1, 'ADMINISTRADOR'),
  ('Nombre2', 'Apellido2', 'nombre2.apellido2@example.com', 'password2', 0, 'img2.jpg', 1, 'DOCENTE'),
  ('Nombre3', 'Apellido3', 'nombre3.apellido3@example.com', 'password3', 0, 'img3.jpg', 1, 'ESTUDIANTE'),
  ('Nombre4', 'Apellido4', 'nombre4.apellido4@example.com', 'password4', 0, 'img4.jpg', 1, 'GESTOR'),
  ('Nombre5', 'Apellido5', 'nombre5.apellido5@example.com', 'password5', 0, 'img5.jpg', 1, 'ADMINISTRADOR'),
  ('Nombre6', 'Apellido6', 'nombre6.apellido6@example.com', 'password6', 0, 'img6.jpg', 1, 'DOCENTE'),
  ('Nombre7', 'Apellido7', 'nombre7.apellido7@example.com', 'password7', 0, 'img7.jpg', 1, 'ESTUDIANTE'),
  ('Nombre8', 'Apellido8', 'nombre8.apellido8@example.com', 'password8', 0, 'img8.jpg', 1, 'GESTOR'),
  ('Nombre9', 'Apellido9', 'nombre9.apellido9@example.com', 'password9', 0, 'img9.jpg', 1, 'ADMINISTRADOR'),
  ('Nombre10', 'Apellido10', 'nombre10.apellido10@example.com', 'password10', 0, 'img10.jpg', 1, 'DOCENTE');


  -- Insertar 5 perfiles con información aleatoria y usuarios asociados
INSERT INTO perfiles (id_usuario, fnacPerfil, domicilioPerfil, contenidoPerfil)
VALUES 
  (1, '1990-01-01', 'Calle 123, Ciudad A', 'Este es el contenido del perfil para el usuario 1.'),
  (2, '1985-05-15', 'Avenida XYZ, Ciudad B', 'Perfil para el usuario 2 con información aleatoria.'),
  (3, '1992-08-20', 'Calle Principal, Ciudad C', 'Contenido del perfil para el usuario 3.'),
  (4, '1988-11-10', 'Calle 456, Ciudad D', 'Información del perfil para el usuario 4.'),
  (5, '1995-04-03', 'Avenida ABC, Ciudad E', 'Este es el contenido del perfil para el usuario 5.');


-- Insertar 30 mensajes entre los usuarios
INSERT INTO mensajes (id_remitente, id_destinatario, contenidoMensaje, fechaMensaje)
VALUES 
  (1, 2, 'Hola, ¿cómo estás?', '2023-10-11 08:00:00'),
  (2, 1, '¡Hola! Estoy bien, ¿y tú?', '2023-10-11 08:05:00'),
  (3, 4, 'Buenos días, ¿puedes ayudarme con algo?', '2023-10-11 09:00:00'),
  (4, 3, 'Claro, ¿en qué necesitas ayuda?', '2023-10-11 09:05:00'),
  -- ... continuar con más mensajes entre los usuarios

  (1, 3, '¡Hola! ¿Qué tal?', '2023-10-11 10:00:00'),
  (3, 1, 'Hola, todo bien. Gracias por preguntar.', '2023-10-11 10:05:00'),
  (5, 6, '¡Feliz cumpleaños!', '2023-10-11 11:00:00'),
  (6, 5, '¡Gracias! ¿Quieres venir a la celebración?', '2023-10-11 11:05:00'),
  -- ... continuar con más mensajes entre los usuarios

  (8, 10, 'Hola, necesitamos discutir el proyecto.', '2023-10-11 14:00:00'),
  (10, 8, 'Por supuesto, ¿a qué hora te viene bien?', '2023-10-11 14:05:00'),
  (9, 7, '¡Qué bueno verte ayer en la reunión!', '2023-10-11 15:00:00'),
  (7, 9, 'Sí, fue genial. Hablamos pronto.', '2023-10-11 15:05:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
