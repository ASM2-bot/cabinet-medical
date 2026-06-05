<?php
require_once __DIR__ . '/../includes/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

// --- Statistiques ---
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalRdv      = $pdo->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
$rdvAujourdhui = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = CURDATE()")->fetchColumn();
$totalMedecins = $pdo->query("SELECT COUNT(*) FROM medecins WHERE actif = 1")->fetchColumn();

// --- Médecins ---
$medecins = $pdo->query("SELECT * FROM medecins WHERE actif = 1 ORDER BY nom")->fetchAll();

// --- RDV du jour ---
$rdvDuJour = $pdo->query("
    SELECT r.heure_rdv, r.motif, r.statut,
           CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
           CONCAT('Dr. ', m.prenom, ' ', m.nom) AS medecin_nom
    FROM rendez_vous r
    JOIN patients p ON p.id = r.patient_id
    JOIN medecins m ON m.id = r.medecin_id
    WHERE r.date_rdv = CURDATE()
    ORDER BY r.heure_rdv ASC
")->fetchAll();
// --- Test rapide ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Administrateur</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <!-- Ton CSS -->
  <link href="../assets/css/style.css" rel="stylesheet"/>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container-fluid px-4 py-4">

  <!-- Titre page -->
  <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
    <div>
      <h1 class="page-title">Tableau de bord <span>Administrateur</span></h1>
      <div class="page-date">
        <span class="live-dot"></span>
        <span id="current-date"></span>
      </div>
    </div>
  </div>
  <!-- Statistiques -->
  <div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">
      <div class="stat-card c-green">
        <div class="stat-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div>
          <div class="stat-value"><?= $totalPatients ?></div>
          <div class="stat-label">Total Patients</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="stat-card c-amber">
        <div class="stat-icon">
          <i class="fa-solid fa-calendar-days"></i>
        </div>
        <div>
          <div class="stat-value"><?= $totalRdv ?></div>
          <div class="stat-label">Total Rendez-vous</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="stat-card c-blue">
        <div class="stat-icon">
          <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div>
          <div class="stat-value"><?= $rdvAujourdhui ?></div>
          <div class="stat-label">RDV Aujourd'hui</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-xl-3">
      <div class="stat-card c-red">
        <div class="stat-icon">
          <i class="fa-solid fa-user-doctor"></i>
        </div>
        <div>
          <div class="stat-value"><?= $totalMedecins ?></div>
          <div class="stat-label">Médecins Actifs</div>
        </div>
      </div>
    </div>

  </div>
  <!-- Médecins + Calendrier -->
  <div class="row g-4 mb-4">

    <!-- Médecins -->
    <div class="col-12 col-lg-6">
      <div class="card-panel h-100">
        <div class="section-title">
          <i class="fa-solid fa-user-doctor"></i>
          Médecins du Cabinet
        </div>

        <?php
        $couleurs = ['doc-green','doc-blue','doc-amber','doc-red'];
        foreach ($medecins as $i => $m) :
        ?>
        <div class="doctor-card">
          <div class="doctor-av <?= $couleurs[$i % 4] ?>">
            <?= strtoupper(substr($m['prenom'],0,1) . substr($m['nom'],0,1)) ?>
          </div>
          <div class="flex-grow-1">
            <div class="doc-name">
              Dr. <?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?>
            </div>
            <div class="doc-spec"><?= htmlspecialchars($m['specialite']) ?></div>
          </div>
          <div class="doc-meta">
            <div class="doc-hours">
              <i class="fa-regular fa-clock"></i>
              <?= substr($m['heure_debut'],0,5) ?> → <?= substr($m['heure_fin'],0,5) ?>
            </div>
            <div class="doc-phone">
              <i class="fa-solid fa-phone"></i>
              <?= htmlspecialchars($m['telephone'] ?? '–') ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- Calendrier -->
    <div class="col-12 col-lg-6">
      <div class="card-panel h-100">
        <div class="section-title">
          <i class="fa-solid fa-calendar"></i>
          Calendrier — <span id="cal-titre"></span>
        </div>
        <div id="calendrier"></div>
      </div>
    </div>

  </div>
  <!-- RDV du jour -->
  <div class="card-panel mb-4">
    <div class="section-title">
      <i class="fa-solid fa-list-check"></i>
      Rendez-vous du Jour
    </div>

    <?php if (empty($rdvDuJour)) : ?>
      <p class="text-center text-muted py-3">
        <i class="fa-solid fa-calendar-xmark me-2"></i>Aucun rendez-vous aujourd'hui.
      </p>
    <?php else : ?>
    <div class="table-responsive">
      <table class="table-rdv">
        <thead>
          <tr>
            <th>Heure</th>
            <th>Patient</th>
            <th>Médecin</th>
            <th>Motif</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rdvDuJour as $r) : ?>
          <tr>
            <td><span class="rdv-time"><?= substr($r['heure_rdv'],0,5) ?></span></td>
            <td><?= htmlspecialchars($r['patient_nom']) ?></td>
            <td><?= htmlspecialchars($r['medecin_nom']) ?></td>
            <td><?= htmlspecialchars($r['motif'] ?? '–') ?></td>
            <td>
              <span class="rdv-statut s-<?= $r['statut'] ?>">
                <?= match($r['statut']) {
                  'confirme'   => 'Confirmé',
                  'en_attente' => 'En attente',
                  'annule'     => 'Annulé',
                  default      => $r['statut']
                } ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>

</div><!-- fin container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
  
</body>
