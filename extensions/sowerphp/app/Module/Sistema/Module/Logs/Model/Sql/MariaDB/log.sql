BEGIN;

DROP TABLE IF EXISTS log;
CREATE TABLE log (
    id SERIAL PRIMARY KEY,
    fechahora DATETIME NOT NULL DEFAULT NOW(),
    identificador VARCHAR(255) NOT NULL,
    origen SMALLINT NOT NULL,
    gravedad SMALLINT NOT NULL,
    usuario INTEGER UNSIGNED,
    ip VARCHAR(45),
    solicitud VARCHAR(2000),
    mensaje TEXT NOT NULL,
    CONSTRAINT log_usuario_fk FOREIGN KEY (usuario)
        REFERENCES usuario (id) MATCH FULL
        ON UPDATE CASCADE ON DELETE RESTRICT
);

COMMIT;
