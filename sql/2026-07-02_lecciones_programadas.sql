ALTER TABLE lecciones
    ADD COLUMN IF NOT EXISTS fechaPublicacionLeccion datetime NULL AFTER estadoLeccion;
