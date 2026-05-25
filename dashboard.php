<?php
session_start();

// US6 : protection de la page, rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host   = 'localhost';
$dbname = 'mon_pti_budget';
$user   = 'root';
$pass   = '';

$depenses       = [];
$total_depenses = 0;
$revenu         = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // US2 : récupérer les 20 dernières dépenses
    $stmt = $pdo->prepare("SELECT * FROM depenses WHERE user_id = ? ORDER BY date DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $depenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul total dépenses
    $stmt2 = $pdo->prepare("SELECT SUM(montant) as total FROM depenses WHERE user_id = ?");
    $stmt2->execute([$_SESSION['user_id']]);
    $row            = $stmt2->fetch(PDO::FETCH_ASSOC);
    $total_depenses = $row['total'] ?? 0;

    // US3 : récupérer le revenu
    $stmt3 = $pdo->prepare("SELECT montant FROM revenus WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt3->execute([$_SESSION['user_id']]);
    $row3   = $stmt3->fetch(PDO::FETCH_ASSOC);
    $revenu = $row3['montant'] ?? 0;

} catch (PDOException $e) {
    // Si la table n'existe pas encore, on continue sans erreur
}

$reste = $revenu - $total_depenses;

// Pourcentage budget utilisé (pour la barre dans le header)
$pct_budget = ($revenu > 0) ? min(round(($total_depenses / $revenu) * 100), 100) : 0;

// Couleur de la barre selon le pourcentage
if ($pct_budget >= 100)     { $couleur_barre = '#dc2626'; } // rouge
elseif ($pct_budget >= 80)  { $couleur_barre = '#d97706'; } // orange
else                         { $couleur_barre = '#2563eb'; } // bleu normal

// Alerte système (pour l'encart)
if ($revenu <= 0) {
    $alerte_classe   = 'alerte-info';
    $alerte_texte    = 'Déclarez votre revenu pour activer les alertes.';
} elseif ($total_depenses > $revenu) {
    $alerte_classe   = 'alerte-danger';
    $alerte_texte    = 'Budget dépassé — vos dépenses excèdent votre revenu ce mois-ci.';
} elseif ($pct_budget >= 80) {
    $alerte_classe   = 'alerte-warning';
    $alerte_texte    = 'Attention — vous avez utilisé ' . $pct_budget . '% de votre budget.';
} else {
    $alerte_classe   = 'alerte-success';
    $alerte_texte    = 'Tout va bien : votre budget est équilibré ce mois-ci.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord — Mon Pti' Budget</title>
  <link rel="stylesheet" href="asset/CSS/style.css">
</head>
<body>

<!-- ================================================================
     EN-TÊTE
================================================================ -->
<header class="topbar">
  <div class="topbar-inner">

    <!-- Logo -->
    <span class="topbar-logo">MON PTI' BUDGET</span>

    <!-- Bloc revenu + barre budget entreprise -->
    <div class="topbar-budget">
      <div class="topbar-revenu">
        <span class="topbar-meta">MON REVENU (€)</span>
        <span class="topbar-chiffre"><?= number_format($revenu, 2, ',', ' ') ?></span>
        <a href="revenu.php" class="topbar-btn-modifier">MODIFIER</a>
      </div>
      <div class="topbar-barre-bloc">
        <span class="topbar-meta">BUDGET ENTREPRISE</span>
        <div class="topbar-barre-fond">
          <div class="topbar-barre-remplie" style="width:<?= $pct_budget ?>%; background:<?= $couleur_barre ?>;"></div>
        </div>
        <span class="topbar-meta">Budget utilisé : <?= $pct_budget ?>%</span>
      </div>
    </div>

    <!-- Nom utilisateur + déconnexion -->
    <div class="topbar-user">
      <span class="topbar-nom"><?= htmlspecialchars($_SESSION['user_nom']) ?></span>
      <a href="logout.php" class="topbar-logout">Déconnexion</a>
    </div>

  </div>
</header>


<!-- ================================================================
     CONTENU
================================================================ -->
<main class="page">

  <!-- ── CARTES STATS ─────────────────────────────────────────── -->
  <div class="cards-grid">

    <div class="card-stat">
      <p class="card-label">Reste à vivre</p>
      <p class="card-val <?= $reste >= 0 ? 'val-pos' : 'val-neg' ?>">
        <?= number_format($reste, 2, ',', ' ') ?> €
      </p>
    </div>

    <div class="card-stat">
      <p class="card-label">Total Dépenses</p>
      <p class="card-val">
        <?= number_format($total_depenses, 2, ',', ' ') ?> €
      </p>
    </div>

    <!-- Encart alerte dans la grille -->
    <div class="card-stat card-alerte-wrap">
      <p class="card-label">ALERTES SYSTÈME</p>
      <div class="alerte-pill <?= $alerte_classe ?>">
        <?= htmlspecialchars($alerte_texte) ?>
      </div>
    </div>

  </div>


  <!-- ── RÉPARTITION DES DÉPENSES PAR CATÉGORIE ───────────────── -->
  <?php
  // On regroupe les dépenses par catégorie pour les barres (US4)
  $repartition = [];
  foreach ($depenses as $d) {
      $cat = $d['categorie'];
      $repartition[$cat] = ($repartition[$cat] ?? 0) + $d['montant'];
  }
  arsort($repartition); // on trie du plus grand au plus petit
  ?>

  <?php if (!empty($repartition)): ?>
  <section class="bloc">
    <h2 class="bloc-titre">Répartition des dépenses</h2>
    <?php foreach ($repartition as $cat => $montant_cat): ?>
      <?php $pct = ($total_depenses > 0) ? round(($montant_cat / $total_depenses) * 100, 1) : 0; ?>
      <div class="repart-ligne">
        <span class="repart-cat"><?= htmlspecialchars($cat) ?></span>
        <div class="repart-barre-fond">
          <div class="repart-barre-remplie" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="repart-montant"><?= number_format($montant_cat, 2, ',', ' ') ?> € (<?= $pct ?>%)</span>
      </div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>


  <!-- ── FORMULAIRE DÉCLARER UNE DÉPENSE ──────────────────────── -->
  <section class="bloc bloc-dark">
    <h2 class="bloc-titre-white">DÉCLARER UNE DÉPENSE</h2>
    <p class="bloc-sous-titre-white">
      Utilise ce formulaire pour ajouter une nouvelle transaction.
      Toutes les valeurs sont calculées après la validation.
    </p>

    <!--
      Ce formulaire poste vers depenses.php.
      Le champ caché "redirect" indique à depenses.php
      de revenir sur dashboard.php après l'ajout.
    -->
    <form method="POST" action="depenses.php">
      <input type="hidden" name="redirect" value="dashboard">

      <div class="form-inline-grid">

        <div class="form-champ">
          <label class="form-label-white" for="f_date">DATE</label>
          <input type="date" id="f_date" name="date"
                 class="form-input" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-champ">
          <label class="form-label-white" for="f_montant">MONTANT (€)</label>
          <input type="number" id="f_montant" name="montant"
                 class="form-input" placeholder="0" min="0.01" step="0.01" required>
        </div>

        <div class="form-champ">
          <label class="form-label-white" for="f_type">TYPE DE DÉPENSE</label>
          <select id="f_type" name="type" class="form-input" required>
            <option value="Personnel">Personnel</option>
            <option value="Professionnel">Professionnel</option>
          </select>
        </div>

        <div class="form-champ">
          <label class="form-label-white" for="f_categorie">CATÉGORIE</label>
          <select id="f_categorie" name="categorie" class="form-input" required>
            <option value="" disabled selected>Choisir...</option>
            <option value="Alimentation">Alimentation</option>
            <option value="Transport">Transport</option>
            <option value="Logement">Logement</option>
            <option value="Santé">Santé</option>
            <option value="Loisirs">Loisirs</option>
            <option value="Vêtements">Vêtements</option>
            <option value="Fournisseurs">Fournisseurs</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Autre">Autre</option>
          </select>
        </div>

      </div><!-- /form-inline-grid -->

      <button type="submit" class="btn-soumettre">VALIDER ET CALCULER</button>

    </form>
  </section>


  <!-- ── HISTORIQUE DES DÉPENSES (US2) ─────────────────────────── -->
  <section class="bloc">
    <div class="bloc-header">
      <h2 class="bloc-titre" style="margin-bottom:0">Historique des dépenses</h2>
      <span class="bloc-count"><?= count($depenses) ?> opération(s)</span>
    </div>

    <?php if (empty($depenses)): ?>
      <div class="vide">
        Aucune dépense enregistrée pour l'instant.<br>
        <a href="depenses.php">Ajouter ta première dépense &rarr;</a>
      </div>
    <?php else: ?>
      <table class="table-hist">
        <thead>
          <tr>
            <th>DATE</th>
            <th>CATÉGORIE</th>
            <th>MONTANT</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($depenses as $d): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($d['date'])) ?></td>
              <td>
                <!--
                  Badge vert = Personnel, badge bleu = Professionnel (US8)
                  On compare en minuscules pour être robuste
                -->
                <span class="badge <?= strtolower($d['type']) === 'professionnel' ? 'badge-pro' : 'badge-perso' ?>">
                  <?= htmlspecialchars($d['categorie']) ?>
                </span>
              </td>
              <td class="td-montant">
                <?= number_format($d['montant'], 2, ',', ' ') ?> €
              </td>
              <td>
                <a href="dashboard.php?supprimer=<?= $d['id'] ?>"
                   class="lien-suppr"
                   onclick="return confirm('Supprimer cette dépense ?')">
                  Supprimer
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

</main><!-- /page -->

</body>
</html>