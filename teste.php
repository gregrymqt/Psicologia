<?php


use Dompdf\Dompdf;
use Dompdf\Options;

// Relatório do sistema
$report = [
    'PHP Version' => phpversion(),
    'Server API' => php_sapi_name(),
    'GD Support' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
    'GD Info' => function_exists('gd_info') ? gd_info() : 'Not available',
    'Memory Limit' => ini_get('memory_limit'),
    'Max Execution Time' => ini_get('max_execution_time'),
    
    'Image Path' => realpath($logoPath),
    'Image Size' => filesize($logoPath).' bytes',
    'Image Permissions' => substr(decoct(fileperms($logoPath)), -4),
    'Free Disk Space' => round(disk_free_space(__DIR__)/1024/1024).' MB free'
];

header('Content-Type: text/plain');
print_r($report);
require_once 'vendor/autoload.php';



// 1. Configuração de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Caminho da imagem
$logoPath = __DIR__ . '/img/marcaDaguaLu.jpeg';

// 3. Verificações robustas
if (!file_exists($logoPath)) {
    die("ERRO: Imagem não encontrada em: " . realpath($logoPath));
}

if (!is_readable($logoPath)) {
    die("ERRO: Sem permissão para ler a imagem. Permissões: " . substr(decoct(fileperms($logoPath)), -4));
}

// 4. Codificação base64 com verificação adicional
try {
    $imageData = file_get_contents($logoPath);
    if ($imageData === false) {
        throw new Exception("Falha ao ler o arquivo de imagem");
    }
    
    $mime = mime_content_type($logoPath);
    if (!$mime) {
        $mime = 'image/jpeg'; // Fallback para JPG se mime_content_type falhar
    }
    
    $base64 = 'data:'.$mime.';base64,'.base64_encode($imageData);
} catch (Exception $e) {
    die("ERRO no processamento da imagem: " . $e->getMessage());
}

// 5. Configuração do DomPDF com opções otimizadas
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('debugKeepTemp', true);
$options->set('tempDir', __DIR__ . '/tmp'); // Diretório temporário dedicado
$options->set('fontCache', __DIR__ . '/fonts'); // Cache de fontes
$options->set('defaultFont', 'Arial');

// 6. HTML seguro com fallback
$html = '
 <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; padding: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .underline { text-decoration: underline; }
                    p { margin-bottom: 15px; }
    .logo {
                height: 200px;
                width: auto;
                max-width: 200px;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                opacity: 0.2;
            }
                
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>ATESTADO PSICOLÓGICO</h2>
                </div>
                
                <p>' . $nomeUsuario . '<br>
                Psicóloga – CRP ' . $crpUsuario . '<br>
                E-mail: ' . $emailUsuario . '</p>
                
                <p>Atesto, para os devidos fins, que o(a) Sr.(a) <span class="underline">' . $nome_paciente . '</span>, 
                esteve em atendimento psicológico nesta data, das ' . date("H:i", strtotime($hora_inicio)) . ' 
                às ' . date("H:i", strtotime($hora_fim)) . '.</p>
                
                <p>Motivo do atendimento (respeitando o sigilo profissional):</p>
                <p>' . nl2br($motivo) . '</p>
                
                <p>Recomendo que, o(a) paciente poderá retornar às suas atividades habituais em ' . $retorno_formatado . '.<br>
                E sugiro que o mesmo seja reavaliado por mim e demais profissionais de saúde que possam estar acompanhando este caso.</p>
                
                <p>Local: ' . $local . '<br>
                Data: ' . $data_formatada . '</p><br><br>
    <img class="logo" src="'.$base64.'" alt="Logo">
    
    
    <!-- Fallback visual -->
    <div class="fallback" style="display: none;">
        [Imagem não carregada: '.basename($logoPath).']
    </div>
</body>
</html>';

// 7. Criação e renderização do PDF
$dompdf = new Dompdf($options);

try {
    // Pré-processamento
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    
    // Renderização com timeout aumentado
    set_time_limit(120); // 2 minutos para renderização
    $dompdf->render();
    
    // Limpeza de buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Saída do PDF
    $dompdf->stream(
        "documento_".date('Ymd_His').".pdf",
        [
            'Attachment' => false,
            'compress' => 1
        ]
    );
    
    exit;
    
} catch (Exception $e) {
    // Log detalhado do erro
    $errorLog = date('[Y-m-d H:i:s]') . " ERRO: " . $e->getMessage() . "\n";
    $errorLog .= "Trace: " . $e->getTraceAsString() . "\n\n";
    file_put_contents(__DIR__ . '/pdf_errors.log', $errorLog, FILE_APPEND);
    
    die("Falha crítica ao gerar PDF. Detalhes foram registrados no log.");
}

// 8. Verificação pós-execução (apenas para debug)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        file_put_contents(
            __DIR__ . '/shutdown_errors.log',
            print_r($error, true),
            FILE_APPEND
        );
    }
});