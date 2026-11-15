-- Rol institucional de consulta integral para coordinación académica.
INSERT INTO roles (codigo, nombre)
SELECT 'COORDINADOR', 'Coordinador académico'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE codigo = 'COORDINADOR');
