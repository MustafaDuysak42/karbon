CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    role ENUM('SUPER_ADMIN', 'CONSULTANT', 'CUSTOMER_VIEWER') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    tax_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    location VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE user_company_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_company (user_id, company_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE user_facility_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_facility (user_id, facility_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    facility_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    product_type ENUM('STEEL', 'ALUMINUM', 'CEMENT') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

CREATE TABLE periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    year INT NOT NULL,
    quarter TINYINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE cbam_inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    facility_id INT NOT NULL,
    product_id INT NOT NULL,
    period_id INT NOT NULL,
    input_type ENUM('production', 'electricity', 'fuel', 'process', 'optional') NOT NULL,
    data_json JSON NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (period_id) REFERENCES periods(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE calculations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    facility_id INT NOT NULL,
    product_id INT NOT NULL,
    period_id INT NOT NULL,
    direct_emissions DECIMAL(18,4) NOT NULL DEFAULT 0,
    indirect_emissions DECIMAL(18,4) NOT NULL DEFAULT 0,
    total_emissions DECIMAL(18,4) NOT NULL DEFAULT 0,
    embedded_electricity DECIMAL(18,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (period_id) REFERENCES periods(id)
);

CREATE TABLE exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    period_id INT NOT NULL,
    version INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_export_version (company_id, period_id, version),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (period_id) REFERENCES periods(id)
);

CREATE TABLE factors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NULL,
    name VARCHAR(200) NOT NULL,
    source VARCHAR(200) NOT NULL,
    year INT NOT NULL,
    value DECIMAL(18,6) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE tenant_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL UNIQUE,
    electricity_ef_override DECIMAL(18,6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE lineage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    reference VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
