BEGIN;

DROP TABLE IF EXISTS afd CASCADE;
CREATE TABLE afd (
  codigo VARCHAR(10) PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL
);

DROP TABLE IF EXISTS afd_estado CASCADE;
CREATE TABLE afd_estado (
  afd VARCHAR(10),
  codigo integer,
  nombre VARCHAR(50) NOT NULL,
  CONSTRAINT afd_estado_pkey PRIMARY KEY (afd, codigo),
  CONSTRAINT afd_estado_afd_fkey FOREIGN KEY (afd) REFERENCES afd (codigo)
    ON UPDATE CASCADE ON DELETE CASCADE
);

DROP TABLE IF EXISTS afd_transicion CASCADE;
CREATE TABLE afd_transicion (
  afd VARCHAR(10),
  desde integer,
  valor VARCHAR(5),
  hasta integer NOT NULL,
  INDEX afd_transicion_afd_hasta_idx (afd, hasta),
  CONSTRAINT afd_transicion PRIMARY KEY (afd, desde, valor),
  CONSTRAINT afd_transicion_desde_fkey FOREIGN KEY (afd, desde)
    REFERENCES afd_estado (afd, codigo) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT afd_transicion_hasta_fkey FOREIGN KEY (afd, hasta)
    REFERENCES afd_estado (afd, codigo) ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;
