<?php
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
require_once 'C:/xampp/htdocs/TiaLu/processa/processa_Site.php';
$psi = new Psicologa();
$consul = new Consulta();
$pdf = new ProcessaPdfs();
$consulPdf = new ConsultaPfd(); // Certifique-se que a classe está disponível

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["gerar_recibo"])) {
        try {
            $pdf->gerarRecibo($_SESSION['resultado_consulta']['id_anam']);
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Erro ao gerar: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } elseif (isset($_POST["gerar_comparecimento"])) {
        try {
            $pdf->gerarComparecimento($_SESSION['resultado_consulta']['id_anam']);
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Erro ao gerar: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } elseif (isset($_POST['gerar_atestado'])) {
        try {
            $pdf->gerarAtestado($_SESSION['resultado_consulta']['id_anam']);
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Erro ao gerar: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } elseif (isset($_POST['consul_paci'])) {
        try {
            $consul->consulpaciente();
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Erro na consulta: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } elseif (isset($_POST['salvar_observacoes'])) {
        // Verifique TODOS os campos necessários
        if (empty($_POST['id_paciente'])) {
            $_SESSION['erro'] = "ID do paciente não informado";
        } elseif (empty($_POST['observacoes'])) {
            $_SESSION['erro'] = "Por favor, insira as observações";
        } else {
            try {
                $consul->salvarObservacoes($_POST['id_paciente'], $_POST['observacoes']);
                $_SESSION['sucesso'] = "Observações salvas com sucesso!";
            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro ao salvar: " . $e->getMessage();
            }
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }elseif(isset($_POST['gerar_consulta'])) {
        try {
            $consulPdf = new ConsultaPfd();
            $resultados = $consulPdf->consultaPdfs();
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
} else {
    header('SiteLu.php');
}


if (isset($_COOKIE['resultado_consulta'])) {
    $_SESSION['resultado_consulta'] = json_decode($_COOKIE['resultado_consulta'], true);
}
// var_dump($_SESSION['resultado_consulta']['id_anam']);


if (isset($_COOKIE['nome_buscado'])) {
    $_SESSION['nome_buscado'] = $_COOKIE['nome_buscado'];
}
$nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
$crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');
$emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8');
$cpfUsuario = htmlspecialchars($_SESSION['usuario']['cpf'] ?? 'cpf não cadastrado', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luciana Venâncio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/SiteLu.css">

</head>

<body>
    <!-- Navbar Responsiva -->
    <nav class="navbar navbar-expand-lg navbar-custom py-2 py-lg-3">
        <div class="container-fluid">
            <div class="welcome-text mx-auto d-none d-sm-flex">
                Seja bem-vinda, <?php echo $nomeUsuario ?>
            </div>
            <!-- Botão de Login (só aparece se não logado) -->
            <div class="d-flex">
                <?php echo $psi->exibirBotoesAuth() ?>
            </div>
        </div>
    </nav>
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
            <div class="document-tab" onclick="showDocument('consulta')">
                <i class="bi bi-receipt d-none d-md-inline"></i> Consulta
            </div>
            <div class="document-tab" onclick="showDocument('informacoes')" style="flex-shrink: 0;">
                <i class="bi bi-info-circle d-none d-md-inline"></i> Informações
            </div>
        </div>
        <!-- Formulário de Atestado (visível por padrão) -->
        <div id="atestado-content" class="document-content active">
            <form id="atestado-form" method="post" action="">
                <input type="hidden" name="id_paciente"
                    value="<?= htmlspecialchars($_SESSION['resultado_consulta']['id_anam'] ?? '') ?>">
                <div class="row g-3">
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
                            <button type="submit" class="btn " name="gerar_atestado">
                                <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                            </button>
                            <button type="reset" class="btn ">
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
                <input type="hidden" name="id_paciente"
                    value="<?= htmlspecialchars($_SESSION['resultado_consulta']['id_anam'] ?? '') ?>">
                <div class="row g-3">

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
                            <button type="submit" class="btn " name="gerar_comparecimento">
                                <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                            </button>
                            <button type="reset" class="btn ">
                                <i class="bi bi-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div id="recibo-content" class="document-content">
            <form id="recibo-form" method="post" action="">
                <input type="hidden" name="id_paciente"
                    value="<?= htmlspecialchars($_SESSION['resultado_consulta']['id_anam'] ?? '') ?>">
                <div class="row g-3">
                    <div class="col-12 mt-2">
                        <div class="btn-group">
                            <button type="submit" class="btn " name="gerar_recibo">
                                <i class="bi bi-file-earmark-pdf"></i> Gerar PDF
                            </button>
                            <button type="reset" class="btn ">
                                <i class="bi bi-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div id="consulta-content" class="document-content">
            <form method="POST" action="" id="consulta_form">
                <input type="hidden" name="id_paciente"
                    value="<?= htmlspecialchars($_SESSION['resultado_consulta']['id_anam'] ?? '') ?>">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="nome_paciente" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="nome_paciente" name="nome_paciente"
                            value="<?= isset($_SESSION['nome_buscado']) ? htmlspecialchars($_SESSION['nome_buscado']) : '' ?>"
                            required autofocus>
                    </div>
                    <div class="col-md-12 text-center mt-3">
                        <div class="btn-group">
                            <button type="submit" class="btn " name="consul_paci">
                                <i class="bi bi-file-earmark-pdf"></i> Consulta
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <!-- Exibição de erros -->
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger mt-3">
                    <?= $_SESSION['erro'] ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>
            <!-- Resultados da Consulta -->
            <?php if (isset($_SESSION['resultado_consulta'])): ?>
                <div class="mt-4">
                    <h4>Resultados para: <?= htmlspecialchars($_SESSION['nome_buscado']) ?></h4>
                    <?php if (!empty($_SESSION['resultado_consulta'])): ?>
                        <div class="row mt-3">
                            <?php foreach ($_SESSION['resultado_consulta'] as $campo => $valor): ?>
                                <?php if (!empty($valor) && $campo !== 'observacao_paciente'): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?= ucwords(str_replace('_', ' ', $campo)) ?>
                                                </h6>
                                                <p class="card-text"><?= nl2br(htmlspecialchars($valor)) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <form method="POST" action="">
                                    <input type="hidden" name="id_paciente"
                                        value="<?= htmlspecialchars($_SESSION['resultado_consulta']['id_anam'] ?? '') ?>">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">Observações do Paciente</h5>
                                        </div>
                                        <div class="card-body">
                                            <textarea name="observacoes" rows="5" style="min-height: 150px;"
                                                class="form-control"><?= htmlspecialchars($_SESSION['resultado_consulta']['observacao_paciente'] ?? '') ?>
                                                                                                    </textarea>
                                        </div>
                                        <div class="card-footer text-end">
                                            <button type="submit" class="btn btn-primary" name="salvar_observacoes">
                                                <i class="bi bi-save"></i> Salvar Observações
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            Nenhum paciente encontrado com este nome.
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3">
                    Nenhum paciente encontrado com este nome.
                </div>
            <?php endif; ?>
        </div>
        <?php unset($_SESSION['resultado_consulta'], $_SESSION['nome_buscado']); ?>

        <div id="informacoes-content" class="document-content">

            <h1>Consulta de Documentos Médicos</h1>

            <form id="consultaForm" method="POST" action="">
                <div class="filtro-option">
                    <input type="radio" id="filtroData" name="filtro" value="data_criacao">
                    <label for="filtroData">Data de Criação</label>
                    <div class="filtro-input" id="inputData">
                        <label for="dataInicio">Data :</label>
                        <input type="date" id="dataInicio" name="dataInicio">
                        
                    </div>
                </div>

                <div class="filtro-option">
                    <input type="radio" id="filtroTipo" name="filtro" value="tipo_documento">
                    <label for="filtroTipo">Tipos de Declaração</label>
                    <div class="filtro-input" id="inputTipo">
                        <select id="tipoDocumento" name="tipoDocumento">
                            <option value="">Selecione um tipo</option>
                            <option value="recibo">recibo</option>
                            <option value="atestado">Atestado</option>
                            <option value="comparecimento">comparecimento</option>
                        </select>
                    </div>
                </div>

               

                <div class="filtro-option">
                    <input type="radio" id="filtroCPF" name="filtro" value="cpf_paciente">
                    <label for="filtroCPF">CPF do Paciente</label>
                    <div class="filtro-input" id="inputCPF">
                        <input type="text" id="cpfPaciente" name="cpfPaciente"
                            placeholder="Digite o CPF (somente números)"
                            onblur="validarCPF(this)" maxlength="11">
                    </div>
                </div>

                <button type="submit" class="btn-consultar" name="gerar_consulta">Consultar</button>
            </form>

            <div id="resultadoConsulta">
            <?php
        if (isset($resultados)) {
            $consulPdf->exibirResultado($resultados);
        } elseif (isset($erro)) {
            echo '<div class="error">'.$erro.'</div>';
        }
        ?>
            </div>
        </div>

    </div>

    <?php echo $psi->exibirModalLogin() ?>
    <!-- Bootstrap JS Bundle + Popper -->
    <script src="script/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>

</html>