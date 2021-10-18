-- SCHEMA PARA MÓDULO: Enlaces
-- Base de datos: MariaDB

BEGIN;
SET FOREIGN_KEY_CHECKS=0;

-- tabla para categorías de los enlaces
DROP TABLE IF EXISTS enlace_categoria CASCADE;
CREATE TABLE enlace_categoria (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
		COMMENT 'Identificador (serial)',
	categoria CHARACTER VARYING (30) NOT NULL
		COMMENT 'Nombre de la categoría',
	madre INTEGER UNSIGNED
		COMMENT 'Categoría madre de esta categoría (si tiene)',
	CONSTRAINT enlace_categoria_madre_fkey FOREIGN KEY (madre)
		REFERENCES enlace_categoria (id)
		MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT 'Categorías de los enlaces de la aplicación';

-- tabla que contiene los enlaces
DROP TABLE IF EXISTS enlace CASCADE;
CREATE TABLE enlace (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
		COMMENT 'Identificador (serial)',
	enlace CHARACTER VARYING (50) NOT NULL
		COMMENT 'Nombre del enlace',
	url CHARACTER VARYING (300) NOT NULL
		COMMENT 'URL (dirección web)',
	categoria INTEGER UNSIGNED NOT NULL
		COMMENT 'Categoría a la que pertenece el enlace',
	CONSTRAINT enlace_categoria_fkey FOREIGN KEY (categoria)
		REFERENCES enlace_categoria (id)
		MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT 'Enlaces de la aplicación';

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
