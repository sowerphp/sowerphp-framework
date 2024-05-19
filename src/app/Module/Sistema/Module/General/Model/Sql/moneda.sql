BEGIN;

DROP TABLE IF EXISTS moneda_cambio CASCADE;
CREATE TABLE moneda_cambio (
  desde CHAR(3),
  a CHAR(3),
  fecha date,
  valor float NOT NULL,
  CONSTRAINT moneda_cambio_pkey PRIMARY KEY (desde, a, fecha)
);

COMMIT;
