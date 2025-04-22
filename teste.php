<?php
// 1. INICIAR SESSÃO (DEVE SER A PRIMEIRA LINHA)
session_start();

// 2. INCLUIR ARQUIVOS NECESSÁRIOS
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. INSTANCIAR CLASSES
$vali = new vali();

// 4. FUNÇÃO DE VERIFICAÇÃO DE LOGIN
function usuarioEstaLogado() {
    return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
}

// 5. VERIFICAÇÃO PRINCIPAL
if (!usuarioEstaLogado()) {
    $erro = '';
    
    // Processar tentativa de login
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modalLu'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        
        if ($usuario = $vali->verificarIdentidade($email, $senha)) {
            $_SESSION['logado'] = true;
            $_SESSION['usuario'] = [
                'email' => $usuario['email_anam'],
                'crp' => $usuario['crp_anam'],
                'nome' => $usuario['nome_anam']
            ];
            header('Location: '.$_SERVER['PHP_SELF']);
            exit;
        } else {
            $erro = "E-mail ou senha incorretos!";
        }
    }
    
    // Exibir modal de login
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
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
                                <label>E-mail</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Senha</label>
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
            $(document).ready(function() {
                var modal = new bootstrap.Modal(document.getElementById('loginModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// 6. USUÁRIO LOGADO - CONTINUAR COM A PÁGINA PRINCIPAL
$nomeUsuario = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Visitante');
$crpUsuario = htmlspecialchars($_SESSION['usuario']['crp'] ?? '');
$emailUsuario = htmlspecialchars($_SESSION['usuario']['email'] ?? '');

// ... [seu código HTML da página principal aqui] ...