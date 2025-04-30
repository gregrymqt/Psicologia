<?php
// 1. INICIAR SESSÃO (DEVE SER SEMPRE A PRIMEIRA LINHA)
ob_start();
session_start();
// 2. INCLUIR DEPENDÊNCIAS
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/includes/conexao.php';
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/includes/funcoes.php';
require_once __DIR__ . '/../vendor/autoload.php'; // ou o caminho correto$vali = new Vali();

use Dompdf\Dompdf;
use Dompdf\Options;


class ProcessaPdfs
{
    private $logoPath;
    private $base64;
    private $options;

    public function __construct()
    {
        $this->configurarImg(); // Configura automaticamente ao instanciar

    }
    public function configurarImg()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        // 2. Caminho da imagem
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
    public function gerarAtestado()
    {
        $camposObrigatorios = ['nome_paciente', 'hora_inicio', 'hora_fim', 'motivo', 'retorno', 'data', 'local'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                die("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }
        // Processa os dados do formulário
        $nome_paciente = htmlspecialchars($_POST["nome_paciente"] ?? '');
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
                           
                           <p>Atesto, para os devidos fins, que o(a) Sr.(a) <span class="underline">' . $nome_paciente . '</span>, 
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
        $dompdf = new Dompdf($this->options);
        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            set_time_limit(120); // 2 minutos para renderização
            $dompdf->render();
            // Limpeza de buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            $dompdf->stream(
                "atestado_" . preg_replace('/[^a-z0-9]/i', '_', $nome_paciente) . "_" . date('Y-m-d') . ".pdf",
                ["Attachment" => true]
            );
            exit;
        } catch (Exception $e) {
            // Log detalhado do erro
            $errorLog = date('[Y-m-d H:i:s]') . " ERRO: " . $e->getMessage() . "\n";
            $errorLog .= "Trace: " . $e->getTraceAsString() . "\n\n";
            file_put_contents(__DIR__ . '/pdf_errors.log', $errorLog, FILE_APPEND);
            die("Falha crítica ao gerar PDF. Detalhes foram registrados no log.");
        }

    }

    public function gerarComparecimento()
    {
        $nome_paciente = htmlspecialchars($_POST["nome_paciente"] ?? '');
        $data_nascimento = htmlspecialchars($_POST["data_nascimento"] ?? '');
        $hora_inicio = htmlspecialchars($_POST["horario_inicio"] ?? '');
        $hora_fim = htmlspecialchars($_POST["horario_fim"] ?? '');
        $local = htmlspecialchars($_POST["local"] ?? '');
        $data_atendimento = htmlspecialchars($_POST["data_atendimento"] ?? '');
        $camposObrigatorios = ['nome_paciente', 'data_nascimento', 'horario_inicio', 'horario_fim', 'local', 'data_atendimento'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                die("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }
        if (!strtotime($data_nascimento) || !strtotime($data_atendimento)) {
            die("Data inválida. Por favor, verifique as datas informadas.");
        }
        // Formatar datas
        $data_nasc_formatada = date("d/m/Y", strtotime($data_nascimento));
        $data_atend_formatada = date("d/m/Y", strtotime($data_atendimento));
        
         $nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
        $crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');
        $emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8');
    
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

            <p>Declaro, para os devidos fins, que o(a) Sr.(a) <span class="underline">' . htmlspecialchars($nome_paciente) . '</span>,<br>
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
        $dompdf = new Dompdf($this->options);
        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            set_time_limit(120); // 2 minutos para renderização
            $dompdf->render();
            // Limpeza de buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            $dompdf->stream(
                "Comparecimento_" . preg_replace('/[^a-z0-9]/i', '_', $nome_paciente) . "_" . date('Y-m-d') . ".pdf",
                ["Attachment" => true]
            );
            exit;
        } catch (Exception $e) {
            // Log detalhado do erro
            $errorLog = date('[Y-m-d H:i:s]') . " ERRO: " . $e->getMessage() . "\n";
            $errorLog .= "Trace: " . $e->getTraceAsString() . "\n\n";
            file_put_contents(__DIR__ . '/pdf_errors.log', $errorLog, FILE_APPEND);
            die("Falha crítica ao gerar PDF. Detalhes foram registrados no log.");
        }
    }

    public function gerarRecibo()
    {


        $camposObrigatorios = ['nome_paciente', 'cpf'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                die("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }
        $nome_paciente = htmlspecialchars($_POST["nome_paciente"] ?? '');
        $cpf = $_POST['cpf'];

         
        $vali = new Vali();

        $cpf_paciente = $vali->formatarCPF($cpf);
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
                        <p>Recebi de <strong>' . htmlspecialchars($nome_paciente, ENT_QUOTES) . '</strong>,</p>
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
        $dompdf = new Dompdf($this->options);
        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            set_time_limit(120); // 2 minutos para renderização
            $dompdf->render();
            // Limpeza de buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            $dompdf->stream(
                "Recibo_" . preg_replace('/[^a-z0-9]/i', '_', $nome_paciente) . "_" . date('Y-m-d') . ".pdf",
                ["Attachment" => true]
            );
            exit;
        } catch (Exception $e) {
            // Log detalhado do erro
            $errorLog = date('[Y-m-d H:i:s]') . " ERRO: " . $e->getMessage() . "\n";
            $errorLog .= "Trace: " . $e->getTraceAsString() . "\n\n";
            file_put_contents(__DIR__ . '/pdf_errors.log', $errorLog, FILE_APPEND);
            die("Falha crítica ao gerar PDF. Detalhes foram registrados no log.");
        }

    }

}

class Psicologa
{
    public function __construct()
    {
        if (isset($_COOKIE['usuario']) && $_COOKIE['usuario'] === 'true' && !isset($_SESSION['usuario'])) {
            $_SESSION['usuario'] = true;
            // Você pode querer recarregar os dados do usuário aqui
        }
        $this->verificarPsico();
        $this->processarLogout();
    }
    function verificarIdentidade($email, $senha)
    {
        try {
            $conn = Conexao::getConnection();

            // Consulta mais segura, selecionando apenas campos necessários
            $stmt = $conn->prepare("SELECT cd_anam, email_anam, cd_crp_anam_chefe, nome_anam, senha_anam, cd_cpf_anam_chefe 
                               FROM anamnese_chefe 
                               WHERE email_anam = :email 
                               LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                return false;
            }
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            // Verificação segura da senha
            if (password_verify($senha, $usuario['senha_anam'])) {
                // Remove a senha antes de retornar
                unset($usuario['senha_anam']);
                return $usuario;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao verificar identidade: " . $e->getMessage());
            return false;
        }
    }
    public function verificarPsico()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['botLogin']))) {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $senha = $_POST['senha'] ?? '';
            if (empty($email) || empty($senha)) {
                $_SESSION['erro_login'] = "Preencha todos os campos!";
                header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
                exit;
            }
            if ($usuario = $this->verificarIdentidade($email, $senha)) {
                $_SESSION['usuario'] = [
                    'id' => $usuario['cd_anam'],  // Importante para identificar o usuário
                    'email' => $usuario['email_anam'],
                    'crp' => $usuario['cd_crp_anam_chefe'],
                    'nome' => $usuario['nome_anam'],
                    'cpf' => $usuario['cd_cpf_anam_chefe']
                ];
                setcookie('logado', 'true', [
                    'expires' => time() + (30 * 60),
                    'path' => '/',
                    'domain' => 'lucianavenanciopsipp.com.br', // seu domínio aqui
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                setcookie('usuario_id', $usuario['cd_anam'], time() + (30 * 60), '/');
                // Redirecionamento seguro
                header('Location: ' . strtok($_SERVER['PHP_SELF'], '?')); // Remove parâmetros da URL
                exit();
            } else {
                $_SESSION['erro_login'] = "E-mail ou senha incorretos!";
                header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
                exit;
            }
        }
    }
    private function processarLogout()
    {
        if (isset($_GET['logout'])) {
            // Limpa os cookies
            setcookie('logado', '', time() - 3600, '/');
            setcookie('usuario_id', '', time() - 3600, '/');
            // Destrói a sessão
            session_unset();
            session_destroy();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
    public function exibirBotoesAuth()
    {
        return '<div class="auth-buttons" style="position: static; margin-left: auto;">' .
            (isset($_SESSION['usuario']) ? $this->botaoLogout() : $this->botaoLogin()) .
            '</div>';
    }
    private function botaoLogin()
    {
        return '<button class="btn btn-outline-primary" onclick="abrirModal()">
    <i class="bi bi-box-arrow-in-right"></i> Login</button>';
    }
    private function botaoLogout()
    {
        return '<button class="btn btn-outline-primary" onclick="window.location.href=\'?logout=1\'">
            <i class="bi bi-box-arrow-right"></i> Sair
            </button>';
    }
    public function exibirModalLogin()
    {
        $html = '
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <h2>Login</h2>';
        if (isset($_SESSION['erro_login'])) {
            $html .= '<p class="erro">' . $_SESSION['erro_login'] . '</p>';
            unset($_SESSION['erro_login']);
        }
        $html .= '
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="botLogin" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div id="modalOverlay" class="overlay"></div>';
        return $html;
    }
}


