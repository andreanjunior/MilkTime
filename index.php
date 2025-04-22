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
        $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i');
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
        $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i');
        $data_hora = str_replace('T', ' ', $data_hora);
        if ($tipo && $quantidade > 0) {
            $stmt = $db->prepare('INSERT INTO mamadas (tipo, quantidade, data_hora) VALUES (?, ?, ?)');
            $stmt->execute([$tipo, $quantidade, $data_hora]);
            header('Location: index.php');
            exit;
        }
    }
}
// Últimas mamadas (apenas 5)
$stmt = $db->query('SELECT * FROM mamadas ORDER BY data_hora DESC LIMIT 5');
$mamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Totais do dia (a partir de 00:01)
$hoje = date('Y-m-d');
$inicio_dia = $hoje . ' 00:01:00';
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
// Média de tempo entre mamadas (a partir de 00:01 do dia atual)
$hoje = date('Y-m-d');
$inicio_dia = $hoje . ' 00:01:00';
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
?><!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Mamadas</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .container { max-width: 600px; margin-top: 12px; }
        .form-row { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 0; }
        .input-field { flex: 1 1 130px; min-width: 120px; margin-bottom: 0; }
        @media (max-width: 700px) {
          .container { padding: 0 2vw; }
          .form-row { flex-direction: column; gap: 0; }
          .input-field { min-width: 100%; margin-bottom: 12px; }
          input[type=datetime-local] {
            max-width: 100%;
            font-size: 1em;
          }
        }
        @media (max-width: 430px) {
          .container { padding: 0 1vw; }
          .input-field { font-size:0.97em; }
        }
        .btn-large { min-width: 120px; padding: 0 10px; }
        .chip-mui { display:inline-flex; align-items:center; font-size:1em; font-weight:500; margin-right:8px; margin-bottom:4px; padding:0 10px; border-radius:18px; height:32px; }
    </style>
    <style>
        #painel-status-bebe { margin: 28px auto 32px auto; padding: 28px 24px 22px 24px; border-radius: 28px; box-shadow: 0 8px 32px 0 rgba(0,0,0,0.12); font-size: 1.18em; background: linear-gradient(120deg, #fff 60%, #f6f7fa 100%); color: #222; display: none; max-width: 430px; position: relative; animation: painelFadeIn 0.7s cubic-bezier(.4,0,.2,1); transition: background 0.3s, color 0.3s, box-shadow 0.3s; border: 1.5px solid #e5e5ea; box-sizing: border-box; -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
        #painel-status-bebe .status-icone { font-size: 2.3em; vertical-align: middle; margin-right: 12px; margin-bottom: 7px; display: inline-block; filter: drop-shadow(0 1px 2px #0001); }
        #painel-status-bebe.atencao { background: linear-gradient(120deg, #fffbe6 60%, #fff8e1 100%); color: #b26a00; border-color: #ffe082; }
        #painel-status-bebe.tudocerto { background: linear-gradient(120deg, #f0fff4 60%, #e0f7fa 100%); color: #1b5e20; border-color: #b2dfdb; }
        #painel-status-bebe.erro { background: linear-gradient(120deg, #fff0f0 60%, #ffeaea 100%); color: #c62828; border-color: #ffcdd2; }
        #painel-status-bebe ul { margin: 14px 0 0 0; padding-left: 22px; font-size: 0.97em; }
        #painel-status-bebe {
            margin: 28px auto 32px auto;
            padding: 28px 24px 22px 24px;
            border-radius: 28px;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.12);
            font-size: 1.18em;
            background: linear-gradient(120deg, #fff 60%, #f6f7fa 100%);
            color: #222;
            display: none;
            max-width: 430px;
            position: relative;
            animation: painelFadeIn 0.7s cubic-bezier(.4,0,.2,1);
            transition: background 0.3s, color 0.3s, box-shadow 0.3s;
            border: 1.5px solid #e5e5ea;
            box-sizing: border-box;
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }
        #painel-status-bebe .status-icone {
            font-size: 2.3em;
            vertical-align: middle;
            margin-right: 12px;
            margin-bottom: 7px;
            display: inline-block;
            filter: drop-shadow(0 1px 2px #0001);
        }
        #painel-status-bebe.atencao {
            background: linear-gradient(120deg, #fffbe6 60%, #fff8e1 100%);
            color: #b26a00;
            border-color: #ffe082;
        }
        #painel-status-bebe.tudocerto {
            background: linear-gradient(120deg, #f0fff4 60%, #e0f7fa 100%);
            color: #1b5e20;
            border-color: #b2dfdb;
        }
        #painel-status-bebe.erro {
            background: linear-gradient(120deg, #fff0f0 60%, #ffeaea 100%);
            color: #c62828;
            border-color: #ffcdd2;
        }
        #painel-status-bebe ul {
            margin: 14px 0 0 0;
            padding-left: 22px;
            font-size: 0.97em;
        }
        #painel-status-bebe .status-titulo {
            font-weight: 600;
            font-size: 1.13em;
            display: inline-block;
            margin-bottom: 4px;
            letter-spacing: 0.01em;
        }
        #painel-status-bebe .status-info {
            font-size: 1.02em;
            color: #666;
            margin-top: 4px;
            margin-bottom: 2px;
            display: block;
        }
        @keyframes painelFadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 600px) {
            #painel-status-bebe { padding: 18px 7vw 16px 7vw; border-radius: 22px; }
        }
    </style>
</head>
<body>
    
    <div class="container">
    <h3 class="center-align" style="font-size:2em;margin-top:18px;margin-bottom:24px;letter-spacing:0.01em;">Contador de Amamentação</h3>
    <div class="row" id="painel-superior" style="display:flex;align-items:flex-start;gap:18px;flex-wrap:wrap;justify-content:flex-start;margin-bottom:12px;"></div>
    <h4 class="left-align" style="margin-bottom:0.5em;font-size:1.35em;line-height:1.2;">Registro de mamadas 🍼:</h4>
    <?php
// Feedback visual após registro
$sucesso = false;
$erro = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tipo && $quantidade > 0) {
        $sucesso = true;
    } else {
        $erro = true;
    }
}
?>
<form method="POST" class="card-panel" autocomplete="off" style="margin-bottom:8px;">
    <div class="form-row" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <?php if ($sucesso): ?>
            <div class="card-panel green lighten-4 green-text text-darken-4" id="msg-sucesso" style="margin-bottom:6px;width:100%;">
                <i class="material-icons left">check_circle</i> Mamada registrada!
            </div>
        <?php elseif ($erro): ?>
            <div class="card-panel red lighten-4 red-text text-darken-4" style="margin-bottom:6px;width:100%;">
                <i class="material-icons left">error</i> Preencha todos os campos corretamente.
            </div>
        <?php endif; ?>
        <div class="input-field" style="flex:1 1 140px;min-width:120px;width:100%;max-width:220px;">
            <i class="material-icons prefix">local_drink</i>
            <select name="tipo" required>
                <option value="" disabled selected>Tipo</option>
                <option value="materno">Leite Materno</option>
                <option value="formula">Fórmula</option>
            </select>
            <label>Tipo</label>
        </div>
        <div class="input-field" style="flex:1 1 100px;min-width:100px;width:100%;max-width:160px;">
            <i class="material-icons prefix">opacity</i>
            <input type="number" name="quantidade" id="quantidade" min="1" required autofocus>
            <label for="quantidade">Qtd (ml)</label>
        </div>
        <div class="input-field" style="flex:2 1 180px;min-width:130px;width:100%;max-width:240px;">
            <i class="material-icons prefix">access_time</i>
            <input type="datetime-local" name="data_hora" id="data_hora" value="<?php echo isset($_POST['data_hora']) ? htmlspecialchars($_POST['data_hora']) : date('Y-m-d\TH:i'); ?>" placeholder="Data/Hora">
        </div>
        <div style="display:flex;align-items:center;justify-content:center;min-width:70px;max-width:90px;padding-left:10px;">
    <button class="btn waves-effect waves-light blue" type="submit" style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;border-radius:12px;box-shadow:0 2px 6px #0001;font-size:1.4em;padding:0;">
        <i class="material-icons" style="margin:0;">add</i>
    </button>
</div>
    </div>
</form>
<style>@media (max-width: 700px) {
    .form-row { flex-direction: column; gap:0; }
    .input-field, .form-row > div { max-width:100% !important; min-width:100% !important; width:100% !important; }
}</style>
<script>
// Foco automático no campo quantidade
setTimeout(function(){
    var q = document.getElementById('quantidade');
    if(q) q.focus();
}, 300);
// Fade out na mensagem de sucesso
setTimeout(function(){
    var msg = document.getElementById('msg-sucesso');
    if(msg) msg.style.display = 'none';
}, 2200);
</script>
        <div style="display:flex;flex-wrap:wrap;gap:8px 8px;margin-bottom:4px;justify-content:space-between;">
    <span class="chip-mui blue"><i class="material-icons left" style="font-size:18px;">add</i> Materno: <b style="margin-left:2px;"><?php echo $totais['materno'] ?? 0; ?>ml</b></span>
    <span class="chip-mui green"><i class="material-icons left" style="font-size:18px;">local_drink</i> Fórmula: <b style="margin-left:2px;"><?php echo $totais['formula'] ?? 0; ?>ml</b></span>
    <span class="chip-mui amber"><i class="material-icons left" style="font-size:18px;">timer</i> Média: <b style="margin-left:2px;"><?php echo $media_intervalo ? tempo_humano($media_intervalo) : 'N/A'; ?></b></span>
</div>
        <div class="mamadas-list card-panel">
            <b>Últimas mamadas:</b>
<?php
// Comparação entre as duas últimas mamadas
if (count($mamadas) >= 2) {
    $ultima = strtotime($mamadas[0]['data_hora']);
    $penultima = strtotime($mamadas[1]['data_hora']);
    $diferenca = abs($ultima - $penultima);
    echo '<div class="chip-mui grey" style="margin-bottom:8px;"><i class="material-icons left" style="font-size:18px;">compare_arrows</i> Intervalo entre as 2 últimas: <b>' . tempo_humano($diferenca) . '</b></div>';
}
?>
            <ul class="collection">
                <?php
                $proxima = null;
$editando = isset($_GET['edit']) ? intval($_GET['edit']) : null;
$count = 0;
foreach ($mamadas as $m) {
    $count++;
    // Garante formato correto para datetime-local ao editar
$dt = '';
if (strpos($m['data_hora'], 'T') !== false) {
    $dt = $m['data_hora'];
} else {
    $dt = str_replace(' ', 'T', $m['data_hora']);
}
// Se faltar segundos, completa
if (strlen($dt) === 16) { /* Y-m-dTH:i */ }
else if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $dt)) { $dt = substr($dt,0,16); }
    $tipo = $m['tipo'] === 'materno' ? 'Leite Materno' : 'Fórmula';
    $extraClass = $count > 5 ? ' hidden-mamada' : '';
    echo '<li class="collection-item' . $extraClass . '" style="position:relative;">';
    // Formulário de edição inline
    if ($editando === intval($m['id'])) {
        echo '<form method="POST" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;" autocomplete="off">';
        echo '<input type="hidden" name="id" value="' . $m['id'] . '"><input type="hidden" name="acao" value="editar">';
        echo '<select name="tipo" style="min-width:90px;">';
        echo '<option value="materno"'.($m['tipo']==='materno'?' selected':'').'>Leite Materno</option>';
        echo '<option value="formula"'.($m['tipo']==='formula'?' selected':'').'>Fórmula</option>';
        echo '</select>';
        echo '<input type="number" name="quantidade" min="1" value="' . $m['quantidade'] . '" style="width:70px;">';
        echo '<input type="datetime-local" name="data_hora" value="' . htmlspecialchars($dt) . '" style="width:170px;">';
        echo '<button class="btn-flat" type="submit" title="Salvar"><i class="material-icons green-text">check</i></button>';
        echo '<a href="index.php" class="btn-flat" title="Cancelar"><i class="material-icons red-text">close</i></a>';
        echo '</form>';
    } else {
        echo '<span class="badge">' . $m['quantidade'] . ' ml</span>';
        echo '<b>' . $tipo . '</b> <br><small>';
        echo date('d/m/Y H:i', strtotime($m['data_hora']));
        if ($proxima) {
            $t = strtotime($m['data_hora']) - strtotime($proxima);
            if ($t > 0) {
                echo ' <span class="grey-text">(' . tempo_humano($t) . ' após)</span>';
            }
        }
        echo '</small>';
        // Botões editar/excluir abaixo
        echo '<div style="margin-top:8px;display:flex;justify-content:center;gap:12px;">';
        echo '<a href="?edit=' . $m['id'] . '" class="btn-flat" title="Editar"><i class="material-icons">edit</i></a>';
        echo '<form method="POST" style="display:inline" onsubmit="return confirm(\'Excluir este registro?\');">';
        echo '<input type="hidden" name="id" value="' . $m['id'] . '"><input type="hidden" name="acao" value="excluir">';
        echo '<button class="btn-flat" type="submit" title="Excluir"><i class="material-icons red-text">delete</i></button>';
        echo '</form>';
        echo '</div>';
    }
    echo '</li>';
    $proxima = $m['data_hora'];
}
                ?>
            </ul>
            <?php if (count($mamadas) > 5): ?>
            <button id="mostrar-mais-mamadas" class="btn-flat blue-text" style="margin-top:6px;">Mais</button>
            <?php endif; ?>
        </div>
    </div>
    <div style="max-width:430px;margin:32px auto 0 auto;">
        <div id="painel-status-bebe">
            <span class="status-icone" id="status-icone">🔄</span>
            <span class="status-titulo">Status do bebê</span><br>
            <span class="status-info" id="status-txt">Carregando...</span>
            <div id="status-detalhes"></div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
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
            // Estilo iOS: ícones SF Symbols-like
            if (data.alertas && data.alertas.length > 0) {
                painel.className = 'atencao';
                icone.textContent = '⚠️'; // Pode ser substituído por SVG se desejar
            } else {
                painel.className = 'tudocerto';
                icone.textContent = '✔️'; // iOS usa checkmark simples
            }
            statusTxt.textContent = data.status;
            let html = `<span style='display:inline-block;margin-bottom:2px;'><b>Total de mamadas hoje:</b> <span style='font-weight:600;'>${data.total_mamadas_dia}</span></span><br>`;
            if (data.intervalo_medio_horas !== null) {
                html += `<span style='display:inline-block;margin-bottom:2px;'><b>Intervalo médio:</b> <span style='font-weight:600;'>${data.intervalo_medio_horas} h</span></span><br>`;
            }
            if (data.alertas && data.alertas.length > 0) {
                html += '<ul>' + data.alertas.map(a => `<li>${a}</li>`).join('') + '</ul>';
            }
            detalhes.innerHTML = html;
            painel.style.display = 'block';
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
document.addEventListener('DOMContentLoaded', function() {
    atualizarPainelStatusBebe();

        var elems = document.querySelectorAll('select');
        M.FormSelect.init(elems);
        setTimeout(function(){
            var elems = document.querySelectorAll('input[type=datetime-local]');
            M.updateTextFields();
        }, 100);
        var btn = document.getElementById('mostrar-mais-mamadas');
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.hidden-mamada').forEach(function(el) {
                    el.style.display = 'list-item'; // Garante que <li> apareça corretamente
                });
                btn.style.display = 'none';
            });
        }
    });
    </script>
</body>
</html>
