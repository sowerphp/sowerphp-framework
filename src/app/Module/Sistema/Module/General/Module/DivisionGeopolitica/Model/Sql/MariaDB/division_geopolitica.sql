BEGIN;

DROP TABLE IF EXISTS region CASCADE;
CREATE TABLE region (
	codigo CHAR(2) PRIMARY KEY
		COMMENT 'Código de la región',
	region CHARACTER VARYING (60) NOT NULL
		COMMENT 'Nombre de la región'
) COMMENT = 'Regiones del país';

DROP TABLE IF EXISTS provincia CASCADE;
CREATE TABLE provincia (
	codigo CHAR(3) PRIMARY KEY
		COMMENT 'Código de la provincia',
	provincia CHARACTER VARYING (30) NOT NULL
		COMMENT 'Nombre de la provincia',
	region CHAR(2) NOT NULL
		COMMENT 'Región a la que pertenece la provincia',
	CONSTRAINT provincia_region_fk FOREIGN KEY (region) REFERENCES region
		(codigo) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'Provincias de cada región del país';

DROP TABLE IF EXISTS comuna CASCADE;
CREATE TABLE comuna (
	codigo CHAR(5) PRIMARY KEY
		COMMENT 'Comunas de cada provincia del país',
	comuna CHARACTER VARYING (40) NOT NULL
		COMMENT 'Código de la comuna',
	provincia CHAR(3) NOT NULL
		COMMENT 'Nombre de la comuna',
	CONSTRAINT comuna_provincia_fk FOREIGN KEY (provincia) REFERENCES
		provincia (codigo) MATCH FULL ON UPDATE CASCADE ON DELETE
		CASCADE
) COMMENT 'Provincia a la que pertenece la comuna';

COMMIT;
