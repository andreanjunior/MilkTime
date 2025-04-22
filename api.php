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
if ($method === 'GET' && $action === 'status') {
    // Diagnóstico de saúde do bebê baseado nas mamadas a partir de 00:01 do dia atual
    $hoje = date('Y-m-d');
    $inicio = $hoje . ' 00:01:00';
    $agora = date('Y-m-d H:i:s');
    $stmt = $db->prepare('SELECT * FROM mamadas WHERE data_hora >= ? AND data_hora <= ? ORDER BY data_hora ASC');
    $stmt->execute([$inicio, $agora]);
    $mamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($mamadas);
    $intervalos = [];
    for ($i = 1; $i < $total; $i++) {
        $t1 = strtotime($mamadas[$i-1]['data_hora']);
        $t2 = strtotime($mamadas[$i]['data_hora']);
        $intervalos[] = abs($t2 - $t1) / 3600; // horas
    }
    $intervalo_medio = $intervalos ? round(array_sum($intervalos) / count($intervalos), 2) : null;
    // Critérios básicos
    $alertas = [];
    // Previsão inteligente: avalia se o ritmo é suficiente para chegar a 8 mamadas até 00:00
    $inicio = strtotime($inicio);
    $agora_ts = strtotime($agora);
    $horas_passadas = max(1, ($agora_ts - $inicio) / 3600); // evita divisão por zero
    $horas_totais = 24 - (1/60); // de 00:01 até 00:00 (aprox. 23.98h)
    $ritmo_atual = $total / $horas_passadas; // mamadas por hora
    $previsao_final = round($ritmo_atual * $horas_totais);
    if ($previsao_final < 8) {
        $alertas[] = 'Poucas mamadas previstas para hoje (mantendo o ritmo atual, não chegará a 8).';
    }
    if ($intervalo_medio !== null && $intervalo_medio > 4) {
        $alertas[] = 'Intervalo médio entre mamadas muito longo (> 4h).';
    }
    $status = 'Tudo certo!';
    if (!empty($alertas)) {
        $status = 'Atenção!';
    }
    echo json_encode([
        'total_mamadas_dia' => $total,
        'intervalo_medio_horas' => $intervalo_medio,
        'alertas' => $alertas,
        'status' => $status,
    ]);
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
