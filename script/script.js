
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

document.addEventListener('DOMContentLoaded', function () {
    // Opcional: Ajustar altura automaticamente conforme o conteúdo
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
});
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


document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const filtroInputs = document.querySelectorAll('.filtro-input');
    const filtroRadios = document.querySelectorAll('input[name="filtro"]');

    // 1. Controle dos Filtros
    filtroInputs.forEach(input => input.style.display = 'none');
    filtroRadios.forEach(radio => {
        if (radio.checked) {
            const inputId = 'input' + radio.id.replace('filtro', '');
            document.getElementById(inputId).style.display = 'block';
        }

        radio.addEventListener('change', function() {
            filtroInputs.forEach(input => input.style.display = 'none');
            const inputId = 'input' + this.id.replace('filtro', '');
            const targetInput = document.getElementById(inputId);
            if (targetInput) {
                targetInput.style.display = 'block';
                targetInput.style.animation = 'fadeIn 0.3s ease';
            }
        });
    });

    const dataInicio = document.getElementById('dataInicio');
    const dataFim = document.getElementById('dataFim');

    if (dataInicio && dataFim) {
        dataFim.addEventListener('change', function () {
            if (dataInicio.value && this.value < dataInicio.value) {
                alert('A data final não pode ser anterior à data inicial');
                this.value = '';
            }
        });
    }
});

