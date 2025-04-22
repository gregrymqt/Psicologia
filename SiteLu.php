<?php
// 1. INICIAR SESSÃO (DEVE SER SEMPRE A PRIMEIRA LINHA)
ob_start();
session_start();

// 2. INCLUIR DEPENDÊNCIAS
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
$vali=new Vali();

function verificarIdentidade($email, $senha)
{
    try {
        $conn = Conexao::getConnection();

        // Consulta mais segura, selecionando apenas campos necessários
        $stmt = $conn->prepare("SELECT cd_anam, email_anam, cd_crp_anam_chefe, nome_anam, senha_anam 
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['modalLu']) || isset($_POST['botLogin'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';

        if ($usuario = verificarIdentidade($email, $senha)) {
            $_SESSION['logado'] = true;
            $_SESSION['usuario'] = [
                'id' => $usuario['cd_anam'],  // Importante para identificar o usuário
                'email' => $usuario['email_anam'],
                'crp' => $usuario['cd_crp_anam_chefe'],
                'nome' => $usuario['nome_anam']
            ];

            // Redirecionamento seguro
            header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
            exit;
        } else {
            $_SESSION['erro_login'] = "E-mail ou senha incorretos!";
            header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
            exit;
        }


        if (!isset($_SESSION['logado'])) {
            $erro = $_SESSION['erro_login'] ?? '';
            unset($_SESSION['erro_login']);
            ?>
            <!DOCTYPE html>
            <html lang="pt-BR">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Login Necessário</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            </head>

            <body>
                <div class="modal" id="loginModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Login</h5>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <?php if (!empty($erro)): ?>
                                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Senha</label>
                                        <input type="password" name="senha" class="form-control" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="modalLu" class="btn btn-primary">Entrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                    $(document).ready(function () {
                        var myModal = new bootstrap.Modal(document.getElementById('loginModal'), {
                            backdrop: 'static',
                            keyboard: false
                        });
                        myModal.show();
                    });
                </script>
            </body>

            </html>
            <?php
            exit;
        }
    }
}


// 5. SE CHEGOU ATÉ AQUI, USUÁRIO ESTÁ LOGADO
// Agora podemos processar a página normalmente
$nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
$crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');
$emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['gerar_atestado'])) {
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
        // Gera o HTML do atestado
        $html_atestado = '
                        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .underline { text-decoration: underline; }
                p { margin-bottom: 15px; }
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
            Data: ' . $data_formatada . '</p>
        </body>
        </html>';
        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html_atestado);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            while (ob_get_level()) {
                ob_end_clean();
            }
            $dompdf->stream(
                "atestado_" . preg_replace('/[^a-z0-9]/i', '_', $nome_paciente) . "_" . date('Y-m-d') . ".pdf",
                ["Attachment" => true]
            );
            exit;
        } catch (Exception $e) {
            error_log("Erro ao gerar PDF de atestado: " . $e->getMessage());
            die("Ocorreu um erro ao gerar o PDF. Por favor, tente novamente mais tarde.");
        }
    } elseif (isset($_POST["gerar_comparecimento"])) {
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
        // Gerar HTML da declaração
        $declaracao = '
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
                .footer {
                    margin-top: 80px;
                    text-align: center;
                }
                .signature-line {
                    width: 300px;
                    border-top: 1px solid black;
                    margin: 40px auto 0;
                    padding-top: 5px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="header">
                DECLARAÇÃO DE COMPARECIMENTO
            </div>
            
            <p>Declaro, para os devidos fins, que o(a) Sr.(a) <span class="underline">' . $nome_paciente . '</span>,<br>
            nascido(a) em ' . $data_nasc_formatada . ', compareceu ao atendimento psicológico nesta data,<br>
            no horário das ' . date("H:i", strtotime($hora_inicio)) . ' às ' . date("H:i", strtotime($hora_fim)) . '.</p>
            
            <p>Esta declaração é emitida para comprovação de presença em consulta.</p>
            
            <p>Local: ' . $local . '<br>
            Data: ' . $data_atend_formatada . '</p>
            
            <div class="signature-line"></div>
            
            <div class="footer">
                ' . $nomeUsuario . '<br>
                CRP ' . $crpUsuario . '
            </div>
        </body>
        </html>';
        try {
            // Configuração do DomPDF
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($declaracao);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            // Limpar buffer de saída
            while (ob_get_level()) {
                ob_end_clean();
            }
            // Gerar o PDF para download
            $dompdf->stream(
                'declaracao_comparecimento_' . preg_replace('/[^a-z0-9]/i', '_', $nome_paciente) . '_' . date('Ymd_His') . '.pdf',
                ["Attachment" => true]
            );
            exit;
        } catch (Exception $e) {
            error_log("Erro ao gerar PDF de comparecimento: " . $e->getMessage());
            die("Ocorreu um erro ao gerar o PDF. Por favor, tente novamente mais tarde.");
        }
    } elseif (isset($_POST["gerar_recibo"])) {
        $nome_paciente = htmlspecialchars($_POST["nome_paciente"] ?? '');
        $cpf = $_POST['cpf'];

        $camposObrigatorios = ['nome_paciente', 'cpf'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                die("Por favor, preencha o campo " . ucfirst(str_replace('_', ' ', $campo)));
            }
        }

        $cpf_paciente = $vali->formatarCPF($cpf);
        $data_hoje = date('d/m/Y');
        $valor_consulta = 250.00;
        $valor_extenso = isset($veri) && method_exists($veri, 'valorPorExtenso')
            ? $veri->valorPorExtenso($valor_consulta)
            : "duzentos e cinquenta reais";
        $data_hoje = date('d/m/Y');
        $data_consulta = date('d/m/Y');
        $nome_arquivo = "recibo_" . str_replace(' ', '_', $dados['nome_completo']) . "_" . date('dmY');
        $valor_formatado = "R$ " . number_format($valor_consulta, 2, ',', '.');


        $recibo_html = '
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
                .footer { margin-top: 100px; font-size: 12px; text-align: center; color: #666; clear: both; }
                .valor-extenso { font-style: italic; color: #444; }
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
            </style>
        </head>
        <body>
            <div class="recibo-container">
                <div class="recibo">
                    <div class="header">
                        <h1>RECIBO DE PAGAMENTO – PSICÓLOGA</h1>
                    </div>
                    
                    <div class="dados-psicologo">
                        <p><strong>Nome da Psicóloga:</strong> ' . $psicologo['nome_anam'] . '</p>
                        <p><strong>CPF:</strong> ' . $psicologo['cd_cpf_anam_chefe'] . '</p>
                        <p><strong>CRP:</strong> ' . $psicologo['cd_crp_anam_chefe'] . '</p>
                        <p><strong>E-mail:</strong> ' . $psicologo['email_anam'] . '</p>
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
                        <p>' . htmlspecialchars($data_hoje) . '</p>
                        <div class="assinatura">
                            <p>' . htmlspecialchars($psicologo['nome_anam']) . '</p>
                            <p>CRP ' . htmlspecialchars($psicologo['cd_crp_anam_chefe']) . '</p>
                        </div>
                    </div>
        
                    <div class="footer">
                        <p>Este recibo é emitido para fins de comprovação de pagamento por serviços prestados na área da Psicologia, conforme legislação vigente.</p>
                        <p>Recibo gerado em ' . htmlspecialchars($data_hoje) . '</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        try {
            // Configuração do DomPDF
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($recibo_html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            // Limpar buffer de saída
            while (ob_get_level()) {
                ob_end_clean();
            }
            // Gerar o PDF para download
            $dompdf->stream(
                'recibo_pagamento_' . preg_replace('/[^a-z0-9]/i', '_', $nome_paciente) . '_' . date('Ymd_His') . '.pdf',
                ["Attachment" => true]
            );
            exit;
        } catch (Exception $e) {
            error_log("Erro ao gerar PDF de comparecimento: " . $e->getMessage());
            die("Ocorreu um erro ao gerar o PDF. Por favor, tente novamente mais tarde.");
        }


    }

}

$paginaHTML = <<<HTML
    <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TiaLu - Site</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
      /* Estilos da Navbar */
      .navbar-custom {
            background-color: rgba(185, 219, 145, 0.75);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand img {
            height: 40px;
            width: auto;
            transition: all 0.3s ease;
        }
        .welcome-text {
            font-size: 1.2rem;
            font-weight: 500;
            color: #333;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 50vw;
        }
        /* Estilos das Abas */
        .document-tabs {
            display: flex;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .document-tab {
            padding: 12px 20px;
            cursor: pointer;
            font-weight: 500;
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .document-tab:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }
        .document-tab.active {
            border-bottom-color: #0d6efd;
            color: #0d6efd;
            font-weight: 600;
        }
        /* Estilos do Conteúdo */
        .document-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .document-content.active {
            display: block;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-group .btn {
            flex: 1 1 150px;
        }
        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Ajustes para mobile */
        @media (max-width: 992px) {
            .welcome-text {
                font-size: 1rem;
                max-width: 40vw;
            }
            .navbar-brand img {
                height: 35px;
            }
            .document-tab {
                padding: 10px 15px;
                font-size: 0.95rem;
            }
        }
        @media (max-width: 768px) {
            .welcome-text {
                font-size: 0.9rem;
                max-width: 30vw;
            }
            .navbar-brand img {
                height: 30px;
            }
            .document-tab {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            .btn-group .btn {
                flex: 1 1 100%;
            }
        }
        @media (max-width: 576px) {
            .welcome-text {
                display: none;
            }
            .navbar-brand img {
                height: 25px;
            }
            .document-tabs {
                justify-content: space-around;
            }
            .document-tab {
                flex: 1;
                text-align: center;
                padding: 10px 5px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Responsiva -->
    <nav class="navbar navbar-expand-lg navbar-custom py-2 py-lg-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="img/LogoLu.png"  alt="Logo TiaLu" class="img-fluid">
            </a>
            
            <div class="welcome-text mx-auto d-none d-sm-flex">
                Seja bem-vinda, {$nomeUsuario}
            </div>
            
            <!-- Botão de Login (só aparece se não logado) -->
            
            <div class="d-flex">
            <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#logmod">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </div>
        </div>
    </nav>
    <div class="modal" id="logmod" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Login</h5>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    
                                    
                                    <div class="mb-3">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Senha</label>
                                        <input type="password" name="senha" class="form-control" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="botLogin" class="btn btn-primary">Entrar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
    
    <!-- Container Principal -->
    <div class="container mt-3 mt-md-4">
        <!-- Abas de Documentos Responsivas -->
        <div class="document-tabs">
            <div class="document-tab active" onclick="showDocument('atestado')">
                <i class="bi bi-file-earmark-medical d-none d-md-inline"></i> Atestado
            </div>
            <div class="document-tab" onclick="showDocument('comparecimento')">
                <i class="bi bi-calendar-check d-none d-md-inline"></i> Comparecimento
            </div>
            <div class="document-tab" onclick="showDocument('recibo')">
                <i class="bi bi-receipt d-none d-md-inline"></i> Recibo
            </div>
        </div>

        <!-- Formulário de Atestado (visível por padrão) -->
        <div id="atestado-content" class="document-content active">
            <form id="atestado-form" method="post" action="">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="nome_paciente" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="nome_paciente" name="nome_paciente" required>
                    </div>
                    <div class="col-md-6">
                        <label for="hora_inicio" class="form-label">Horário de Início:</label>
                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                    </div>
                    <div class="col-md-6">
                        <label for="hora_fim" class="form-label">Horário de Término:</label>
                        <input type="time" class="form-control" id="hora_fim" name="hora_fim" required>
                    </div>
                    <div class="col-12">
                        <label for="motivo" class="form-label">Motivo do Atendimento:</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="retorno" class="form-label">Data de Retorno:</label>
                        <input type="date" class="form-control" id="retorno" name="retorno" required>
                    </div>
                    <div class="col-md-6">
                        <label for="data" class="form-label">Data do Atestado:</label>
                        <input type="date" class="form-control" id="data" name="data" required>
                    </div>
                    <div class="col-12">
                        <label for="local" class="form-label">Local:</label>
                        <input type="text" class="form-control" id="local" name="local" required>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-success" name="gerar_atestado">
                                <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Formulário de Comparecimento (oculto por padrão) -->
        <div id="comparecimento-content" class="document-content">
            <form id="comparecimento-form" method="post" action="">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="nome_paciente_comp" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="nome_paciente_comp" name="nome_paciente" required>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                   </div>
                    <div class="col-md-6">
                        <label for="data_atendimento" class="form-label">Data da Consulta:</label>
                        <input type="date" class="form-control" id="data_atendimento" name="data_atendimento" required>
                    </div>
                    <div class="col-md-6">
                        <label for="horario_inicio" class="form-label">Horário de início:</label>
                        <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" required>
                    </div>
                    <div class="col-md-6">
                        <label for="horario_fim" class="form-label">Horário de término:</label>
                        <input type="time" class="form-control" id="horario_fim" name="horario_fim" required>
                    </div>
                    <div class="col-12">
                        <label for="local_comparecimento" class="form-label">Local:</label>
                        <input type="text" class="form-control" id="local_comparecimento" name="local" required>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-success" name="gerar_comparecimento">
                                <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="recibo-content" class="document-content">
        <form id="recibo-form" method="post" action="">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="nome_paciente_comp" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="nome_paciente_recibo" name="nome_paciente" required>
                    </div>
                    <div class="col-md-4">
                            <label for="cpf" class="form-label required-field">CPF do paciente</label>
                            <input type="text" class="form-control" id="cpf" name="cpf"
                                required>
                        </div>
                        <div class="col-12 mt-2">
                        <div class="btn-group">
                        <button type="submit" class="btn btn-success" name="gerar_recibo">
                                <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
        </div>






    </div>
    <!-- Bootstrap JS Bundle + Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      function showDocument(documentType) {
            // Esconde todos os conteúdos
            document.querySelectorAll('.document-content').forEach(content => {
                content.classList.remove('active');
            });
            // Remove a classe active de todas as abas
            document.querySelectorAll('.document-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            // Mostra o conteúdo selecionado
            document.getElementById(documentType + '-content').classList.add('active');
            // Ativa a aba selecionada
            event.currentTarget.classList.add('active');
        }
        // Melhorar a experiência em dispositivos móveis
        document.addEventListener('DOMContentLoaded', function () {
            // Ajustar altura dos textareas
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.style.minHeight = '100px';
                textarea.addEventListener('focus', function () {
                    this.style.minHeight = '150px';
                });
            });
            // Suavizar rolagem nas abas em mobile
            const tabsContainer = document.querySelector('.document-tabs');
            if (tabsContainer.scrollWidth > tabsContainer.clientWidth) {
                tabsContainer.classList.add('scroll-snap');
            }
        });
        
    </script>
</body>
</html>
HTML;

echo $paginaHTML;

// ... [restante do seu código HTML] ...
?>