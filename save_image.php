<?php
// save_image.php
header('Content-Type: application/json');

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
    exit;
}

// Vérifier que c'est bien une image (type MIME réel, pas l'extension)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['image']['tmp_name']);
$autorises = ['image/png', 'image/jpeg', 'image/webp'];
if (!in_array($mime, $autorises, true)) {
    echo json_encode(['success' => false, 'message' => 'Format non autorisé']);
    exit;
}

// Nom d'origine, sécurisé et débarrassé de son extension
$original = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
$original = preg_replace('/[^A-Za-z0-9_\-]/', '_', $original);
if ($original === '') $original = 'image';

$folder = isset($_POST['folder']) ? preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['folder']) : '';
if ($folder !== '') {
    $dossier = __DIR__ . '/screenshot/' . $folder;
    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);   // crée le sous-dossier si besoin
    }
} else {
    $dossier = __DIR__ . '/screenshot';
}

// 1. Charger l'image envoyée (PNG, JPEG ou WebP, peu importe)
$src = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name']));
if ($src === false) {
    echo json_encode(['success' => false, 'message' => 'Image illisible']);
    exit;
}

$w = imagesx($src);
$h = imagesy($src);

// 2. Redessiner sur fond blanc (le JPG ne gère pas la transparence)
$jpg = imagecreatetruecolor($w, $h);
$blanc = imagecolorallocate($jpg, 255, 255, 255);
imagefill($jpg, 0, 0, $blanc);
imagecopy($jpg, $src, 0, 0, 0, 0, $w, $h);

// 3. Sauvegarder en JPG
$nouveauNom = $original . '_2.jpg';
$destination = $dossier . $nouveauNom;

if (imagejpeg($jpg, $destination, 85)) {
    // Nettoyer un éventuel ancien "_2.png" pour ne pas avoir deux versions
    $ancienPng = $dossier . $original . '_2.png';
    if (file_exists($ancienPng)) unlink($ancienPng);

    echo json_encode(['success' => true, 'filename' => $nouveauNom]);
} else {
    echo json_encode(['success' => false, 'message' => 'Échec de l\'écriture']);
}

imagedestroy($src);
imagedestroy($jpg);