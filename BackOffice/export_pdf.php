<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/fpdf.php';
require_once __DIR__ . '/../Controllers/SuiviJournalierController.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) die('ID manquant');

$controller = new SuiviJournalierController();
$user = $controller->getUser($id);
$suivis = $controller->getSuiviUser($id);

function pdfText(string $text): string
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
}

$logo = __DIR__ . '/../FrontOffice/images/logo.png';

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 14);
$pdf->AddPage();

if (is_file($logo)) {
    $pdf->Image($logo, 10, 8, 24, 0);
}

$fullName = trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')));

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(44, 126, 52);
$pdf->Cell(0, 11, pdfText('Rapport de suivi santé'), 0, 1, 'C');
$pdf->SetTextColor(80, 80, 80);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, pdfText('Généré le ' . date('d/m/Y H:i')), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, pdfText('Utilisateur : ' . $fullName), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, pdfText('Email : ' . (string) ($user['email'] ?? '-')), 0, 1, 'L');
$pdf->Ln(2);

$headers = ['Date', 'Poids', 'Calories', 'Sommeil', 'Pas', 'Sport', 'Hydratation'];
$widths = [28, 20, 26, 24, 22, 24, 30];

$pdf->SetFillColor(44, 126, 52);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
foreach ($headers as $i => $h) {
    $pdf->Cell($widths[$i], 8, pdfText($h), 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetTextColor(30, 30, 30);
$pdf->SetFont('Arial', '', 8);
$shade = false;
foreach ($suivis as $s) {
    $pdf->SetFillColor($shade ? 245 : 255, $shade ? 248 : 255, $shade ? 246 : 255);
    $row = [
        (string) ($s['date_jour'] ?? '-'),
        (string) ($s['poids'] ?? '-'),
        (string) ($s['calories'] ?? '-'),
        (string) ($s['sommeil_heures'] ?? '-'),
        (string) ($s['nbr_pas'] ?? '-'),
        (string) ($s['nbr_activites_sport'] ?? '-'),
        (string) ($s['hydratation_litre'] ?? '-'),
    ];
    foreach ($row as $i => $cell) {
        $pdf->Cell($widths[$i], 7, pdfText($cell), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $shade = !$shade;
}

$pdf->Output('D', 'suivi_sante_' . $id . '_' . date('Ymd_Hi') . '.pdf');
exit;