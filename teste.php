<?php
session_start();

// Configurações
$basePath = 'caminho/pdf/'; // Ajuste para seu caminho real
$nomeArquivo = 'recibo_lucas_vicente_2025-05-02.pdf'; // Ou obtenha dinamicamente

// Gerar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processar requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_pdf'])) {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF inválido!');
    }

    // Buscar o arquivo no diretório
    $arquivos = scandir($basePath);
    $pdfEncontrado = null;
    
    foreach ($arquivos as $arquivo) {
        if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'pdf' && $arquivo === $nomeArquivo) {
            $pdfEncontrado = $basePath . $arquivo;
            break;
        }
    }

    // Se encontrado, exibir o PDF
    if ($pdfEncontrado && file_exists($pdfEncontrado)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . filesize($pdfEncontrado));
        readfile($pdfEncontrado);
        exit;
    } else {
        die('PDF não encontrado: ' . htmlspecialchars($nomeArquivo));
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar PDF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .btn-pdf {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-pdf:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .pdf-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" name="abrir_pdf" class="btn-pdf" title="Visualizar PDF">
            <i class="fas fa-file-pdf"></i> Abrir PDF
        </button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_pdf'])): ?>
        <div class="pdf-container">
            <?php
            // Listar todos os PDFs disponíveis (para debug)
            echo "<h3>Arquivos PDF no diretório:</h3>";
            $arquivos = scandir($basePath);
            foreach ($arquivos as $arquivo) {
                if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'pdf') {
                    echo htmlspecialchars($arquivo) . "<br>";
                }
            }
            ?>
        </div>
    <?php endif; ?>
</body>
</html>