<?php

require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
function exibirResultados($resultados) {
    if (empty($resultados)) {
        echo '<div class="alert alert-info">Nenhum resultado encontrado.</div>';
        return;
    }
    
    foreach ($resultados as $paciente) {
        echo '<div class="card mb-3 paciente-card">';
        echo '<div class="card-header">';
        echo '<h5>' . htmlspecialchars($paciente['nome_completo']) . '</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<div class="row">';
        
        // Informações básicas
        echo '<div class="col-md-6">';
        echo '<p><strong>Idade:</strong> ' . htmlspecialchars($paciente['idade']) . '</p>';
        echo '<p><strong>Gênero:</strong> ' . htmlspecialchars($paciente['genero']) . '</p>';
        echo '<p><strong>Telefone:</strong> ' . htmlspecialchars($paciente['telefone']) . '</p>';
        echo '</div>';
        
        // Informações adicionais
        echo '<div class="col-md-6">';
        echo '<p><strong>Motivo da Procura:</strong> ' . nl2br(htmlspecialchars($paciente['motivo_procura'])) . '</p>';
        echo '<p><strong>Última Atualização:</strong> ' . htmlspecialchars($paciente['data_atualizacao']) . '</p>';
        echo '</div>';
        
        echo '</div>';
        echo '<button class="btn btn-sm btn-outline-primary ver-detalhes" data-id="' . $paciente['id'] . '">Ver Detalhes Completos</button>';
        echo '</div>';
        echo '</div>';
    }
}


class Vali{


    

 public function formatarCPF($cpf)
{
    if (empty($cpf))
        return 'Não informado';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function valorPorExtenso($valor = 0)
{
    $singular = ["centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão"];
    $plural = ["centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhões"];

    $c = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
    $d = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    $d10 = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"];
    $u = ["", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];

    $z = 0;
    $rt = "";

    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);

    for ($i = 0; $i < count($inteiro); $i++) {
        for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++) {
            $inteiro[$i] = "0" . $inteiro[$i];
        }
    }

    $fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);

    for ($i = 0; $i < count($inteiro); $i++) {
        $valor = $inteiro[$i];
        $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
        $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
        $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

        $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
        $t = count($inteiro) - 1 - $i;
        $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";

        if ($valor == "000") {
            $z++;
        } elseif ($z > 0) {
            $z--;
        }

        if (($t == 1) && ($z > 0) && ($inteiro[0] > 0)) {
            $r .= (($z > 1) ? " de " : "") . $plural[$t];
        }

        if ($r) {
            $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
        }
    }

    $rt = trim($rt);
    return $rt ? ucfirst($rt) : "Zero reais";
}



function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}
function validarData($data, $formato = 'Y-m-d')
{
    $d = DateTime::createFromFormat($formato, $data);
    return $d && $d->format($formato) === $data;
}
function sanitizarTexto($texto)
{
    return htmlspecialchars(strip_tags(trim($texto)), ENT_QUOTES, 'UTF-8');
}
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function validarTelefone($telefone)
{
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    return (strlen($telefone) >= 10 && strlen($telefone) <= 11);
}
}