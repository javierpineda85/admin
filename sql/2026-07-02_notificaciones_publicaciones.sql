CREATE TABLE IF NOT EXISTS notificaciones (
    idNotificacion int NOT NULL AUTO_INCREMENT,
    id_usuario int NOT NULL,
    tipoNotificacion varchar(40) NOT NULL,
    referenciaTipo varchar(30) NOT NULL,
    referenciaId int NOT NULL,
    tituloNotificacion varchar(160) NOT NULL,
    detalleNotificacion text NULL,
    urlNotificacion varchar(255) NULL,
    fechaNotificacion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idNotificacion),
    UNIQUE KEY uq_notificacion_usuario_ref (id_usuario, tipoNotificacion, referenciaTipo, referenciaId),
    KEY idx_notificaciones_usuario_fecha (id_usuario, fechaNotificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
