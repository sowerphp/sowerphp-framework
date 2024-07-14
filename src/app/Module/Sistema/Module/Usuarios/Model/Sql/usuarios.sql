-- SCHEMA PARA MÓDULO: Sistema.Usuarios
-- Base de datos: PostgreSQL

BEGIN;

-- tabla para usuarios
DROP SEQUENCE IF EXISTS usuario_id_seq CASCADE;
CREATE SEQUENCE usuario_id_seq
    INCREMENT 1
    MINVALUE 1
    MAXVALUE 9223372036854775807
    START 1000
    CACHE 1
;
DROP TABLE IF EXISTS usuario CASCADE;
CREATE TABLE usuario (
    id INTEGER NOT NULL DEFAULT nextval('usuario_id_seq') PRIMARY KEY,
    nombre CHARACTER VARYING (50) NOT NULL,
    usuario CHARACTER VARYING (30) NOT NULL,
    email CHARACTER VARYING (50) NOT NULL,
    contrasenia VARCHAR(255) NOT NULL,
    contrasenia_intentos SMALLINT NOT NULL DEFAULT 3,
    hash CHAR(32) NOT NULL,
    token CHAR(64),
    activo BOOLEAN NOT NULL DEFAULT true,
    ultimo_ingreso_fecha_hora TIMESTAMP WITHOUT TIME ZONE,
    ultimo_ingreso_desde CHARACTER VARYING (45),
    ultimo_ingreso_hash CHAR(32)
);
CREATE UNIQUE INDEX ON usuario (usuario);
CREATE UNIQUE INDEX ON usuario (email);
CREATE UNIQUE INDEX ON usuario (hash);
COMMENT ON TABLE usuario IS 'Usuarios de la aplicación';
COMMENT ON COLUMN usuario.id IS 'Identificador (serial)';
COMMENT ON COLUMN usuario.nombre IS 'Nombre real del usuario';
COMMENT ON COLUMN usuario.usuario IS 'Nombre de usuario';
COMMENT ON COLUMN usuario.email IS 'Correo electrónico del usuario';
COMMENT ON COLUMN usuario.contrasenia IS 'Contraseña del usuario';
COMMENT ON COLUMN usuario.contrasenia_intentos IS
	'Intentos de inicio de sesión antes de bloquear cuenta';
COMMENT ON COLUMN usuario.hash IS 'Hash único del usuario (32 caracteres)';
COMMENT ON COLUMN usuario.token IS
	'Token para servicio secundario de autorización';
COMMENT ON COLUMN usuario.activo IS 'Indica si el usuario está o no activo en la aplicación';
COMMENT ON COLUMN usuario.ultimo_ingreso_fecha_hora IS 'Fecha y hora del último ingreso del usuario';
COMMENT ON COLUMN usuario.ultimo_ingreso_desde IS 'Dirección IP del último ingreso del usuario';
COMMENT ON COLUMN usuario.ultimo_ingreso_hash IS 'Hash del último ingreso del usuario';

-- tabla para grupos
DROP SEQUENCE IF EXISTS grupo_id_seq CASCADE;
CREATE SEQUENCE grupo_id_seq
    INCREMENT 1
    MINVALUE 1
    MAXVALUE 9223372036854775807
    START 1000
    CACHE 1
;
DROP TABLE IF EXISTS grupo CASCADE;
CREATE TABLE grupo (
    id INTEGER NOT NULL DEFAULT nextval('grupo_id_seq') PRIMARY KEY,
    grupo CHARACTER VARYING (30) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT true
);
CREATE UNIQUE INDEX ON grupo (grupo);
COMMENT ON TABLE grupo IS 'Grupos de la aplicación';
COMMENT ON COLUMN grupo.id IS 'Identificador (serial)';
COMMENT ON COLUMN grupo.grupo IS 'Nombre del grupo';
COMMENT ON COLUMN grupo.activo IS 'Indica si el grupo se encuentra activo';

-- tabla que relaciona usuarios con sus grupos
DROP TABLE IF EXISTS usuario_grupo CASCADE;
CREATE TABLE usuario_grupo (
    usuario INTEGER NOT NULL,
    grupo INTEGER NOT NULL,
    primario BOOLEAN NOT NULL DEFAULT false,
    CONSTRAINT usuario_grupo_pk PRIMARY KEY (usuario, grupo),
    CONSTRAINT usuario_grupo_usuario_fkey FOREIGN KEY (usuario) REFERENCES usuario (id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT usuario_grupo_grupo_fkey FOREIGN KEY (grupo) REFERENCES grupo (id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON TABLE usuario_grupo IS 'Relación entre usuarios y los grupos a los que pertenecen';
COMMENT ON COLUMN usuario_grupo.usuario IS 'Usuario de la aplicación';
COMMENT ON COLUMN usuario_grupo.grupo IS 'Grupo al que pertenece el usuario';
COMMENT ON COLUMN usuario_grupo.primario IS 'Indica si el grupo es el grupo primario del usuario';

-- tabla que contiene los permisos de los grupos sobre recursos
DROP TABLE IF EXISTS auth CASCADE;
CREATE TABLE auth (
    id serial PRIMARY KEY,
    grupo INTEGER NOT NULL,
    recurso CHARACTER VARYING (300),
    CONSTRAINT auth_grupo_fkey FOREIGN KEY (grupo) REFERENCES grupo (id) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON TABLE auth IS 'Permisos de grupos para acceder a recursos';
COMMENT ON COLUMN auth.id IS 'Identificador (serial)';
COMMENT ON COLUMN auth.grupo IS 'Grupo al que se le concede el permiso';
COMMENT ON COLUMN auth.recurso IS 'Recurso al que el grupo tiene acceso';

-- tabla para los datos extra del usuario (api, configuraciones, etc, propias de la aplicación)
DROP TABLE IF EXISTS usuario_config CASCADE;
CREATE TABLE usuario_config (
    usuario INTEGER NOT NULL,
    configuracion VARCHAR(32) NOT NULL,
    variable VARCHAR(64) NOT NULL,
    valor TEXT,
    json BOOLEAN NOT NULL DEFAULT false,
    CONSTRAINT usuario_config_pkey PRIMARY KEY (usuario, configuracion, variable),
    CONSTRAINT usuario_config_usuario_fk FOREIGN KEY (usuario)
                REFERENCES usuario (id) MATCH FULL
                ON UPDATE CASCADE ON DELETE CASCADE
);

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
