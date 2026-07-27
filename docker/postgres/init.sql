-- Tabla de Roles
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (nombre) VALUES ('admin'), ('escritura'), ('lectura');

-- Tabla de Usuarios (Sincronizados vía CAS)
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150),
    nombre_completo VARCHAR(150),
    rol_id INT REFERENCES roles(id) DEFAULT 1, -- Por ahora todos Admin
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla del Módulo de Configuraciones (Punto 4)
CREATE TABLE configuraciones (
    id SERIAL PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255),
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Semillas iniciales de configuración
INSERT INTO configuraciones (clave, valor, descripcion) VALUES
('APP_URL', 'https://infraestructura.dgeip.edu.uy', 'URL Base del sistema'),
('INTEGRATION_ZABBIX_URL', 'https://zabbix.dgeip.edu.uy/api_jsonrpc.php', 'URL de API Zabbix'),
('INTEGRATION_GLPI_URL', 'https://glpi.dgeip.edu.uy/apirest.php', 'URL de API GLPI');
