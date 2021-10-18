-- SCHEMA PARA MÓDULO: Enlaces
-- Base de datos: PostgreSQL

BEGIN;

-- tabla para categorías de los enlaces
DROP TABLE IF EXISTS enlace_categoria CASCADE;
CREATE TABLE enlace_categoria (
	id serial PRIMARY KEY,
	categoria CHARACTER VARYING (30) NOT NULL,
	madre INTEGER,
	CONSTRAINT enlace_categoria_madre_fkey FOREIGN KEY (madre)
		REFERENCES enlace_categoria (id)
		MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON TABLE enlace_categoria IS
	'Categorías de los enlaces de la aplicación';
COMMENT ON COLUMN enlace_categoria.id IS 'Identificador (serial)';
COMMENT ON COLUMN enlace_categoria.categoria IS 'Nombre de la categoría';
COMMENT ON COLUMN enlace_categoria.madre IS
	'Categoría madre de esta categoría (si tiene)';

-- tabla que contiene los enlaces
DROP TABLE IF EXISTS enlace CASCADE;
CREATE TABLE enlace (
	id serial PRIMARY KEY,
	enlace CHARACTER VARYING (50) NOT NULL,
	url CHARACTER VARYING (300) NOT NULL,
	categoria INTEGER NOT NULL,
	CONSTRAINT enlace_categoria_fkey FOREIGN KEY (categoria)
		REFERENCES enlace_categoria (id)
		MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON TABLE enlace IS 'Enlaces de la aplicación';
COMMENT ON COLUMN enlace.id IS 'Identificador (serial)';
COMMENT ON COLUMN enlace.enlace IS 'Nombre del enlace';
COMMENT ON COLUMN enlace.url IS 'URL (dirección web)';
COMMENT ON COLUMN enlace.categoria IS 'Categoría a la que pertenece el enlace';

COMMIT;
