<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/fpdf.php';
require_once __DIR__ . '/../Controllers/PostController.php';
require_once __DIR__ . '/../Controllers/CommentaireController.php';

$postController        = new PostController();
$commentaireController = new CommentaireController();
$posts                 = $postController->getAll();

// ── Custom PDF class ──────────────────────────────────────────────────────
class PostsPDF extends FPDF
{
    function Header()
    {
        // Green header bar — tall enough for logo
        $this->SetFillColor(47, 111, 87);
        $this->Rect(0, 0, 297, 30, 'F');

        $logoPath = __DIR__ . '/../FrontOffice/images/logo.png';
        $logo = file_exists($logoPath) ? $logoPath : null;

        if ($logo) {
            // Draw a white rounded rect behind logo to mask any background
            $this->SetFillColor(255, 255, 255);
            $this->RoundedRect(4, 3, 28, 24, 3, 'F');
            $this->Image($logo, 5, 4, 26, 22);
        }

        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(36, 6);
        $this->Cell(0, 9, 'HappyBite - Liste des Posts', 0, 1, 'C');

        // Date
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(200, 230, 215);
        $this->SetX(36);
        $this->Cell(0, 6, 'Genere le ' . date('d/m/Y a H:i'), 0, 1, 'C');

        $this->Ln(8);
        $this->SetTextColor(0, 0, 0);
    }

    // Helper: rounded rectangle
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style === 'F') $op = 'f';
        elseif ($style === 'FD' || $style === 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $xc = $x+$w-$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
        $this->_Arc($xc+$r*$MyArc, $yc-$r, $xc+$r, $yc-$r*$MyArc, $xc+$r, $yc);
        $xc = $x+$w-$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc+$r, $yc+$r*$MyArc, $xc+$r*$MyArc, $yc+$r, $xc, $yc+$r);
        $xc = $x+$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
        $this->_Arc($xc-$r*$MyArc, $yc+$r, $xc-$r, $yc+$r*$MyArc, $xc-$r, $yc);
        $xc = $x+$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc-$r, $yc-$r*$MyArc, $xc-$r*$MyArc, $yc-$r, $xc, $yc-$r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }

    function TableHeader()
    {
        $this->SetFillColor(234, 244, 239);
        $this->SetTextColor(47, 111, 87);
        $this->SetFont('Arial', 'B', 9);
        $this->SetDrawColor(200, 220, 210);
        $this->SetLineWidth(0.3);

        $this->Cell(10,  8, '#',           1, 0, 'C', true);
        $this->Cell(90,  8, 'Contenu',     1, 0, 'L', true);
        $this->Cell(22,  8, 'Likes',       1, 0, 'C', true);
        $this->Cell(28,  8, 'Commentaires',1, 0, 'C', true);
        $this->Cell(40,  8, 'Date',        1, 1, 'C', true);
    }

    function TableRow(int $id, string $contenu, int $likes, int $comments, string $date, bool $shade)
    {
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(30, 30, 30);
        $this->SetDrawColor(220, 230, 225);
        $this->SetLineWidth(0.2);

        if ($shade) {
            $this->SetFillColor(248, 252, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }

        // Truncate long content
        $short = mb_strlen($contenu) > 80 ? mb_substr($contenu, 0, 77) . '...' : $contenu;
        // Strip non-latin chars that FPDF can't render
        $short = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $short);
        $date  = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $date);

        $this->Cell(10,  7, (string)$id,       1, 0, 'C', true);
        $this->Cell(90,  7, $short,             1, 0, 'L', true);
        $this->Cell(22,  7, (string)$likes,     1, 0, 'C', true);
        $this->Cell(28,  7, (string)$comments,  1, 0, 'C', true);
        $this->Cell(40,  7, $date,              1, 1, 'C', true);
    }
}

// ── Build PDF ─────────────────────────────────────────────────────────────
$pdf = new PostsPDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 18);

// Summary stats
$totalLikes    = array_sum(array_column($posts, 'nombreLikes'));
$totalComments = 0;
foreach ($posts as $p) {
    $totalComments += count($commentaireController->getByPostId($p['id']));
}

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(60, 6, 'Total posts : ' . count($posts), 0, 0);
$pdf->Cell(60, 6, 'Total likes : ' . $totalLikes,   0, 0);
$pdf->Cell(60, 6, 'Total commentaires : ' . $totalComments, 0, 1);
$pdf->Ln(3);

// Table
$pdf->TableHeader();

$shade = false;
foreach ($posts as $post) {
    $commentsCount = count($commentaireController->getByPostId($post['id']));
    $date          = date('d/m/Y H:i', strtotime($post['datePublication']));
    $pdf->TableRow(
        $post['id'],
        $post['contenu'],
        (int)$post['nombreLikes'],
        $commentsCount,
        $date,
        $shade
    );
    $shade = !$shade;
}

// Output
$pdf->Output('D', 'Posts_HappyBite_' . date('Ymd_Hi') . '.pdf');
exit;
