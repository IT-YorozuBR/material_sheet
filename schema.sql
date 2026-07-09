CREATE DATABASE IF NOT EXISTS componentes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE componentes_db;

CREATE TABLE IF NOT EXISTS componentes_estampados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stampers_part_number VARCHAR(100) NOT NULL,
    parts_name VARCHAR(150) NOT NULL,
    spec VARCHAR(100),
    g_weight DECIMAL(12,4) NOT NULL,
    thickness DECIMAL(12,4),
    width DECIMAL(12,4),
    pitch DECIMAL(12,4),
    bl_sheet_weight DECIMAL(12,4)
);

CREATE TABLE IF NOT EXISTS ordens_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_ordem VARCHAR(100) NOT NULL UNIQUE,
    data_recebimento DATE NOT NULL,
    supplier VARCHAR(100),
    warehouse VARCHAR(100),
    packing_slip VARCHAR(100),
    purchase_order VARCHAR(100),
    receipt_no VARCHAR(100),
    production_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS recebimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ordem_compra_id INT NOT NULL,
    componente_id INT NOT NULL,
    received_weight DECIMAL(12,3) NOT NULL,
    expected_qty DECIMAL(12,3) NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ordem_compra_id) REFERENCES ordens_compra(id) ON DELETE CASCADE,
    FOREIGN KEY (componente_id) REFERENCES componentes_estampados(id)
);

-- Alguns componentes de exemplo para você conseguir testar o cadastro de ordens
INSERT INTO componentes_estampados (stampers_part_number, parts_name, spec, g_weight, thickness, width, pitch, bl_sheet_weight) VALUES
('PN-001', 'Terminal A', 'SPCC', 1.2500, 0.3000, 25.0000, 5.0000, 0.4500),
('PN-002', 'Terminal B', 'SPCE', 0.8500, 0.2500, 20.0000, 4.0000, 0.3800),
('PN-003', 'Contato C', 'SUS304', 2.1000, 0.5000, 30.0000, 6.5000, 0.6200);
