<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

$total_clients   = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$total_products  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_contracts = $pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn();
$total_revenue   = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM contracts")->fetchColumn();

$recent_contracts = $pdo->query("
    SELECT c.*, cl.full_name, p.product_name 
    FROM contracts c 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    LEFT JOIN products p ON c.product_id = p.id 
    ORDER BY c.id DESC LIMIT 5
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
            <h3><?= $total_clients ?></h3>
            <p>Total Clients</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📦</div>
        <div class="stat-info">
            <h3><?= $total_products ?></h3>
            <p>Total Produits</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📄</div>
        <div class="stat-info">
            <h3><?= $total_contracts ?></h3>
            <p>Total Contrats</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">💰</div>
        <div class="stat-info">
            <h3><?= number_format($total_revenue, 2) ?> DA</h3>
            <p>Chiffre d'affaires</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>📄 Derniers Contrats</h2>
        <a href="contracts.php" class="btn btn-primary">Voir tout</a>
    </div>
    <table>
        <thead>
            <tr>
              
                <th>Client</th>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Prix Total</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_contracts)): ?>
            <tr><td colspan="7" style="text-align:center;color:#aaa;padding:30px;">Aucun contrat trouvé</td></tr>
            <?php else: ?>
            <?php foreach ($recent_contracts as $c): ?>
            <tr>
                
                <td><strong><?= htmlspecialchars($c['contract_number']) ?></strong></td>
                <td><?= htmlspecialchars($c['full_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['product_name'] ?? '-') ?></td>
                <td><?= $c['quantity'] ?></td>
                <td><strong><?= number_format($c['total_price'], 2) ?> DA</strong></td>
                <td><?= $c['contract_date'] ?></td>
                <td>
                    <a href="generate_pdf.php?id=<?= $c['id'] ?>" class="btn btn-danger btn-sm">📥 PDF</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
