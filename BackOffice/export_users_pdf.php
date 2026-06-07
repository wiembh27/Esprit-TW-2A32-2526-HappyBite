<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/fpdf.php';
require_once __DIR__ . '/../Controllers/SuiviJournalierController.php';

$controller = new SuiviJournalierController();
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$users = $search !== '' ? $controller->searchUsersBackoffice($search) : $controller->listUsersBackoffice();

function formatList($data): string
{
    if (empty($data)) return '-';
    if (is_array($data)) return implode(', ', $data);
    $decoded = json_decode((string) $data, true);
    if (is_array($decoded)) return implode(', ', $decoded);
    return (string) $data;
}

function pdfText(string $text): string
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
}

$logo = __DIR__ . '/../FrontOffice/images/logo.png';

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 14);
$pdf->AddPage();

if (is_file($logo)) {
    $pdf->Image($logo, 10, 8, 26, 0);
}

$pdf->SetFont('Arial', 'B', 15);
$pdf->SetTextColor(44, 126, 52);
$pdf->Cell(0, 12, pdfText('Profils santé des utilisateurs'), 0, 1, 'C');
$pdf->SetTextColor(90, 90, 90);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 4, pdfText('Généré le ' . date('d/m/Y H:i')), 0, 1, 'C');
$pdf->Ln(3);

$headers = ['ID', 'Nom', 'Email', 'Taille', 'Poids', 'Objectif', 'Allergenes', 'Carences', 'Maladies'];
$widths  = [12, 35, 62, 18, 18, 36, 40, 36, 36];

$pdf->SetFillColor(44, 126, 52);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 8);
foreach ($headers as $i => $head) {
    $pdf->Cell($widths[$i], 8, pdfText($head), 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(30, 30, 30);
$shade = false;
foreach ($users as $user) {
    $pdf->SetFillColor($shade ? 245 : 255, $shade ? 248 : 255, $shade ? 246 : 255);
    $row = [
        (string) ($user['id_profil_sante'] ?? '-'),
        trim((string) (($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
        (string) ($user['email'] ?? '-'),
        (string) ($user['taille'] ?? '-'),
        (string) ($user['poids_actuel'] ?? '-'),
        (string) ($user['objectif'] ?? '-'),
        formatList($user['allergenes'] ?? ''),
        formatList($user['carences'] ?? ''),
        formatList($user['maladies'] ?? ''),
    ];
    foreach ($row as $i => $cell) {
        $txt = mb_strlen($cell) > 30 ? mb_substr($cell, 0, 27) . '...' : $cell;
        $pdf->Cell($widths[$i], 7, pdfText($txt), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $shade = !$shade;
}

$pdf->Output('D', 'utilisateurs_sante_' . date('Ymd_Hi') . '.pdf');
exit;