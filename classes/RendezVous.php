<?php
class RendezVous {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Compter tous les RDV
    public function compter(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM rendez_vous")
            ->fetchColumn();
    }

    // Compter les RDV d'aujourd'hui
    public function compterAujourdhui(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = CURDATE()")
            ->fetchColumn();
    }

    // RDV du jour avec détails
    public function getAujourdhui(): array {
        return $this->pdo->query("
            SELECT r.*, 
                   CONCAT(p.prenom,' ',p.nom) AS patient_nom,
                   CONCAT('Dr. ',m.prenom,' ',m.nom) AS medecin_nom
            FROM rendez_vous r
            JOIN patients p ON p.id = r.patient_id
            JOIN medecins m ON m.id = r.medecin_id
            WHERE r.date_rdv = CURDATE()
            ORDER BY r.heure_rdv ASC
        ")->fetchAll();
    }

    // Tous les RDV avec filtres
    public function getListe(string $filtre = 'tous', string $recherche = ''): array {
        $conditions = [];
        $params     = [];

        if ($filtre === 'aujourd_hui') {
            $conditions[] = "r.date_rdv = CURDATE()";
        }
        if ($filtre === 'en_attente') {
            $conditions[] = "r.statut = 'en_attente'";
        }
        if ($recherche) {
            $conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR m.nom LIKE ?)";
            $like = '%' . $recherche . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $sql = "
            SELECT r.*,
                   CONCAT(p.prenom,' ',p.nom) AS patient_nom,
                   CONCAT('Dr. ',m.prenom,' ',m.nom) AS medecin_nom
            FROM rendez_vous r
            JOIN patients p ON p.id = r.patient_id
            JOIN medecins m ON m.id = r.medecin_id
        ";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY r.date_rdv DESC, r.heure_rdv ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Ajouter un RDV
    public function ajouter(array $data): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO rendez_vous (patient_id, medecin_id, date_rdv, heure_rdv, motif, statut)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$data['patient_id'],
            (int)$data['medecin_id'],
            $data['date_rdv'],
            $data['heure_rdv'],
            trim($data['motif']),
            $data['statut'] ?? 'en_attente'
        ]);
    }

    // Modifier un RDV
    public function modifier(int $id, array $data): void {
        $stmt = $this->pdo->prepare("
            UPDATE rendez_vous
            SET patient_id=?, medecin_id=?, date_rdv=?,
                heure_rdv=?, motif=?, statut=?
            WHERE id=?
        ");
        $stmt->execute([
            (int)$data['patient_id'],
            (int)$data['medecin_id'],
            $data['date_rdv'],
            $data['heure_rdv'],
            trim($data['motif']),
            $data['statut'],
            $id
        ]);
    }

    // Supprimer un RDV
    public function supprimer(int $id): void {
        $this->pdo
            ->prepare("DELETE FROM rendez_vous WHERE id=?")
            ->execute([$id]);
    }

    // Liste patients pour select
    public function getPatients(): array {
        return $this->pdo->query("
            SELECT id, CONCAT(prenom,' ',nom) AS nom_complet
            FROM patients ORDER BY nom
        ")->fetchAll();
    }

    // Liste médecins pour select
    public function getMedecins(): array {
        return $this->pdo->query("
            SELECT id, CONCAT('Dr. ',prenom,' ',nom) AS nom_complet, specialite
            FROM medecins WHERE actif=1 ORDER BY nom
        ")->fetchAll();
    }
}