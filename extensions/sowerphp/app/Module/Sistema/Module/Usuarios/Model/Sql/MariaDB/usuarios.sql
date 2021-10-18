-- SCRIPT SQL PARA MÓDULO: Sistema.Usuarios
-- Base de datos: MariaDB

BEGIN;

-- SCHEMA PARA EL MÓDULO: Sistema.Usuarios
SET FOREIGN_KEY_CHECKS=0;

-- tabla para usuarios
DROP TABLE IF EXISTS usuario CASCADE;
CREATE TABLE usuario (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
		COMMENT 'Identificador (serial)',
	nombre CHARACTER VARYING (50) NOT NULL
		COMMENT 'Nombre real del usuario',
	usuario CHARACTER VARYING (30) NOT NULL
		COMMENT 'Nombre de usuario',
	usuario_ldap CHARACTER VARYING (30)
		COMMENT 'Nombre de usuario de LDAP',
	email CHARACTER VARYING (50) NOT NULL
		COMMENT 'Correo electrónico del usuario',
	contrasenia VARCHAR(255) NOT NULL
		COMMENT 'Contraseña del usuario',
	contrasenia_intentos SMALLINT NOT NULL DEFAULT 3
		COMMENT 'Intentos de inicio de sesión antes de bloquear cuenta',
	hash CHAR(32) NOT NULL
		COMMENT 'Hash único del usuario (32 caracteres)',
	token CHAR(64)
		COMMENT 'Token para servicio secundario de autorización',
	activo BOOLEAN NOT NULL DEFAULT true
		COMMENT 'Indica si el usuario está o no activo',
	ultimo_ingreso_fecha_hora TIMESTAMP
		COMMENT 'Fecha y hora del último ingreso del usuario',
	ultimo_ingreso_desde CHARACTER VARYING (45)
		COMMENT 'Dirección IP del último ingreso del usuario',
	ultimo_ingreso_hash CHAR(32)
	COMMENT 'Hash del último ingreso del usuario'
) ENGINE = InnoDB COMMENT = 'Usuarios de la aplicación';
ALTER TABLE usuario AUTO_INCREMENT=1000;
CREATE UNIQUE INDEX usuario_usuario_idx ON usuario (usuario);
CREATE UNIQUE INDEX usuario_usuario_ldap_idx ON usuario (usuario_ldap);
CREATE UNIQUE INDEX usuario_email_idx ON usuario (email);
CREATE UNIQUE INDEX usuario_hash_idx ON usuario (hash);

-- tabla para grupos
DROP TABLE IF EXISTS grupo CASCADE;
CREATE TABLE grupo (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
		COMMENT 'Identificador (serial)',
	grupo CHARACTER VARYING (30) NOT NULL
		COMMENT 'Nombre del grupo',
	activo BOOLEAN NOT NULL DEFAULT true
		COMMENT 'Indica si el grupo se encuentra activo'
) ENGINE = InnoDB COMMENT = 'Grupos de la aplicación';
ALTER TABLE grupo AUTO_INCREMENT=1000;
CREATE UNIQUE INDEX grupo_grupo_idx ON grupo (grupo);

-- tabla que relaciona usuarios con sus grupos
DROP TABLE IF EXISTS usuario_grupo CASCADE;
CREATE TABLE usuario_grupo (
	usuario INTEGER UNSIGNED NOT NULL
		COMMENT 'Usuario de la aplicación',
	grupo INTEGER UNSIGNED NOT NULL
		COMMENT 'Grupo al que pertenece el usuario',
	primario BOOLEAN NOT NULL DEFAULT false
		COMMENT 'Indica si el grupo es el grupo primario del usuario',
	PRIMARY KEY (usuario, grupo),
	CONSTRAINT usuario_grupo_usuario_fk FOREIGN KEY (usuario)
		REFERENCES usuario (id)
		MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT usuario_grupo_grupo_fk FOREIGN KEY (grupo)
		REFERENCES grupo (id)
		MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Relación entre usuarios y sus grupos';

-- tabla que contiene los permisos de los grupos sobre recursos
DROP TABLE IF EXISTS auth CASCADE;
CREATE TABLE auth (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
		COMMENT 'Identificador (serial)',
	grupo INTEGER UNSIGNED NOT NULL
		COMMENT 'Grupo al que se le concede el permiso',
	recurso CHARACTER VARYING (300)
		COMMENT 'Recurso al que el grupo tiene acceso',
	CONSTRAINT auth_grupo_fk FOREIGN KEY (grupo)
		REFERENCES grupo (id)
		MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB COMMENT = 'Permisos de grupos para acceder a recursos';

-- tabla para los datos extra del usuario (api, configuraciones, etc, propias de la aplicación)
DROP TABLE IF EXISTS usuario_config CASCADE;
CREATE TABLE usuario_config (
    usuario INTEGER UNSIGNED NOT NULL,
    configuracion VARCHAR(32) NOT NULL,
    variable VARCHAR(64) NOT NULL,
    valor TEXT,
    json BOOLEAN NOT NULL DEFAULT false,
    CONSTRAINT usuario_config_pkey PRIMARY KEY (usuario, configuracion, variable),
    CONSTRAINT usuario_config_usuario_fk FOREIGN KEY (usuario)
                REFERENCES usuario (id) MATCH FULL
                ON UPDATE CASCADE ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS=1;

-- DATOS PARA EL MÓDULO: Sistema.Usuarios

INSERT INTO grupo (grupo) VALUES
	-- Grupo para quienes desarrollan la aplicación
	('sysadmin'),
	-- Grupo para aquellos que administran la aplicación y al no ser
	-- desarrolladores no necesitan "ver todo"
	('appadmin'),
	-- Grupo para crear/editar/eliminar cuentas de usuario
	('passwd')
;

INSERT INTO auth (grupo, recurso) VALUES
	-- grupo sysadmin tiene acceso a todos los recursos de la aplicación
	((SELECT id FROM grupo WHERE grupo = 'sysadmin'), '*'),
	((SELECT id FROM grupo WHERE grupo = 'appadmin'), '/sistema*'),
	((SELECT id FROM grupo WHERE grupo = 'passwd'),
		'/sistema/usuarios/usuarios*')
;

INSERT INTO usuario (nombre, usuario, email, contrasenia, hash) VALUES
	-- usuario por defecto admin con clave admin, el hash único DEBE ser
	-- cambiado, ¡¡¡ES UN RIESGO DEJAR EL MISMO!!!
	('Administrador', 'admin', 'admin@example.com',
	'8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918',
	't7dr5B1ujphds043WMMEFWwFLeyWYqMU')
;

INSERT INTO usuario_grupo (usuario, grupo, primario) VALUES
	((SELECT id FROM usuario WHERE usuario = 'admin'),
		(SELECT id FROM grupo WHERE grupo = 'sysadmin'), true)
;

COMMIT;
