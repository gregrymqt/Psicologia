<?php
// 1. INICIAR SESSÃO (DEVE SER SEMPRE A PRIMEIRA LINHA)
ob_start();
session_start(); 
// 2. INCLUIR DEPENDÊNCIAS
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/includes/conexao.php';
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/includes/funcoes.php';
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/processa/processa_Site.php';

$psi = new Psicologa();
$vali = new Vali(); // Verifique se a classe Vali também existe!

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante', ENT_QUOTES, 'UTF-8');
    $crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? 'CRP não informado', ENT_QUOTES, 'UTF-8');
    $emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? 'E-mail não cadastrado', ENT_QUOTES, 'UTF-8');
    $cpfUsuario = htmlspecialchars($_SESSION['usuario']['cpf'] ?? 'CPF não cadastrado', ENT_QUOTES, 'UTF-8');
    $cdUsuario = htmlspecialchars($_SESSION['usuario']['id'] ?? 'Código não encontrado', ENT_QUOTES, 'UTF-8');
} else {
    // Defina valores padrão caso o usuário não esteja logado
    $nomeUsuario = 'Visitante';
    $crpUsuario = 'CRP não informado';
    $emailUsuario = 'E-mail não cadastrado';
    $cpfUsuario = 'CPF não cadastrado';
    $cdUsuario = 'Código não encontrado';
}

function consultaPaciente($nome)
{
    try {
        $conn = Conexao::getConnection();
        $stmt = $conn->prepare("SELECT * 
                               FROM anamnese
                               WHERE nome_completo = :nome
                               LIMIT 1");
        $stmt->bindParam(':nome', $nome);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao verificar identidade: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
     if (isset($_POST['consul_paci']) && isset($_SESSION['logado']) && !empty($usuario['id'])) {
        $nomeConsul = trim($_POST['nome_paciente']); // Remove espaços extras
        $nomeConsul = filter_var($nomeConsul, FILTER_SANITIZE_STRING);
        $dadosPaciente = consultaPaciente($nomeConsul);
        $_SESSION['resultado_consulta'] = [
            'dados' => $dadosPaciente,
            'nome_buscado' => $nomeConsul
        ];
      header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF']));
        exit;
    }
}
if (!empty($_SESSION['resultado_consulta'])) {
    $dadosPaciente = $_SESSION['resultado_consulta']['dados'];
    $nomeConsul = $_SESSION['resultado_consulta']['nome_buscado'];
    // Limpa o resultado da sessão imediatamente após pegar os dados
    unset($_SESSION['resultado_consulta']);
    if ($dadosPaciente !== false) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const container = document.getElementById("dados-paciente");
                const resultadoDiv = document.getElementById("resultado-consulta");
                
                // Configurações iniciais
                resultadoDiv.style.display = "block";
                container.innerHTML = "";
                
                // Cria as linhas de dados
                let html = \'\';';
        foreach ($dadosPaciente as $campo => $valor) {
            if (!empty($valor)) {
                $campoFormatado = ucfirst(str_replace('_', ' ', $campo));
                $valorSanitizado = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');

                echo 'html += `<div class="col-md-6 mb-3">
                            <div class="dados-item">
                                <strong class="d-block text-muted small">' . $campoFormatado . '</strong>
                                <span class="d-block">' . $valorSanitizado . '</span>
                            </div>
                        </div>`;';
            }
        }
        echo 'if(html === \'\') {
                    container.innerHTML = `<div class="col-12">
                        <div class="alert alert-info">Paciente encontrado, mas nenhum dado preenchido.</div>
                    </div>`;
                } else {
                    container.innerHTML = html;
                }
            });
            </script>';
    } else {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const container = document.getElementById("dados-paciente");
                container.innerHTML = `<div class="col-12">
                    <div class="alert alert-warning">Nenhum paciente encontrado com o nome ' . htmlspecialchars($nomeConsul, ENT_QUOTES, 'UTF-8') . '</div>
                </div>`;
                document.getElementById("resultado-consulta").style.display = "block";
            });
            </script>';
    }
}

$paginaHTML = <<<HTML
    <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luciana Venâncio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
// body {
//     background-image: url('img/marcaDaguaLu.png');
//     background-size: 390px; /* Largura fixa (altura proporcional) */
//     background-repeat: no-repeat;
//     background-position: center calc(100% + 100px);  /* 50px acima do rodapé */    background-attachment: fixed;
// }
      /* Estilos da Navbar */
      .navbar-custom {
            background-color: rgba(135, 150, 99, 0.84);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand img {
            height: 70px;
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
        
        .mensagem-erro {
    font-size: 0.8em;
    margin-top: 5px;
}

.invalido {
    border-color: red;
}

.valido {
    border-color: green;
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
            background-color: rgba(135, 150, 99, 0.84);
        }
        .modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 500px;
    border-radius: 8px;
    z-index: 1050;
    overflow: hidden; /* Para manter bordas arredondadas */
}

.modal-content {
    padding: 30px; /* Espaçamento interno */
    min-height: 300px; /* Altura mínima */
    display: flex;
    flex-direction: column;
}

.modal h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}

/* Container dos botões */
.modal .btn-primary {
    background-color: #4a6baf;
    border-color: #4a6baf;
}

.modal-buttons {
    display: flex;
    justify-content: center; /* Centraliza horizontalmente */
    gap: 15px; /* Espaço entre botões */
    margin-top: auto; /* Empurra para baixo */
    padding-top: 20px;
}

/* Estilo dos botões */
.modal .btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
    flex: 1; /* Faz os botões terem mesma largura */
    max-width: 150px; /* Largura máxima */
} 


.modal .btn-primary {
    background-color: #4a6baf;
    border-color: #4a6baf;
}

.modal .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

/* Efeito hover */
.modal .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Mensagem de erro */
.erro {
    color: #dc3545;
    text-align: center;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f8d7da;
    border-radius: 4px;
    border: 1px solid #f5c6cb;
}

/* Campos do formulário */
.modal .form-control {
    margin-bottom: 15px;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #ced4da;
}

/* Overlay */
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1040;
}

body.modal-open {
    overflow: hidden;
}
        .dados-item {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}
.dados-item:hover {
    background-color: #e9ecef;
    border-left-color: #0b5ed7;
}
#resultado-consulta {
    animation: fadeIn 0.5s ease-out;
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
            body {
    background-image: url('img/marcaDaguaLu.png');
    background-size: 280px; /* Largura fixa (altura proporcional) */
    background-repeat: no-repeat;
    background-position: center calc(100% + 55px);  /* 50px acima do rodapé */    background-attachment: fixed;
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
            .modal {
        width: 95%;
        padding: 15px;
    }
        }
           @media (max-width: 374px) {
    .modal .btn {
        padding: 8px 15px; /* Reduz o padding */
        max-width: 120px; /* Reduz a largura máxima */
        font-size: 14px; /* Opcional: reduz o tamanho da fonte */
    }
    
    .modal-buttons {
        gap: 10px; /* Reduz o espaço entre botões */
        padding-top: 15px;
    }
}
    </style>
</head>
<body>
    <!-- Navbar Responsiva -->
     <nav class="navbar navbar-expand-lg navbar-custom py-2 py-lg-3">
        <div class="container-fluid">
            
            <div class="welcome-text mx-auto d-none d-sm-flex">
                Seja bem-vinda, {$nomeUsuario}
            </div>
            
            <!-- Botão de Login (só aparece se não logado) -->
            
            <div class="d-flex">
    {$psi->exibirBotoesAuth()}
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
        </div>
        <!-- Formulário de Atestado (visível por padrão) -->
        <div id="atestado-content" class="document-content active">
            <form id="atestado-form" method="post" action="processa/processa_Site.php">
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
            <form id="comparecimento-form" method="post" action="processa/processa_Site.php">
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
        <form id="recibo-form" method="post" action="processa/processa_Site.php">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="nome_paciente_comp" class="form-label">Nome do Paciente:</label>
                        <input type="text" class="form-control" id="nome_paciente_recibo" name="nome_paciente" required>
                    </div>
                    <div class="col-md-4">
                            <label for="cpf" class="form-label required-field">CPF do paciente</label>
                            <input type="text" class="form-control" onblur="validarCPF(this)" id="cpf" name="cpf" required>
                            <div id="cpf-mensagem" class="mensagem-erro"></div>
                        </div>
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
    <form id="consulta-form" method="post" action="">
        <div class="row g-3">
            <div class="col-12">
                <label for="nome_paciente_comp" class="form-label">Nome do Paciente:</label>
                <input type="text" class="form-control" id="nome_paciente_recibo" name="nome_paciente" required>
            </div>
            <div class="col-12 mt-2">
                <div class="btn-group">
                    <button type="submit" class="btn " name="consul_paci">
                        <i class="bi bi-file-earmark-pdf"></i> Consulta
                    </button>
                    <button type="reset" class="btn ">
                        <i class="bi bi-eraser"></i> Limpar
                    </button>
                </div>
            </div>
        </div>
    </form>
    <div id="resultado-consulta" class="mt-4" style="display: none;">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Dados do Paciente</h5>
            </div>
            <div class="card-body">
                <div class="row" id="dados-paciente">
                    <!-- Os dados serão inseridos aqui via JavaScript/PHP -->
                </div>
            </div>
        </div>
    </div>
</div>
  </div>
  
    {$psi->exibirModalLogin()}

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
        textarea.addEventListener('blur', function () {
            if (!this.value) {
                this.style.minHeight = '100px';
            }
        });
    });   
    // Suavizar rolagem nas abas em mobile
    const tabsContainer = document.querySelector('.document-tabs');
    if (tabsContainer && tabsContainer.scrollWidth > tabsContainer.clientWidth) {
        tabsContainer.classList.add('scroll-snap');
    } 
    // Ativar a primeira aba por padrão se nenhuma estiver ativa
    if (!document.querySelector('.document-tab.active')) {
        const firstTab = document.querySelector('.document-tab');
        if (firstTab) {
            firstTab.classList.add('active');
            const firstContent = document.querySelector('.document-content');
            if (firstContent) firstContent.classList.add('active');
        }
    }
});     

function validarCPF(input) {
    // Obtém o valor do campo
    const cpf = input.value;
    
    // Remove caracteres não numéricos
    const cpfLimpo = cpf.replace(/\D/g, '');
    
    // Verifica se tem 11 dígitos ou se é uma sequência de dígitos iguais
    if (cpfLimpo.length !== 11 || /^(\d)\1{10}$/.test(cpfLimpo)) {
        mostrarErro(input, 'CPF inválido');
        return false;
    }
    
    // Validação do primeiro dígito verificador
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpfLimpo.charAt(i)) * (10 - i);
    }
    let resto = (soma * 10) % 11;
    resto = resto === 10 ? 0 : resto;
    if (resto !== parseInt(cpfLimpo.charAt(9))) {
        mostrarErro(input, 'CPF inválido');
        return false;
    }
    
    // Validação do segundo dígito verificador
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpfLimpo.charAt(i)) * (11 - i);
    }
    resto = (soma * 10) % 11;
    resto = resto === 10 ? 0 : resto;
    if (resto !== parseInt(cpfLimpo.charAt(10))) {
        mostrarErro(input, 'CPF inválido');
        return false;
    }
    
    // Formata e mostra como válido
    input.value = formatarCPF(cpfLimpo);
    mostrarErro(input, '', true);
    return true;
}

function formatarCPF(cpf) {
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function mostrarErro(input, mensagem, valido = false) {
    const mensagemElemento = document.getElementById('cpf-mensagem');
    mensagemElemento.textContent = mensagem;
    
    if (valido) {
        input.classList.remove('invalido');
        input.classList.add('valido');
        mensagemElemento.style.color = 'green';
    } else {
        input.classList.remove('valido');
        input.classList.add('invalido');
        mensagemElemento.style.color = 'red';
    }
}

function abrirModal() {
    document.getElementById('loginModal').style.display = 'block';
    document.getElementById('overlay').style.display = 'block';
    document.body.classList.add('modal-open');
}

function fecharModal() {
    document.getElementById('loginModal').style.display = 'none';
    document.getElementById('overlay').style.display = 'none';
    document.body.classList.remove('modal-open');
}
    </script>
</body>
</html>
HTML;

echo $paginaHTML;

?>