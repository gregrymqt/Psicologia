<?php
session_start();
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';


// Verifica se pelo menos um filtro está habilitado (Passo 5)
$filtros_habilitados = false;
$filtros = [];

foreach ($_POST as $key => $value) {
    if (strpos($key, '_habilitado') !== false && $value == 'on') {
        $filtros_habilitados = true;
        $campo = str_replace('_habilitado', '', $key);
        $filtros[$campo] = $_POST[$campo] ?? null;
    }
}

if (!$filtros_habilitados) {
    $_SESSION['erro'] = "Pelo menos um filtro deve ser habilitado para realizar a consulta.";
    header('Location: C:/xampp/htdocs/TiaLu/TiaLu.php');
    exit;
}

// Construção da consulta SQL dinâmica
$sql = "SELECT * FROM anamnese WHERE 1=1";
$params = [];

foreach ($filtros as $campo => $valor) {
    switch ($campo) {
        case 'filtro_nome':
            $sql .= " AND nome_completo LIKE ?";
            $params[] = "%$valor%";
            break;
        case 'filtro_idade':
            $idade_min = $_POST['idade_min'] ?? 0;
            $idade_max = $_POST['idade_max'] ?? 120;
            $sql .= " AND idade BETWEEN ? AND ?";
            $params[] = $idade_min;
            $params[] = $idade_max;
            break;
        // Adicione mais casos para outros filtros
    }
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $_SESSION['resultados_consulta'] = $resultados;
    header('Location: C:/xampp/htdocs/TiaLu/TiaLu.php');
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao consultar banco de dados: " . $e->getMessage();
    header('Location: C:/xampp/htdocs/TiaLu/TiaLu.php');
}