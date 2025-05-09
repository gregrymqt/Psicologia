<?php
// Configurações seguras de sessão
session_name('SESSECURE');
session_start([
    'cookie_lifetime' => 3600,
    'cookie_secure' => true,       // Só envia por HTTPS
    'cookie_httponly' => true,     // Inacessível via JS
    'cookie_samesite' => 'Strict', // Proteção contra CSRF
    'use_strict_mode' => true      // Melhora a segurança
]);

// Geração do token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Controle de inatividade (corrigido o typo)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    session_unset();
    session_destroy();
    session_start(); // Reinicia para nova sessão
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$_SESSION['LAST_ACTIVITY'] = time();
// 2. INCLUIR DEPENDÊNCIAS
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
require_once __DIR__ . '/../vendor/autoload.php'; // ou o caminho correto$vali = new Vali();
require_once 'C:/xampp/htdocs/TiaLu/processa/processa_pdf.php';
$ProcesaPdfs = new ProcessaPdfs();

// Verifica se foi solicitado a visualização de PDF
if (isset($_GET['action']) && $_GET['action'] === 'view_pdf' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Verifica se o ID é válido
    if ($id <= 0) {
        die("ID do PDF inválido");
    }

    $ProcessaSite = new ConsultaPfd();
    // Chama o método para exibir o PDF
    echo $ProcessaSite->showPdf($id);
    exit; // Importante para não renderizar o resto da página
}


class Consulta
{
    public function __construct()
    {
        if (isset($_COOKIE['resultado_consulta']) && $_COOKIE['resultado_consulta'] === 'true' && !isset($_SESSION['resultado_consulta'])) {
            $_SESSION['resultado_consulta'] = true;
            // Você pode querer recarregar os dados do usuário aqui
        }
    }
    public function salvarObservacoes($id, $observacoes)
    {
        try {
            $conn = Conexao::getConnection();
            $stmt = $conn->prepare("UPDATE anamnese SET observacao_paciente = ? WHERE id_anam = ?");
            $stmt->execute([$observacoes, $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao salvar observações: " . $e->getMessage());
            return false;
        }
    }
    public function consulpaciente()
    {
        if (isset($_POST['nome_paciente'])) {
            $nomePaciente = trim($_POST['nome_paciente']);

            try {
                $conn = Conexao::getConnection();
                $stmt = $conn->prepare("SELECT * FROM anamnese 
                          WHERE nome_completo LIKE :nome 
                          ORDER BY id_anam DESC 
                          LIMIT 1");
                $stmt->bindValue(':nome', '%' . $nomePaciente . '%');
                $stmt->execute();

                $_SESSION['resultado_consulta'] = $stmt->fetch(PDO::FETCH_ASSOC);
                $_SESSION['nome_buscado'] = $nomePaciente;

                setcookie('logado_2', 'true', [
                    'expires' => time() + (30 * 60),
                    'path' => '/',
                    'domain' => 'lucianavenanciopsipp.com.br', // seu domínio aqui
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);

                header('Location: ' . strtok($_SERVER['PHP_SELF'], '?')); // Remove parâmetros da URL
                exit();
            } catch (PDOException $e) {
                $_SESSION['erro'] = "Erro na consulta: " . $e->getMessage();
            }
        } else {
            $_SESSION['erro'] = "Por favor, informe o nome do paciente";
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}




class ConsultaPfd
{
    private $conn;
    public function __construct()
    {
        $this->conn = Conexao::getConnection();


    }
    public function consultaPdfs()
    {
        try {
            $sql = "SELECT data_criacao, tipo_documento, cpf_paciente, nome_paciente, id_pdf  FROM anamnese_pdfs WHERE 1=1";
            $params = [];
            // Se nenhum filtro foi selecionado
            if (!isset($_POST['filtro']) || empty($_POST['filtro'])) {
                $sql = "SELECT data_criacao, tipo_documento, cpf_paciente, nome_paciente, id_pdf 
                        FROM anamnese_pdfs 
                        ORDER BY data_criacao DESC 
                        LIMIT 100";
            } else {
                switch ($_POST['filtro']) {
                    case 'data_criacao':
                        if (!empty($_POST['dataInicio'])) {
                            $sql .= " AND data_criacao = :dataInicio ";
                            $params[':dataInicio'] = $_POST['dataInicio'];
                        }
                        break;
                    case 'tipo_documento':
                        if (!empty($_POST['tipoDocumento'])) {
                            $sql .= " AND tipo_documento = :tipoDocumento";
                            $params[':tipoDocumento'] = $_POST['tipoDocumento'];
                        }
                        break;
                    case 'cpf_paciente':
                        if (!empty($_POST['cpfPaciente'])) {
                            $sql .= " AND cpf_paciente = :cpfPaciente";
                            $params[':cpfPaciente'] = $_POST['cpfPaciente'];
                        }
                        break;
                }
            }
            // Prepara e executa a query
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Erro ao consultar o banco: ' . $e->getMessage());
        }
    }
    public function exibirResultado($resultados)
    {
        if (!empty($resultados)) {
            echo '<h2>Resultados da Consulta</h2>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>ID</th><th>Data</th><th>Tipo</th><th>CPF</th><th>Paciente</th></tr></thead>';
            echo '<tbody>';
            foreach ($resultados as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['data_criacao']) . '</td>';
                echo '<td>' . htmlspecialchars($row['tipo_documento']) . '</td>';
                echo '<td>' . htmlspecialchars($row['cpf_paciente']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nome_paciente']) . '</td>';
                echo '<td>';
                if (!empty($row['id_pdf'])) {
                    echo '<form method="post" action="" style="display:inline;">
            <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
            <input type="hidden" name="id_pdf" value="' . htmlspecialchars($row['id_pdf']) . '">
            <button type="submit" name="abrir_pdf" class="btn-pdf" title="Visualizar PDF">
                <i class="fas fa-file-pdf"></i> Abrir PDF
            </button>
          </form>';
                } else {
                    echo '<span class="pdf-missing">
            <i class="far fa-file-pdf"></i> Não disponível
          </span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="alert alert-info">Nenhum documento encontrado.</div>';
        }
    }

    public function showPdf($id)
    {
        try {
            // 1. Busca no banco
            $stmt = $this->conn->prepare("SELECT conteudo_pdf FROM anamnese_pdfs WHERE id_pdf = ?");
            $stmt->execute([$id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document || empty($document['conteudo_pdf'])) {
                throw new Exception("Registro não encontrado");
            }

            // 2. Construir caminho seguro
            $nomeArquivo = basename($document['conteudo_pdf']);

            $arquivos = scandir(ProcessaPdfs::getBasePath());
            $pdfEncontrado = null;

            foreach ($arquivos as $arquivo) {
                if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'pdf' && $arquivo === $nomeArquivo) {
                    $pdfEncontrado = ProcessaPdfs::getBasePath() . $arquivo;
                    break;
                }
            }

            if ($pdfEncontrado && file_exists($pdfEncontrado)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
                header('Content-Length: ' . filesize($pdfEncontrado));
                readfile($pdfEncontrado);
                exit;
            }
        } catch (Exception $e) {
            // Página de erro simples
            echo '<!DOCTYPE html>
        <html>
        <head><title>Erro</title></head>
        <body>
            <h2>Erro ao visualizar PDF</h2>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p>Arquivo: ' . htmlspecialchars($nomeArquivo ?? '') . '</p>
        </body>
        </html>';
        }
    }
}



class Psicologa
{
    public function __construct()
    {

        $this->verificarPsico();
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
    public function exibirModalLogin()
    {
        return '
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="fecharModal()">&times;</span>
                <h2>Login</h2>
                ' . (isset($_SESSION['erro_login']) ? '<p class="erro">' . $_SESSION['erro_login'] . '</p>' : '') . '
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
        <div id="modalOverlay" class="overlay"></div>
        <script>
            function abrirModal() {
                document.getElementById("loginModal").style.display = "block";
                document.getElementById("modalOverlay").style.display = "block";
            }
            function fecharModal() {
                document.getElementById("loginModal").style.display = "none";
                document.getElementById("modalOverlay").style.display = "none";
            }
            document.getElementById("modalOverlay").addEventListener("click", fecharModal);
        </script>';
    }
}



class Autenticacao
{
    // Constantes para tipos de usuário
    const PSICOLOGO = 'psi';
    const PACIENTE = 'user';
    private $psicologa;

    public function __construct()
    {
        $this->psicologa = new Psicologa();
    }

    // Método principal para exibir os botões de autenticação
    public function exibirBotoesAuth($tipoUsuario = null)
    {
        echo '<div class="auth-buttons">';
        if ($tipoUsuario === self::PSICOLOGO) {
            // Para psicólogo: mostra login ou logout
            if (!isset($_SESSION['usuario'])) {
                echo $this->botaoLogin();
            } else {
                echo $this->botaoLogout($tipoUsuario);
            }
        } elseif ($tipoUsuario === self::PACIENTE && !empty($_SESSION['resultado_consulta'])) {
            echo $this->botaoLogout($tipoUsuario);
        }
        echo '</div>';
    }

    // Botão de logout com tratamento diferenciado
    private function botaoLogout($tipoUsuario)
    {
        $classes = 'btn btn-outline-';
        $icone = '<i class="bi bi-box-arrow-right"></i> ';
        $name = '';
        $texto = '';

        if ($tipoUsuario === self::PACIENTE) {
            $classes .= 'secondary';
            $name = 'logoutPaci';
            $texto = 'Sair como Paciente';
        } elseif ($tipoUsuario === self::PSICOLOGO) {
            $classes .= 'primary';
            $name = 'logoutPsi';
            $texto = 'Sair como Psicólogo';
        }

        return sprintf(
            '<form method="post" class="d-inline">
                <button type="submit" name="%s" class="%s">
                    %s%s
                </button>
            </form>',
            htmlspecialchars($name),
            htmlspecialchars($classes),
            $icone,
            htmlspecialchars($texto)
        );
    }

    private function botaoLogin()
    {
        return '<button class="btn btn-outline-success" onclick="abrirModal()">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
                </button>';
    }

    public function exibirEstruturaCompleta()
    {
        return $this->botaoLogin() . $this->psicologa->exibirModalLogin();
    }

    // Processa o logout quando solicitado
    public function processarLogout()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Logout Psicólogo
            if (isset($_POST['logoutPsi']) && isset($_SESSION['usuario'])) {
                $this->realizarLogout(self::PSICOLOGO);
                $this->redirecionar();
            }
            // Logout Paciente
            elseif (isset($_POST['logoutPaci']) && !empty($_SESSION['resultado_consulta'])) {
                $this->realizarLogout(self::PACIENTE);
                $this->redirecionar();
            }
        }
    }

    // Executa o logout de fato
    private function realizarLogout($tipo)
    {
        if ($tipo === self::PSICOLOGO) {
            unset($_SESSION['usuario']);
            session_regenerate_id(true); // Melhora a segurança
        } elseif ($tipo === self::PACIENTE) {
            unset($_SESSION['resultado_consulta']);
            unset($_COOKIE['resultado_consulta']);
            unset($_COOKIE['nome_buscado']);
            session_regenerate_id(true);

        }
    }

    // Redirecionamento seguro
    private function redirecionar()
    {
        $url = filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL);
        header("Location: " . $url);
        exit;
    }
}


