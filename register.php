<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur  = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = 'localhost';
    $dbname = 'mon_pti_budget';
    $user   = 'root';
    $pass   = '';

    $nom      = htmlspecialchars(trim($_POST['nom']));
    $email    = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if (empty($nom) || empty($email) || empty($password) || empty($confirm)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caractères.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Vérifier si l'email existe déjà
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $erreur = "Cet email est déjà utilisé.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $email, $hash]);

                $message = "Compte créé avec succès ! Tu peux te connecter.";
            }

        } catch (PDOException $e) {
            $erreur = "Erreur base de données : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Créer un compte - Mon Pti' Budget</title>
  <link rel="stylesheet" href="asset/CSS/style.css">
  <style>
    .alert-error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      text-align: center;
    }
    .alert-success {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #6ee7b7;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="card">
    <h1 class="logo">💰 Mon Pti' Budget</h1>
    <h2>Créer un compte</h2>
    <p class="subtitle">Rejoins Mon Pti' Budget</p>

    <?php if ($erreur): ?>
      <div class="alert-error"><?= $erreur ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
      <div class="alert-success">
        <?= $message ?>
        <br><a href="login.php" style="color:#065f46;font-weight:600;">→ Se connecter</a>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST">

      <div class="field">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" placeholder="Ton prénom ou pseudo" required
               value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="exemple@mail.com" required
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>

      <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="Min. 6 caractères" required>
      </div>

      <div class="field">
        <label for="confirm">Confirmer le mot de passe</label>
        <input type="password" id="confirm" name="confirm" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn">Créer mon compte</button>

    </form>

    <div class="links">
      <a href="login.php">← Déjà un compte ? Se connecter</a>
    </div>
  </div>

</body>
</html>