<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/RendezVous.php';

$rdvModel = new RendezVous($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $rdvModel->ajouter($_POST);
        $msg = ['type' => 'success', 'texte' => 'Rendez-vous ajouté avec succès.'];
    }
    if ($action === 'modifier') {
        $rdvModel->modifier((int)$_POST['id'], $_POST);
        $msg = ['type' => 'success', 'texte' => 'Rendez-vous modifié avec succès.'];
    }
    if ($action === 'supprimer') {
        $rdvModel->supprimer((int)$_POST['id']);
        $msg = ['type' => 'warning', 'texte' => 'Rendez-vous supprimé.'];
    }
}

$recherche = trim($_GET['q'] ?? '');
$filtre    = $_GET['filtre'] ?? 'tous';
$rdvList   = $rdvModel->getListe($filtre, $recherche);
$patients  = $rdvModel->getPatients();
$medecins  = $rdvModel->getMedecins();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rendez-vous – Cabinet Médical</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link href="../assets/css/style.css" rel="stylesheet"/>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container-fluid px-4 py-4">

  <!-- Titre -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
      <h1 class="page-title">Gestion des <span>Rendez-vous</span></h1>
      <div class="page-date"><?= count($rdvList) ?> rendez-vous trouvé(s)</div>
    </div>
    <button class="btn-action" data-bs-toggle="modal" data-bs-target="#modalAjout">
      <i class="fa-solid fa-plus"></i>
      Nouveau RDV
    </button>
  </div>

  <!-- Message -->
  <?php if (isset($msg)) : ?>
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show mb-3"
       role="alert" style="border-radius:12px; font-size:.85rem;">
    <?= htmlspecialchars($msg['texte']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Recherche + Filtres -->
  <div class="card-panel mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">

      <!-- Barre recherche -->
      <form method="GET" action="" class="d-flex gap-2 flex-grow-1">
        <input type="hidden" name="filtre" value="<?= htmlspecialchars($filtre) ?>"/>
        <div class="input-group">
          <span class="input-group-text" style="background:#f0f4f3; border-color:var(--bordure);">
            <i class="fa-solid fa-magnifying-glass text-muted"></i>
          </span>
          <input type="text" name="q" class="form-control"
                 placeholder="Rechercher par patient ou médecin…"
                 value="<?= htmlspecialchars($recherche) ?>"
                 style="border-color:var(--bordure); font-size:.85rem;"/>
        </div>
        <button type="submit" class="btn-action">
          <i class="fa-solid fa-search"></i>
        </button>
        <?php if ($recherche) : ?>
        <a href="rendez_vous.php" class="btn-action-outline">
          <i class="fa-solid fa-xmark"></i>
        </a>
        <?php endif; ?>
      </form>

      <!-- Boutons filtre -->
      <div class="d-flex gap-2">
        <a href="?filtre=tous"
           class="<?= $filtre==='tous' ? 'btn-action' : 'btn-action-outline' ?>">
          Tous
        </a>
        <a href="?filtre=aujourd_hui"
           class="<?= $filtre==='aujourd_hui' ? 'btn-action' : 'btn-action-outline' ?>">
          <i class="fa-solid fa-calendar-day"></i>
          Aujourd'hui
        </a>
        <a href="?filtre=en_attente"
           class="<?= $filtre==='en_attente' ? 'btn-action' : 'btn-action-outline' ?>">
          <i class="fa-solid fa-clock"></i>
          En attente
        </a>
      </div>

    </div>
  </div>
  <!-- Tableau -->
  <div class="card-panel">
    <div class="section-title">
      <i class="fa-solid fa-calendar-check"></i>
      Liste des Rendez-vous
    </div>

    <div class="table-responsive">
      <table class="table-rdv">
        <thead>
          <tr>
            <th>Date</th>
            <th>Heure</th>
            <th>Patient</th>
            <th>Médecin</th>
            <th>Motif</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>

          <?php if (empty($rdvList)) : ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              <i class="fa-solid fa-calendar-xmark me-2"></i>
              Aucun rendez-vous trouvé.
            </td>
          </tr>
          <?php endif; ?>

          <?php foreach ($rdvList as $r) : ?>
          <tr>
            <td style="font-size:.83rem;">
              <?= date('d/m/Y', strtotime($r['date_rdv'])) ?>
            </td>
            <td>
              <span class="rdv-time"><?= substr($r['heure_rdv'],0,5) ?></span>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="patient-av">
                  <?php
                    $parts = explode(' ', $r['patient_nom']);
                    echo strtoupper(substr($parts[0],0,1) . substr($parts[1] ?? '',0,1));
                  ?>
                </div>
                <span style="font-size:.83rem; font-weight:500;">
                  <?= htmlspecialchars($r['patient_nom']) ?>
                </span>
              </div>
            </td>
            <td style="font-size:.83rem;">
              <?= htmlspecialchars($r['medecin_nom']) ?>
            </td>
            <td style="font-size:.83rem;">
              <?= htmlspecialchars($r['motif'] ?? '–') ?>
            </td>
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
            <td>
              <div class="d-flex gap-1">
                <!-- Modifier -->
                <button class="btn-icon btn-edit"
                        data-bs-toggle="modal"
                        data-bs-target="#modalModifier"
                        data-id="<?= $r['id'] ?>"
                        data-patient="<?= $r['patient_id'] ?>"
                        data-medecin="<?= $r['medecin_id'] ?>"
                        data-date="<?= $r['date_rdv'] ?>"
                        data-heure="<?= substr($r['heure_rdv'],0,5) ?>"
                        data-motif="<?= htmlspecialchars($r['motif'] ?? '') ?>"
                        data-statut="<?= $r['statut'] ?>">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <!-- Supprimer -->
                <form method="POST" onsubmit="return confirm('Supprimer ce rendez-vous ?')">
                  <input type="hidden" name="action" value="supprimer"/>
                  <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                  <button type="submit" class="btn-icon btn-del">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>
  </div>

</div><!-- fin container -->
<div class="modal fade" id="modalAjout" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:1px solid var(--bordure);">

      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-family:'Sora',sans-serif; font-weight:700;">
          <i class="fa-solid fa-calendar-plus me-2" style="color:var(--vert);"></i>
          Nouveau Rendez-vous
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="ajouter"/>
          <div class="row g-3">

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Patient *</label>
              <select name="patient_id" class="form-select form-select-sm" required>
                <option value="">— Choisir un patient —</option>
                <?php foreach ($patients as $p) : ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['nom_complet']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Médecin *</label>
              <select name="medecin_id" class="form-select form-select-sm" required>
                <option value="">— Choisir un médecin —</option>
                <?php foreach ($medecins as $m) : ?>
                <option value="<?= $m['id'] ?>">
                  <?= htmlspecialchars($m['nom_complet']) ?>
                  · <?= htmlspecialchars($m['specialite']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Date *</label>
              <input type="date" name="date_rdv" class="form-control form-control-sm"
                     min="<?= date('Y-m-d') ?>" required/>
            </div>

            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Heure *</label>
              <input type="time" name="heure_rdv" class="form-control form-control-sm" required/>
            </div>

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Motif</label>
              <input type="text" name="motif" class="form-control form-control-sm"
                     placeholder="Ex: Consultation, Suivi, Urgence…"/>
            </div>

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Statut</label>
              <select name="statut" class="form-select form-select-sm">
                <option value="en_attente">En attente</option>
                <option value="confirme">Confirmé</option>
              </select>
            </div>

          </div>
          <div class="d-flex gap-2 justify-content-end mt-3">
            <button type="button" class="btn-action-outline" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn-action">Enregistrer</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<div class="modal fade" id="modalModifier" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:1px solid var(--bordure);">

      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-family:'Sora',sans-serif; font-weight:700;">
          <i class="fa-solid fa-pen me-2" style="color:var(--vert);"></i>
          Modifier Rendez-vous
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="modifier"/>
          <input type="hidden" name="id" id="mod-id"/>
          <div class="row g-3">

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Patient *</label>
              <select name="patient_id" id="mod-patient" class="form-select form-select-sm" required>
                <?php foreach ($patients as $p) : ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['nom_complet']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Médecin *</label>
              <select name="medecin_id" id="mod-medecin" class="form-select form-select-sm" required>
                <?php foreach ($medecins as $m) : ?>
                <option value="<?= $m['id'] ?>">
                  <?= htmlspecialchars($m['nom_complet']) ?>
                  · <?= htmlspecialchars($m['specialite']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Date *</label>
              <input type="date" name="date_rdv" id="mod-date"
                     class="form-control form-control-sm" required/>
            </div>

            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Heure *</label>
              <input type="time" name="heure_rdv" id="mod-heure"
                     class="form-control form-control-sm" required/>
            </div>

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Motif</label>
              <input type="text" name="motif" id="mod-motif"
                     class="form-control form-control-sm"/>
            </div>

            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Statut</label>
              <select name="statut" id="mod-statut" class="form-select form-select-sm">
                <option value="en_attente">En attente</option>
                <option value="confirme">Confirmé</option>
                <option value="annule">Annulé</option>
              </select>
            </div>

          </div>
          <div class="d-flex gap-2 justify-content-end mt-3">
            <button type="button" class="btn-action-outline" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn-action">Mettre à jour</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Remplir le modal modifier avec les données du RDV cliqué
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('mod-id').value      = this.dataset.id;
    document.getElementById('mod-date').value    = this.dataset.date;
    document.getElementById('mod-heure').value   = this.dataset.heure;
    document.getElementById('mod-motif').value   = this.dataset.motif;

    // Sélectionner le bon patient
    const selPatient = document.getElementById('mod-patient');
    selPatient.value = this.dataset.patient;

    // Sélectionner le bon médecin
    const selMedecin = document.getElementById('mod-medecin');
    selMedecin.value = this.dataset.medecin;

    // Sélectionner le bon statut
    const selStatut = document.getElementById('mod-statut');
    selStatut.value = this.dataset.statut;
  });
});
</script>
</body>
</html>