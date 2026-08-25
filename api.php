<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Caracas');

$host = 'localhost';
$db   = 'sistema_personal';
$user = 'root';
$pass = ''; // Por defecto en Laragon no hay contraseña

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Error de conexión: " . $e->getMessage()]));
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

if ($action === 'get_all') {
    $users = $pdo->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
    $docs = $pdo->query("SELECT * FROM documentos")->fetchAll(PDO::FETCH_ASSOC);
    
    $entrada = [];
    $salida = [];
    
    foreach ($docs as $d) {
        $d['archivos'] = json_decode($d['archivos'], true) ?: [];
        if ($d['tipo_flujo'] === 'entrada') {
            $entrada[] = $d;
        } else {
            $salida[] = $d;
        }
    }
    
    echo json_encode([
        "users" => $users,
        "documentos" => $entrada,
        "documentosSalida" => $salida
    ]);
    exit;
}

if ($action === 'save_doc' || $action === 'update_doc') {
    $sql = "INSERT INTO documentos (id, tipo_flujo, destinatario, fecha, numeroControl, numeroOficio, tipoDocumento, asunto, descripcion, division, accion, profesional, fechaEntrega, condicion, archivos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            destinatario=VALUES(destinatario), fecha=VALUES(fecha), numeroOficio=VALUES(numeroOficio), tipoDocumento=VALUES(tipoDocumento), asunto=VALUES(asunto), descripcion=VALUES(descripcion), condicion=VALUES(condicion)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['id'],
        $data['tipo_flujo'],
        $data['destinatario'] ?? '',
        $data['fecha'] ?? '',
        $data['numeroControl'] ?? '',
        $data['numeroOficio'] ?? '',
        $data['tipoDocumento'] ?? '',
        $data['asunto'] ?? '',
        $data['descripcion'] ?? '',
        $data['division'] ?? '',
        $data['accion'] ?? '',
        $data['profesionalRegistro'] ?? $data['profesional'] ?? '',
        $data['fechaEntrega'] ?? '',
        $data['condicion'] ?? 'en_proceso',
        json_encode($data['archivos'] ?? [])
    ]);
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($action === 'update_condicion') {
    $stmt = $pdo->prepare("UPDATE documentos SET condicion = ? WHERE id = ?");
    $stmt->execute([$data['condicion'], $data['id']]);
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($action === 'delete_doc') {
    $stmt = $pdo->prepare("DELETE FROM documentos WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($action === 'save_user') {
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, role, division) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['username'], $data['password'], $data['role'], $data['division']]);
    echo json_encode(["status" => "ok"]);
    exit;
}

if ($action === 'delete_user') {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE username = ?");
    $stmt->execute([$data['username']]);
    echo json_encode(["status" => "ok"]);
    exit;
}
?>