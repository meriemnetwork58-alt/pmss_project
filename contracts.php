<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$message = '';
$edit_contract = null;

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contracts WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = '<div class="alert alert-success">✅ Contrat supprimé avec succès.</div>';
}

if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_contract = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id      = (int)$_POST['client_id'];
    $product_id     = (int)$_POST['product_id'];
    $quantity       = (int)$_POST['quantity'];
    $unit_price     = (float)$_POST['unit_price'];
    $total_price    = $quantity * $unit_price;
    $contract_date  = $_POST['contract_date'];
    $notes          = trim($_POST['notes']);
    $id             = (int)($_POST['id'] ?? 0);

    if ( $client_id && $product_id) {
        if ($id) {
            $pdo->prepare("UPDATE contracts SET client_id=?, product_id=?, quantity=?, unit_price=?, total_price=?, contract_date=?, notes=? WHERE id=?")
           ->execute([ $client_id, $product_id, $quantity, $unit_price, $total_price, $contract_date, $notes,$id]);
            $message = '<div class="alert alert-success">✅ Contrat modifié avec succès.</div>';
        } else {
          $pdo->prepare("INSERT INTO contracts (client_id, product_id, quantity, unit_price, total_price, contract_date, notes,id) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$client_id, $product_id, $quantity, $unit_price, $total_price, $contract_date, $notes,$id ]);
            $message = '<div class="alert alert-success">✅ Contrat créé avec succès.</div>';
        }
        $edit_contract = null;
    }
}

$clients  = $pdo->query("SELECT id, full_name FROM clients ORDER BY full_name")->fetchAll();
$products = $pdo->query("SELECT id, product_name, price FROM products ORDER BY product_name")->fetchAll();
$contracts = $pdo->query("
    SELECT c.*, cl.full_name, p.product_name
    FROM contracts c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN products p ON c.product_id = p.id
    ORDER BY c.id DESC
")->fetchAll();
?>

<?= $message ?>

<div class="card" style="margin-bottom:25px;">
    <div class="card-header">
        <h2><?= $edit_contract ? '✏️ Modifier Contrat' : '➕ Nouveau Contrat' ?></h2>
    </div>
    <div style="padding:25px;">
        <form method="POST" id="contractForm">
            <?php if ($edit_contract): ?>
                <input type="hidden" name="id" value="<?= $edit_contract['id'] ?>">
            <?php endif; ?>
          
                <div class="form-group">
                    <label>Client *</label>
                    <select name="client_id" required>
                        <option value="">-- Choisir un client --</option>
                        <?php foreach ($clients as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= ($edit_contract['client_id'] ?? '') == $cl['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Produit *</label>
                    <select name="product_id" id="product_select" required onchange="fillPrice(this)">
                        <option value="">-- Choisir un produit --</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"
                            <?= ($edit_contract['product_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['product_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantité</label>
                    <input type="number" name="quantity" id="quantity" min="1" value="<?= $edit_contract['quantity'] ?? 1 ?>"
                           oninput="calcTotal()">
                </div>
                <div class="form-group">
                    <label>Prix Unitaire (DA)</label>
                    <input type="number" name="unit_price" id="unit_price" step="0.01" min="0"
                           value="<?= $edit_contract['unit_price'] ?? '' ?>" oninput="calcTotal()" placeholder="Auto-rempli">
                </div>
                <div class="form-group">
                    <label>Prix Total (DA)</label>
                    <input type="text" id="total_display" readonly
                           value="<?= isset($edit_contract['total_price']) ? number_format($edit_contract['total_price'],2) : '0.00' ?>"
                           style="background:#f8f9fc;font-weight:700;color:#27ae60;">
                </div>
                <div class="form-group">
                    <label>Date du Contrat</label>
                    <input type="date" name="contract_date" value="<?= $edit_contract['contract_date'] ?? date('Y-m-d') ?>">
                </div>
                
            </div>
            <button type="submit" class="btn btn-primary">
                <?= $edit_contract ? '💾 Enregistrer modifications' : '📄 Créer le contrat' ?>
            </button>
            <?php if ($edit_contract): ?>
                <a href="contracts.php" class="btn" style="background:#eee;color:#555;margin-left:10px;">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>📄 Liste des Contrats (<?= count($contracts) ?>)</h2>
    </div>
    <table>
        <header>
            <tr><th></th><th>Client</th><th>Produit</th><th>Qté</th><th>Prix Unit.</th><th>Prix Total</th><th>Date</th><th>Actions</th></tr>
        </header>
        <tbody>
            <?php if (empty($contracts)): ?>
            <tr><td colspan="8" style="text-align:center;color:#aaa;padding:30px;">Aucun contrat trouvé</td></tr>
            <?php else: ?>
            <?php foreach ($contracts as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['contract_number']) ?></strong></td>
                <td><?= htmlspecialchars($c['full_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['product_name'] ?? '-') ?></td>
                <td><?= $c['quantity'] ?></td>
                <td><?= number_format($c['unit_price'], 2) ?> DA</td>
                <td><strong style="color:#27ae60;"><?= number_format($c['total_price'], 2) ?> DA</strong></td>
                <td><?= $c['contract_date'] ?></td>
                <td style="white-space:nowrap;">
                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-info btn-sm">✏️</a>
                    <a href="generate_pdf.php?id=<?= $c['id'] ?>" class="btn btn-danger btn-sm" target="_blank">📥 PDF</a>
                    <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer ce contrat ?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Auto-fill price from product selection
function fillPrice(select) {
    const opt = select.options[select.selectedIndex];
    const price = opt.getAttribute('data-price') || 0;
    document.getElementById('unit_price').value = price;
    calcTotal();
}

function calcTotal() {
    const qty   = parseFloat(document.getElementById('quantity').value) || 0;
    const price = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = qty * price;
    document.getElementById('total_display').value = total.toFixed(2);
}

// Init on load for edit mode
window.onload = function() { calcTotal(); };
</script>

<?php require_once 'includes/footer.php'; ?>
