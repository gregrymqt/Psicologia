function showDocument(documentType, element) {
    // Salva no localStorage
    localStorage.setItem('ultimaAbaAtiva', documentType);
    
    // Esconde todos os conteúdos
    document.querySelectorAll('.document-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Remove a classe active de todas as abas
    document.querySelectorAll('.document-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Mostra o conteúdo selecionado
    const content = document.getElementById(documentType + '-content');
    if (content) {
        content.style.display = 'block';
    }
    
    // Ativa a aba selecionada
    if (element) {
        element.classList.add('active');
    }
}

// Carregar a última aba ativa
document.addEventListener('DOMContentLoaded', function() {
    // Ajustes para mobile
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.style.minHeight = '100px';
        textarea.addEventListener('focus', function() {
            this.style.minHeight = '150px';
        });
        textarea.addEventListener('blur', function() {
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

    // Restaurar última aba ou ativar a primeira
    const ultimaAba = localStorage.getItem('ultimaAbaAtiva');
    if (ultimaAba) {
        const aba = document.querySelector(`.document-tab[onclick*="${ultimaAba}"]`);
        if (aba) {
            // Simula o clique na aba
            showDocument(ultimaAba, aba);
        }
    } else {
        // Ativa a primeira aba por padrão
        const firstTab = document.querySelector('.document-tab');
        if (firstTab) {
            showDocument('atestado', firstTab);
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

    if (!document.querySelector('input[name="filtro"]:checked') && filtroRadios.length > 0) {
        filtroRadios[0].checked = true;
    }
    
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

});



