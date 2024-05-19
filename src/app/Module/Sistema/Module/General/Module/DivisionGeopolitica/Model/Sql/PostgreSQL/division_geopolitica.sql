BEGIN;

DROP TABLE IF EXISTS region CASCADE;
CREATE TABLE region (
	codigo CHAR(2) PRIMARY KEY,
	region CHARACTER VARYING (60) NOT NULL,
	orden SMALLINT NOT NULL DEFAULT 0
);
COMMENT ON TABLE region IS 'Regiones del país';
COMMENT ON COLUMN region.codigo IS 'Código de la región';
COMMENT ON COLUMN region.region IS 'Nombre de la región';

DROP TABLE IF EXISTS provincia CASCADE;
CREATE TABLE provincia (
	codigo CHAR(3) PRIMARY KEY,
	provincia CHARACTER VARYING (30) NOT NULL,
	region CHAR(2) NOT NULL,
	CONSTRAINT provincia_region_fk FOREIGN KEY (region) REFERENCES region
		(codigo) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
);
COMMENT ON TABLE provincia IS 'Provincias de cada región del país';
COMMENT ON COLUMN provincia.codigo IS 'Código de la provincia';
COMMENT ON COLUMN provincia.provincia IS 'Nombre de la provincia';
COMMENT ON COLUMN provincia.region IS 'Región a la que pertenece la provincia';

DROP TABLE IF EXISTS comuna CASCADE;
CREATE TABLE comuna (
	codigo CHAR(5) PRIMARY KEY,
	comuna CHARACTER VARYING (40) NOT NULL,
	provincia CHAR(3) NOT NULL,
	CONSTRAINT comuna_provincia_fk FOREIGN KEY (provincia) REFERENCES
		provincia (codigo) MATCH FULL ON UPDATE CASCADE ON DELETE
		CASCADE
);
COMMENT ON TABLE comuna IS 'Comunas de cada provincia del país';
COMMENT ON COLUMN comuna.codigo IS 'Código de la comuna';
COMMENT ON COLUMN comuna.comuna IS 'Nombre de la comuna';
COMMENT ON COLUMN comuna.provincia IS 'Provincia a la que pertenece la comuna';

COMMIT;
