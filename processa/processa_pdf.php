<?php
// 1. INICIAR SESSÃO (DEVE SER SEMPRE A PRIMEIRA LINHA)
ob_start();
// 2. INCLUIR DEPENDÊNCIAS
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
require_once 'C:/xampp/htdocs/TiaLu/processa/processa_Site.php';
require_once __DIR__ . '/../vendor/autoload.php'; // ou o caminho correto$vali = new Vali();

use Dompdf\Dompdf;
use Dompdf\Options;

class ProcessaPdf
{
    private $logoPath;
    private $base64;
    private $options;
    private  $basePath;


    public function __construct()
    {
        $this->configurarImg(); // Configura automaticamente ao instanciar
        $this->basePath = realpath('C:/xampp/htdocs/TiaLu/caminho/pdf');
        // Garante que o diretório base existe
        if (!file_exists( $this->basePath)) {
            if (!mkdir( $this->basePath, 0755, true)) {
                throw new Exception("Não foi possível criar o diretório para PDFs");
            }
        }

    }
    public function getbasePath(){
        return $this->basePath;
    }
    public function configurarImg()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        // 2. Caminho da imagem
        $this->logoPath = 'C:/xampp/htdocs/TiaLu/img/marcaDaguaLu.jpeg';
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
            if (!$mime) {
                $mime = 'image/jpeg'; // Fallback para JPG se mime_content_type falhar
            }
            $this->base64 = 'data:' . $mime . ';base64,' . base64_encode($imageData);
        } catch (Exception $e) {
            die("ERRO no processamento da imagem: " . $e->getMessage());
        }
        // 5. Configuração do DomPDF com opções otimizadas
        $this->options = new Options();
        $this->options->set('isRemoteEnabled', true);
        $this->options->set('isPhpEnabled', true);
        $this->options->set('isHtml5ParserEnabled', true);
        $this->options->set('debugKeepTemp', true);
        $this->options->set('tempDir', __DIR__ . '/tmp'); // Diretório temporário dedicado
        $this->options->set('fontCache', __DIR__ . '/fonts'); // Cache de fontes
        $this->options->set('defaultFont', 'Arial');
        // 5. SE CHEGOU ATÉ AQUI, USUÁRIO ESTÁ LOGADO
// Agora podemos processar a página normalmente
    }
    public function gerarAtestado($identificacao)
    {
        // Validação do ID do paciente
        if (!is_numeric($identificacao)) {
            die("ID do paciente inválido");
        }
         $camposObrigatorios = ['hora_inicio', 'hora_fim', 'motivo', 'retorno', 'data', 'local'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                die("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }
        // Processa os dados do formulário
        $hora_inicio = htmlspecialchars($_POST["hora_inicio"] ?? '');
        $hora_fim = htmlspecialchars($_POST["hora_fim"] ?? '');
        $motivo = htmlspecialchars($_POST["motivo"] ?? '');
        $retorno = htmlspecialchars($_POST["retorno"] ?? '');
        $data = htmlspecialchars($_POST["data"] ?? '');
        $local = htmlspecialchars($_POST["local"] ?? '');
        if (!strtotime($retorno) || !strtotime($data)) {
            die("Data inválida. Por favor, verifique as datas informadas.");
        }
        // Formata as datas
        $retorno_formatado = date("d/m/Y", strtotime($retorno));
        $data_formatada = date("d/m/Y", strtotime($data));

        $nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
        $crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');
        $emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8');
        // Gera o HTML do atestado
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
                           
                           <p>' . htmlspecialchars($nomeUsuario) . '<br>
                           Psicóloga – CRP ' . htmlspecialchars($crpUsuario) . '<br>
                           E-mail: ' . htmlspecialchars($emailUsuario) . '</p>
                           
                           <p>Atesto, para os devidos fins, que o(a) Sr.(a) <span class="underline">' . $_SESSION['resultado_consulta']['NOME_COMPLETO'] . '</span>, 
                           esteve em atendimento psicológico nesta data, das ' . date("H:i", strtotime($hora_inicio)) . ' 
                           às ' . date("H:i", strtotime($hora_fim)) . '.</p>
                           
                           <p>Motivo do atendimento (respeitando o sigilo profissional):</p>
                           <p>' . nl2br($motivo) . '</p>
                           
                           <p>Recomendo que, o(a) paciente poderá retornar às suas atividades habituais em ' . $retorno_formatado . '.<br>
                           E sugiro que o mesmo seja reavaliado por mim e demais profissionais de saúde que possam estar acompanhando este caso.</p>
                           
                           <p>Local: ' . $local . '<br>
                           Data: ' . htmlspecialchars($data_formatada) . '</p><br><br>
               <img class="logo" src="' . $this->base64 . '" alt="Logo">              
               
               <!-- Fallback visual -->
               <div class="fallback" style="display: none;">
                   [Imagem não carregada: ' . basename($this->logoPath) . ']
               </div>
           </body>
           </html>';

        // Gera o nome do arquivo
        $filename = "atestado_" . preg_replace('/[^a-z0-9]/i', '_', $_SESSION['resultado_consulta']['NOME_COMPLETO']) . "_" . date('Y-m-d') . ".pdf";
        $filepath = $this->getbasePath() . DIRECTORY_SEPARATOR . $filename;

        // Cria o PDF
        $dompdf = new Dompdf($this->options);

        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

        // Armazena no banco de dados
            $this->salvarNoBanco($filepath, $identificacao, $nomeUsuario, 'atestado');


            // 2. Gera HTML com o botão de download
            echo $this->generatePdfViewerHtml($dompdf->output(), $filename);
            exit;
        } catch (Exception $e) {
            // Remove o arquivo se houve erro
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            error_log("Erro ao gerar PDF: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Erro ao gerar documento"
            ];
        }
    }

    public function gerarComparecimento($identificacao)
    {
        // Validação do ID do paciente
        if (!is_numeric($identificacao)) {
            die("ID do paciente inválido");
        }

        $hora_inicio = htmlspecialchars($_POST["horario_inicio"] ?? '');
        $hora_fim = htmlspecialchars($_POST["horario_fim"] ?? '');
        $local = htmlspecialchars($_POST["local"] ?? '');
        $data_atendimento = htmlspecialchars($_POST["data_atendimento"] ?? '');
        $camposObrigatorios = ['horario_inicio', 'horario_fim', 'local', 'data_atendimento'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                die("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }
        if (!strtotime($_SESSION['resultado_consulta']['DATA_NASCIMENTO']) || !strtotime($data_atendimento)) {
            die("Data inválida. Por favor, verifique as datas informadas.");
        }
        // Formatar datas
        $data_nasc_formatada = date("d/m/Y", strtotime($_SESSION['resultado_consulta']['DATA_NASCIMENTO']));
        $data_atend_formatada = date("d/m/Y", strtotime($data_atendimento));

        $nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
        $crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');

        // Gerar HTML da declaração
        $html = '
    <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { 
                    font-family: DejaVu Sans, sans-serif; 
                    line-height: 1.6; 
                    padding: 50px;
                    font-size: 14px;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px;
                    font-weight: bold;
                    font-size: 16px;
                }
                .underline { 
                    text-decoration: underline;
                    display: inline-block;
                    min-width: 200px;
                }
                .logo-container {
        text-align: center;
        margin: 20px 0;
        width: 100%;
    }  
    /* Estilo da logo */
    .logo {
        height: 200px;
        width: auto;
        max-width: 200px;
        display: block;
        margin: 0 auto;
    }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            opacity: 0.2;
        }
                .signature-line {
                    width: 300px;     
                          border-top: 1px solid black;   
                                    margin: 40px auto 0;           
                                        padding-top: 5px;               
                                             text-align: center; }
                                    .navbar-brand img {  height: 40px;      width: auto;   transition: all 0.3s ease; }

            </style>
        </head>
        <body>
            <div class="header">
                DECLARAÇÃO DE COMPARECIMENTO
            </div>

            <p>Declaro, para os devidos fins, que o(a) Sr.(a) <span class="underline">' . htmlspecialchars($_SESSION['resultado_consulta']['NOME_COMPLETO']) . '</span>,<br>
            nascido(a) em ' . htmlspecialchars($data_nasc_formatada) . ', compareceu ao atendimento psicológico nesta data,<br>
            no horário das ' . date("H:i", strtotime($hora_inicio)) . ' às ' . date("H:i", strtotime($hora_fim)) . '.</p>
            
            <p>Esta declaração é emitida para comprovação de presença em consulta.</p>
            
            <p>Local: ' . htmlspecialchars($local) . '<br>
            Data: ' . htmlspecialchars($data_atend_formatada) . '</p>
            
            <div class="signature-line"></div>
            
            <div class="footer">
                ' . htmlspecialchars($nomeUsuario) . '<br>
                CRP ' . htmlspecialchars($crpUsuario) . '
            </div><br><br>
                       <div class="logo-container">
    <img class="logo" src="' . $this->base64 . '" alt="Logo">
</div>
           
           
           <!-- Fallback visual -->
           <div class="fallback" style="display: none;">
               [Imagem não carregada: ' . basename($this->logoPath) . ']
           </div>

        </body>
        </html>';
        $filename = "comparecimento_" . preg_replace('/[^a-z0-9]/i', '_', $_SESSION['resultado_consulta']['NOME_COMPLETO']) . "_" . date('Y-m-d') . ".pdf";
        $filepath = $this->getbasePath() . DIRECTORY_SEPARATOR . $filename;

        // Cria o PDF
        $dompdf = new Dompdf($this->options);

     
        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();


            // Armazena no banco de dados
            $this->salvarNoBanco($filepath, $identificacao, $nomeUsuario, 'comparecimento');

            // 2. Gera HTML com o botão de download
            echo $this->generatePdfViewerHtml($dompdf->output(), $filename);
            exit;


        } catch (Exception $e) {
            // Remove o arquivo se houve erro
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            error_log("Erro ao gerar PDF: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Erro ao gerar documento"
            ];
        }
    }

    public function gerarRecibo($identificacao)
    {
    
     if (!is_numeric($identificacao)) {
            die("ID do paciente inválido");
        }

         $vali = new Vali();

        $cpf_paciente = $vali->formatarCPF($_SESSION['resultado_consulta']['CPF']);
        date_default_timezone_set('America/Sao_Paulo');
        // Obtém a data e hora atual
        $dataSaoPaulo = new DateTime();
        // Formata a data e hora (opcional)
        $dataFormatada = $dataSaoPaulo->format('d/m/Y');
        $valor_consulta = 250.00;
        $valor_extenso = isset($veri) && method_exists($veri, 'valorPorExtenso')
            ? $veri->valorPorExtenso($valor_consulta)
            : "duzentos e cinquenta reais";
        $valor_formatado = "R$ " . number_format($valor_consulta, 2, ',', '.');

        $nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
        $crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');
        $emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8');
        $cpfUsuario = htmlspecialchars($_SESSION['usuario']['cpf'] ?? 'cpf não cadastrado', ENT_QUOTES, 'UTF-8');


        ;
        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Recibo de Psicologia</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .recibo-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
                .recibo { margin-bottom: 30px; }
                .header { text-align: center; margin-bottom: 20px; }
                .dados-psicologo, .dados-paciente { margin-bottom: 15px; line-height: 1.6; }
                .separador { text-align: center; margin: 20px 0; font-size: 24px; color: #555; }
                .data-assinatura { margin-top: 50px; text-align: right; }
                .assinatura { margin-top: 80px; border-top: 1px solid #000; padding-top: 5px; width: 300px; float: right; }
.logo {
            height: 95px;
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
        }                .valor-extenso { font-style: italic; color: #444; }
                .download-btn {
                    display: block;
                    width: 200px;
                    margin: 20px auto;
                    padding: 10px;
                    background-color: #4CAF50;
                    color: white;
                    text-align: center;
                    text-decoration: none;
                    border-radius: 5px;
                }       
                     .navbar-brand img {  height: 40px;      width: auto;   transition: all 0.3s ease; }

            </style>
        </head>
        <body>
         <div class="recibo-container">
                <div class="recibo">
                    <div class="header">
                        <h1>RECIBO DE PAGAMENTO – PSICÓLOGA</h1>
                    </div>
                    
                    <div class="dados-psicologo">
                        <p><strong>Nome da Psicóloga:</strong> ' . htmlspecialchars($nomeUsuario) . '</p>
                        <p><strong>CPF:</strong> ' . htmlspecialchars($cpfUsuario) . '</p>
                        <p><strong>CRP:</strong> ' . htmlspecialchars($crpUsuario) . '</p>
                        <p><strong>E-mail:</strong> ' . htmlspecialchars($emailUsuario) . '</p>
                    </div>
                    
                    <div class="separador">–––––––</div>
                    
                    <div class="header">
                        <h2>RECIBO DE PAGAMENTO</h2>
                    </div>
                    
                    <div class="corpo-recibo">
                        <p>Recebi de <strong>' . htmlspecialchars($_SESSION['resultado_consulta']['NOME_COMPLETO'], ENT_QUOTES) . '</strong></p>
                        <div class="dados-paciente">
                            <p>CPF: ' . htmlspecialchars($cpf_paciente, ENT_QUOTES) . '</p>
                        </div>
                        <p>o valor de <strong>' . htmlspecialchars($valor_formatado) . '</strong> (<span class="valor-extenso">' . htmlspecialchars($valor_extenso) . '</span>) referente a uma sessão de psicoterapia individual, realizada na data ' . htmlspecialchars($valor_extenso) . '</p>
                    </div>
                    
                    <div class="data-assinatura">
                        <p>' . htmlspecialchars($dataFormatada) . '</p>
                        <div class="assinatura">
                            <p>' . htmlspecialchars($nomeUsuario) . '</p>
                            <p>CRP ' . htmlspecialchars($crpUsuario) . '</p>
                        </div>
                    </div>
        
                    <div class="footer">
                        <p>Este recibo é emitido para fins de comprovação de pagamento por serviços prestados na área da Psicologia, conforme legislação vigente.</p>
                        <p>Recibo gerado em ' . htmlspecialchars($dataFormatada) . '</p>
                    </div>
                </div>
            </div>
             </div><br><br>
                       <img class="logo" src="' . $this->base64 . '" alt="Logo">
           
           
           <!-- Fallback visual -->
           <div class="fallback" style="display: none;">
               [Imagem não carregada: ' . basename($this->logoPath) . ']
           </div>
        </body>
        </html>';
        $filename = "recibo_" . preg_replace('/[^a-z0-9]/i', '_', $_SESSION['resultado_consulta']['NOME_COMPLETO']) . "_" . date('Y-m-d') . ".pdf";
        $filepath = $this->getbasePath(). DIRECTORY_SEPARATOR . $filename;

        // Cria o PDF
        $dompdf = new Dompdf($this->options);

       
        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Salva o PDF no servidor

            // Armazena no banco de dados
            $this->salvarNoBanco($filepath, $identificacao, $nomeUsuario, 'recibo');


            // 2. Gera HTML com o botão de download
            echo $this->generatePdfViewerHtml($dompdf->output(), $filename);
            exit;

        } catch (Exception $e) {
            // Remove o arquivo se houve erro
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            error_log("Erro ao gerar PDF: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Erro ao gerar documento"
            ];
        }
    }
    
    private function salvarNoBanco($filepath, $idPaciente, $usuario, $tipo)
    {
        $conn = Conexao::getConnection();
        $conn->beginTransaction();
        $erros = [];
        $cpf=$_SESSION['resultado_consulta']['CPF'];
        $nome_paciente=$_SESSION['resultado_consulta']['NOME_COMPLETO'];

        try {
            $stmt = $conn->prepare("
                    INSERT INTO anamnese_pdfs 
                    (conteudo_pdf, id_paciente, tipo_documento, usuario_criacao, cpf_paciente, nome_paciente) 
                    VALUES (:caminho, :id_paciente, :tipo, :usuario , :cpf, :nome_paciente)
                ");

            $stmt->execute([
                ':caminho' => $filepath,
                ':id_paciente' => $idPaciente,
                ':tipo' => $tipo,
                ':usuario' => $usuario,
                ':cpf' => $cpf,
                ':nome_paciente' => $nome_paciente
            ]);

            $conn->commit();
            return true;

        } catch (PDOException $e) {
            $conn->rollBack();
            $erros[] = "Erro no banco de dados: " . $e->getMessage();
        } catch (Exception $e) {
            $conn->rollBack();
            $erros[] = $e->getMessage();
        }
        // Se houve erros, mostra eles
        foreach ($erros as $erro) {
            echo "<p>Erro: $erro</p>";
        }
    }
    
    private function generatePdfViewerHtml($pdfContent, $filename)
    {
        $base64Pdf = base64_encode($pdfContent);

        return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <title>Visualizar Recibo</title>
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
                   <i class="bi bi-download"></i> Baixar Recibo
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