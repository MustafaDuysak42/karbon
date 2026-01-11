INSERT INTO users (name, email, role, password_hash) VALUES
('Süper Admin', 'admin@cbam.local', 'SUPER_ADMIN', '$2y$12$qGvmtbyT8778X2Y8eRxtYOpxaIH.wj9ruNue9r5uKaLOw7HztO3E.'),
('Danışman Demo', 'consultant@cbam.local', 'CONSULTANT', '$2y$12$qGvmtbyT8778X2Y8eRxtYOpxaIH.wj9ruNue9r5uKaLOw7HztO3E.'),
('Müşteri Viewer', 'viewer@cbam.local', 'CUSTOMER_VIEWER', '$2y$12$qGvmtbyT8778X2Y8eRxtYOpxaIH.wj9ruNue9r5uKaLOw7HztO3E.');

INSERT INTO companies (name, tax_number) VALUES
('Anka Çelik', 'TR1234567890'),
('Ege Alüminyum', 'TR9876543210');

INSERT INTO facilities (company_id, name, location) VALUES
(1, 'Gebze Tesisi', 'Kocaeli'),
(1, 'İzmir Tesisi', 'İzmir'),
(2, 'Manisa Tesisi', 'Manisa');

INSERT INTO products (company_id, facility_id, name, product_type) VALUES
(1, 1, 'Sıcak Haddelenmiş Çelik', 'STEEL'),
(2, 3, 'Birincil Alüminyum', 'ALUMINUM');

INSERT INTO periods (company_id, year, quarter) VALUES
(1, 2024, 1),
(2, 2024, 1);

INSERT INTO user_company_assignments (user_id, company_id) VALUES
(2, 1),
(2, 2),
(3, 1);

INSERT INTO user_facility_assignments (user_id, facility_id) VALUES
(2, 1),
(2, 3),
(3, 1);

INSERT INTO factors (company_id, name, source, year, value, unit) VALUES
(NULL, 'Şebeke EF', 'EU Default', 2024, 0.45, 'tCO2/MWh'),
(1, 'Yakıt EF', 'EPDK', 2024, 0.25, 'tCO2/t');

INSERT INTO tenant_settings (company_id, electricity_ef_override) VALUES
(1, 0.38);

INSERT INTO lineage (company_id, entity_type, reference) VALUES
(1, 'Export', 'cbam_export_1_1_v1.xlsx');
