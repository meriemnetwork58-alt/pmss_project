<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once 'includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die('ID contrat manquant.'); }

$stmt = $pdo->prepare("
    SELECT c.*, cl.full_name, cl.phone, cl.email, cl.address,
           p.product_name, p.category
    FROM contracts c
    LEFT JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$contract = $stmt->fetch();

if (!$contract) { die('Contrat introuvable.'); }

// Generate PDF as HTML (printable)
$tva_rate = 0.19; // 19% TVA Algeria
$subtotal  = $contract['total_price'];
$tva       = $subtotal * $tva_rate;
$grand_total = $subtotal + $tva;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrat <?= htmlspecialchars($contract['contact_number']) ?></title>
<style>
    @media print {
        body { margin: 0; }
        .no-print { display: none !important; }
        .page { box-shadow: none !important; margin: 0 !important; }
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #e8e8e8; padding: 20px; }
    .no-print {
        text-align: center; margin-bottom: 20px;
        display: flex; gap: 10px; justify-content: center;
    }
    .btn-print, .btn-back {
        padding: 10px 25px; border: none; border-radius: 8px;
        font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none;
        display: inline-block;
    }
    .btn-print { background: #e74c3c; color: #fff; }
    .btn-back  { background: #667eea; color: #fff; }

    .page {
        background: #fff;
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        padding: 20mm 18mm;
        box-shadow: 0 5px 30px rgba(0,0,0,0.15);
    }

    /* HEADER */
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #1a1a2e; }
    .company-name h1 { font-size: 26px; color: #1a1a2e; font-weight: 900; letter-spacing: 1px; }
    .company-name p { color: #666; font-size: 12px; margin-top: 4px; }
    .doc-title { text-align: right; }
    .doc-title h2 { font-size: 22px; color: #667eea; font-weight: 800; }
    .doc-title p { color: #888; font-size: 12px; }
    .contract-number {
        display: inline-block;
        background: #1a1a2e; color: #fff;
        padding: 5px 14px; border-radius: 20px;
        font-size: 13px; font-weight: 700; margin-top: 6px;
    }

    /* INFO BOXES */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-box { border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px; }
    .info-box h3 { font-size: 11px; font-weight: 800; color: #667eea; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    .info-box p { font-size: 13px; color: #333; line-height: 1.7; }
    .info-box strong { color: #1a1a2e; }

    /* TABLE */
    .section-title { font-size: 13px; font-weight: 800; color: #1a1a2e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #f0f0f0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    thead tr { background: #1a1a2e; }
    thead th { padding: 12px 14px; color: #fff; font-size: 12px; font-weight: 700; text-align: left; }
    tbody td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:nth-child(even) td { background: #f8f9fc; }
    .text-right { text-align: right; }

    /* TOTALS */
    .totals-section { display: flex; justify-content: flex-end; margin-bottom: 30px; }
    .totals-table { width: 300px; }
    .totals-table tr td { padding: 8px 14px; font-size: 13px; }
    .totals-table tr td:first-child { color: #666; }
    .totals-table tr td:last-child { text-align: right; font-weight: 700; color: #1a1a2e; }
    .totals-table .grand-total td { background: #1a1a2e; color: #fff !important; border-radius: 6px; font-size: 15px; }
    .totals-table .grand-total td:last-child { color: #fff !important; }

    /* NOTES */
    .notes-box { background: #f8f9fc; border-radius: 10px; padding: 16px; margin-bottom: 30px; }
    .notes-box h3 { font-size: 12px; font-weight: 800; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .notes-box p { font-size: 13px; color: #555; line-height: 1.6; }

    /* SIGNATURES */
    .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
    .sig-box { text-align: center; }
    .sig-box p { font-size: 12px; color: #888; margin-bottom: 5px; }
    .sig-box strong { font-size: 13px; color: #1a1a2e; }
    .sig-line { border-bottom: 1px dashed #ccc; height: 60px; margin: 10px 0; }

    /* FOOTER */
    .page-footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; color: #aaa; font-size: 11px; }
</style>
</head>
<body>

<div class="no-print">
    <a href="contracts.php" class="btn-back">← Retour aux contrats</a>
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Télécharger PDF</button>
</div>

<div class="page">
    <!-- HEADER -->
    <div class="header">
        <div class="company-name">
            <h1>📊 Digital Plus</h1>
            <p>Système de Gestion de Projets</p>
            <p>Algérie</p>
        </div>
        <div class="doc-title">
            <h2>BON DE CONTRAT</h2>
            <p>Date: <?= date('d/m/Y', strtotime($contract['contract_date'])) ?></p>
            <div class="contract-number"><?= htmlspecialchars($contract['contract_number']) ?></div>
        </div>
    </div>

    <!-- CLIENT & CONTRACT INFO -->
    <div class="info-grid">
        <div class="info-box">
            <h3>👤 Informations Client</h3>
            <p>
                <strong><?= htmlspecialchars($contract['full_name']) ?></strong><br>
                <?php if ($contract['phone']): ?>📞 <?= htmlspecialchars($contract['phone']) ?><br><?php endif; ?>
                <?php if ($contract['email']): ?>✉️ <?= htmlspecialchars($contract['email']) ?><br><?php endif; ?>
                <?php if ($contract['address']): ?>📍 <?= htmlspecialchars($contract['address']) ?><?php endif; ?>
            </p>
        </div>
        <div class="info-box">
            <h3>📄 Détails du Contrat</h3>
            <p>
                <strong>N° Contract:</strong> <?= htmlspecialchars($contract['contract_number']) ?><br>
                <strong>Date:</strong> <?= date('d/m/Y', strtotime($contract['contract_date'])) ?><br>
                <strong>Émis par:</strong> <?= htmlspecialchars($_SESSION['username']) ?><br>
                <strong>Statut:</strong> <span style="color:#27ae60;font-weight:700;">✅ Validé</span>
            </p>
        </div>
    </div>

    <!-- PRODUCTS TABLE -->
    <div class="section-title">Détail de la Commande</div>
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Désignation</th>
                <th>Catégorie</th>
                <th class="text-right">Qté</th>
                <th class="text-right">Prix Unitaire</th>
                <th class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>01</td>
                <td><strong><?= htmlspecialchars($contract['product_name']) ?></strong></td>
                <td><?= htmlspecialchars($contract['category']) ?></td>
                <td class="text-right"><?= $contract['quantity'] ?></td>
                <td class="text-right"><?= number_format($contract['unit_price'], 2) ?> DA</td>
                <td class="text-right"><strong><?= number_format($subtotal, 2) ?> DA</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- TOTALS -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Sous-total HT:</td>
                <td><?= number_format($subtotal, 2) ?> DA</td>
            </tr>
            <tr>
                <td>TVA (19%):</td>
                <td><?= number_format($tva, 2) ?> DA</td>
            </tr>
            <tr class="grand-total">
                <td>💰 TOTAL TTC:</td>
                <td><?= number_format($grand_total, 2) ?> DA</td>
            </tr>
        </table>
    </div>

    <?php if ($contract['notes']): ?>
    <!-- NOTES -->
    <div class="notes-box">
        <h3>📝 Notes & Observations</h3>
        <p><?= nl2br(htmlspecialchars($contract['notes'])) ?></p>
    </div>
    <?php endif; ?>