-- SCRIPT SQL PARA MODULO: Sistema.Usuarios
-- Base de datos: Sqlite

BEGIN TRANSACTION;

-- SCHEMA PARA EL MODULO: Sistema.Usuarios

-- tabla para usuarios
DROP TABLE IF EXISTS usuario;
CREATE TABLE usuario (
	id INTEGER PRIMARY KEY,
	nombre CHARACTER VARYING (50) NOT NULL,
	usuario CHARACTER VARYING (30) NOT NULL,
	usuario_ldap CHARACTER VARYING (30),
	email CHARACTER VARYING (50) NOT NULL,
	contrasenia CHARACTER VARYING(255) NOT NULL,
	contrasenia_intentos SMALLINT NOT NULL DEFAULT 3,
	hash CHAR(32) NOT NULL,
	token CHAR(64),
	activo INTEGER NOT NULL DEFAULT 1,
	ultimo_ingreso_fecha_hora TIMESTAMP,
	ultimo_ingreso_desde CHARACTER VARYING (45),
	ultimo_ingreso_hash CHAR(32)
);
CREATE UNIQUE INDEX usuario_usuario_idx ON usuario (usuario);
CREATE UNIQUE INDEX usuario_usuario_ldap_idx ON usuario (usuario_ldap);
CREATE UNIQUE INDEX usuario_email_idx ON usuario (email);
CREATE UNIQUE INDEX usuario_hash_idx ON usuario (hash);

-- tabla para grupos
DROP TABLE IF EXISTS grupo;
CREATE TABLE grupo (
	id INTEGER PRIMARY KEY,
	grupo CHARACTER VARYING (30) NOT NULL,
	activo INTEGER NOT NULL DEFAULT 1
);
CREATE UNIQUE INDEX grupo_grupo_idx ON grupo (grupo);

-- tabla que relaciona usuarios con sus grupos
DROP TABLE IF EXISTS usuario_grupo;
CREATE TABLE usuario_grupo (
	usuario INTEGER NOT NULL,
	grupo INTEGER NOT NULL,
	primario INTEGER NOT NULL DEFAULT 1,
	PRIMARY KEY (usuario, grupo),
	CONSTRAINT usuario_grupo_usuario_fk FOREIGN KEY (usuario)
		REFERENCES usuario (id)
		MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT usuario_grupo_grupo_fk FOREIGN KEY (grupo)
		REFERENCES grupo (id)
		MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE
);

-- tabla que contiene los permisos de los grupos sobre recursos
DROP TABLE IF EXISTS auth;
CREATE TABLE auth (
	id INTEGER PRIMARY KEY,
	grupo INTEGER NOT NULL,
	recurso CHARACTER VARYING (300),
	CONSTRAINT auth_grupo_fk FOREIGN KEY (grupo)
		REFERENCES grupo (id)
		MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE
);

-- tabla para los datos extra del usuario (api, configuraciones, etc, propias de la aplicación)
DROP TABLE IF EXISTS usuario_config CASCADE;
CREATE TABLE usuario_config (
    usuario INTEGER NOT NULL,
    configuracion VARCHAR(32) NOT NULL,
    variable VARCHAR(64) NOT NULL,
    valor TEXT,
    json INTEGER NOT NULL DEFAULT 0,
    CONSTRAINT usuario_config_pkey PRIMARY KEY (usuario, configuracion, variable),
    CONSTRAINT usuario_config_usuario_fk FOREIGN KEY (usuario)
                REFERENCES usuario (id) MATCH FULL
                ON UPDATE CASCADE ON DELETE CASCADE
);

-- DATOS PARA EL MODULO: Sistema.Usuarios

INSERT INTO grupo (grupo) VALUES
	-- Grupo para quienes desarrollan la aplicacion
	('sysadmin'),
	-- Grupo para aquellos que administran la aplicacion y al no ser
	-- desarrolladores no necesitan "ver todo"
	('appadmin'),
	-- Grupo para crear/editar/eliminar cuentas de usuario
	('passwd')
;

INSERT INTO auth (grupo, recurso) VALUES
	-- grupo sysadmin tiene acceso a todos los recursos de la aplicacion
	((SELECT id FROM grupo WHERE grupo = 'sysadmin'), '*'),
	((SELECT id FROM grupo WHERE grupo = 'appadmin'), '/sistema*'),
	((SELECT id FROM grupo WHERE grupo = 'passwd'),
		'/sistema/usuarios/usuarios*')
;

INSERT INTO usuario (nombre, usuario, email, contrasenia, hash) VALUES
	-- usuario por defecto admin con clave admin, el hash unico DEBE ser
	-- cambiado, ¡¡¡ES UN RIESGO DEJAR EL MISMO!!!
	('Administrador', 'admin', 'admin@example.com',
	'8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918',
	't7dr5B1ujphds043WMMEFWwFLeyWYqMU')
;

INSERT INTO usuario_grupo (usuario, grupo, primario) VALUES
	((SELECT id FROM usuario WHERE usuario = 'admin'),
		(SELECT id FROM grupo WHERE grupo = 'sysadmin'), 1)
;

COMMIT;
