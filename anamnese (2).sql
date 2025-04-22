-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Tempo de geração: 23/04/2025 às 01:52
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `anamnese`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `anamnese`
--

CREATE TABLE `anamnese` (
  `id_anam` int(11) NOT NULL,
  `nome_completo` varchar(100) NOT NULL,
  `idade` tinyint(3) UNSIGNED NOT NULL,
  `data_nascimento` date NOT NULL,
  `genero` varchar(50) NOT NULL,
  `estado_civil` varchar(30) DEFAULT NULL,
  `escolaridade` varchar(50) DEFAULT NULL,
  `profissao` varchar(80) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `motivo_procura` text NOT NULL,
  `situacoes_significativas` text DEFAULT NULL,
  `acompanhamento_anterior` enum('Sim','Não') DEFAULT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `data_atualizacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cd_cpf` varchar(14) DEFAULT NULL,
  `nome_responsavel` varchar(100) DEFAULT NULL,
  `grau_parentesco` varchar(30) DEFAULT NULL,
  `telefone_responsavel` varchar(20) DEFAULT NULL,
  `email_responsavel` varchar(100) DEFAULT NULL,
  `cpf_responsavel` varchar(14) DEFAULT NULL,
  `nome_social` varchar(100) DEFAULT NULL,
  `parentesco` varchar(50) DEFAULT NULL,
  `reside_com` varchar(100) DEFAULT NULL,
  `relacoes_familiares` text DEFAULT NULL,
  `onde_estuda` varchar(150) DEFAULT NULL,
  `ano_escolar` varchar(20) DEFAULT NULL,
  `profissao_atual` varchar(100) DEFAULT NULL,
  `onde_trabalha` varchar(100) DEFAULT NULL,
  `observacoes_profissional` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de anamnese psicológica de adultos e adolescente';

-- --------------------------------------------------------

--
-- Estrutura para tabela `anamnese_chefe`
--

CREATE TABLE `anamnese_chefe` (
  `cd_anam` int(11) NOT NULL,
  `nome_anam` varchar(100) NOT NULL,
  `email_anam` varchar(100) NOT NULL,
  `senha_anam` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `ultimo_acesso` datetime DEFAULT NULL,
  `status` enum('ativo','inativo','suspenso') DEFAULT 'ativo',
  `cd_cpf_anam_chefe` varchar(14) DEFAULT NULL,
  `cd_crp_anam_chefe` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de administradores do sistema de anamnese';

--
-- Despejando dados para a tabela `anamnese_chefe`
--

INSERT INTO `anamnese_chefe` (`cd_anam`, `nome_anam`, `email_anam`, `senha_anam`, `token_recuperacao`, `token_expiracao`, `data_criacao`, `ultimo_acesso`, `status`, `cd_cpf_anam_chefe`, `cd_crp_anam_chefe`) VALUES
(1, 'Luciana Venancio Nascimento dos Reis ', 'lucianavenancionreis@hotmail.com', '$2y$10$stfZYVW9wAcEqok.KWKg4ubncqbQJmApI9BZPnwOR9Mfx1g1D0AU6', NULL, NULL, '2025-04-21 13:12:17', NULL, 'ativo', '095.761.957-05', '05/33849');

-- --------------------------------------------------------

--
-- Estrutura para tabela `anamnese_logs`
--

CREATE TABLE `anamnese_logs` (
  `log_id` int(11) NOT NULL,
  `anamnese_id` int(11) NOT NULL,
  `acao` enum('INSERT','UPDATE','DELETE') DEFAULT NULL,
  `dados_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_anteriores`)),
  `usuario` varchar(50) DEFAULT NULL,
  `data_log` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `anamnese`
--
ALTER TABLE `anamnese`
  ADD PRIMARY KEY (`id_anam`),
  ADD KEY `idx_nome` (`nome_completo`),
  ADD KEY `idx_data_registro` (`data_registro`),
  ADD KEY `idx_telefone` (`telefone`);

--
-- Índices de tabela `anamnese_chefe`
--
ALTER TABLE `anamnese_chefe`
  ADD PRIMARY KEY (`cd_anam`),
  ADD UNIQUE KEY `email_anam` (`email_anam`),
  ADD KEY `idx_email` (`email_anam`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `anamnese_logs`
--
ALTER TABLE `anamnese_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `anamnese_id` (`anamnese_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anamnese`
--
ALTER TABLE `anamnese`
  MODIFY `id_anam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `anamnese_chefe`
--
ALTER TABLE `anamnese_chefe`
  MODIFY `cd_anam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `anamnese_logs`
--
ALTER TABLE `anamnese_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `anamnese_logs`
--
ALTER TABLE `anamnese_logs`
  ADD CONSTRAINT `anamnese_logs_ibfk_1` FOREIGN KEY (`anamnese_id`) REFERENCES `anamnese` (`id_anam`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
