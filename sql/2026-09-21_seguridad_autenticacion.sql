CREATE TABLE IF NOT EXISTS auth_recuperaciones (
    idRecuperacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tokenHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    venceEn DATETIME NOT NULL,
    usadoEn DATETIME NULL,
    ipHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    PRIMARY KEY (idRecuperacion),
    UNIQUE KEY uq_auth_recuperacion_token (tokenHash),
    KEY idx_auth_recuperacion_usuario (id_usuario, usadoEn, venceEn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_intentos (
    idIntento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo VARCHAR(30) NOT NULL,
    claveHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idIntento),
    KEY idx_auth_intento_limite (tipo, claveHash, creadoEn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
