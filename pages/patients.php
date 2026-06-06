<?php
require_once __DIR__ . '/../includes/auth.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/database.php';

// ─────────────────────────────
//  AJOUTER un patient
// ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'ajouter') {
    $stmt = $pdo->prepare("
        INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($_POST['nom']),
        trim($_POST['prenom']),
        $_POST['date_naissance'] ?: null,
        trim($_POST['telephone']),
        trim($_POST['email']),
        trim($_POST['adresse'])
    ]);
    $msg = ['type' => 'success', 'texte' => 'Patient ajouté avec succès.'];
}

// ─────────────────────────────
//  MODIFIER un patient
// ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'modifier') {
    $stmt = $pdo->prepare("
        UPDATE patients
        SET nom = ?, prenom = ?, date_naissance = ?, telephone = ?, email = ?, adresse = ?
        WHERE id = ?
    ");
    $stmt->execute([
        trim($_POST['nom']),
        trim($_POST['prenom']),
        $_POST['date_naissance'] ?: null,
        trim($_POST['telephone']),
        trim($_POST['email']),
        trim($_POST['adresse']),
        (int)$_POST['id']
    ]);
    $msg = ['type' => 'success', 'texte' => 'Patient modifié avec succès.'];
}

// ─────────────────────────────
//  SUPPRIMER un patient
// ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'supprimer') {
    $pdo->prepare("DELETE FROM patients WHERE id = ?")
        ->execute([(int)$_POST['id']]);
    $msg = ['type' => 'warning', 'texte' => 'Patient supprimé.'];
}

// ─────────────────────────────
//  RECHERCHER / LISTER
// ─────────────────────────────
$recherche = trim($_GET['q'] ?? '');

if ($recherche) {
    $stmt = $pdo->prepare("
        SELECT * FROM patients
        WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ?
        ORDER BY created_at DESC
    ");
    $like = '%' . $recherche . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
}

$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestion Patients – Cabinet Médical</title>

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
      <h1 class="page-title">Gestion des <span>Patients</span></h1>
      <div class="page-date">
        <?= count($patients) ?> patient(s) trouvé(s)
      </div>
    </div>
    <!-- Bouton ouvrir modal ajout -->
    <button class="btn-action" data-bs-toggle="modal" data-bs-target="#modalAjout">
      <i class="fa-solid fa-plus"></i>
      Nouveau patient
    </button>
  </div>

  <!-- Message succès / warning -->
  <?php if (isset($msg)) : ?>
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show mb-3" role="alert"
       style="border-radius:12px; font-size:.85rem;">
    <?= htmlspecialchars($msg['texte']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Barre de recherche -->
  <div class="card-panel mb-3">
    <form method="GET" action="" class="d-flex gap-2">
      <div class="input-group">
        <span class="input-group-text" style="background:#f0f4f3; border-color:var(--bordure);">
          <i class="fa-solid fa-magnifying-glass text-muted"></i>
        </span>
        <input type="text" name="q" class="form-control"
               placeholder="Rechercher par nom, prénom ou téléphone…"
               value="<?= htmlspecialchars($recherche) ?>"
               style="border-color:var(--bordure); font-size:.85rem;"/>
      </div>
      <button type="submit" class="btn-action">
        <i class="fa-solid fa-search"></i>
        Chercher
      </button>
      <?php if ($recherche) : ?>
      <a href="patients.php" class="btn-action-outline">
        <i class="fa-solid fa-xmark"></i>
        Effacer
      </a>
      <?php endif; ?>
    </form>
  </div>
  <!-- Tableau -->
  <div class="card-panel">
    <div class="section-title">
      <i class="fa-solid fa-users"></i>
      Liste des Patients
    </div>

    <div class="table-responsive">
      <table class="table-rdv">
        <thead>
          <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Date naissance</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Inscrit le</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>

          <?php if (empty($patients)) : ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              <i class="fa-solid fa-user-slash me-2"></i>
              Aucun patient trouvé.
            </td>
          </tr>
          <?php endif; ?>

          <?php foreach ($patients as $p) : ?>
          <tr>
            <td style="color:var(--gris); font-size:.75rem;"><?= $p['id'] ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="patient-av">
                  <?= strtoupper(substr($p['prenom'],0,1) . substr($p['nom'],0,1)) ?>
                </div>
                <div>
                  <div style="font-weight:600; font-size:.85rem;">
                    <?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <?= $p['date_naissance']
                  ? date('d/m/Y', strtotime($p['date_naissance']))
                  : '–' ?>
            </td>
            <td><?= htmlspecialchars($p['telephone'] ?? '–') ?></td>
            <td><?= htmlspecialchars($p['email'] ?? '–') ?></td>
            <td style="font-size:.78rem;">
              <?= date('d/m/Y', strtotime($p['created_at'])) ?>
            </td>
            <td>
              <div class="d-flex gap-1">
                <!-- Bouton modifier -->
                <button class="btn-icon btn-edit"
                        data-bs-toggle="modal"
                        data-bs-target="#modalModifier"
                        data-id="<?= $p['id'] ?>"
                        data-nom="<?= htmlspecialchars($p['nom']) ?>"
                        data-prenom="<?= htmlspecialchars($p['prenom']) ?>"
                        data-naissance="<?= $p['date_naissance'] ?>"
                        data-telephone="<?= htmlspecialchars($p['telephone'] ?? '') ?>"
                        data-email="<?= htmlspecialchars($p['email'] ?? '') ?>"
                        data-adresse="<?= htmlspecialchars($p['adresse'] ?? '') ?>">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <!-- Bouton supprimer -->
                <form method="POST" onsubmit="return confirm('Supprimer ce patient ?')">
                  <input type="hidden" name="action" value="supprimer"/>
                  <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
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
<!-- Modal Ajouter -->
<div class="modal fade" id="modalAjout" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:1px solid var(--bordure);">

      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-family:'Sora',sans-serif; font-weight:700;">
          <i class="fa-solid fa-user-plus me-2" style="color:var(--vert);"></i>
          Nouveau Patient
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="ajouter"/>
          <div class="row g-3">

            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Prénom *</label>
              <input type="text" name="prenom" class="form-control form-control-sm" required/>
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Nom *</label>
              <input type="text" name="nom" class="form-control form-control-sm" required/>
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Date naissance</label>
              <input type="date" name="date_naissance" class="form-control form-control-sm"/>
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Téléphone</label>
              <input type="text" name="telephone" class="form-control form-control-sm"/>
            </div>
            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Email</label>
              <input type="email" name="email" class="form-control form-control-sm"/>
            </div>
            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Adresse</label>
              <textarea name="adresse" class="form-control form-control-sm" rows="2"></textarea>
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
<!-- Modal Modifier -->
<div class="modal fade" id="modalModifier" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:1px solid var(--bordure);">

      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-family:'Sora',sans-serif; font-weight:700;">
          <i class="fa-solid fa-pen me-2" style="color:var(--vert);"></i>
          Modifier Patient
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="modifier"/>
          <input type="hidden" name="id" id="mod-id"/>
          <div class="row g-3">

            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Prénom *</label>
              <input type="text" name="prenom" id="mod-prenom" class="form-control form-control-sm" required/>
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Nom *</label>
              <input type="text" name="nom" id="mod-nom" class="form-control form-control-sm" required/>
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Date naissance</label>
              <input type="date" name="date_naissance" id="mod-naissance" class="form-control form-control-sm"/>
            </div>
            <div class="col-6">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Téléphone</label>
              <input type="text" name="telephone" id="mod-telephone" class="form-control form-control-sm"/>
            </div>
            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Email</label>
              <input type="email" name="email" id="mod-email" class="form-control form-control-sm"/>
            </div>
            <div class="col-12">
              <label class="form-label" style="font-size:.82rem; font-weight:500;">Adresse</label>
              <textarea name="adresse" id="mod-adresse" class="form-control form-control-sm" rows="2"></textarea>
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
// Remplir le modal modifier avec les données du patient cliqué
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('mod-id').value        = this.dataset.id;
    document.getElementById('mod-nom').value       = this.dataset.nom;
    document.getElementById('mod-prenom').value    = this.dataset.prenom;
    document.getElementById('mod-naissance').value = this.dataset.naissance;
    document.getElementById('mod-telephone').value = this.dataset.telephone;
    document.getElementById('mod-email').value     = this.dataset.email;
    document.getElementById('mod-adresse').value   = this.dataset.adresse;
  });
});
</script>
</body>
</html>
