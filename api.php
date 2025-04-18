<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
$db = getDb();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'listar') {
    // Lista últimas mamadas
    $stmt = $db->query('SELECT * FROM mamadas ORDER BY data_hora DESC LIMIT 20');
    $mamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($mamadas);
    exit;
}
if ($method === 'GET' && $action === 'totais') {
    // Totais do dia
    $hoje = date('Y-m-d');
    $stmt = $db->prepare('SELECT tipo, SUM(quantidade) as total FROM mamadas WHERE date(data_hora) = ? GROUP BY tipo');
    $stmt->execute([$hoje]);
    $totais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode($totais);
    exit;
}
if ($method === 'GET' && $action === 'media') {
    // Média de tempo entre mamadas
    $stmt = $db->query('SELECT data_hora FROM mamadas ORDER BY data_hora DESC LIMIT 10');
    $datas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $intervalos = [];
    for ($i = 1; $i < count($datas); $i++) {
        $d1 = strtotime($datas[$i-1]);
        $d2 = strtotime($datas[$i]);
        $intervalos[] = abs($d1 - $d2);
    }
    $media = count($intervalos) ? array_sum($intervalos)/count($intervalos) : 0;
    echo json_encode(['media_segundos' => $media]);
    exit;
}
if ($method === 'POST' && $action === 'registrar') {
    // Registrar mamada
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? '';
    $quantidade = intval($data['quantidade'] ?? 0);
    $data_hora = $data['data_hora'] ?? date('Y-m-d H:i');
    if ($tipo && $quantidade > 0) {
        $stmt = $db->prepare('INSERT INTO mamadas (tipo, quantidade, data_hora) VALUES (?, ?, ?)');
        $stmt->execute([$tipo, $quantidade, $data_hora]);
        echo json_encode(['ok' => true]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
    exit;
}
http_response_code(404);
echo json_encode(['erro' => 'Ação não encontrada']);
