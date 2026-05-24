<?php
session_start();

// Si déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = 'localhost';
    $dbname = 'mon_pti_budget';
    $user   = 'root';
    $pass   = '';

    $email    = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $erreur = "Tous les champs sont obligatoires.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['user_id']  = $utilisateur['id'];
                $_SESSION['user_nom'] = $utilisateur['nom'];
                header('Location: dashboard.php');
                exit;
            } else {
                $erreur = "Email ou mot de passe incorrect.";
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
  <title>Connexion - Mon Pti' Budget</title>
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
  </style>
</head>
<body>

  <div class="card">
    <h1 class="logo">💰 Mon Pti' Budget</h1>
    <h2>Connexion</h2>
    <p class="subtitle">Accède à ton tableau de bord</p>

    <?php if ($erreur): ?>
      <div class="alert-error"><?= $erreur ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="exemple@mail.com" required
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>

      <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn">Se connecter</button>

    </form>

    <div class="links">
      <a href="forgot-password.html">Mot de passe oublié ?</a>
      <a href="register.php">Créer un compte</a>
    </div>
  </div>

</body>
</html>