-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 27-07-2025 a las 23:27:31
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `menudinamico`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

DROP TABLE IF EXISTS `citas`;
CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `id_doctor` int NOT NULL,
  `id_sucursal` int NOT NULL,
  `id_tipo_cita` int NOT NULL DEFAULT '1',
  `fecha_hora` datetime NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `tipo_cita` enum('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `estado` varchar(20) DEFAULT 'Pendiente',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notas` text,
  `enlace_virtual` varchar(500) DEFAULT NULL,
  `sala_virtual` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_cita`),
  KEY `id_paciente` (`id_paciente`),
  KEY `id_doctor` (`id_doctor`),
  KEY `id_sucursal` (`id_sucursal`),
  KEY `id_tipo_cita` (`id_tipo_cita`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id_cita`, `id_paciente`, `id_doctor`, `id_sucursal`, `id_tipo_cita`, `fecha_hora`, `motivo`, `tipo_cita`, `estado`, `fecha_creacion`, `notas`, `enlace_virtual`, `sala_virtual`) VALUES
(12, 9, 1, 4, 1, '2025-07-03 11:30:00', 'XD', 'presencial', 'Pendiente', '2025-07-02 00:37:24', 'FASFASD', NULL, NULL),
(13, 9, 1, 4, 1, '2025-07-03 14:00:00', 'sdfsadfas', 'presencial', 'Cancelada', '2025-07-02 04:55:51', 'dsfasdfsad\n[CANCELADA 2025-07-21 08:28:11] dsafadsffasdfdsafds', NULL, NULL),
(14, 9, 1, 4, 1, '2025-07-04 08:00:00', 'XSADSA', 'presencial', 'Completada', '2025-07-02 15:03:00', 'FASDFASD', NULL, NULL),
(15, 9, 5, 4, 1, '2025-07-07 08:00:00', 'ASDAS', 'presencial', 'Cancelada', '2025-07-03 06:28:42', 'ASASFAS\n[CANCELADA 2025-07-14 08:04:37] SE CANCELO PORQUE ASI ES LA VIDA', NULL, NULL),
(16, 6, 6, 2, 1, '2025-07-03 10:00:00', 'sdfgfd', 'presencial', 'Confirmada', '2025-07-03 06:43:44', 'dfgds', NULL, NULL),
(17, 9, 1, 4, 1, '2025-07-04 15:30:00', 'aassas', 'presencial', 'Completada', '2025-07-04 07:50:25', 'asasas', NULL, NULL),
(18, 9, 1, 4, 1, '2025-07-08 08:00:00', 'LE DUELE', 'presencial', 'Completada', '2025-07-06 06:51:23', 'A', NULL, NULL),
(19, 6, 1, 4, 1, '2025-07-08 08:30:00', 'fasdfdsa', 'presencial', 'Cancelada', '2025-07-06 07:09:54', 'fsafddas\n[CANCELADA 2025-07-13 22:57:08] XDDDDDDDDD', NULL, NULL),
(20, 6, 1, 4, 2, '2025-07-09 09:00:00', 'aaa', 'virtual', 'Completada', '2025-07-06 07:43:46', 'aa', 'https://zoom.us/j/835765490', ''),
(21, 6, 5, 4, 1, '2025-07-07 08:30:00', 'aaa', 'presencial', 'Cancelada', '2025-07-06 07:49:52', 'aaa\n[CANCELADA 2025-07-21 08:25:30] se cancelo lamentablemente', NULL, NULL),
(26, 6, 1, 4, 1, '2025-07-08 10:00:00', 'fsadfasd', 'presencial', 'Completada', '2025-07-06 08:11:37', 'afsd', NULL, NULL),
(29, 6, 1, 4, 1, '2025-07-10 09:00:00', 'fasdf', 'presencial', 'Cancelada', '2025-07-06 08:22:40', 'fasdf\naa\n[CANCELADA 2025-07-13 22:51:00] PORQUE ASI ES LA VIDA', NULL, NULL),
(30, 10, 1, 4, 1, '2025-07-11 10:30:00', 'ALGO', 'presencial', 'Completada', '2025-07-06 22:47:14', 'NO TENGO CEDULA', NULL, NULL),
(31, 6, 1, 4, 1, '2025-07-17 09:00:00', 'fsdfds', 'presencial', 'Confirmada', '2025-07-13 03:17:29', 'sdfasdfas', NULL, NULL),
(33, 9, 1, 4, 2, '2025-07-14 08:00:00', 'MOTIVOS', 'virtual', 'Completada', '2025-07-13 22:55:00', 'ASD', 'https://zoom.us/j/946362819', ''),
(35, 9, 1, 4, 1, '2025-07-17 09:30:00', 'fsdfads', 'presencial', 'Confirmada', '2025-07-14 04:00:39', 'sdfasdf', NULL, NULL),
(36, 10, 1, 4, 2, '2025-07-17 10:00:00', 'SADFDS', 'virtual', 'Completada', '2025-07-14 13:11:37', 'SDFASD', 'https://zoom.us/j/751379041', ''),
(37, 6, 1, 4, 1, '2025-07-21 11:30:00', 'ASDASDA', 'presencial', 'Cancelada', '2025-07-21 13:37:11', 'DSADSADSA\n[CANCELADA 2025-07-21 08:38:00] fgadgfadsgfadsfdsaf', NULL, NULL),
(38, 6, 1, 4, 1, '2025-07-22 09:00:00', 'FASDFADSF', 'presencial', 'Confirmada', '2025-07-21 13:44:38', 'DSAFDSAFDSAFDSA', NULL, NULL),
(39, 6, 10, 2, 1, '2025-07-28 19:30:00', 'pasaron COSAS', 'presencial', 'Completada', '2025-07-28 03:35:23', 'ASDF', NULL, NULL),
(40, 6, 10, 2, 1, '2025-07-29 19:30:00', 'fsdfasd', 'presencial', 'Completada', '2025-07-28 03:48:12', 'fadsfdas', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas_medicas`
--

DROP TABLE IF EXISTS `consultas_medicas`;
CREATE TABLE IF NOT EXISTS `consultas_medicas` (
  `id_consulta` int NOT NULL AUTO_INCREMENT,
  `id_cita` int NOT NULL,
  `id_historial` int NOT NULL,
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `motivo_consulta` text NOT NULL,
  `sintomatologia` text,
  `diagnostico` text NOT NULL,
  `tratamiento` text,
  `observaciones` text,
  `fecha_seguimiento` date DEFAULT NULL,
  PRIMARY KEY (`id_consulta`),
  KEY `id_cita` (`id_cita`),
  KEY `id_historial` (`id_historial`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `consultas_medicas`
--

INSERT INTO `consultas_medicas` (`id_consulta`, `id_cita`, `id_historial`, `fecha_hora`, `motivo_consulta`, `sintomatologia`, `diagnostico`, `tratamiento`, `observaciones`, `fecha_seguimiento`) VALUES
(1, 17, 1, '2025-07-06 06:48:33', 'AAAA', 'SE SIENTE MAL', 'XD', 'SE PODRIA DECIR', 'Y ESTO TAMBIEN', '2025-07-06'),
(2, 19, 2, '2025-07-06 07:20:03', 'fasdfdsa', 'sadasd', 'ssdasd', 'sadsa', 'sd', '2025-07-06'),
(3, 26, 2, '2025-07-06 08:14:56', 'fsadfasd', 'AASAS', 'ASAS', 'ASAS', 'ASAS', '2025-07-06'),
(4, 20, 2, '2025-07-06 21:44:28', 'aaa', 'aaaa', 'sdfsda', 'dasdfasd', 'sdfasd', '2025-07-06'),
(5, 30, 3, '2025-07-06 22:49:28', 'ALGO', 'LE DUELE LA ESPALDA', 'SE PUEDE IR', 'DROGAS', 'XD', '2025-07-06'),
(6, 18, 1, '2025-07-12 17:03:24', 'LE DUELE', 'sfdasd', 'sdfasd', 'dsfas', 'sdfdas', NULL),
(12, 31, 2, '2025-07-13 04:27:11', 'fsdfds', 'XDD', 'XDD', 'XDD', 'XDD', NULL),
(16, 33, 1, '2025-07-14 03:22:16', 'MOTIVOS', 'XDD', 'XDD', 'FDS', 'SDFADSF', '2025-07-30'),
(17, 36, 3, '2025-07-14 13:19:48', 'SADFDS', 'ASFASFASD', 'ADSFASDF', 'TOME PARACETAMOL 500 MG UNA VEZ AL DÍA', 'FASDFDSAFDSA', NULL),
(18, 39, 2, '2025-07-28 03:38:28', 'pasaron COSAS', 'SDS', 'ASDADS', 'SDA', 'ASDASD', NULL),
(21, 40, 2, '2025-07-28 04:25:54', 'fsdfasd', 'fasdfdsa', 'fsadf', 'asdf', 'asdfdas', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctores`
--

DROP TABLE IF EXISTS `doctores`;
CREATE TABLE IF NOT EXISTS `doctores` (
  `id_doctor` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_especialidad` int NOT NULL,
  `titulo_profesional` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_doctor`),
  UNIQUE KEY `id_usuario` (`id_usuario`),
  KEY `id_especialidad` (`id_especialidad`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `doctores`
--

INSERT INTO `doctores` (`id_doctor`, `id_usuario`, `id_especialidad`, `titulo_profesional`) VALUES
(1, 55, 1, 'MSc Cardiólogo'),
(5, 67, 1, 'dssss'),
(6, 68, 6, 'msc'),
(10, 80, 5, 'MSC. Neurólogo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctores_sucursales`
--

DROP TABLE IF EXISTS `doctores_sucursales`;
CREATE TABLE IF NOT EXISTS `doctores_sucursales` (
  `id_doctor_sucursal` int NOT NULL AUTO_INCREMENT,
  `id_doctor` int NOT NULL,
  `id_sucursal` int NOT NULL,
  PRIMARY KEY (`id_doctor_sucursal`),
  KEY `id_doctor` (`id_doctor`),
  KEY `id_sucursal` (`id_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `doctores_sucursales`
--

INSERT INTO `doctores_sucursales` (`id_doctor_sucursal`, `id_doctor`, `id_sucursal`) VALUES
(1, 1, 4),
(5, 5, 4),
(11, 6, 2),
(16, 10, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_excepciones`
--

DROP TABLE IF EXISTS `doctor_excepciones`;
CREATE TABLE IF NOT EXISTS `doctor_excepciones` (
  `id_excepcion` int NOT NULL AUTO_INCREMENT,
  `id_doctor` int NOT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('no_laborable','horario_especial','vacaciones','feriado') DEFAULT 'no_laborable',
  `hora_inicio` time DEFAULT NULL COMMENT 'Solo para horario_especial',
  `hora_fin` time DEFAULT NULL COMMENT 'Solo para horario_especial',
  `motivo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_excepcion`),
  UNIQUE KEY `unique_doctor_fecha` (`id_doctor`,`fecha`),
  KEY `idx_doctor_fecha` (`id_doctor`,`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `doctor_excepciones`
--

INSERT INTO `doctor_excepciones` (`id_excepcion`, `id_doctor`, `fecha`, `tipo`, `hora_inicio`, `hora_fin`, `motivo`, `activo`, `fecha_creacion`) VALUES
(1, 1, '2025-01-01', 'feriado', NULL, NULL, 'Año Nuevo', 1, '2025-07-01 04:46:30'),
(2, 1, '2025-12-25', 'feriado', NULL, NULL, 'Navidad', 1, '2025-07-01 04:46:30'),
(3, 1, '2025-07-15', 'vacaciones', NULL, NULL, 'Vacaciones de verano', 1, '2025-07-01 04:46:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_horarios`
--

DROP TABLE IF EXISTS `doctor_horarios`;
CREATE TABLE IF NOT EXISTS `doctor_horarios` (
  `id_horario` int NOT NULL AUTO_INCREMENT,
  `id_doctor` int NOT NULL,
  `id_sucursal` int NOT NULL,
  `dia_semana` tinyint NOT NULL COMMENT '1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `duracion_cita` int DEFAULT '30' COMMENT 'Duración en minutos por cita',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_horario`),
  UNIQUE KEY `unique_doctor_sucursal_dia_hora` (`id_doctor`,`id_sucursal`,`dia_semana`,`hora_inicio`),
  KEY `idx_doctor_sucursal` (`id_doctor`,`id_sucursal`),
  KEY `idx_dia_semana` (`dia_semana`),
  KEY `id_sucursal` (`id_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `doctor_horarios`
--

INSERT INTO `doctor_horarios` (`id_horario`, `id_doctor`, `id_sucursal`, `dia_semana`, `hora_inicio`, `hora_fin`, `duracion_cita`, `activo`, `fecha_creacion`) VALUES
(1, 1, 4, 1, '08:00:00', '12:00:00', 30, 1, '2025-07-01 04:46:30'),
(2, 1, 4, 1, '14:00:00', '18:00:00', 30, 1, '2025-07-01 04:46:30'),
(3, 1, 4, 2, '08:00:00', '15:00:00', 30, 1, '2025-07-01 04:46:30'),
(4, 1, 4, 3, '09:00:00', '13:00:00', 30, 1, '2025-07-01 04:46:30'),
(5, 1, 4, 3, '15:00:00', '19:00:00', 30, 1, '2025-07-01 04:46:30'),
(6, 1, 4, 4, '08:00:00', '12:00:00', 30, 1, '2025-07-01 04:46:30'),
(7, 1, 4, 4, '14:00:00', '17:00:00', 30, 1, '2025-07-01 04:46:30'),
(8, 1, 4, 5, '08:00:00', '16:00:00', 30, 1, '2025-07-01 04:46:30'),
(9, 5, 4, 1, '08:00:00', '20:00:00', 30, 1, '2025-07-03 06:27:23'),
(10, 5, 4, 4, '08:00:00', '17:00:00', 30, 1, '2025-07-03 06:27:23'),
(11, 6, 2, 4, '08:00:00', '23:00:00', 45, 1, '2025-07-03 06:42:30'),
(12, 6, 4, 2, '08:00:00', '23:00:00', 30, 1, '2025-07-03 06:42:30'),
(22, 10, 2, 1, '08:00:00', '20:00:00', 30, 1, '2025-07-28 03:22:53'),
(23, 10, 2, 2, '08:00:00', '20:00:00', 30, 1, '2025-07-28 03:22:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

DROP TABLE IF EXISTS `especialidades`;
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id_especialidad` int NOT NULL AUTO_INCREMENT,
  `nombre_especialidad` varchar(100) NOT NULL,
  `descripcion` text,
  PRIMARY KEY (`id_especialidad`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id_especialidad`, `nombre_especialidad`, `descripcion`) VALUES
(1, 'Cardiología', 'Especialidad médica que se ocupa del diagnóstico y tratamiento de las enfermedades del corazón y del aparato circulatorio.'),
(2, 'Pediatría', 'Especialidad médica que estudia al niño y sus enfermedades.'),
(3, 'Traumatología', 'Especialidad médica que se dedica al estudio, tratamiento y rehabilitación de las lesiones del sistema musculoesquelético.'),
(4, 'Dermatología', 'Especialidad médica encargada del estudio, diagnóstico y tratamiento de las enfermedades de la piel.'),
(5, 'Neurología', 'Especialidad médica que trata los trastornos del sistema nervioso.'),
(6, 'Ginecología', 'Especialidad médica que trata las enfermedades del sistema reproductor femenino.'),
(7, 'Oftalmología', 'Especialidad médica que estudia las enfermedades del ojo y su tratamiento.'),
(8, 'Psiquiatría', 'Especialidad médica dedicada al estudio, diagnóstico, tratamiento y prevención de las enfermedades mentales.'),
(10, 'Terapia', 'Terapia Física');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades_sucursales`
--

DROP TABLE IF EXISTS `especialidades_sucursales`;
CREATE TABLE IF NOT EXISTS `especialidades_sucursales` (
  `id_especialidad_sucursal` int NOT NULL AUTO_INCREMENT,
  `id_especialidad` int NOT NULL,
  `id_sucursal` int NOT NULL,
  PRIMARY KEY (`id_especialidad_sucursal`),
  KEY `id_especialidad` (`id_especialidad`),
  KEY `id_sucursal` (`id_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `especialidades_sucursales`
--

INSERT INTO `especialidades_sucursales` (`id_especialidad_sucursal`, `id_especialidad`, `id_sucursal`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 2, 1),
(5, 2, 2),
(7, 3, 1),
(8, 3, 3),
(9, 4, 2),
(10, 4, 3),
(12, 5, 1),
(13, 5, 2),
(14, 6, 1),
(15, 6, 2),
(16, 6, 3),
(18, 7, 1),
(20, 8, 2),
(21, 8, 3),
(40, 1, 4),
(41, 4, 4),
(42, 6, 4),
(43, 5, 4),
(44, 7, 4),
(45, 2, 4),
(46, 8, 4),
(47, 3, 4),
(56, 10, 4),
(57, 10, 2),
(58, 10, 3),
(59, 10, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

DROP TABLE IF EXISTS `estados`;
CREATE TABLE IF NOT EXISTS `estados` (
  `id_estado` int NOT NULL AUTO_INCREMENT,
  `nombre_estado` varchar(50) NOT NULL,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id_estado`, `nombre_estado`) VALUES
(1, 'Activo'),
(2, 'Bloqueado'),
(3, 'Pendiente'),
(4, 'Inactivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historiales_clinicos`
--

DROP TABLE IF EXISTS `historiales_clinicos`;
CREATE TABLE IF NOT EXISTS `historiales_clinicos` (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  UNIQUE KEY `id_paciente` (`id_paciente`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `historiales_clinicos`
--

INSERT INTO `historiales_clinicos` (`id_historial`, `id_paciente`, `fecha_creacion`, `ultima_actualizacion`) VALUES
(1, 9, '2025-07-06 06:48:33', '2025-07-06 06:48:33'),
(2, 6, '2025-07-06 07:20:03', '2025-07-06 07:20:03'),
(3, 10, '2025-07-06 22:49:28', '2025-07-06 22:49:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

DROP TABLE IF EXISTS `menus`;
CREATE TABLE IF NOT EXISTS `menus` (
  `id_menu` int NOT NULL AUTO_INCREMENT,
  `nombre_menu` varchar(100) NOT NULL,
  PRIMARY KEY (`id_menu`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id_menu`, `nombre_menu`) VALUES
(8, 'Datos Generales'),
(11, 'Soporte'),
(21, 'Recepcionista'),
(22, 'Enfermería'),
(23, 'Doctor'),
(24, 'Cita Pacientes'),
(25, 'Historial Médico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE IF NOT EXISTS `pacientes` (
  `id_paciente` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `tipo_sangre` varchar(10) DEFAULT NULL,
  `alergias` text,
  `antecedentes_medicos` text,
  `contacto_emergencia` varchar(100) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `numero_seguro` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id_paciente`),
  UNIQUE KEY `id_usuario` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id_paciente`, `id_usuario`, `fecha_nacimiento`, `tipo_sangre`, `alergias`, `antecedentes_medicos`, `contacto_emergencia`, `telefono_emergencia`, `numero_seguro`, `telefono`) VALUES
(1, 51, '2017-06-02', 'O+', 'NINGUNA', 'NINGUNO', '099223322', '2333232323', '22222', '98978987'),
(6, 60, '1964-09-03', 'O-', 'NINGUNA', 'NO SE', 'TU MAMI', '345435345', 'AAAAA', '65765765'),
(9, 63, '1966-03-26', 'O+', 'fsadfadsfdas', '23423sdf', '09958485', '23423432', '234234', '0988988195'),
(10, 69, '2003-11-05', 'O-', 'SI MUCHAS', 'SIDA', '3333', '0923333', '3333', '0992531112'),
(11, 79, '2005-09-02', 'B+', 'NO SE', 'TAL VEZ', 'fasfsd', '0934434', '3434334', '34534534');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_roles_submenus`
--

DROP TABLE IF EXISTS `permisos_roles_submenus`;
CREATE TABLE IF NOT EXISTS `permisos_roles_submenus` (
  `id_permiso` int NOT NULL AUTO_INCREMENT,
  `id_roles_submenus` int NOT NULL,
  `puede_crear` tinyint(1) DEFAULT '0',
  `puede_editar` tinyint(1) DEFAULT '0',
  `puede_eliminar` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_permiso`),
  KEY `id_roles_submenus` (`id_roles_submenus`)
) ENGINE=InnoDB AUTO_INCREMENT=585 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `permisos_roles_submenus`
--

INSERT INTO `permisos_roles_submenus` (`id_permiso`, `id_roles_submenus`, `puede_crear`, `puede_editar`, `puede_eliminar`) VALUES
(417, 431, 1, 1, 1),
(418, 432, 1, 1, 1),
(419, 433, 1, 1, 1),
(420, 434, 1, 1, 1),
(454, 468, 1, 1, 1),
(455, 469, 1, 1, 1),
(456, 470, 1, 1, 1),
(457, 471, 1, 1, 1),
(458, 468, 1, 1, 1),
(459, 469, 1, 1, 1),
(460, 470, 1, 1, 1),
(461, 471, 1, 1, 1),
(516, 526, 1, 1, 1),
(528, 538, 1, 1, 1),
(529, 539, 1, 1, 1),
(531, 541, 1, 1, 1),
(563, 573, 1, 1, 1),
(564, 574, 1, 1, 1),
(575, 585, 1, 1, 1),
(576, 586, 1, 1, 1),
(577, 587, 1, 1, 1),
(578, 588, 1, 1, 1),
(579, 589, 1, 1, 1),
(580, 590, 1, 1, 1),
(581, 591, 1, 1, 1),
(582, 592, 1, 1, 1),
(583, 593, 1, 1, 1),
(584, 594, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `fecha_creacion`) VALUES
(1, 'Administrador', '2025-06-26 21:28:51'),
(64, 'Test', '2025-06-26 21:28:51'),
(65, 'Servicios', '2025-06-26 21:28:51'),
(70, 'Medico', '2025-06-26 21:58:34'),
(71, 'Paciente', '2025-06-26 21:58:34'),
(72, 'Recepcionista', '2025-06-26 21:58:34'),
(73, 'Enfermero', '2025-06-26 21:58:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_submenus`
--

DROP TABLE IF EXISTS `roles_submenus`;
CREATE TABLE IF NOT EXISTS `roles_submenus` (
  `id_roles_submenus` int NOT NULL AUTO_INCREMENT,
  `id_rol` int NOT NULL,
  `id_submenu` int NOT NULL,
  PRIMARY KEY (`id_roles_submenus`),
  KEY `id_rol` (`id_rol`),
  KEY `id_submenu` (`id_submenu`)
) ENGINE=InnoDB AUTO_INCREMENT=595 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `roles_submenus`
--

INSERT INTO `roles_submenus` (`id_roles_submenus`, `id_rol`, `id_submenu`) VALUES
(431, 64, 16),
(432, 64, 18),
(433, 64, 19),
(434, 64, 22),
(468, 65, 16),
(469, 65, 18),
(470, 65, 19),
(471, 65, 22),
(526, 73, 33),
(538, 72, 36),
(539, 72, 29),
(541, 71, 35),
(573, 70, 34),
(574, 70, 36),
(585, 1, 31),
(586, 1, 32),
(587, 1, 16),
(588, 1, 30),
(589, 1, 18),
(590, 1, 33),
(591, 1, 36),
(592, 1, 29),
(593, 1, 22),
(594, 1, 19);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `submenus`
--

DROP TABLE IF EXISTS `submenus`;
CREATE TABLE IF NOT EXISTS `submenus` (
  `id_submenu` int NOT NULL AUTO_INCREMENT,
  `id_menu` int NOT NULL,
  `nombre_submenu` varchar(100) NOT NULL,
  `url_submenu` varchar(255) NOT NULL,
  PRIMARY KEY (`id_submenu`),
  KEY `id_menu` (`id_menu`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `submenus`
--

INSERT INTO `submenus` (`id_submenu`, `id_menu`, `nombre_submenu`, `url_submenu`) VALUES
(16, 8, 'Gestion Roles', 'http://localhost/MenuDinamico/vistas/gestion/gestionroles.php'),
(18, 8, 'Gestion Usuarios', 'http://localhost/MenuDinamico/vistas/gestion/gestionusuarios.php'),
(19, 11, 'Crear Menu', 'http://localhost/MenuDinamico/vistas/gestion/gestionmenus.php'),
(22, 11, 'Asignar SubMenus', 'http://localhost/MenuDinamico/vistas/gestion/gestionsubmenus.php'),
(29, 21, 'Citas', 'http://localhost/MenuDinamico/vistas/recepcion/gestionar_citas.php'),
(30, 8, 'Gestion Sucursales', 'http://localhost/MenuDinamico/vistas/gestion/gestionsucursales.php'),
(31, 8, 'Gestion Doctores', 'http://localhost/MenuDinamico/vistas/gestion/gestiondoctores.php'),
(32, 8, 'Gestion Especialidades', 'http://localhost/MenuDinamico/vistas/gestion/gestionespecialidades.php'),
(33, 22, 'Triaje', 'http://localhost/MenuDinamico/vistas/enfermeria/triaje.php'),
(34, 23, 'Citas Medicas', 'http://localhost/MenuDinamico/controladores/ConsultasMedicasControlador/ConsultasMedicasController.php'),
(35, 24, 'Consulta Citas', 'http://localhost/MenuDinamico/vistas/pacientes/consulta_citas.php'),
(36, 25, 'Consultar Historial', 'http://localhost/MenuDinamico/vistas/historial_medico/historial_medico.php');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
CREATE TABLE IF NOT EXISTS `sucursales` (
  `id_sucursal` int NOT NULL AUTO_INCREMENT,
  `nombre_sucursal` varchar(100) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `horario_atencion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_sucursal`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id_sucursal`, `nombre_sucursal`, `direccion`, `telefono`, `email`, `horario_atencion`, `estado`) VALUES
(1, 'Hospital Norte', 'Av. Los Shyris 123, Quito', '022456789', 'hospital.norte@mediplus.com', 'Lunes a Domingo 24/7', 1),
(2, 'Clínica Central', 'Av. 6 de Diciembre 456, Quito', '022123456', 'clinica.central@mediplus.com', 'Lunes a Viernes 07:00 - 19:00, Sábados 08:00 - 14:00', 1),
(3, 'Consultorio Sur', 'Av. Maldonado 789, Quito', '022789123', 'consultorio.sur@mediplus.com', 'Lunes a Sábado 08:00 - 20:00', 1),
(4, 'Centro Médico Oriental', 'Av. González Suárez 234, Quito', '022345678', 'centro.oriental@mediplus.com', 'Lunes a Viernes 08:00 - 17:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_cita`
--

DROP TABLE IF EXISTS `tipos_cita`;
CREATE TABLE IF NOT EXISTS `tipos_cita` (
  `id_tipo_cita` int NOT NULL AUTO_INCREMENT,
  `nombre_tipo` varchar(50) NOT NULL,
  `descripcion` text,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tipo_cita`),
  UNIQUE KEY `nombre_tipo` (`nombre_tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `tipos_cita`
--

INSERT INTO `tipos_cita` (`id_tipo_cita`, `nombre_tipo`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'Presencial', 'Cita médica presencial en consultorio o sucursal', 1, '2025-07-01 01:01:52'),
(2, 'Virtual', 'Cita médica por videollamada o telemedicina', 1, '2025-07-01 01:01:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `triage`
--

DROP TABLE IF EXISTS `triage`;
CREATE TABLE IF NOT EXISTS `triage` (
  `id_triage` int NOT NULL AUTO_INCREMENT,
  `id_cita` int NOT NULL,
  `id_enfermero` int NOT NULL,
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nivel_urgencia` tinyint NOT NULL,
  `estado_triaje` enum('Completado','Urgente','Critico','Pendiente_Atencion') DEFAULT 'Completado',
  `temperatura` decimal(4,1) DEFAULT NULL,
  `presion_arterial` varchar(10) DEFAULT NULL,
  `frecuencia_cardiaca` int DEFAULT NULL,
  `frecuencia_respiratoria` int DEFAULT NULL,
  `saturacion_oxigeno` int DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `talla` int DEFAULT NULL,
  `imc` decimal(4,2) DEFAULT NULL,
  `observaciones` text,
  PRIMARY KEY (`id_triage`),
  KEY `id_cita` (`id_cita`),
  KEY `id_enfermero` (`id_enfermero`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `triage`
--

INSERT INTO `triage` (`id_triage`, `id_cita`, `id_enfermero`, `fecha_hora`, `nivel_urgencia`, `estado_triaje`, `temperatura`, `presion_arterial`, `frecuencia_cardiaca`, `frecuencia_respiratoria`, `saturacion_oxigeno`, `peso`, `talla`, `imc`, `observaciones`) VALUES
(1, 14, 51, '2025-07-04 06:32:15', 3, 'Completado', 36.5, '120/80', 80, 18, 98, 70.00, 170, 24.22, 'TOS'),
(2, 17, 51, '2025-07-04 07:51:34', 3, 'Completado', 30.0, '120/80', 150, 21, 70, 55.00, 160, 21.48, 'NO SE'),
(3, 18, 51, '2025-07-06 06:52:24', 3, 'Urgente', 36.5, '120/80', 90, 20, 98, 80.00, 180, 24.69, 'XD'),
(4, 19, 51, '2025-07-06 07:17:13', 4, 'Critico', 36.5, '120/80', 80, 18, 100, 50.00, 160, 19.53, 'a'),
(5, 26, 51, '2025-07-06 08:13:57', 2, 'Pendiente_Atencion', 36.5, '120/80', 90, 18, 100, 70.00, 170, 24.22, 'XDDD'),
(6, 20, 51, '2025-07-06 21:34:14', 2, 'Pendiente_Atencion', 36.5, '120/80', 85, 18, 98, 70.00, 170, 24.22, 'BIENa'),
(7, 30, 51, '2025-07-06 22:48:45', 4, 'Critico', 36.5, '120/70', 150, 18, 80, 60.00, 170, 20.76, 'SE NOS VA SEÑORES'),
(8, 31, 51, '2025-07-13 03:44:06', 1, 'Completado', 36.5, '120/83', 70, 18, 99, 70.00, 170, 24.22, 'REGISTRA TOS FUERTE '),
(9, 33, 51, '2025-07-13 22:59:22', 1, 'Completado', 36.0, '120/80', 80, 20, 98, 70.00, 170, 24.22, 'asd'),
(10, 36, 51, '2025-07-14 13:16:19', 2, 'Pendiente_Atencion', 36.5, '120/80', 100, 18, 98, 80.00, 172, 27.04, 'LE DUELE EL CUERPO Y VOMITA'),
(11, 39, 51, '2025-07-28 03:36:30', 1, 'Completado', 36.5, '120/80', 80, 18, 99, 70.00, 170, 24.22, 'le duele el pie'),
(12, 40, 51, '2025-07-28 03:48:48', 2, 'Pendiente_Atencion', 36.5, '120/80', 80, 18, 99, 70.00, 170, 24.22, 'asdfasd');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `cedula` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nombres` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `id_estado` int DEFAULT NULL,
  `apellidos` varchar(255) NOT NULL,
  `sexo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nacionalidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `correo` varchar(255) NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `id_rol` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `username_2` (`username`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_rol` (`id_rol`),
  KEY `fk_id_estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `cedula`, `username`, `nombres`, `id_estado`, `apellidos`, `sexo`, `nacionalidad`, `correo`, `password`, `id_rol`, `fecha_creacion`) VALUES
(21, 1755973342, 'admin', 'LUIS MARCO', 1, 'JURADO BENAVIDES', 'M', 'Ecuadorean', 'admin@hotmail.com', '$2y$10$E7jPWeqnrSVpRkin0aUBtuZVkKkLs/dv.qdFXcwYnj.g9bjoOyRxC', 1, '2025-06-26 21:28:47'),
(50, 2147483647, 'fasdf', 'asddasfasd', 4, 'sdafads', 'F', 'Bosnian, Herzegovinian', 'a@a.com', '$2y$10$bs0Y36N4VaAlW7hRPkIkR.65RyfyTUHpUYHkGB.z4yf9C.wrkbrXa', 64, '2025-06-26 21:28:47'),
(51, 1755414446, 'moises', 'MOISES DAVID', 1, 'BETANCOURT MANTILLA', 'M', 'Ecuadorean', 'moises@hotmail.com', '$2y$10$/KptyfFfku/qAJWeKmERFOOl1eW3D4Cf2WSCHb9m3z5IppIvWiYdi', 73, '2025-06-26 21:28:47'),
(52, 1001534121, 'markkkkkk', 'MARCO', 1, 'JURADO', 'M', 'Ecuadorean', 'marco@hotmail.com', '$2y$10$YORqGuui.fAY/3/kCvrbAOTl3f6MkNdIN09NKRsSXpUSMME2zCj3K', 72, '2025-06-26 21:28:47'),
(55, 1720021201, 'enr', 'ENRIQUE SEBASTIAN', 1, 'JIMENEZ AGUILAR', 'M', 'Ecuadorean', 'enrique@hotmail.com', '$2y$10$R0HEaGU5BpXXI7bjHZNABeoELAeduKqGzUCn3wL7MXagCF43EfBiq', 70, '2025-06-26 21:28:47'),
(60, 1001534120, 'jurado.marco', 'JURADO VILLAGOMEZ', 1, 'MARCO VINICIO', 'M', 'Ecuadorean', 'swooshing12@gmail.com', '$2y$10$HP7FuGqetSqhiugcD9.jYuKkjV4R7YDaiho57tFbuiL1CQlbDfOX.', 71, '2025-06-27 03:08:08'),
(63, 1001657632, 'benavides.lidia', 'BENAVIDES QUESPAZ', 1, 'LIDIA MARCIA', 'M', 'Ecuadorean', 'luismjb12@gmail.com', '$2y$10$.tHNd8Vt2oVaA.NEC.xFA.j3Fl9IU8G7JaGCa.hOeWvE3v4eIiGGS', 71, '2025-07-01 04:57:22'),
(67, 1001223453, 'dr.matias.ruiz', 'MATIAS PAUL', 1, 'RUIZ GALLARDO', 'M', 'Albanian', 'safdas@gasda.com', '$2y$10$7PizhEQrFBgmDkbT6gzSGuzw9Tvm76rf6OaRHcaZIAoHQMiU8W3q6', 70, '2025-07-03 06:27:23'),
(68, 2147483647, 'si.no', 'SI', 1, 'NO', 'F', 'Ecuadorean', 'sdfasd@asfdads.com', '$2y$10$gmqeXzNo.l56sFZLHeAQuuxOj8KM/lF/8f14pzRLag055HPemoebq', 70, '2025-07-03 06:42:30'),
(69, 1727516294, 'sanchez.justin', 'SANCHEZ LUJE', 1, 'JUSTIN SEBASTIAN', 'M', 'Ecuadorean', 'justin@gmail.com', '$2y$10$D.kHCq4x4Zfg9E2olxG/0eXSnRwukUC6OSjhEmxl9qr53sse5E.jC', 71, '2025-07-06 22:46:29'),
(79, 1727516286, 'sanchez.valeria', 'SANCHEZ LUJE', 1, 'VALERIA NAZARETH', 'M', 'Ecuadorean', 'fadsfas@asdfdas.com', '$2y$10$WM.r7Zd/lhFh5xy.1mjvQOveujOJ.drA2UCOzx2DEV0tRf2lf.C5O', 71, '2025-07-21 13:56:17'),
(80, 1003618798, 'dr.diego.andrade', 'DIEGO BLADIMIR', 1, 'ANDRADE ANDRADE', 'M', 'Ecuadorean', 'swooshing14@gmail.com', '$2y$10$fuSCdkm/tWO8BMg9SYuwj.0tfpYNKLpnHCW/w6l8UprC3OQvJ/mEa', 70, '2025-07-28 03:22:53');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id_doctor`),
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`),
  ADD CONSTRAINT `citas_ibfk_4` FOREIGN KEY (`id_tipo_cita`) REFERENCES `tipos_cita` (`id_tipo_cita`);

--
-- Filtros para la tabla `consultas_medicas`
--
ALTER TABLE `consultas_medicas`
  ADD CONSTRAINT `consultas_medicas_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`),
  ADD CONSTRAINT `consultas_medicas_ibfk_2` FOREIGN KEY (`id_historial`) REFERENCES `historiales_clinicos` (`id_historial`);

--
-- Filtros para la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD CONSTRAINT `doctores_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctores_ibfk_2` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`);

--
-- Filtros para la tabla `doctores_sucursales`
--
ALTER TABLE `doctores_sucursales`
  ADD CONSTRAINT `doctores_sucursales_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id_doctor`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctores_sucursales_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `doctor_excepciones`
--
ALTER TABLE `doctor_excepciones`
  ADD CONSTRAINT `doctor_excepciones_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id_doctor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `doctor_horarios`
--
ALTER TABLE `doctor_horarios`
  ADD CONSTRAINT `doctor_horarios_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id_doctor`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_horarios_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `especialidades_sucursales`
--
ALTER TABLE `especialidades_sucursales`
  ADD CONSTRAINT `especialidades_sucursales_ibfk_1` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`) ON DELETE CASCADE,
  ADD CONSTRAINT `especialidades_sucursales_ibfk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historiales_clinicos`
--
ALTER TABLE `historiales_clinicos`
  ADD CONSTRAINT `historiales_clinicos_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`);

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permisos_roles_submenus`
--
ALTER TABLE `permisos_roles_submenus`
  ADD CONSTRAINT `permisos_roles_submenus_ibfk_1` FOREIGN KEY (`id_roles_submenus`) REFERENCES `roles_submenus` (`id_roles_submenus`) ON DELETE CASCADE;

--
-- Filtros para la tabla `roles_submenus`
--
ALTER TABLE `roles_submenus`
  ADD CONSTRAINT `roles_submenus_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_submenus_ibfk_2` FOREIGN KEY (`id_submenu`) REFERENCES `submenus` (`id_submenu`) ON DELETE CASCADE;

--
-- Filtros para la tabla `submenus`
--
ALTER TABLE `submenus`
  ADD CONSTRAINT `submenus_ibfk_1` FOREIGN KEY (`id_menu`) REFERENCES `menus` (`id_menu`) ON DELETE CASCADE;

--
-- Filtros para la tabla `triage`
--
ALTER TABLE `triage`
  ADD CONSTRAINT `triage_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`),
  ADD CONSTRAINT `triage_ibfk_2` FOREIGN KEY (`id_enfermero`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_id_estado` FOREIGN KEY (`id_estado`) REFERENCES `estados` (`id_estado`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
