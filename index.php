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
// Últimas mamadas
$stmt = $db->query('SELECT * FROM mamadas ORDER BY data_hora DESC LIMIT 20');
$mamadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Totais do dia
$hoje = date('Y-m-d');
$stmt = $db->prepare('SELECT tipo, SUM(quantidade) as total FROM mamadas WHERE date(data_hora) = ? GROUP BY tipo');
$stmt->execute([$hoje]);
$totais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
// Totais últimas 24h
$stmt = $db->prepare('SELECT tipo, SUM(quantidade) as total FROM mamadas WHERE data_hora >= datetime("now", "-1 day") GROUP BY tipo');
$stmt->execute();
$totais24h = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
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
// Média de tempo entre mamadas (todas)
$stmt = $db->query('SELECT data_hora FROM mamadas ORDER BY data_hora DESC LIMIT 10');
$datas = $stmt->fetchAll(PDO::FETCH_COLUMN);
$intervalos = [];
for ($i = 1; $i < count($datas); $i++) {
    $d1 = strtotime($datas[$i-1]);
    $d2 = strtotime($datas[$i]);
    $intervalos[] = abs($d1 - $d2);
}
$media_intervalo = count($intervalos) ? array_sum($intervalos)/count($intervalos) : 0;
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
.chip-mui.blue { background:#e3f2fd; color:#1976d2; }
.chip-mui.green { background:#e8f5e9; color:#388e3c; }
.chip-mui.amber { background:#fff8e1; color:#ff8f00; }
.chip-mui.grey { background:#ececec; color:#333; }
.mamadas-list { margin-top: 12px; }
.collection-item { padding: 4px 8px 2px 8px; font-size: 0.98em; display: flex; align-items: center; justify-content: space-between; min-height: 38px; }
.card-panel { margin: 10px 0 6px 0; padding: 12px 16px; border-radius: 14px; }
h4 { margin-bottom: 10px; font-size: 1.5em; }
small { font-size: .93em; }

    </style>
</head>
<body>
    <div class="container">
        <h4 class="center-align">Registro de Mamadas</h4>
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
    <span class="chip-mui blue"><i class="material-icons left" style="font-size:18px;">local_hospital</i> Materno: <b style="margin-left:2px;"><?php echo $totais['materno'] ?? 0; ?>ml</b></span>
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
foreach ($mamadas as $m) {
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
    echo '<li class="collection-item" style="position:relative;">';
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
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var elems = document.querySelectorAll('select');
        M.FormSelect.init(elems);
        setTimeout(function(){
            var elems = document.querySelectorAll('input[type=datetime-local]');
            M.updateTextFields();
        }, 100);
    });
    </script>
</body>
</html>
