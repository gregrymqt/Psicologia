<?php
session_start();
// Verifica o caminho absoluto do arquivo
require_once 'C:/xampp/htdocs/TiaLu/includes/conexao.php';
require_once 'C:/xampp/htdocs/TiaLu/includes/funcoes.php';
// Função auxiliar para simplificar o código
function escape($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
// Supondo que você tenha os dados do formulário em $dados_form
$dados_form = $_POST ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anamnese Psicológica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
  background-color:  rgba(135, 150, 99, 0.84);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding-top: 40px; /* Valor mínimo para evitar cortar no topo */
}

.logo-fixed {
  display: block;
  margin: -90px auto 5px; /* Margem negativa extrema para levantar ao máximo */
  width: 400px; /* Tamanho máximo da logo */
  max-width: 90%;
  z-index: 1;
  border-radius: 20px;
  position: relative;
}

.form-container {
  background-color: white;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  margin: -70px auto 0; /* Margem negativa extrema para colar no logo */
  max-width: 900px;
  padding: 30px;
  border: 2px solid rgba(1, 37, 27, 0.3);
  position: relative;
  z-index: 0;
}

.section-title {
  color: rgba(1, 37, 27, 0.63);
  border-bottom: 2px solid rgb(26, 57, 78);
  padding-bottom: 10px;
  margin-top: 30px;
  margin-bottom: 20px;
}

.required-field::after {
  content: " *";
  color: red;
}

.form-section {
  margin-bottom: 30px;
  padding: 20px;
  background-color: #f8fafc;
  border-radius: 8px;
}

.responsavel-section {
  display: none;
  background-color: #fff3cd;
}

@media (max-width: 950px) {
  body {
    padding-top: 30px;
  }
  .logo-fixed {
    width: 350px;
    margin: -80px auto 5px;
  }
  .form-container {
    margin: -60px 20px 0;
  }
}

@media (max-width: 599px) {
  body {
    padding-top: 20px;
  }
  .logo-fixed {
    width: 300px;
    margin: -60px auto 5px;
  }
  .form-container {
    margin-top: -40px;
  }
}

@media (max-width: 425px) {
  .logo-fixed {
    width: 260px;
    margin: -50px auto 5px;
  }
}

@media (max-width: 320px) {
  body {
    padding-top: 15px;
  }
  .logo-fixed {
    width: 220px;
    margin: -40px auto 5px;
  }
}
    </style>

</head>

<body>

<div class="logo-container">
  <img class="logo-fixed" src="img/marcaDaguaLu.png" alt="Logo Tia Lu">
</div>

    <div class="container py-4">
        
        <div class="form-container">
            <h1 style="color:rgba(1, 37, 27, 0.63);" class="text-center mb-4">ANAMNESE PSICOLÓGICA</h1>

            <form action="/TiaLu/processa/processa_anamnese.php" method="post" id="formAnamnese">

                <!-- DADOS DE IDENTIFICAÇÃO DO PACIENTE -->
                <div class="form-section">
                    <h2 class="section-title">DADOS DE IDENTIFICAÇÃO DO PACIENTE</h2>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nome_completo" class="form-label required-field">Nome completo</label>
                            <input type="text" class="form-control" id="nome_completo" name="nome_completo"
                                value="<?= escape($dados_form['nome_completo'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="nome_social" class="form-label">Nome social (se houver)</label>
                            <input type="text" class="form-control" id="nome_social" name="nome_social"
                                value="<?= escape($dados_form['nome_social'] ?? '') ?>">
                        </div>

                        <div class="col-md-3">
                            <label for="data_nascimento" class="form-label required-field">Data de nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                value="<?= escape($dados_form['data_nascimento'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-2">
                            <label for="idade" class="form-label required-field">Idade</label>
                            <input type="number" class="form-control" id="idade" name="idade" min="0" max="120"
                                required>
                        </div>

                        <div class="col-md-3">
                            <label for="genero" class="form-label required-field">Gênero</label>
                            <select class="form-select" id="genero" name="genero" required>
                                <option value="" selected disabled>Selecione...</option>
                                <option value="Feminino" <?= (isset($dados_form['genero']) && $dados_form['genero'] === 'Feminino') ? 'selected' : '' ?>>Feminino</option>
                                <option value="Masculino" <?= (isset($dados_form['genero']) && $dados_form['genero'] === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                <option value="Não-binário" <?= (isset($dados_form['genero']) && $dados_form['genero'] === 'Não-binário') ? 'selected' : '' ?>>Não-binário</option>
                                <option value="Outro" <?= (isset($dados_form['genero']) && $dados_form['genero'] === 'Outro') ? 'selected' : '' ?>>Outro</option>
                                <option value="Prefiro_não_informar" <?= (isset($dados_form['genero']) && $dados_form['genero'] === 'Prefiro_não_informar') ? 'selected' : '' ?>>Prefiro não
                                    informar</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="estado_civil" class="form-label">Estado civil</label>
                            <select class="form-select" id="estado_civil" name="estado_civil">
                                <option value="" selected disabled>Selecione...</option>
                                <option value="Solteiro(a)" <?= (isset($dados_form['estado_civil']) && $dados_form['estado_civil'] === 'Solteiro(a)') ? 'selected' : '' ?>>Solteiro(a)
                                </option>
                                <option value="Casado(a)" <?= (isset($dados_form['estado_civil']) && $dados_form['estado_civil'] === 'Casado(a)') ? 'selected' : '' ?>>Casado(a)</option>
                                <option value="Divorciado(a)" <?= (isset($dados_form['estado_civil']) && $dados_form['estado_civil'] === 'Divorciado(a)') ? 'selected' : '' ?>>Divorciado(a)
                                </option>
                                <option value="Viúvo(a)" <?= (isset($dados_form['estado_civil']) && $dados_form['estado_civil'] === 'Viúvo(a)') ? 'selected' : '' ?>>Viúvo(a)</option>
                                <option value="Separado(a)" <?= (isset($dados_form['estado_civil']) && $dados_form['estado_civil'] === 'Separado(a)') ? 'selected' : '' ?>>Separado(a)
                                </option>
                                <option value="União_estável" <?= (isset($dados_form['estado_civil']) && $dados_form['estado_civil'] === 'União_estável') ? 'selected' : '' ?>>União_estável
                                </option>
                            </select>
                        </div>




                        <div class="col-md-4">
                            <label for="escolaridade" class="form-label">Escolaridade</label>
                            <select class="form-select" id="escolaridade" name="escolaridade">
                                <option value="" selected disabled>Selecione...</option>
                                <option value="Fundamental incompleto">Fundamental incompleto</option>
                                <option value="Fundamental completo">Fundamental completo</option>
                                <option value="Médio incompleto">Médio incompleto</option>
                                <option value="Médio completo">Médio completo</option>
                                <option value="Superior incompleto">Superior incompleto</option>
                                <option value="Superior completo">Superior completo</option>
                                <option value="Pós-graduação">Pós-graduação</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label for="profissao" class="form-label">Profissão (ou série/ano escolar)</label>
                            <input type="text" class="form-control" id="profissao" name="profissao">
                        </div>

                        <div class="col-12">
                            <label for="endereco" class="form-label required-field">Endereço completo</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" required>
                        </div>

                        <div class="col-md-4">
                            <label for="telefone" class="form-label required-field">Telefone / WhatsApp</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" required>
                        </div>

                        <div class="col-md-4">
                            <label for="email" class="form-label required-field">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="col-md-4">
                            <label for="cpf" class="form-label required-field">CPF do paciente</label>
                            <input type="text" class="form-control" id="cpf" name="cpf"
                                value="<?= escape($dados_form['cpf'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- DADOS DO RESPONSÁVEL LEGAL (se menor de 18 anos) -->
                <div class="form-section responsavel-section" id="responsavelSection">
                    <h2 class="section-title">DADOS DO RESPONSÁVEL LEGAL (se menor de 18 anos)</h2>
                    <p class="text-muted">(para fins de emissão de recibo de pagamento)</p>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nome_responsavel" class="form-label">Nome completo do responsável</label>
                            <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel">
                        </div>

                        <div class="col-md-4">
                            <label for="parentesco" class="form-label">Grau de parentesco</label>
                            <input type="text" class="form-control" id="parentesco" name="parentesco">
                        </div>

                        <div class="col-md-4">
                            <label for="telefone_responsavel" class="form-label">Telefone / WhatsApp</label>
                            <input type="tel" class="form-control" id="telefone_responsavel"
                                name="telefone_responsavel">
                        </div>

                        <div class="col-md-4">
                            <label for="email_responsavel" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email_responsavel" name="email_responsavel">
                        </div>

                        <div class="col-md-4">
                            <label for="cpf_responsavel" class="form-label">CPF do responsável</label>
                            <input type="text" class="form-control" id="cpf_responsavel" name="cpf_responsavel">
                        </div>
                    </div>
                </div>

                <!-- MOTIVO DA PROCURA POR ATENDIMENTO PSICOLÓGICO -->
                <div class="form-section">
                    <h2 class="section-title">MOTIVO DA PROCURA POR ATENDIMENTO PSICOLÓGICO</h2>

                    <div class="mb-3">
                        <label for="motivo_procura" class="form-label required-field">Qual é a principal queixa ou
                            demanda?</label>
                        <textarea class="form-control" id="motivo_procura" name="motivo_procura" rows="4"
                            required><?= escape($dados_form['motivo_procura'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- HISTÓRICO FAMILIAR E SOCIAL DO PACIENTE -->
                <div class="form-section">
                    <h2 class="section-title">HISTÓRICO FAMILIAR E SOCIAL DO PACIENTE</h2>

                    <div class="mb-3">
                        <label for="reside_com" class="form-label">Com quem reside atualmente?</label>
                        <textarea class="form-control" id="reside_com" name="reside_com" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="relacoes_familiares" class="form-label">Relações familiares e convivência:</label>
                        <textarea class="form-control" id="relacoes_familiares" name="relacoes_familiares"
                            rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="situacoes_significativas" class="form-label">Há situações significativas que
                            impactaram a vida do(a) paciente? (luto, separação, mudanças de residência, mudança de
                            escola e etc.):</label>
                        <textarea class="form-control" id="situacoes_significativas" name="situacoes_significativas"
                            rows="3"></textarea>
                    </div>
                </div>

                <!-- HISTÓRICO PESSOAL E ESCOLAR/PROFISSIONAL -->
                <div class="form-section">
                    <h2 class="section-title">HISTÓRICO ESCOLAR / PROFISSIONAL</h2>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Já realizou acompanhamento psicológico ou psiquiátrico
                                anteriormente?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="acompanhamento_anterior"
                                    id="acompanhamento_sim" value="Sim"
                                    <?= (isset($dados_form['acompanhamento_anterior']) && $dados_form['acompanhamento_anterior'] === 'Sim') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="acompanhamento_sim">Sim</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="acompanhamento_anterior"
                                    id="acompanhamento_nao" value="Não"
                                    <?= (!isset($dados_form['acompanhamento_anterior']) || $dados_form['acompanhamento_anterior'] === 'Não') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="acompanhamento_nao">Não</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="situacao_escolar_profissional" class="form-label">Situação escolar/profissional
                            atual:</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="onde_estuda" class="form-label">Onde estuda? (se menor)</label>
                                <input type="text" class="form-control" id="onde_estuda" name="onde_estuda">
                            </div>
                            <div class="col-md-6">
                                <label for="ano_escolar" class="form-label">Em que ano escolar está? (se menor)</label>
                                <input type="text" class="form-control" id="ano_escolar" name="ano_escolar">
                            </div>
                            <div class="col-md-6">
                                <label for="profissao_atual" class="form-label">Profissão:</label>
                                <input type="text" class="form-control" id="profissao_atual" name="profissao_atual">
                            </div>
                            <div class="col-md-6">
                                <label for="onde_trabalha" class="form-label">Onde trabalha atualmente?</label>
                                <input type="text" class="form-control" id="onde_trabalha" name="onde_trabalha">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes_profissional" class="form-label">OBSERVAÇÕES DO PROFISSIONAL (SE
                            NECESSÁRIO)</label>
                        <textarea class="form-control" id="observacoes_profissional" name="observacoes_profissional"
                            rows="3"></textarea>
                    </div>
                </div>

                
                    <button type="submit" class="btn btn-primary btn-lg" >Enviar </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <script>
        $(document).ready(function () {
            // Máscaras para os campos
            $('#telefone').mask('(00) 00000-0000');
            $('#cpf').mask('000.000.000-00');
            $('#cpf_responsavel').mask('000.000.000-00');
            $('#telefone_responsavel').mask('(00) 00000-0000');

            // Mostrar/ocultar seção do responsável baseado na idade
            $('#idade').on('change', function () {
                if ($(this).val() < 18) {
                    $('#responsavelSection').fadeIn();
                    // Torna os campos do responsável obrigatórios
                    $('#nome_responsavel, #cpf_responsavel').attr('required', true);
                } else {
                    $('#responsavelSection').fadeOut();
                    // Remove a obrigatoriedade
                    $('#nome_responsavel, #cpf_responsavel').removeAttr('required');
                }
            });

            // Calcular idade automaticamente quando selecionar data de nascimento
            $('#data_nascimento').on('change', function () {
                const birthDate = new Date($(this).val());
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                $('#idade').val(age);
                // Dispara o evento change da idade para mostrar/ocultar responsável
                $('#idade').trigger('change');
            });
        });
    </script>
</body>
</html>

