<?php
class Patient {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Récupérer tous les patients
    public function getAll(): array {
        return $this->pdo
            ->query("SELECT * FROM patients ORDER BY created_at DESC")
            ->fetchAll();
    }

    // Rechercher des patients
    public function rechercher(string $q): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM patients
            WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ?
            ORDER BY created_at DESC
        ");
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    // Compter tous les patients
    public function compter(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM patients")
            ->fetchColumn();
    }

    // Ajouter un patient
    public function ajouter(array $data): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['nom']),
            trim($data['prenom']),
            $data['date_naissance'] ?: null,
            trim($data['telephone']),
            trim($data['email']),
            trim($data['adresse'])
        ]);
    }

    // Modifier un patient
    public function modifier(int $id, array $data): void {
        $stmt = $this->pdo->prepare("
            UPDATE patients
            SET nom=?, prenom=?, date_naissance=?, telephone=?, email=?, adresse=?
            WHERE id=?
        ");
        $stmt->execute([
            trim($data['nom']),
            trim($data['prenom']),
            $data['date_naissance'] ?: null,
            trim($data['telephone']),
            trim($data['email']),
            trim($data['adresse']),
            $id
        ]);
    }

    // Supprimer un patient
    public function supprimer(int $id): void {
        $this->pdo
            ->prepare("DELETE FROM patients WHERE id=?")
            ->execute([$id]);
    }
}