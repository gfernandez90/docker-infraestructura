<?php
// src/models/ConfiguracionModel.php

class ConfiguracionModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM configuraciones ORDER BY clave ASC")->fetchAll();
    }

    public function updateMultiple(array $configuraciones): void {
        $stmt = $this->db->prepare("UPDATE configuraciones SET valor = :valor, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id");
        
        foreach ($configuraciones as $id => $valor) {
            $stmt->execute(['valor' => $valor, 'id' => $id]);
        }
    }
}