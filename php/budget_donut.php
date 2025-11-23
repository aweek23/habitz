<?php
// Simple JPGraph donut chart for budget categories.
// This endpoint returns a PNG stream; it falls back to a placeholder
// when the JPGraph library isn't available in the expected location.

// Expected JPGraph location relative to this script.
$jpgraphBase = __DIR__ . '/jpgraph/src';
$jpgraphMain = $jpgraphBase . '/jpgraph.php';
$jpgraphPie  = $jpgraphBase . '/jpgraph_pie.php';

// Data definition
$labels = ['Nourriture', 'Loisirs', 'Sport', 'Épargne', 'Autre'];
$values = [24, 22, 14, 22, 18];
$colors = ['#4ade80', '#fbbf24', '#60a5fa', '#a78bfa', '#94a3b8'];

// If JPGraph is missing, render a fallback placeholder.
if (!file_exists($jpgraphMain) || !file_exists($jpgraphPie)) {
    $width = 720; $height = 420;
    $img = imagecreatetruecolor($width, $height);
    $bg  = imagecolorallocate($img, 15, 20, 28);
    $fg  = imagecolorallocate($img, 220, 230, 240);
    imagefill($img, 0, 0, $bg);

    $message = "JPGraph manquant\\nAjoute la librairie dans php/jpgraph/ pour afficher le graphique.";
    $lines = explode("\n", $message);
    $y = 160;
    foreach ($lines as $line) {
        imagestring($img, 5, 80, $y, $line, $fg);
        $y += 24;
    }

    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

require_once $jpgraphMain;
require_once $jpgraphPie;

$graph = new PieGraph(720, 420);
$graph->SetFrame(false);
$graph->img->SetAntiAliasing(true);
$graph->SetMargin(24, 220, 24, 24);
$graph->SetColor('#0f141c');
$graph->legend->Pos(0.78, 0.45, 'center', 'center');
$graph->legend->SetFillColor('white@0');
$graph->legend->SetFont(FF_DEFAULT, FS_NORMAL, 10);
$graph->legend->SetMarkAbsSize(10);
$graph->legend->SetColumnMargin(12);

$plot = new PiePlotC($values);
$plot->SetCenter(0.4, 0.5);
$plot->SetSize(0.32);
$plot->SetSliceColors($colors);
$plot->SetLegends($labels);
$plot->value->SetFormat('%d%%');
$plot->value->SetColor('white');
$plot->SetLabelPos(0.55);
$plot->title->Set('Budget');
$plot->title->SetFont(FF_DEFAULT, FS_BOLD, 11);
$plot->title->SetColor('#e6edf5');
$plot->SetGuideLines(true, false);
$plot->SetGuideLinesAdjust(1.2);
$plot->SetMidColor('#0f141c');

$graph->Add($plot);
$graph->Stroke();
