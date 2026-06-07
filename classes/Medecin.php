<?php
class Medecin {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Récupérer tous les médecins actifs
    public function getAll(): array {
        return $this->pdo
            ->query("SELECT * FROM medecins WHERE actif = 1 ORDER BY nom")
            ->fetchAll();
    }

    // Compter les médecins actifs
    public function compter(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM medecins WHERE actif = 1")
            ->fetchColumn();
    }
}