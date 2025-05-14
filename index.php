<?php
// Tudo em um arquivo! Cria banco SQLite, interface Material Design, tudo PHP puro.
$db = new PDO('sqlite:' . __DIR__ . '/mamadas.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE IF NOT EXISTS mamadas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo TEXT NOT NULL,
    quantidade INTEGER NOT NULL,
    data_hora DATETIME NOT NULL
)');
setlocale(LC_TIME, 'pt_BR.UTF-8');
date_default_timezone_set('America/Sao_Paulo');
// Registrar, editar ou excluir mamada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'excluir' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $db->prepare('DELETE FROM mamadas WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: index.php');
        exit;
    } elseif ($acao === 'editar' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $tipo = $_POST['tipo'] ?? '';
        $quantidade = intval($_POST['quantidade'] ?? 0);
        $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i:s');
        $data_hora = str_replace('T', ' ', $data_hora);
        if ($tipo && $quantidade > 0) {
            $stmt = $db->prepare('UPDATE mamadas SET tipo = ?, quantidade = ?, data_hora = ? WHERE id = ?');
            $stmt->execute([$tipo, $quantidade, $data_hora, $id]);
            header('Location: index.php');
            exit;
        }
    } else {
        // Registrar novo
        $tipo = $_POST['tipo'] ?? '';
        $quantidade = intval($_POST['quantidade'] ?? 0);
        $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i:s');
        $data_hora = str_replace('T', ' ', $data_hora);
        if ($tipo && $quantidade > 0) {
            $stmt = $db->prepare('INSERT INTO mamadas (tipo, quantidade, data_hora) VALUES (?, ?, ?)');
            $stmt->execute([$tipo, $quantidade, $data_hora]);
            header('Location: index.php');
            exit;
        }
    }
}
// Últimas mamadas (últimos 5 registros antes de 00:00 + registros do dia atual)
$hoje = date('Y-m-d');
$inicio_dia_atual = $hoje . ' 00:00:00';
$ontem = date('Y-m-d', strtotime('-1 day'));
$inicio_dia_ontem = $ontem . ' 00:00:00';

// Primeiro, obtemos os últimos 5 registros do dia anterior à meia-noite
$stmt_anterior = $db->prepare('
    SELECT * FROM mamadas 
    WHERE data_hora >= ? AND data_hora < ? 
    ORDER BY data_hora DESC 
    LIMIT 5
');
$stmt_anterior->execute([$inicio_dia_ontem, $inicio_dia_atual]);
$mamadas_anteriores = $stmt_anterior->fetchAll(PDO::FETCH_ASSOC);

// Agora, obtemos todos os registros do dia atual
$stmt_atual = $db->prepare('
    SELECT * FROM mamadas 
    WHERE data_hora >= ? 
    ORDER BY data_hora DESC
');
$stmt_atual->execute([$inicio_dia_atual]);
$mamadas_atuais = $stmt_atual->fetchAll(PDO::FETCH_ASSOC);

// Combinamos os dois conjuntos em ordem cronológica inversa (mais recente primeiro)
$mamadas = array_merge($mamadas_atuais, $mamadas_anteriores);

// Ordenamos novamente para garantir que estejam na ordem correta
usort($mamadas, function($a, $b) {
    return strtotime($b['data_hora']) - strtotime($a['data_hora']);
});

// Totais do dia (a partir de 00:00)
$hoje = date('Y-m-d');
$inicio_dia = $hoje . ' 00:00:00';
$agora = date('Y-m-d H:i:s');
$stmt = $db->prepare('SELECT tipo, SUM(quantidade) as total FROM mamadas WHERE data_hora >= ? AND data_hora <= ? GROUP BY tipo');
$stmt->execute([$inicio_dia, $agora]);
$totais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
// Média, maior e menor intervalo do dia
$stmt = $db->prepare('SELECT data_hora FROM mamadas WHERE date(data_hora) = ? ORDER BY data_hora DESC');
$stmt->execute([$hoje]);
$datas_dia = $stmt->fetchAll(PDO::FETCH_COLUMN);
$intervalos_dia = [];
for ($i = 1; $i < count($datas_dia); $i++) {
    $d1 = strtotime($datas_dia[$i-1]);
    $d2 = strtotime($datas_dia[$i]);
    $intervalos_dia[] = abs($d1 - $d2);
}
$media_dia = count($intervalos_dia) ? array_sum($intervalos_dia)/count($intervalos_dia) : 0;
$maior_dia = count($intervalos_dia) ? max($intervalos_dia) : 0;
$menor_dia = count($intervalos_dia) ? min($intervalos_dia) : 0;
// Média, maior e menor intervalo últimas 24h
$stmt = $db->prepare('SELECT data_hora FROM mamadas WHERE data_hora >= datetime("now", "-1 day") ORDER BY data_hora DESC');
$stmt->execute();
$datas_24h = $stmt->fetchAll(PDO::FETCH_COLUMN);
$intervalos_24h = [];
for ($i = 1; $i < count($datas_24h); $i++) {
    $d1 = strtotime($datas_24h[$i-1]);
    $d2 = strtotime($datas_24h[$i]);
    $intervalos_24h[] = abs($d1 - $d2);
}
$media_24h = count($intervalos_24h) ? array_sum($intervalos_24h)/count($intervalos_24h) : 0;
$maior_24h = count($intervalos_24h) ? max($intervalos_24h) : 0;
$menor_24h = count($intervalos_24h) ? min($intervalos_24h) : 0;
// Média de tempo entre mamadas (a partir de 00:00 do dia atual)
$hoje = date('Y-m-d');
$inicio_dia = $hoje . ' 00:00:00';
$agora = date('Y-m-d H:i:s');
$stmt = $db->prepare('SELECT data_hora FROM mamadas WHERE data_hora >= ? AND data_hora <= ? ORDER BY data_hora ASC');
$stmt->execute([$inicio_dia, $agora]);
$datas_dia = $stmt->fetchAll(PDO::FETCH_COLUMN);
$intervalos_dia = [];
for ($i = 1; $i < count($datas_dia); $i++) {
    $d1 = strtotime($datas_dia[$i-1]);
    $d2 = strtotime($datas_dia[$i]);
    $intervalos_dia[] = abs($d1 - $d2);
}
$media_intervalo = count($intervalos_dia) ? array_sum($intervalos_dia)/count($intervalos_dia) : 0;
function tempo_humano($segundos) {
    if ($segundos < 60) return $segundos . 's';
    $min = floor($segundos/60);
    if ($min < 60) return $min . 'min';
    $h = floor($min/60);
    $min = $min % 60;
    return $h . 'h ' . $min . 'min';
}
// Data de nascimento da Alice (ajuste aqui se necessário)
$dt_nascimento = '2025-03-25';
$hoje_data = new DateTime(date('Y-m-d'));
$nasc_data = new DateTime($dt_nascimento);
$diff = $nasc_data->diff($hoje_data);
$idade_str = $diff->m > 0 ? ( ($diff->m == 1 ? '1 mês' : $diff->m . ' meses') . ' e ' . $diff->d . ' dias' ) : ($diff->d . ' dias');
$idade_str = $diff->m > 0 ? $idade_str : ($diff->d == 1 ? '1 dia' : $diff->d . ' dias');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MilkTime</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style-extra.css">
</head>
<body>
    <!-- Toggle de modo escuro -->
    <div class="theme-toggle" id="theme-toggle">
        <i class="material-icons">light_mode</i>
    </div>

    <div class="container">
        <div class="center-align" style="margin-top:24px;margin-bottom:18px;">
            <img src="logo.svg" alt="MilkTime Logo" style="height:72px;">
            <h5 style="margin-top:12px;font-family:var(--font-primary);font-weight:700;">
                <span style="color:var(--primary);">Milk</span><span style="color:var(--secondary);">Time</span>
            </h5>
            <p style="margin-top:4px;color:var(--text-secondary);">
                Alice - <?php echo $idade_str; ?>
            </p>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($sucesso) && $sucesso): ?>
        <div class="card-panel green lighten-4 green-text text-darken-3" id="msg-sucesso" style="border-radius:18px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;">
                <i class="material-icons" style="margin-right:8px;">check_circle</i>
                <span>Mamada registrada com sucesso!</span>
            </div>
        </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($erro) && $erro): ?>
        <div class="card-panel red lighten-4 red-text text-darken-3" id="msg-erro" style="border-radius:18px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;">
                <i class="material-icons" style="margin-right:8px;">error</i>
                <span>Erro: Verifique os dados inseridos.</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Card de registro de mamada -->
        <div class="card-panel">
            <h5 class="card-title" style="font-family:var(--font-primary);font-weight:700;margin-bottom:20px;color:var(--text-primary);">Nova Mamada</h5>
            
            <form method="POST" autocomplete="off">
                <div class="form-row">
                    <div style="display:flex;gap:12px;width:100%;margin-bottom:20px;">
                        <div class="tipo-btn peito" id="btn-peito" onclick="selecionarTipo('peito')">
                            <i class="material-icons">child_care</i>
                            <span>Peito</span>
                            <input type="radio" name="tipo" value="peito" style="display:none;" id="radio-peito">
                        </div>
                        
                        <div class="tipo-btn formula" id="btn-formula" onclick="selecionarTipo('formula')">
                            <i class="material-icons">opacity</i>
                            <span>Fórmula</span>
                            <input type="radio" name="tipo" value="formula" style="display:none;" id="radio-formula">
                        </div>
                    </div>
                    
                    <div class="input-field" style="width:100%;margin-bottom:16px;">
                        <i class="material-icons prefix">local_drink</i>
                        <input type="number" name="quantidade" id="quantidade" min="1" required autofocus>
                        <label for="quantidade">Quantidade (ml)</label>
                    </div>

                    <div class="input-field" style="width:100%;margin-bottom:16px;">
                        <i class="material-icons prefix">event</i>
                        <input type="datetime-local" name="data_hora" id="data_hora" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        <label for="data_hora" class="active">Data e Hora</label>
                    </div>

                    <button type="submit" class="btn subtle-hover" style="width:100%;margin-top:10px;">
                        <i class="material-icons left">add_circle</i>Registrar Mamada
                    </button>
                </div>
            </form>
        </div>

        <!-- Cards de resumo -->
        <div class="resumo-grid">
            <div class="resumo-card">
                <i class="material-icons card-icon">pie_chart</i>
                <div class="card-title">Total Hoje</div>
                <div class="animated-value card-value">
                    <?php 
                        echo isset($totais['peito']) ? $totais['peito'] : '0'; 
                        echo ' + ';
                        echo isset($totais['formula']) ? $totais['formula'] : '0';
                        echo ' ml';
                    ?>
                </div>
                <div class="card-subtitle">
                    <i class="material-icons" style="font-size:16px;vertical-align:text-bottom;color:var(--primary);">child_care</i> 
                    <?php echo isset($totais['peito']) ? $totais['peito'] . ' ml' : '0 ml'; ?>
                </div>
                <div class="card-subtitle">
                    <i class="material-icons" style="font-size:16px;vertical-align:text-bottom;color:var(--secondary);">opacity</i>
                    <?php echo isset($totais['formula']) ? $totais['formula'] . ' ml' : '0 ml'; ?>
                </div>
            </div>

            <div class="resumo-card">
                <i class="material-icons card-icon">schedule</i>
                <div class="card-title">Média Intervalo</div>
                <div class="animated-value card-value">
                    <?php echo tempo_humano($media_24h); ?>
                </div>
                <div class="card-subtitle">
                    Hoje: <?php echo tempo_humano($media_dia); ?>
                </div>
            </div>

            <div class="resumo-card">
                <i class="material-icons card-icon">update</i>
                <div class="card-title">Última Mamada</div>
                <div class="animated-value card-value">
                    <?php 
                        if (count($mamadas) > 0) {
                            $ultima = strtotime($mamadas[0]['data_hora']);
                            $agora = time();
                            $diff = $agora - $ultima;
                            echo tempo_humano($diff) . ' atrás';
                        } else {
                            echo "Nenhuma";
                        }
                    ?>
                </div>
                <div class="card-subtitle">
                    <?php 
                        if (count($mamadas) > 0) {
                            echo $mamadas[0]['tipo'] === 'peito' ? 
                                '<i class="material-icons" style="font-size:16px;vertical-align:text-bottom;color:var(--primary);">child_care</i>' : 
                                '<i class="material-icons" style="font-size:16px;vertical-align:text-bottom;color:var(--secondary);">opacity</i>';
                            echo ' ' . $mamadas[0]['quantidade'] . ' ml';
                            
                            // Mostrar o intervalo exato de forma mais detalhada
                            $ultima = strtotime($mamadas[0]['data_hora']);
                            $agora = time();
                            $diff = $agora - $ultima;
                            
                            $horas = floor($diff / 3600);
                            $minutos = floor(($diff % 3600) / 60);
                            echo '<div style="margin-top:6px;">Tempo desde: <b>' . $horas . 'h ' . $minutos . 'min</b></div>';
                            
                            // Mostrar o intervalo entre a última e a penúltima mamada
                            if (count($mamadas) > 1) {
                                $ultima = strtotime($mamadas[0]['data_hora']);
                                $penultima = strtotime($mamadas[1]['data_hora']);
                                $diff_entre_mamadas = $ultima - $penultima;
                                
                                $horas_entre = floor($diff_entre_mamadas / 3600);
                                $minutos_entre = floor(($diff_entre_mamadas % 3600) / 60);
                                echo '<div style="margin-top:4px;">Intervalo anterior: <b>' . $horas_entre . 'h ' . $minutos_entre . 'min</b></div>';
                            }
                        }
                    ?>
                </div>
            </div>
        </div>

        <!-- Histórico de mamadas -->
        <div class="card-panel">
            <h5 class="card-title" style="font-family:var(--font-primary);font-weight:700;margin-bottom:16px;color:var(--text-primary);">Histórico Recente</h5>

            <ul class="collection" style="border:none;">
                <?php foreach ($mamadas as $index => $m): ?>
                    <li class="collection-item <?php echo $index >= 5 ? 'hidden-mamada' : ''; ?>" style="<?php echo $index >= 5 ? 'display:none;' : ''; ?>">
                        <div class="info-principal">
                            <div class="tipo-icon">
                                <?php if ($m['tipo'] === 'peito'): ?>
                                    <i class="material-icons" style="color:var(--primary);">child_care</i>
                                <?php else: ?>
                                    <i class="material-icons" style="color:var(--secondary);">opacity</i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="quantidade"><?php echo $m['quantidade']; ?> ml</span>
                                <div class="info-secundaria">
                                    <?php 
                                        $data = new DateTime($m['data_hora']);
                                        echo $data->format('d/m H:i'); 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="#modal-edit-<?php echo $m['id']; ?>" class="modal-trigger btn-flat" style="padding:6px;"><i class="material-icons" style="color:var(--text-secondary);">edit</i></a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                <button type="submit" class="btn-flat" style="padding:6px;" onclick="return confirm('Tem certeza que deseja excluir?')">
                                    <i class="material-icons" style="color:var(--text-secondary);">delete</i>
                                </button>
                            </form>
                        </div>
                    </li>

                    <!-- Modal de edição -->
                    <div id="modal-edit-<?php echo $m['id']; ?>" class="modal" style="border-radius:24px;overflow:hidden;">
                        <div class="modal-content">
                            <h5 style="font-family:var(--font-primary);font-weight:700;">Editar Mamada</h5>
                            <form method="POST" autocomplete="off">
                                <input type="hidden" name="acao" value="editar">
                                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                
                                <div style="display:flex;gap:12px;width:100%;margin:20px 0;">
                                    <div class="tipo-btn peito <?php echo $m['tipo'] === 'peito' ? 'active' : ''; ?>" onclick="selecionarTipoModal(this, 'peito-<?php echo $m['id']; ?>')">
                                        <i class="material-icons">child_care</i>
                                        <span>Peito</span>
                                        <input type="radio" name="tipo" value="peito" <?php echo $m['tipo'] === 'peito' ? 'checked' : ''; ?> id="peito-<?php echo $m['id']; ?>" style="display:none;">
                                    </div>
                                    
                                    <div class="tipo-btn formula <?php echo $m['tipo'] === 'formula' ? 'active' : ''; ?>" onclick="selecionarTipoModal(this, 'formula-<?php echo $m['id']; ?>')">
                                        <i class="material-icons">opacity</i>
                                        <span>Fórmula</span>
                                        <input type="radio" name="tipo" value="formula" <?php echo $m['tipo'] === 'formula' ? 'checked' : ''; ?> id="formula-<?php echo $m['id']; ?>" style="display:none;">
                                    </div>
                                </div>

                                <div class="input-field">
                                    <i class="material-icons prefix">local_drink</i>
                                    <input type="number" name="quantidade" value="<?php echo $m['quantidade']; ?>" min="1" required>
                                    <label for="quantidade" class="active">Quantidade (ml)</label>
                                </div>

                                <div class="input-field">
                                    <i class="material-icons prefix">event</i>
                                    <?php
                                        $dt = new DateTime($m['data_hora']);
                                        $formattedDate = $dt->format('Y-m-d\TH:i');
                                    ?>
                                    <input type="datetime-local" name="data_hora" value="<?php echo $formattedDate; ?>" required>
                                    <label class="active">Data e Hora</label>
                                </div>
                            
                                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 0 0 0;">
                                    <a href="#!" class="modal-close btn-flat">Cancelar</a>
                                    <button type="submit" class="btn">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </ul>
            
            <?php if (count($mamadas) > 5): ?>
            <button id="mostrar-mais-mamadas" class="btn-flat blue-text" style="margin-top:16px;display:block;width:100%;text-align:center;">
                Ver mais registros
            </button>
            <?php endif; ?>
        </div>

        <!-- Status do bebê -->
        <div id="painel-status-bebe">
            <span class="status-icone" id="status-icone">🔄</span>
            <span class="status-titulo">Status do bebê</span><br>
            <span class="status-info" id="status-txt">Carregando...</span>
            <div id="status-detalhes"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Materialize init
            M.AutoInit();
            var elems = document.querySelectorAll('.modal');
            M.Modal.init(elems, {});
            
            // Atualizar status
            atualizarPainelStatusBebe();
            
            // Ver mais registros
            var btn = document.getElementById('mostrar-mais-mamadas');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.hidden-mamada').forEach(function(el) {
                        el.style.display = 'flex';
                    });
                    btn.style.display = 'none';
                });
            }
            
            // Mensagem de sucesso - esconder após 3 segundos
            var msgSucesso = document.getElementById('msg-sucesso');
            if (msgSucesso) {
                setTimeout(function() {
                    msgSucesso.style.opacity = '0';
                    setTimeout(function() {
                        msgSucesso.style.display = 'none';
                    }, 300);
                }, 3000);
            }
            
            // Toggle de tema
            initThemeToggle();
            
            // Inicializar com o primeiro tipo selecionado
            setTimeout(function() {
                const primeiroTipo = document.querySelector('.tipo-btn');
                if (primeiroTipo) {
                    primeiroTipo.click();
                }
            }, 100);
            
            // Animar valores na carga inicial
            animateValues();
        });
        
        // Funções de tipo de mamada
        function selecionarTipo(tipo) {
            document.getElementById('btn-peito').classList.remove('active');
            document.getElementById('btn-formula').classList.remove('active');
            document.getElementById('btn-' + tipo).classList.add('active');
            document.getElementById('radio-' + tipo).checked = true;
        }
        
        function selecionarTipoModal(el, id) {
            el.parentNode.querySelectorAll('.tipo-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            el.classList.add('active');
            document.getElementById(id).checked = true;
        }
        
        // Theme toggle
        function initThemeToggle() {
            const themeToggle = document.getElementById('theme-toggle');
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Check for saved theme preference or default to prefersDarkScheme
            const currentTheme = localStorage.getItem('theme') || 
                                (prefersDarkScheme.matches ? 'dark' : 'light');
            
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                themeToggle.querySelector('i').textContent = 'dark_mode';
            }
            
            // Toggle theme on click
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                
                const icon = themeToggle.querySelector('i');
                const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
                
                icon.textContent = theme === 'dark' ? 'dark_mode' : 'light_mode';
                localStorage.setItem('theme', theme);
            });
        }
        
        // Status do bebê
        function atualizarPainelStatusBebe() {
            fetch('api.php?action=status')
                .then(resp => resp.json())
                .then(data => {
                    const painel = document.getElementById('painel-status-bebe');
                    const statusTxt = document.getElementById('status-txt');
                    const detalhes = document.getElementById('status-detalhes');
                    const icone = document.getElementById('status-icone');
                    
                    if (!data || typeof data !== 'object') {
                        painel.className = 'erro';
                        statusTxt.textContent = 'Erro ao obter status.';
                        detalhes.innerHTML = '';
                        icone.textContent = '❌';
                        painel.style.display = 'block';
                        return;
                    }
                    
                    if (data.alertas && data.alertas.length > 0) {
                        painel.className = 'atencao';
                        icone.textContent = '⚠️';
                    } else {
                        painel.className = 'tudocerto';
                        icone.textContent = '✅';
                    }
                    
                    statusTxt.textContent = data.status;
                    
                    let html = `<span style='display:inline-block;margin-bottom:6px;'><b>Total hoje:</b> <span style='font-weight:600;'>${data.total_mamadas_dia}</span></span><br>`;
                    
                    if (data.intervalo_medio_horas !== null) {
                        const med = data.intervalo_medio_horas;
                        const hrs = Math.floor(med);
                        const mins = Math.round((med - hrs) * 60);
                        const medStr = hrs > 0 ? `${hrs}h ${mins}min` : `${mins}min`;
                        html += `<span style='display:inline-block;margin-bottom:6px;'><b>Intervalo médio:</b> <span style='font-weight:600;'>${medStr}</span></span><br>`;
                    }
                    
                    if (data.alertas && data.alertas.length > 0) {
                        html += '<ul>' + data.alertas.map(a => `<li>${a}</li>`).join('') + '</ul>';
                    }
                    
                    detalhes.innerHTML = html;
                    painel.style.display = 'block';
                    
                    // Animar valores
                    animateValues();
                })
                .catch(() => {
                    const painel = document.getElementById('painel-status-bebe');
                    painel.className = 'erro';
                    document.getElementById('status-txt').textContent = 'Erro ao obter status.';
                    document.getElementById('status-detalhes').innerHTML = '';
                    document.getElementById('status-icone').textContent = '❌';
                    painel.style.display = 'block';
                });
        }

        // Animação de valores
        function animateValues() {
            document.querySelectorAll('.animated-value').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(10px)';
                
                setTimeout(() => {
                    el.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 300);
            });
        }
        
        // Animar valores na carga inicial
        animateValues();
    </script>
</body>
</html>
