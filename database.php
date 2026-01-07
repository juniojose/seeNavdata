<?php
class Database {
    private $db;

    public function __construct() {
        try {
            // Cria ou abre o arquivo do banco de dados
            $this->db = new SQLite3('navdata.db');
            $this->initialize();
        } catch (Exception $e) {
            die("Erro ao conectar ao SQLite: " . $e->getMessage());
        }
    }

    private function initialize() {
        // Tabela para armazenar os acessos
        $query = "CREATE TABLE IF NOT EXISTS visits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT,
            user_agent TEXT,
            platform TEXT,
            screen_resolution TEXT,
            timezone_js TEXT,
            timezone_geo TEXT,
            country_geo TEXT,
            isp TEXT,
            canvas_hash TEXT,
            webgl_renderer TEXT,
            is_mobile INTEGER,
            is_suspicious INTEGER DEFAULT 0,
            raw_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($query);
    }

    public function getDb() {
        return $this->db;
    }

    public function close() {
        $this->db->close();
    }
}
