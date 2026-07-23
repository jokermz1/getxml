// Funções utilitárias JavaScript
const App = {
    // Configurações
    config: {
        baseUrl: window.location.origin + '/getxml/public/',
        apiUrl: window.location.origin + '/getxml/public/api/',
        debug: true
    },

    // Inicialização
    init: function() {
        this.setupEventListeners();
        this.setupCSRF();
        this.setupTooltips();
        this.setupNotifications();
        this.setupFormValidation();
        this.setupDateMasks();
        this.setupLoadingStates();
    },

    // Configura event listeners globais
    setupEventListeners: function() {
        // Intercepta todos os formulários
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // Intercepta links com data-confirm
        document.addEventListener('click', this.handleLinkClick.bind(this));
        
        // Configura AJAX global
        this.setupAjax();
    },

    // Configura proteção CSRF
    setupCSRF: function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            this.csrfToken = csrfToken.getAttribute('content');
        }
    },

    // Configura tooltips
    setupTooltips: function() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', this.showTooltip);
            tooltip.addEventListener('mouseleave', this.hideTooltip);
        });
    },

    // Configura notificações
    setupNotifications: function() {
        this.createNotificationContainer();
        this.showStoredNotifications();
    },

    // Configura validação de formulários
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
        });
    },

    // Configura máscaras de data
    setupDateMasks: function() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // Já é nativo no HTML5, mas podemos adicionar validação extra
            input.addEventListener('change', this.validateDate);
        });
    },

    // Configura estados de loading
    setupLoadingStates: function() {
        const buttons = document.querySelectorAll('[data-loading]');
        buttons.forEach(button => {
            button.addEventListener('click', this.showLoading.bind(this));
        });
    },

    // Configura AJAX
    setupAjax: function() {
        // Configura headers padrão para fetch
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            // Adiciona CSRF token se disponível
            if (App.csrfToken) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-Token'] = App.csrfToken;
            }
            
            // Adiciona headers comuns
            options.headers = options.headers || {};
            options.headers['X-Requested-With'] = 'XMLHttpRequest';
            
            return originalFetch(url, options);
        };
    },

    // Manipula submissão de formulários
    handleFormSubmit: function(event) {
        const form = event.target;
        
        // Verifica se é formulário AJAX
        if (form.hasAttribute('data-ajax')) {
            event.preventDefault();
            this.submitFormAjax(form);
        }
        
        // Verifica confirmação
        if (form.hasAttribute('data-confirm')) {
            const message = form.getAttribute('data-confirm');
            if (!confirm(message)) {
                event.preventDefault();
            }
        }
    },

    // Manipula clique em links
    handleLinkClick: function(event) {
        const link = event.target.closest('a');
        
        if (link && link.hasAttribute('data-confirm')) {
            const message = link.getAttribute('data-confirm');
            if (!confirm(message)) {
                event.preventDefault();
            }
        }
        
        if (link && link.hasAttribute('data-ajax')) {
            event.preventDefault();
            this.loadLinkAjax(link);
        }
    },

    // Submete formulário via AJAX
    submitFormAjax: function(form) {
        const formData = new FormData(form);
        const method = form.method || 'POST';
        const action = form.action || window.location.href;
        const loadingText = form.getAttribute('data-loading-text') || 'Enviando...';
        
        // Mostra loading
        this.setFormLoading(form, true, loadingText);
        
        fetch(action, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            this.setFormLoading(form, false);
            
            if (data.success) {
                this.notify('success', data.message || 'Operação realizada com sucesso!');
                
                // Redireciona se especificado
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
                
                // Recarrega página se especificado
                if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
                
                // Executa callback se especificado
                if (data.callback) {
                    window[data.callback](data);
                }
            } else {
                this.notify('error', data.message || 'Erro ao realizar operação.');
                
                // Mostra erros de validação
                if (data.errors) {
                    this.showFormErrors(form, data.errors);
                }
            }
        })
        .catch(error => {
            this.setFormLoading(form, false);
            this.notify('error', 'Erro ao processar requisição.');
            if (this.config.debug) {
                console.error('Erro:', error);
            }
        });
    },

    // Carrega link via AJAX
    loadLinkAjax: function(link) {
        const url = link.href;
        const target = link.getAttribute('data-target');
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            if (target) {
                document.querySelector(target).innerHTML = html;
            } else {
                document.body.innerHTML = html;
            }
        })
        .catch(error => {
            this.notify('error', 'Erro ao carregar conteúdo.');
            if (this.config.debug) {
                console.error('Erro:', error);
            }
        });
    },

    // Define estado de loading do formulário
    setFormLoading: function(form, loading, text = 'Enviando...') {
        const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        
        buttons.forEach(button => {
            button.disabled = loading;
            button.dataset.originalText = button.textContent;
            button.textContent = loading ? text : (button.dataset.originalText || button.textContent);
        });
    },

    // Mostra erros de formulário
    showFormErrors: function(form, errors) {
        // Remove erros anteriores
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
        
        // Adiciona novos erros
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('has-error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = Array.isArray(errors[fieldName]) 
                    ? errors[fieldName][0] 
                    : errors[fieldName];
                
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
            }
        });
    },

    // Valida formulário
    validateForm: function(event) {
        const form = event.target;
        let isValid = true;
        
        // Valida campos required
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                this.showFieldError(field, 'Este campo é obrigatório');
            }
        });
        
        // Valida email
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                isValid = false;
                this.showFieldError(field, 'Email inválido');
            }
        });
        
        if (!isValid) {
            event.preventDefault();
        }
    },

    // Mostra erro de campo
    showFieldError: function(field, message) {
        field.classList.add('has-error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        field.parentNode.insertBefore(errorDiv, field.nextSibling);
    },

    // Valida email
    isValidEmail: function(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    // Valida data
    validateDate: function(event) {
        const input = event.target;
        const value = input.value;
        
        if (value && !this.isValidDate(value)) {
            this.showFieldError(input, 'Data inválida');
        }
    },

    // Verifica se data é válida
    isValidDate: function(dateString) {
        const date = new Date(dateString);
        return !isNaN(date.getTime());
    },

    // Mostra loading
    showLoading: function(event) {
        const button = event.target.closest('[data-loading]');
        if (button) {
            const text = button.getAttribute('data-loading-text') || 'Carregando...';
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = text;
        }
    },

    // Cria container de notificações
    createNotificationContainer: function() {
        if (!document.querySelector('#notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    },

    // Mostra notificação
    notify: function(type, message, duration = 5000) {
        const container = document.querySelector('#notification-container');
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 4px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
            justify-content: space-between;
        `;
        
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        notification.style.borderLeft = `4px solid ${colors[type] || colors.info}`;
        
        notification.innerHTML = `
            <span>${message}</span>
            <button style="background:none;border:none;cursor:pointer;font-size:18px;margin-left:10px;">&times;</button>
        `;
        
        // Adiciona evento de fechar
        notification.querySelector('button').addEventListener('click', () => {
            notification.remove();
        });
        
        container.appendChild(notification);
        
        // Auto-remove após duração
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, duration);
        
        // Armazena para persistência
        this.storeNotification(type, message);
    },

    // Armazena notificação
    storeNotification: function(type, message) {
        const notifications = JSON.parse(sessionStorage.getItem('notifications') || '[]');
        notifications.push({ type, message, timestamp: Date.now() });
        sessionStorage.setItem('notifications', JSON.stringify(notifications));
    },

    // Mostra notificações armazenadas
    showStoredNotifications: function() {
        const notifications = JSON.parse(sessionStorage.getItem('notifications') || '[]');
        notifications.forEach(notification => {
            this.notify(notification.type, notification.message, 3000);
        });
        sessionStorage.removeItem('notifications');
    },

    // Mostra tooltip
    showTooltip: function(event) {
        const element = event.target;
        const text = element.getAttribute('data-tooltip');
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 10000;
            white-space: nowrap;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.top = (rect.bottom + 5) + 'px';
        tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
        
        element._tooltip = tooltip;
    },

    // Esconde tooltip
    hideTooltip: function(event) {
        const element = event.target;
        if (element._tooltip) {
            element._tooltip.remove();
            delete element._tooltip;
        }
    },

    // Formata moeda
    formatCurrency: function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    // Formata data
    formatDate: function(date, format = 'dd/MM/yyyy') {
        const d = new Date(date);
        
        if (isNaN(d.getTime())) {
            return date;
        }
        
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        
        return format
            .replace('dd', day)
            .replace('MM', month)
            .replace('yyyy', year)
            .replace('HH', hours)
            .replace('mm', minutes);
    },

    // Formata CNPJ
    formatCNPJ: function(cnpj) {
        return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Copia para clipboard
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.notify('success', 'Copiado para área de transferência!');
            });
        } else {
            // Fallback para navegadores antigos
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            this.notify('success', 'Copiado para área de transferência!');
        }
    },

    // Confirma ação
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // Redireciona
    redirect: function(url) {
        window.location.href = url;
    },

    // Recarrega página
    reload: function() {
        window.location.reload();
    },

    // Obtém parâmetro da URL
    getUrlParam: function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },

    // Define parâmetro da URL
    setUrlParam: function(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    },

    // Remove parâmetro da URL
    removeUrlParam: function(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.pushState({}, '', url);
    }
};

// Adiciona estilos CSS para animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .has-error {
        border-color: #dc3545 !important;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 12px;
        margin-top: 5px;
    }
    
    .notification {
        animation: slideIn 0.3s ease-out;
    }
`;
document.head.appendChild(style);

// Inicializa quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => App.init());
} else {
    App.init();
}

// Exporta para uso global
window.App = App;
