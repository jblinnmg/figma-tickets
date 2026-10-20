<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json"); // Pour s'assurer que la réponse est en JSON

$db_config['host'] = "localhost";
$db_config['db'] = "seqcorpoprod";
$db_config['user'] = "seqcorpoprod";
$db_config['pass'] = "qA0iX5pW2n";

$screenshotDir = __DIR__ . '/screenshot/';
if (!file_exists($screenshotDir)) {
    mkdir($screenshotDir, 0755, true);
}

try {
    $conn = new PDO("mysql:host=".$db_config['host'].";dbname=".$db_config['db'].";charset=utf8mb4", $db_config['user'], $db_config['pass']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $result = array();

    if(isset($_REQUEST["func"]) && $_REQUEST["func"] == "allcomment") {
        // Vérifier que id_module et id_page sont bien fournis
        if(isset($_REQUEST["id_module"]) && isset($_REQUEST["id_page"])) {
            $id_module = $_REQUEST["id_module"];
            $id_page = $_REQUEST["id_page"];

            // Requête préparée pour éviter les injections SQL
            $stmt = $conn->prepare("
                SELECT *
                FROM sequoia_ticket
                WHERE id_module = :id_module
                AND id_page = :id_page
                AND status = 1
                ORDER BY time ASC
            ");

            // Liaison des paramètres
            $stmt->bindParam(':id_module', $id_module);
            $stmt->bindParam(':id_page', $id_page);

            // Exécution de la requête
            $stmt->execute();

            // Récupération des résultats
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if($comments) {
                $result = [
                    "success" => true,
                    "comments" => $comments
                ];
            } else {
                $result = [
                    "success" => true,
                    "comments" => [] // Retourne un tableau vide si aucun résultat
                ];
            }
        } else {
            $result = [
                "success" => false,
                "message" => "Les paramètres id_module et id_page sont requis."
            ];
        }
    } 
    else if(isset($_REQUEST["func"]) && $_REQUEST["func"] == "getMyProjects") {
        // Vérifier que id_module et id_page sont bien fournis
        if(isset($_REQUEST["client"])) {
            
            $id_client = $_REQUEST["client"];
            // Requête préparée pour éviter les injections SQL
            if($id_client == 'sequoia') {
              $stmt = $conn->prepare("
                    SELECT *
                    FROM sequoia_projects
                    ORDER BY nom ASC
                ");  
            } else {
                $stmt = $conn->prepare("
                    SELECT *
                    FROM sequoia_projects
                    WHERE client = :client
                    ORDER BY nom ASC
                ");

                // Liaison des paramètres
                $stmt->bindParam(':client', $id_client);
            }

            // Exécution de la requête
            $stmt->execute();

            // Récupération des résultats
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if($projects) {
                $result = [
                    "success" => true,
                    "projects" => $projects
                ];
            } else {
                $result = [
                    "success" => true,
                    "projects" => [] // Retourne un tableau vide si aucun résultat
                ];
            }
        } else {
            $result = [
                "success" => false,
                "message" => "Le paramètre 'client' est requis."
            ];
        }
    }
    // --- 2. Ajouter un commentaire ---
    else if($_REQUEST["func"] == "addcomment") {
        // Vérifier que tous les champs requis sont présents
        $requiredFields = ['id_module', 'id_page', 'nom', 'description'];
        $missingFields = array_diff($requiredFields, array_keys($_REQUEST));

        if (!empty($missingFields)) {
            $result = [
                "success" => false,
                "message" => "Champs manquants: " . implode(', ', $missingFields)
            ];
        } else {
            // Récupérer les données
            $folder_proj = $_REQUEST["folder"];
            $id_module = $_REQUEST["id_module"];
            $id_page = $_REQUEST["id_page"];
            $nom = $_REQUEST["nom"];
            $description = $_REQUEST["description"];
            // $reply = isset($_REQUEST["reply"]) ? $_REQUEST["reply"] : 0; // 0 par défaut si non spécifié

            $reply = isset($_REQUEST["reply"]) ? (int)$_REQUEST["reply"] : 0;
            // Gérer le screenshot si présent
                        // Gérer le screenshot si présent
            $screenshotPath = null;
            $fileName = '';
            if ($reply === 0 && isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {

                // 1. Lire l'image source depuis le fichier temporaire
                //    (accepte PNG, JPEG, WebP — peu importe ce qu'envoie le navigateur)
                $src = imagecreatefromstring(file_get_contents($_FILES['screenshot']['tmp_name']));

                if ($src === false) {
                    $result = ["success" => false, "message" => "Fichier image illisible."];
                    echo json_encode($result);
                    exit;
                }

                $srcW = imagesx($src);
                $srcH = imagesy($src);

                // 2. Convertir en JPG : fond blanc obligatoire car le JPG ne gère
                //    pas la transparence (sinon les zones transparentes sortent en noir)
                $jpg = imagecreatetruecolor($srcW, $srcH);
                $blanc = imagecolorallocate($jpg, 255, 255, 255);
                imagefill($jpg, 0, 0, $blanc);
                imagecopy($jpg, $src, 0, 0, 0, 0, $srcW, $srcH);

                // 3. Sauvegarder uniquement la version JPG (qualité 85 : bon compromis)
                $fileName = 'screenshot_' . time() . '_' . uniqid() . '.jpg';
                $filePath = $screenshotDir . $folder_proj. '/' . $fileName;

                if (!imagejpeg($jpg, $filePath, 85)) {
                    $result = ["success" => false, "message" => "Échec de l'écriture du JPG."];
                    echo json_encode($result);
                    exit;
                }

                imagedestroy($src);
                imagedestroy($jpg);

                // 4. Générer la miniature (200px de large) à partir du JPG
                $thumbWidth = 200;
                $thumbHeight = (int) round($srcH * $thumbWidth / $srcW);

                $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
                imagecopyresampled($thumb, imagecreatefromjpeg($filePath), 0, 0, 0, 0, $thumbWidth, $thumbHeight, $srcW, $srcH);

                $thumbFile = pathinfo($fileName, PATHINFO_FILENAME) . '_thumb.jpg';
                imagejpeg($thumb, $screenshotDir. $folder_proj. '/'  . $thumbFile, 85);
                imagedestroy($thumb);
            }

            // Insérer le commentaire dans la base de données
            $stmt = $conn->prepare("
                INSERT INTO sequoia_ticket
                (nom, description, id_module, id_page, reply, time, status, screenshot)
                VALUES
                (:nom, :description, :id_module, :id_page, :reply, NOW(), 1, :screenshot)
            ");

            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id_module', $id_module);
            $stmt->bindParam(':id_page', $id_page);
            $stmt->bindParam(':reply', $reply, PDO::PARAM_INT);
            $stmt->bindParam(':screenshot', $fileName);

            $stmt->execute();

            $result = [
                "success" => true,
                "message" => "Commentaire ajouté avec succès.",
                "insertId" => $conn->lastInsertId() // Retourne l'ID du nouveau commentaire
            ];
        }
    }
        // --- 4. Fermer un commentaire (status = 2) ---
    else if(isset($_REQUEST["func"]) && $_REQUEST["func"] == "closecomment") {
        if(isset($_REQUEST["id"]) && (int)$_REQUEST["id"] > 0) {
            $id = (int)$_REQUEST["id"];

            $stmt = $conn->prepare("UPDATE sequoia_ticket SET status = 2 WHERE ID = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = [
                "success" => true,
                "message" => "Commentaire fermé.",
                "affected" => $stmt->rowCount()
            ];
        } else {
            $result = [
                "success" => false,
                "message" => "Le paramètre 'id' est requis."
            ];
        }
    }

    // --- 3. Fonction non reconnue ---
    else {
        $result = [
            "success" => false,
            "message" => "La fonction spécifiée n'existe pas."
        ];
    }

    echo json_encode($result);
}
catch(PDOException $e) {
    // Retourne une erreur en JSON
    echo json_encode([
        "success" => false,
        "message" => "Erreur de connexion à la base de données: " . $e->getMessage()
    ]);
}
?>