<?php
require_once 'db.php';
header('Content-Type: application/json');

// Ajuste de timezone para Brasília
date_default_timezone_set('America/Sao_Paulo');

try {
    $db = getDb();
    
    // Verifica a ação solicitada
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'status':
            // Obtém o status atual do bebê com base nas mamadas recentes
            $status = getStatusBebe($db);
            echo json_encode($status);
            break;
            
        case 'stats':
            // Endpoint para estatísticas gerais
            $stats = getStatsGerais($db);
            echo json_encode($stats);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação não reconhecida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Obtém o status atual do bebê com base nas mamadas recentes
 * @param PDO $db Conexão com o banco de dados
 * @return array Status do bebê e alertas
 */
function getStatusBebe($db) {
    // Última mamada
    $stmt = $db->query('SELECT * FROM mamadas ORDER BY data_hora DESC LIMIT 1');
    $ultima = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Cálculo de intervalo entre mamadas (últimas 24h)
    $stmt = $db->query("
        SELECT 
            data_hora 
        FROM 
            mamadas 
        WHERE 
            data_hora >= datetime('now', '-24 hours') 
        ORDER BY 
            data_hora DESC
    ");
    $mamadas24h = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total de mamadas hoje
    $hoje = date('Y-m-d');
    $inicio_dia = $hoje . ' 00:00:00';
    $fim_dia = $hoje . ' 23:59:59';
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN tipo = 'peito' THEN quantidade ELSE 0 END) as total_peito,
            SUM(CASE WHEN tipo = 'formula' THEN quantidade ELSE 0 END) as total_formula,
            SUM(quantidade) as total_ml
        FROM 
            mamadas 
        WHERE 
            data_hora >= :inicio_dia AND data_hora <= :fim_dia
    ");
    $stmt->bindParam(':inicio_dia', $inicio_dia);
    $stmt->bindParam(':fim_dia', $fim_dia);
    $stmt->execute();
    $totais_hoje = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Cálculo de intervalo médio
    $intervalo_medio = calcularIntervaloMedio($mamadas24h);
    
    // Verifica se precisa de alimentação
    $alertas = [];
    $status = "Tudo em ordem";
    
    if (!$ultima) {
        $status = "Sem registros";
    } else {
        // Ajustando para o timezone correto
        $ultima_timestamp = strtotime($ultima['data_hora']);
        $agora = time();
        $horas_desde_ultima = ($agora - $ultima_timestamp) / 3600;
        
        // Verifica se está muito tempo sem mamar
        if ($horas_desde_ultima > 4) {
            $alertas[] = "Já se passaram " . number_format($horas_desde_ultima, 1) . " horas desde a última alimentação";
            $status = "Atenção";
        }
        
        // Verifica se teve poucas mamadas hoje
        if ($totais_hoje['total'] < 6 && date('H') > 14) {
            $alertas[] = "Apenas " . $totais_hoje['total'] . " alimentações hoje, pode precisar de mais";
            $status = "Atenção";
        }
        
        // Verifica volume total abaixo do esperado
        if ($totais_hoje['total_ml'] < 500 && date('H') > 16) {
            $alertas[] = "Volume total hoje: " . $totais_hoje['total_ml'] . "ml (abaixo do recomendado)";
            $status = "Atenção";
        }
    }
    
    return [
        'status' => $status,
        'alertas' => $alertas,
        'total_mamadas_dia' => $totais_hoje['total'] ?? 0,
        'total_ml_dia' => $totais_hoje['total_ml'] ?? 0,
        'peito_ml' => $totais_hoje['total_peito'] ?? 0,
        'formula_ml' => $totais_hoje['total_formula'] ?? 0,
        'intervalo_medio_horas' => $intervalo_medio,
        'ultima_mamada' => $ultima ? [
            'tipo' => $ultima['tipo'],
            'quantidade' => $ultima['quantidade'],
            'tempo_passado' => $ultima ? tempo_humano(time() - strtotime($ultima['data_hora'])) : 'N/A'
        ] : null,
    ];
}

/**
 * Obtém estatísticas gerais sobre alimentação
 * @param PDO $db Conexão com o banco de dados
 * @return array Estatísticas gerais
 */
function getStatsGerais($db) {
    // Total de mamadas nos últimos 7 dias
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_mamadas,
            SUM(quantidade) as volume_total,
            AVG(quantidade) as media_volume,
            SUM(CASE WHEN tipo = 'peito' THEN 1 ELSE 0 END) as qtd_peito,
            SUM(CASE WHEN tipo = 'formula' THEN 1 ELSE 0 END) as qtd_formula,
            SUM(CASE WHEN tipo = 'peito' THEN quantidade ELSE 0 END) as volume_peito,
            SUM(CASE WHEN tipo = 'formula' THEN quantidade ELSE 0 END) as volume_formula
        FROM 
            mamadas 
        WHERE 
            data_hora >= datetime('now', '-7 days')
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Média de mamadas por dia
    $stats['media_mamadas_dia'] = round($stats['total_mamadas'] / 7, 1);
    
    // Porcentagem de cada tipo
    $stats['porcentagem_peito'] = $stats['total_mamadas'] > 0 
        ? round(($stats['qtd_peito'] / $stats['total_mamadas']) * 100) 
        : 0;
        
    $stats['porcentagem_formula'] = $stats['total_mamadas'] > 0 
        ? round(($stats['qtd_formula'] / $stats['total_mamadas']) * 100) 
        : 0;
    
    return $stats;
}

/**
 * Calcula o intervalo médio entre as mamadas
 * @param array $mamadas Lista de mamadas ordenadas por data
 * @return float|null Intervalo médio em horas ou null se insuficiente
 */
function calcularIntervaloMedio($mamadas) {
    if (count($mamadas) < 2) {
        return null;
    }
    
    $intervalos = [];
    $anterior = null;
    
    foreach ($mamadas as $mamada) {
        $atual = strtotime($mamada['data_hora']);
        if ($anterior !== null) {
            $intervalo = abs($anterior - $atual) / 3600; // Converte para horas
            if ($intervalo < 12) { // Ignora intervalos muito longos
                $intervalos[] = $intervalo;
            }
        }
        $anterior = $atual;
    }
    
    if (empty($intervalos)) {
        return null;
    }
    
    return array_sum($intervalos) / count($intervalos);
}

/**
 * Formata o tempo para exibição amigável
 * @param int $segundos Tempo em segundos
 * @return string Tempo formatado
 */
function tempo_humano($segundos) {
    if ($segundos < 60) {
        return "agora";
    }
    
    if ($segundos < 3600) {
        $minutos = floor($segundos / 60);
        return $minutos . "min";
    }
    
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);
    
    if ($minutos > 0) {
        return $horas . "h" . $minutos . "min";
    }
    
    return $horas . "h";
}
