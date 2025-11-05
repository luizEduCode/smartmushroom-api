<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo'); // Defina seu timezone aqui

echo "<h2>PHP Date/Time Debug</h2>";

// Teste 1: Data atual
$now = new DateTime();
echo "<b>1. new DateTime():</b> " . $now->format('Y-m-d H:i:s') . "<br>";

// Teste 2: Tempo Unix
echo "<b>2. time():</b> " . time() . " (Unix Timestamp)<br>";
echo "<b>   strtotime('now'):</b> " . strtotime('now') . "<br>";

// Teste 3: Variáveis de servidor para data
echo "<b>3. \$_SERVER['REQUEST_TIME_FLOAT']:</b> " . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME_FLOAT']) . "<br>";
echo "<b>   \$_SERVER['REQUEST_TIME']:</b> " . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . "<br>";

// Teste 4: PHP Info (para verificar php.ini e timezone)
// phpinfo(); // Remova ou comente para não expor informações em produção.

?>