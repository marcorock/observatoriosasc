CREATE TABLE IF NOT EXISTS dashboard_config (
    id INT NOT NULL PRIMARY KEY,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_bsc_data_referencia ON bsc (data_referencia);
