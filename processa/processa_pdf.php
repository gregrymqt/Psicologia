<?php
// 1. INICIAR SESSÃO (DEVE SER SEMPRE A PRIMEIRA LINHA)
ob_start();
session_start();
// 2. INCLUIR DEPENDÊNCIAS
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
require_once 'C:/xampp/htdocs/TiaLu/processa/processa_Site.php';
require_once __DIR__ . '/../vendor/autoload.php'; // ou o caminho correto$vali = new Vali();

use Dompdf\Dompdf;
use Dompdf\Options;

class ProcessaPdfs
{
    private $logoPath;
    private $base64;
    private $options;
    private $basePath;
    public function __construct()
    {
        $this->configurarImg(); // Configura automaticamente ao instanciar
         $this->basePath = realpath('/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/caminho/pdf');
        // Garante que o diretório base existe
        if (!file_exists($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true)) {
                throw new Exception("Não foi possível criar o diretório para PDFs");
            }
        }
    }
    public function configurarImg()
    {
        $this->logoPath = '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/img/marcaDaguaLu.jpeg';
        // 3. Verificações robustas
        if (!file_exists($this->logoPath)) {
            die("ERRO: Imagem não encontrada em: " . realpath($this->logoPath));
        }
        if (!is_readable($this->logoPath)) {
            die("ERRO: Sem permissão para ler a imagem. Permissões: " . substr(decoct(fileperms($this->logoPath)), -4));
        }
        // 4. Codificação base64 com verificação adicional
        try {
            $imageData = file_get_contents($this->logoPath);
            if ($imageData === false) {
                throw new Exception("Falha ao ler o arquivo de imagem");
            }
            $mime = mime_content_type($this->logoPath);
            $this->base64 = 'data:' . $mime . ';base64,' . base64_encode($imageData);
        } catch (Exception $e) {
            die("ERRO no processamento da imagem: " . $e->getMessage());
        }
        // 5. Configuração do DomPDF com opções otimizadas
        $this->options = new Options();
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('isPhpEnabled', true);
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('defaultFont', 'Arial');
        
    }
    public function gerarAtestado($identificacao)
    {
        $this->validarIdPaciente($identificacao);
        $camposObrigatorios = ['hora_inicio', 'hora_fim', 'motivo', 'retorno', 'data', 'local'];
        $this->validarCamposObrigatorios($camposObrigatorios);

        $dados = $this->processarDadosFormulario();
        $html = $this->gerarHtmlAtestado($dados);

        return $this->gerarPdf($html, $identificacao, 'atestado');
    }

    public function gerarComparecimento($identificacao)
    {
        $this->validarIdPaciente($identificacao);
        $camposObrigatorios = ['horario_inicio', 'horario_fim', 'local', 'data_atendimento'];
        $this->validarCamposObrigatorios($camposObrigatorios);

        $dados = $this->processarDadosFormulario();
        $html = $this->gerarHtmlComparecimento($dados);

        return $this->gerarPdf($html, $identificacao, 'comparecimento');
    }

    public function gerarRecibo($identificacao)
    {
        $this->validarIdPaciente($identificacao);
        
        $vali = new Vali();
        $cpf_paciente = $vali->formatarCPF($_SESSION['resultado_consulta']['CPF']);
        $dados = $this->processarDadosRecibo($cpf_paciente);
        $html = $this->gerarHtmlRecibo($dados);

        return $this->gerarPdf($html, $identificacao, 'recibo');
    }

    private function gerarPdf($html, $idPaciente, $tipoDocumento)
    {
        $nomeArquivo = $this->gerarNomeArquivo($tipoDocumento);
        $caminhoArquivo = $this->basePath . DIRECTORY_SEPARATOR . $nomeArquivo;

        try {
            $dompdf = new Dompdf($this->options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents($caminhoArquivo, $dompdf->output());
            $this->salvarNoBanco($caminhoArquivo, $idPaciente, $_SESSION['usuario']['nome'] ?? 'Visitante', $tipoDocumento);

            return $this->generatePdfViewerHtml($dompdf->output(), $nomeArquivo);
        } catch (Exception $e) {
            if (file_exists($caminhoArquivo)) {
                unlink($caminhoArquivo);
            }
            error_log("Erro ao gerar PDF: " . $e->getMessage());
            throw new Exception("Erro ao gerar documento");
        }
    }
    private function validarCamposObrigatorios($campos)
    {
        foreach ($campos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }
    }
    private function processarDadosFormulario()
    {
        return [
            'hora_inicio' => htmlspecialchars($_POST["hora_inicio"] ?? ''),
            'hora_fim' => htmlspecialchars($_POST["hora_fim"] ?? ''),
            'motivo' => htmlspecialchars($_POST["motivo"] ?? ''),
            'retorno' => htmlspecialchars($_POST["retorno"] ?? ''),
            'data' => htmlspecialchars($_POST["data"] ?? ''),
            'local' => htmlspecialchars($_POST["local"] ?? ''),
            'nome_paciente' => $_SESSION['resultado_consulta']['NOME_COMPLETO'],
            'nome_usuario' => htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8'),
            'crp_usuario' => htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8'),
            'email_usuario' => htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8'),
            'data_nascimento' => $_SESSION['resultado_consulta']['DATA_NASCIMENTO'] ?? null
        ];
    }
    private function processarDadosRecibo($cpf_paciente)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $dataAtual = new DateTime();
        
        return [
            'valor_consulta' => 250.00,
            'valor_extenso' => "duzentos e cinquenta reais",
            'valor_formatado' => "R$ " . number_format(250.00, 2, ',', '.'),
            'data_formatada' => $dataAtual->format('d/m/Y'),
            'nome_paciente' => $_SESSION['resultado_consulta']['NOME_COMPLETO'],
            'cpf_paciente' => $cpf_paciente,
            'nome_usuario' => htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8'),
            'crp_usuario' => htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8'),
            'email_usuario' => htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8'),
            'cpf_usuario' => htmlspecialchars($_SESSION['usuario']['cpf'] ?? 'cpf não cadastrado', ENT_QUOTES, 'UTF-8')
        ];
    }
    private function gerarHtmlAtestado($dados)
    {
        $retornoFormatado = date("d/m/Y", strtotime($dados['retorno']));
        $dataFormatada = date("d/m/Y", strtotime($dados['data']));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .underline { text-decoration: underline; }
        p { margin-bottom: 15px; }
        .logo { height: 200px; width: auto; max-width: 200px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; opacity: 0.2; }
    </style>
</head>
<body>
    <div class="header"><h2>ATESTADO PSICOLÓGICO</h2></div>
    
    <p>{$dados['nome_usuario']}<br>Psicóloga – CRP {$dados['crp_usuario']}<br>E-mail: {$dados['email_usuario']}</p>
    
    <p>Atesto, para os devidos fins, que o(a) Sr.(a) <span class="underline">{$dados['nome_paciente']}</span>, 
    esteve em atendimento psicológico nesta data, das {$dados['hora_inicio']} 
    às {$dados['hora_fim']}.</p>
    
    <p>Motivo do atendimento (respeitando o sigilo profissional):</p>
    <p>{$dados['motivo']}</p>
    
    <p>Recomendo que, o(a) paciente poderá retornar às suas atividades habituais em {$retornoFormatado}.<br>
    E sugiro que o mesmo seja reavaliado por mim e demais profissionais de saúde que possam estar acompanhando este caso.</p>
    
    <p>Local: {$dados['local']}<br>Data: {$dataFormatada}</p><br><br>
    <img class="logo" src="{$this->base64}" alt="Logo">
</body>
</html>
HTML;
    }

    private function gerarHtmlComparecimento($dados)
    {
        $dataNascFormatada = date("d/m/Y", strtotime($dados['data_nascimento']));
        $dataAtendFormatada = date("d/m/Y", strtotime($dados['data']));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; padding: 50px; font-size: 14px; }
        .header { text-align: center; margin-bottom: 40px; font-weight: bold; font-size: 16px; }
        .underline { text-decoration: underline; display: inline-block; min-width: 200px; }
        .logo-container { text-align: center; margin: 20px 0; width: 100%; }
        .logo { height: 200px; width: auto; max-width: 200px; display: block; margin: 0 auto; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; opacity: 0.2; }
        .signature-line { width: 300px; border-top: 1px solid black; margin: 40px auto 0; padding-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">DECLARAÇÃO DE COMPARECIMENTO</div>

    <p>Declaro, para os devidos fins, que o(a) Sr.(a) <span class="underline">{$dados['nome_paciente']}</span>,<br>
    nascido(a) em {$dataNascFormatada}, compareceu ao atendimento psicológico nesta data,<br>
    no horário das {$dados['hora_inicio']} às {$dados['hora_fim']}.</p>
    
    <p>Esta declaração é emitida para comprovação de presença em consulta.</p>
    
    <p>Local: {$dados['local']}<br>Data: {$dataAtendFormatada}</p>
    
    <div class="signature-line"></div>
    
    <div class="footer">
        {$dados['nome_usuario']}<br>CRP {$dados['crp_usuario']}
    </div><br><br>
    <div class="logo-container"><img class="logo" src="{$this->base64}" alt="Logo"></div>
</body>
</html>
HTML;
    }

    private function gerarHtmlRecibo($dados)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .recibo-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
        .recibo { margin-bottom: 30px; }
        .header { text-align: center; margin-bottom: 20px; }
        .dados-psicologo, .dados-paciente { margin-bottom: 15px; line-height: 1.6; }
        .separador { text-align: center; margin: 20px 0; font-size: 24px; color: #555; }
        .data-assinatura { margin-top: 50px; text-align: right; }
        .assinatura { margin-top: 80px; border-top: 1px solid #000; padding-top: 5px; width: 300px; float: right; }
        .logo { height: 95px; width: auto; max-width: 200px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; opacity: 0.2; }
        .valor-extenso { font-style: italic; color: #444; }
    </style>
</head>
<body>
    <div class="recibo-container">
        <div class="recibo">
            <div class="header"><h1>RECIBO DE PAGAMENTO – PSICÓLOGA</h1></div>
            
            <div class="dados-psicologo">
                <p><strong>Nome da Psicóloga:</strong> {$dados['nome_usuario']}</p>
                <p><strong>CPF:</strong> {$dados['cpf_usuario']}</p>
                <p><strong>CRP:</strong> {$dados['crp_usuario']}</p>
                <p><strong>E-mail:</strong> {$dados['email_usuario']}</p>
            </div>
            
            <div class="separador">–––––––</div>
            
            <div class="header"><h2>RECIBO DE PAGAMENTO</h2></div>
            
            <div class="corpo-recibo">
                <p>Recebi de <strong>{$dados['nome_paciente']}</strong></p>
                <div class="dados-paciente"><p>CPF: {$dados['cpf_paciente']}</p></div>
                <p>o valor de <strong>{$dados['valor_formatado']}</strong> (<span class="valor-extenso">{$dados['valor_extenso']}</span>) referente a uma sessão de psicoterapia individual, realizada na data {$dados['data_formatada']}</p>
            </div>
            
            <div class="data-assinatura">
                <p>{$dados['data_formatada']}</p>
                <div class="assinatura">
                    <p>{$dados['nome_usuario']}</p>
                    <p>CRP {$dados['crp_usuario']}</p>
                </div>
            </div>

            <div class="footer">
                <p>Este recibo é emitido para fins de comprovação de pagamento por serviços prestados na área da Psicologia, conforme legislação vigente.</p>
                <p>Recibo gerado em {$dados['data_formatada']}</p>
            </div>
        </div>
    </div>
    <img class="logo" src="{$this->base64}" alt="Logo">
</body>
</html>
HTML;
    }
    
    private function gerarNomeArquivo($tipoDocumento)
    {
        $nomePaciente = preg_replace('/[^a-z0-9]/i', '_', $_SESSION['resultado_consulta']['NOME_COMPLETO']);
        return "{$tipoDocumento}_{$nomePaciente}_" . date('Y-m-d') . ".pdf";
    }

    private function salvarNoBanco($filepath, $idPaciente, $usuario, $tipo)
    {
        $conn = Conexao::getConnection();
        
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("
                INSERT INTO anamnese_pdfs 
                (conteudo_pdf, id_paciente, tipo_documento, usuario_criacao, cpf_paciente, nome_paciente) 
                VALUES (:caminho, :id_paciente, :tipo, :usuario, :cpf, :nome_paciente)
            ");

            $stmt->execute([
                ':caminho' => $filepath,
                ':id_paciente' => $idPaciente,
                ':tipo' => $tipo,
                ':usuario' => $usuario,
                ':cpf' => $_SESSION['resultado_consulta']['CPF'],
                ':nome_paciente' => $_SESSION['resultado_consulta']['NOME_COMPLETO']
            ]);

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw new Exception("Erro ao salvar no banco de dados: " . $e->getMessage());
        }
    }

    private function generatePdfViewerHtml($pdfContent, $filename)
    {
        $base64Pdf = base64_encode($pdfContent);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Visualizar Documento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .pdf-container { display: flex; flex-direction: column; height: 100vh; }
        .pdf-toolbar { 
            background: #f5f5f5; 
            padding: 10px; 
            display: flex; 
            justify-content: flex-end;
            border-bottom: 1px solid #ddd;
        }
        .download-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .pdf-viewer {
            flex-grow: 1;
            border: none;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="pdf-toolbar">
            <a href="data:application/pdf;base64,{$base64Pdf}" 
               download="{$filename}" 
               class="download-btn">
               Baixar Documento
            </a>
        </div>
        <iframe class="pdf-viewer" 
                src="data:application/pdf;base64,{$base64Pdf}"></iframe>
    </div>
</body>
</html>
HTML;
    }
}