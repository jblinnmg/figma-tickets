<?php
// check-token.php
header('Content-Type: application/json');
session_start();

$token = $_POST['token'] ?? '';
$expiry = $_POST['expiry'] ?? 0;

if (isset($_SESSION['auth_token']) && $_SESSION['auth_token'] === $token && $_SESSION['auth_expiry'] == $expiry) {
    // Vérifier que le token n'a pas expiré
    if (time() <= $expiry) {
        echo json_encode(['valid' => true]);
        exit;
    }
}

// Token invalide ou expiré
echo json_encode(['valid' => false]);
?>