BEGIN;

DROP TABLE IF EXISTS log;
CREATE TABLE log (
    id BIGSERIAL PRIMARY KEY,
    fechahora TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    identificador VARCHAR(255) NOT NULL,
    origen SMALLINT NOT NULL,
    gravedad SMALLINT NOT NULL,
    usuario INTEGER,
    ip VARCHAR(45),
    solicitud VARCHAR(2000),
    mensaje TEXT NOT NULL,
    CONSTRAINT log_usuario_fk FOREIGN KEY (usuario)
        REFERENCES usuario (id) MATCH FULL
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX log_usuario_idx ON log (usuario);

COMMIT;
