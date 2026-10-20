<?php
// auth.php
header('Content-Type: application/json');
session_start();

// Identifiants en dur (à remplacer par une base de données ou .htpasswd en production)
// LOGIN            => MOT DE PASSE
$validUsers = [
    'sequoia'  => 'mdg69130',  // SUPER ADMIN
    'client1'  => 'client1',  // CLIENT 1
];

// Récupérer les identifiants envoyés
$login    = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

// Le login existe-t-il, et le mot de passe correspond-il ?
if (isset($validUsers[$login]) && $password === $validUsers[$login]) {
    // Générer un token aléatoire (32 caractères)
    $token = bin2hex(random_bytes(16));

    $_SESSION['auth_token'] = $token;
    $_SESSION['auth_user']  = $login;
    $_SESSION['auth_expiry'] = time() + 86400; // 24h

    echo json_encode([
        'success' => true,
        'token'   => $token,
        'expiry'  => $_SESSION['auth_expiry']
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Identifiants invalides'
]);