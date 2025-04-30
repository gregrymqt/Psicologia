<?php
session_start();
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/includes/conexao.php';
require_once '/home/u104715539/domains/lucianavenanciopsipp.com.br//public_html/includes/funcoes.php';
$vali= new Vali();

if ($_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $erros = [];
    $dados = [
        'nome_completo' => $vali->sanitizarTexto($_POST['nome_completo'] ?? ''),
        'nome_social' => $vali->sanitizarTexto($_POST['nome_social'] ?? ''),
        'data_nascimento' => $vali->sanitizarTexto($_POST['data_nascimento'] ?? ''),
        'idade' => (int) ($_POST['idade'] ?? 0),
        'genero' => $vali->sanitizarTexto($_POST['genero'] ?? ''),
        'estado_civil' => $vali->sanitizarTexto($_POST['estado_civil'] ?? ''),
        'escolaridade' => $vali->sanitizarTexto($_POST['escolaridade'] ?? ''),
        'profissao' => $vali->sanitizarTexto($_POST['profissao'] ?? ''),
        'cep' => $vali->sanitizarTexto($_POST['cep'] ?? ''),
        'telefone' => $vali->sanitizarTexto($_POST['telefone'] ?? ''),
        'email' => $vali->sanitizarTexto($_POST['email'] ?? ''),
        'cpf' => $vali->sanitizarTexto($_POST['cpf'] ?? ''),
        'nome_responsavel' => $vali->sanitizarTexto($_POST['nome_responsavel'] ?? ''),
        'parentesco' => $vali->sanitizarTexto($_POST['parentesco'] ?? ''),
        'telefone_responsavel' => $vali->sanitizarTexto($_POST['telefone_responsavel'] ?? ''),
        'email_responsavel' => $vali->sanitizarTexto($_POST['email_responsavel'] ?? ''),
        'cpf_responsavel' => $vali->sanitizarTexto($_POST['cpf_responsavel'] ?? ''),
        'acompanhamento_anterior' => !empty($_POST['acompanhamento_anterior']),
        'reside_com' => $vali->sanitizarTexto($_POST['reside_com'] ?? ''),
        'relacoes_familiares' => $vali->sanitizarTexto($_POST['relacoes_familiares'] ?? ''),
        'situacoes_significativas' => $vali->sanitizarTexto($_POST['situacoes_significativas'] ?? ''),
        'onde_estuda' => $vali->sanitizarTexto($_POST['onde_estuda'] ?? ''),
        'ano_escolar' => (int) ($_POST['ano_escolar'] ?? 0),
        'profissao_atual' => $vali->sanitizarTexto($_POST['profissao_atual'] ?? ''),
        'onde_trabalha' => $vali->sanitizarTexto($_POST['onde_trabalha'] ?? ''),
        'observacoes_profissional' => $vali->sanitizarTexto($_POST['observacoes_profissional'] ?? '')
    ];
    if (empty($dados['nome_completo'])) {
        $erros[] = "O nome completo é obrigatório.";
    }
    if (!$vali->validarData($dados['data_nascimento'])) {
        $erros[] = "Data de nascimento inválida.";
    }
    if ($dados['idade'] < 0 || $dados['idade'] > 120) {
        $erros[] = "Idade inválida.";
    }
    if (empty($dados['genero'])) {
        $erros[] = "O gênero é obrigatório.";
    }
    if (empty($dados['cep'])) {
        $erros[] = "O cep é obrigatório.";
    }
    if (!$vali->validarTelefone($dados['telefone'])) {
        $erros[] = "Telefone inválido.";
    }
    if (!$vali->validarCPF($dados['cpf'])) {
        $erros[] = "CPF inválido.";
    }
    // Validações condicionais (se menor de 18 anos)
    if ($dados['idade'] < 18) {
        if (empty($dados['nome_responsavel'])) {
            $erros[] = "Nome do responsável é obrigatório para menores de 18 anos.";
        }
        if (!$vali->validarCPF($dados['cpf_responsavel'])) {
            $erros[] = "CPF do responsável inválido.";
        }
    }
    // Validação de e-mail se fornecido
    if (!empty($dados['email']) && !$vali->validarEmail($dados['email'])) {
        $erros[] = "E-mail do paciente inválido.";
    }
    if (!empty($dados['email_responsavel']) && !$vali->validarEmail($dados['email_responsavel'])) {
        $erros[] = "E-mail do responsável inválido.";
    }
    if ($dados['idade'] < 18 && !$vali->validarTelefone($dados['telefone_responsavel'])) {
        $erros[] = "Telefone do responsável inválido ou ausente.";
    }
    $sql = "INSERT INTO anamnese (
    nome_completo, nome_social, data_nascimento, idade, genero, estado_civil, escolaridade, 
    profissao, cep, telefone, email, cpf, nome_responsavel, parentesco, 
    telefone_responsavel, email_responsavel, CPF_REPONSAVEL, acompanhamento_anterior, reside_com, 
    relacoes_familiares, situacoes_significativas,  onde_estuda, 
    ano_escolar, profissao_atual, onde_trabalha, observacoes_profissional, data_registro
) VALUES (
    :nome_completo, :nome_social, :data_nascimento, :idade, :genero, :estado_civil, :escolaridade, 
    :profissao, :cep, :telefone, :email, :cpf, :nome_responsavel, :parentesco, 
    :telefone_responsavel, :email_responsavel, :cpf_responsavel, :acompanhamento_anterior, :reside_com, 
    :relacoes_familiares, :situacoes_significativas, :onde_estuda, 
    :ano_escolar, :profissao_atual, :onde_trabalha, :observacoes_profissional, NOW()
)";
    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome_completo', $dados['nome_completo']);
        $stmt->bindParam(':nome_social', $dados['nome_social']);
        $stmt->bindParam(':data_nascimento', $dados['data_nascimento']);
        $stmt->bindParam(':idade', $dados['idade'], PDO::PARAM_INT);
        $stmt->bindParam(':genero', $dados['genero']);
        $stmt->bindParam(':estado_civil', $dados['estado_civil']);
        $stmt->bindParam(':escolaridade', $dados['escolaridade']);
        $stmt->bindParam(':profissao', $dados['profissao']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':cpf', $dados['cpf']);
        $stmt->bindParam(':nome_responsavel', $dados['nome_responsavel']);
        $stmt->bindParam(':parentesco', $dados['parentesco']);
        $stmt->bindParam(':telefone_responsavel', $dados['telefone_responsavel']);
        $stmt->bindParam(':email_responsavel', $dados['email_responsavel']);
        $stmt->bindParam(':cpf_responsavel', $dados['cpf_responsavel']);
        $stmt->bindParam(':acompanhamento_anterior', $dados['acompanhamento_anterior']);
        $stmt->bindParam(':reside_com', $dados['reside_com']);
        $stmt->bindParam(':relacoes_familiares', $dados['relacoes_familiares']);
        $stmt->bindParam(':situacoes_significativas', $dados['situacoes_significativas']);
        $stmt->bindParam(':onde_estuda', $dados['onde_estuda']);
        $stmt->bindParam(':ano_escolar', $dados['ano_escolar']);
        $stmt->bindParam(':profissao_atual', $dados['profissao_atual']);
        $stmt->bindParam(':onde_trabalha', $dados['onde_trabalha']);
        $stmt->bindParam(':observacoes_profissional', $dados['observacoes_profissional']);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar a query");
        }
        $id_paciente = $conn->lastInsertId();

        $conn->commit();
        header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
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

   

      