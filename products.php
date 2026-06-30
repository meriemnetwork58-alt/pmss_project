<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$message = '';
$edit_product = null;

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = '<div class="alert alert-success">✅ Produit supprimé avec succès.</div>';
}

if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_product = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $category     = trim($_POST['category']);
    $quantity     = (int)$_POST['quantity'];
    $price        = (float)$_POST['price'];
    $id           = (int)($_POST['id'] ?? 0);

    if ($product_name) {
        if ($id) {
            $pdo->prepare("UPDATE products SET product_name=?, category=?, quantity=?, price=? WHERE id=?")
                ->execute([$product_name, $category, $quantity, $price, $id]);
            $message = '<div class="alert alert-success">✅ Produit modifié avec succès.</div>';
        } else {
            $pdo->prepare("INSERT INTO products (product_name, category, quantity, price, created_at) VALUES (?,?,?,?,NOW())")
                ->execute([$product_name, $category, $quantity, $price]);
            $message = '<div class="alert alert-success">✅ Produit ajouté avec succès.</div>';
        }
        $edit_product = null;
    }
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>

<?= $message ?>

<div class="card" style="margin-bottom:25px;">
    <div class="card-header">
        <h2><?= $edit_product ? '✏️ Modifier Produit' : '➕ Ajouter un Produit' ?></h2>
    </div>
    <div style="padding:25px;">
        <form method="POST">
            <?php if ($edit_product): ?>
                <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du Produit *</label>
                    <input type="text" name="product_name" required placeholder="Ex: Ciment CPA 42.5"
                           value="<?= htmlspecialchars($edit_product['product_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <input type="text" name="category" placeholder="Ex: Matériaux de construction"
                           value="<?= htmlspecialchars($edit_product['category'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Quantité en stock</label>
                    <input type="number" name="quantity" min="0" placeholder="0"
                           value="<?= $edit_product['quantity'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label>Prix Unitaire (DA)</label>
                    <input type="number" name="price" step="0.01" min="0" placeholder="0.00"
                           value="<?= $edit_product['price'] ?? 0 ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $edit_product ? '💾 Enregistrer modifications' : '➕ Ajouter produit' ?>
            </button>
            <?php if ($edit_product): ?>
                <a href="products.php" class="btn" style="background:#eee;color:#555;margin-left:10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>📦 Liste des Produits (<?= count($products) ?>)</h2>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Nom Produit</th><th>Catégorie</th><th>Stock</th><th>Prix Unitaire</th><th>Créé le</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
            <tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">Aucun produit trouvé</td></tr>
            <?php else: ?>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><strong><?= htmlspecialchars($p['product_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td>
                    <span style="background:<?= $p['quantity'] > 0 ? 'rgba(39,174,96,0.1)' : 'rgba(231,76,60,0.1)' ?>;
                          color:<?= $p['quantity'] > 0 ? '#27ae60' : '#e74c3c' ?>;
                          padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                        <?= $p['quantity'] ?>
                    </span>
                </td>
                <td><strong><?= number_format($p['price'], 2) ?> DA</strong></td>
                <td><?= $p['created_at'] ?></td>
                <td>
                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-info btn-sm">✏️ Modifier</a>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ce produit ?')">🗑️ Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
