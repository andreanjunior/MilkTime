<?php
// db.php: inicializa e conecta ao banco SQLite, cria tabela se não existir
function getDb() {
    $db = new PDO('sqlite:' . __DIR__ . '/mamadas.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Cria tabela se não existir
    $db->exec('CREATE TABLE IF NOT EXISTS mamadas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tipo TEXT NOT NULL,
        quantidade INTEGER NOT NULL,
        data_hora DATETIME NOT NULL
    )');
    // Limpeza automática de registros antigos (>48 horas)
    $threshold = date('Y-m-d H:i:s', strtotime('-48 hours'));
    $db->prepare('DELETE FROM mamadas WHERE data_hora < ?')->execute([$threshold]);
    return $db;
}
?>
