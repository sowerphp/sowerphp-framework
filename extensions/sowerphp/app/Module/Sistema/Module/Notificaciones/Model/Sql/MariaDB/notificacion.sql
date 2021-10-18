BEGIN;

DROP TABLE IF EXISTS notificacion;
CREATE TABLE notificacion (
    id SERIAL PRIMARY KEY,
    fechahora DATETIME NOT NULL DEFAULT NOW(),
    gravedad SMALLINT NOT NULL,
    de INTEGER UNSIGNED,
    para INTEGER UNSIGNED NOT NULL,
    descripcion TEXT NOT NULL,
    icono VARCHAR(50),
    enlace VARCHAR(2000),
    leida BOOLEAN NOT NULL DEFAULT false,
    CONSTRAINT notificacion_de_fk FOREIGN KEY (de)
        REFERENCES usuario (id) MATCH FULL
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT notificacion_para_fk FOREIGN KEY (para)
        REFERENCES usuario (id) MATCH FULL
        ON UPDATE CASCADE ON DELETE RESTRICT
);
CREATE INDEX notificacion_para_idx ON notificacion (para);

COMMIT;
