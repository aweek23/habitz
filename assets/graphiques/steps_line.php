<?php
// JPGraph line/area chart for step evolution.
// Emits a PNG; falls back to a placeholder if JPGraph isn't available.

$jpgraphBase = __DIR__ . '/../../api/jpgraph/src';
$jpgraphMain = $jpgraphBase . '/jpgraph.php';
$jpgraphLine = $jpgraphBase . '/jpgraph_line.php';

// Sample step data (text axis, linear scale).
$steps = [2600, 6200, 5900, 6800, 7200, 7000, 7800];

// Fallback placeholder when JPGraph is missing.
if (!file_exists($jpgraphMain) || !file_exists($jpgraphLine)) {
    $width = 640; $height = 280;
    $img = imagecreatetruecolor($width, $height);
    $bg  = imagecolorallocate($img, 15, 20, 28);
    $fg  = imagecolorallocate($img, 220, 230, 240);
    imagefill($img, 0, 0, $bg);

    $message = "JPGraph manquant\nAjoute la librairie dans php/jpgraph/ pour afficher le graphique.";
    $lines = explode("\n", $message);
    $y = 110;
    foreach ($lines as $line) {
        imagestring($img, 5, 60, $y, $line, $fg);
        $y += 24;
    }

    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

require_once $jpgraphMain;
require_once $jpgraphLine;

// -----------------------------
// Données
// -----------------------------
$data = $steps;              // ton tableau de pas
$max  = max($data);

// -----------------------------
// Graph
// -----------------------------
$width  = 415;
$height = 165;

$graph = new Graph(640, 280);

// 1) Désactiver l'anti-aliasing pour que SetWeight fonctionne
$graph->img->SetAntiAliasing(false);

$graph->SetScale('textlin', 0, max($steps) * 1.1);

// fond sombre partout + pas de bordure visible
$graph->SetMargin(0, 0, 0, 0);
$graph->SetMarginColor('#0f141c');
$graph->SetColor('#0f141c');
$graph->SetFrame(true, '#0f141c', 0);

// -----------------------------
// Axes (comme tu avais)
// -----------------------------
$graph->xaxis->HideLabels();
$graph->xaxis->HideTicks(true, true);
$graph->xaxis->SetColor('#5ad1ff');
$graph->xaxis->SetWeight(1);

$graph->yaxis->Hide();
$graph->ygrid->Show(false);

// -----------------------------
// Ligne + remplissage
// -----------------------------
$linePlot = new LinePlot($data);

// IMPORTANT : on ajoute d’abord le plot…
$graph->Add($linePlot);

// …puis on règle le style
$linePlot->SetColor('#5ad1ff');
$linePlot->SetWeight(7);              // ⇐ augmente ici (3, 5, 7, etc.)

$linePlot->SetFillFromYMin();
$linePlot->SetFillGradient('#13242a', '#4fd8ff', GRAD_VER);
$linePlot->SetStepStyle(false);

$graph->SetBox(false);
$graph->Stroke();
