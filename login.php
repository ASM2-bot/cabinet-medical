<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/database.php';

// Déjà connecté → dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = trim($_POST['mot_de_passe'] ?? '');

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['admin_id']  = $user['id'];
            $_SESSION['admin_nom'] = $user['nom'];
            header('Location: admin/dashboard.php');
            exit;
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Connexion – Cabinet Médical</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link href="assets/css/style.css" rel="stylesheet"/>
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">

    <!-- Logo -->
    <div class="text-center mb-4">
      <div class="brand-icon mx-auto mb-3"
           style="width:54px; height:54px; font-size:22px;">
        <i class="fa-solid fa-stethoscope"></i>
      </div>
      <h1 style="font-family:'Sora',sans-serif; font-size:1.3rem; font-weight:700;">
        Cabinet Médical Al Amal
      </h1>
      <p style="font-size:.82rem; color:var(--gris); margin-top:4px;">
        Espace Administrateur
      </p>
    </div>

    <!-- Erreur -->
    <?php if ($erreur) : ?>
    <div class="alert alert-danger alert-dismissible fade show"
         style="border-radius:10px; font-size:.83rem;" role="alert">
      <i class="fa-solid fa-circle-exclamation me-2"></i>
      <?= htmlspecialchars($erreur) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <form method="POST" action="login.php">

      <div class="mb-3">
        <label class="form-label" style="font-size:.83rem; font-weight:500;">
          Adresse email
        </label>
        <div class="input-group">
          <span class="input-group-text"
                style="background:#f0f4f3; border-color:var(--bordure);">
            <i class="fa-solid fa-envelope" style="color:var(--gris);"></i>
          </span>
          <input type="email" name="email" class="form-control"
                 placeholder="admin@cabinet.ma"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 style="border-color:var(--bordure);"
                 required/>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label" style="font-size:.83rem; font-weight:500;">
          Mot de passe
        </label>
        <div class="input-group">
          <span class="input-group-text"
                style="background:#f0f4f3; border-color:var(--bordure);">
            <i class="fa-solid fa-lock" style="color:var(--gris);"></i>
          </span>
          <input type="password" name="mot_de_passe" class="form-control"
                 placeholder="••••••••"
                 style="border-color:var(--bordure);"
                 required/>
          <button type="button" class="input-group-text"
                  style="background:#f0f4f3; border-color:var(--bordure); cursor:pointer;"
                  onclick="toggleMdp()">
            <i class="fa-solid fa-eye" id="icon-oeil" style="color:var(--gris);"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-action w-100">
        <i class="fa-solid fa-right-to-bracket"></i>
        Se connecter
      </button>

    </form>

    <p class="text-center mt-4" style="font-size:.72rem; color:var(--gris);">
      Projet Web – Groupe 4 &nbsp;|&nbsp; <?= date('Y') ?>
    </p>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleMdp() {
  const input  = document.querySelector('input[name="mot_de_passe"]');
  const icone  = document.getElementById('icon-oeil');
  if (input.type === 'password') {
    input.type   = 'text';
    icone.className = 'fa-solid fa-eye-slash';
  } else {
    input.type   = 'password';
    icone.className = 'fa-solid fa-eye';
  }
}
</script>
</body>
</html>