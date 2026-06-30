<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$message = '';
$edit_client = null;

// DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM clients WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = '<div class="alert alert-success">✅ Client supprimé avec succès.</div>';
}

// EDIT LOAD
if (isset($_GET['edit'])) {
    $edit_client = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $edit_client->execute([(int)$_GET['edit']]);
    $edit_client = $edit_client->fetch();
}

// INSERT or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);
    $address   = trim($_POST['address']);
    $id        = (int)($_POST['id'] ?? 0);

    if ($full_name) {
        if ($id) {
            $pdo->prepare("UPDATE clients SET full_name=?, phone=?, email=?, address=? WHERE id=?")
                ->execute([$full_name, $phone, $email, $address, $id]);
            $message = '<div class="alert alert-success">✅ Client modifié avec succès.</div>';
        } else {
            $pdo->prepare("INSERT INTO clients (full_name, phone, email, address, created_at) VALUES (?,?,?,?,NOW())")
                ->execute([$full_name, $phone, $email, $address]);
            $message = '<div class="alert alert-success">✅ Client ajouté avec succès.</div>';
        }
        $edit_client = null;
    }
}

$clients = $pdo->query("SELECT * FROM clients ORDER BY id DESC")->fetchAll();
?>

<?= $message ?>

<div class="card" style="margin-bottom:25px;">
    <div class="card-header">
        <h2><?= $edit_client ? '✏️ Modifier Client' : '➕ Ajouter un Client' ?></h2>
    </div>
    <div style="padding:25px;">
        <form method="POST">
            <?php if ($edit_client): ?>
                <input type="hidden" name="id" value="<?= $edit_client['id'] ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom Complet *</label>
                    <input type="text" name="full_name" required placeholder="Ex: Ahmed Benali"
                           value="<?= htmlspecialchars($edit_client['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="phone" placeholder="0555 xx xx xx"
                           value="<?= htmlspecialchars($edit_client['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@exemple.com"
                           value="<?= htmlspecialchars($edit_client['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Adresse</label>
                    <input type="text" name="address" placeholder="Adresse complète"
                           value="<?= htmlspecialchars($edit_client['address'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $edit_client ? '💾 Enregistrer modifications' : '➕ Ajouter client' ?>
            </button>
            <?php if ($edit_client): ?>
                <a href="clients.php" class="btn" style="background:#eee;color:#555;margin-left:10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>👥 Liste des Clients (<?= count($clients) ?>)</h2>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Nom Complet</th><th>Téléphone</th><th>Email</th><th>Adresse</th><th>Créé le</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
            <tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">Aucun client trouvé</td></tr>
            <?php else: ?>
            <?php foreach ($clients as $c): ?>
            <tr>
                <td>#<?= $c['id'] ?></td>
                <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($c['phone']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['address']) ?></td>
                <td><?= $c['created_at'] ?></td>
                <td>
                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-info btn-sm">✏️ Modifier</a>
                    <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ce client ?')">🗑️ Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
