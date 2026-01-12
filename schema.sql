CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','consultant') NOT NULL DEFAULT 'consultant',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_reports INT NOT NULL DEFAULT 0,
  used_reports INT NOT NULL DEFAULT 0,
  expiry_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subscriptions_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE installations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consultant_id INT NOT NULL,
  name VARCHAR(190) NOT NULL,
  unlocode VARCHAR(20) NOT NULL,
  address VARCHAR(255) NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_installations_consultant
    FOREIGN KEY (consultant_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE processes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  installation_id INT NOT NULL,
  cn_code VARCHAR(20) NOT NULL,
  production_amount DECIMAL(18,4) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_processes_installation
    FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE CASCADE
);

CREATE TABLE emissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  installation_id INT NOT NULL,
  type ENUM('direct','indirect') NOT NULL,
  energy_source VARCHAR(100) NOT NULL,
  amount DECIMAL(18,4) NOT NULL,
  ef_factor DECIMAL(18,6) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_emissions_installation
    FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE CASCADE
);

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  installation_id INT NOT NULL,
  user_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reports_installation
    FOREIGN KEY (installation_id) REFERENCES installations(id) ON DELETE CASCADE,
  CONSTRAINT fk_reports_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(100) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
