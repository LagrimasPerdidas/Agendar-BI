/**
 * ============================================
 * SISTEMA DE AGENDAMENTO DO BILHETE DE IDENTIDADE
 * República de Angola - SIAC/DNAICC
 * Versão Profissional 2.0
 * ============================================
 */

// ============================================
// CONFIGURAÇÃO E CONSTANTES
// ============================================

const CONFIG = {
    APP_NAME: 'SIAC - Bilhete de Identidade',
    VERSION: '2.0.0',
    SESSION_KEY: 'siac_session',
    TOKEN_KEY: 'siac_token',
    THEME_KEY: 'siac_theme',
    NOTIFICATIONS_KEY: 'siac_notifications',
    ANIMATION_DURATION: 300,
    DEBOUNCE_DELAY: 300,
    TOAST_DURATION: 5000
};

// ============================================
// UTILITÁRIOS
// ============================================

const utils = {
    // Formatação de data
    formatDate: (date, options = {}) => {
        if (!date) return '';
        const d = new Date(date);
        const defaultOptions = { day: '2-digit', month: '2-digit', year: 'numeric' };
        return d.toLocaleDateString('pt-AO', { ...defaultOptions, ...options });
    },

    // Formatação de data e hora
    formatDateTime: (date) => {
        if (!date) return '';
        const d = new Date(date);
        return d.toLocaleDateString('pt-AO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Formatação de moeda (Kwanza)
    formatCurrency: (value) => {
        return new Intl.NumberFormat('pt-AO', {
            style: 'currency',
            currency: 'AOA',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    },

    // Formatação de número
    formatNumber: (value) => {
        return new Intl.NumberFormat('pt-AO').format(value);
    },

    // Formatação de telefone angolano
    formatPhone: (phone) => {
        if (!phone) return '';
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 9) {
            return `+244 ${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`;
        }
        return phone;
    },

    // Geração de código único
    generateCode: (prefix = 'BI') => {
        const timestamp = Date.now().toString(36).toUpperCase();
        const random = Math.random().toString(36).substring(2, 6).toUpperCase();
        return `${prefix}-${timestamp}-${random}`;
    },

    // Geração de ID único
    generateId: () => {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    // Validação de email
    isValidEmail: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validação de telefone angolano
    isValidPhone: (phone) => {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length === 9 && cleaned.startsWith('9');
    },

    // Validação de NIF angolano
    isValidNIF: (nif) => {
        const re = /^\d{10}$/;
        return re.test(nif);
    },

    // Validação de BI angolano
    isValidBI: (bi) => {
        const re = /^\d{9}[A-Za-z]{2}\d{3}$/;
        return re.test(bi);
    },

    // Sanitização contra XSS
    sanitize: (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // Debounce
    debounce: (func, wait = CONFIG.DEBOUNCE_DELAY) => {
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

    // Throttle
    throttle: (func, limit) => {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Scroll suave
    scrollTo: (element, offset = 80) => {
        const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top, behavior: 'smooth' });
    },

    // Copiar para clipboard
    copyToClipboard: async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            return false;
        }
    },

    // Formatar tempo relativo
    timeAgo: (date) => {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        const intervals = {
            ano: 31536000,
            mês: 2592000,
            semana: 604800,
            dia: 86400,
            hora: 3600,
            minuto: 60,
            segundo: 1
        };

        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval > 1 ? 's' : ''} atrás`;
            }
        }
        return 'Agora';
    },

    // Capitalizar texto
    capitalize: (str) => {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    // Máscara de input
    applyMask: (value, mask) => {
        let i = 0;
        let result = '';
        for (let m of mask) {
            if (m === '#') {
                if (i < value.length) {
                    result += value[i];
                    i++;
                }
            } else {
                result += m;
            }
        }
        return result;
    }
};

// ============================================
// GERENCIAMENTO DE SESSÃO
// ============================================

const session = {
    getUser: () => {
        try {
            const data = localStorage.getItem(CONFIG.SESSION_KEY);
            return data ? JSON.parse(data) : null;
        } catch {
            return null;
        }
    },

    setUser: (user) => {
        localStorage.setItem(CONFIG.SESSION_KEY, JSON.stringify(user));
    },

    clearUser: () => {
        localStorage.removeItem(CONFIG.SESSION_KEY);
        localStorage.removeItem(CONFIG.TOKEN_KEY);
    },

    isAuthenticated: () => {
        return !!session.getUser();
    },

    isAdmin: () => {
        const user = session.getUser();
        return user && user.role === 'admin';
    },

    updateLastActivity: () => {
        const user = session.getUser();
        if (user) {
            user.lastActivity = new Date().toISOString();
            session.setUser(user);
        }
    }
};

// ============================================
// ARMAZENAMENTO LOCAL
// ============================================

const storage = {
    get: (key) => {
        try {
            const data = localStorage.getItem(`siac_${key}`);
            return data ? JSON.parse(data) : null;
        } catch {
            return null;
        }
    },

    set: (key, value) => {
        localStorage.setItem(`siac_${key}`, JSON.stringify(value));
    },

    remove: (key) => {
        localStorage.removeItem(`siac_${key}`);
    },

    getMarcacoes: () => storage.get('marcacoes') || [],
    setMarcacoes: (marcacoes) => storage.set('marcacoes', marcacoes),
    
    getPostos: () => storage.get('postos') || getDefaultPostos(),
    setPostos: (postos) => storage.set('postos', postos),
    
    getServicos: () => storage.get('servicos') || getDefaultServicos(),
    setServicos: (servicos) => storage.set('servicos', servicos),
    
    getUsers: () => storage.get('users') || [],
    setUsers: (users) => storage.set('users', users)
};

// Dados padrão
function getDefaultPostos() {
    return [
        { id: 1, nome: 'SIAC Luanda - Cazenga', endereco: 'Bairro Cazenga, Luanda', telefone: '+244 923 456 789', vagas: { '2026-02-02': 50, '2026-02-03': 45, '2026-02-04': 40 }, ativo: true },
        { id: 2, nome: 'SIAC Luanda - Maianga', endereco: 'Avenida Revolução de Outubro, Maianga', telefone: '+244 923 456 790', vagas: { '2026-02-02': 30, '2026-02-03': 35, '2026-02-04': 25 }, ativo: true },
        { id: 3, nome: 'SIAC Benguela', endereco: 'Centro da Cidade, Benguela', telefone: '+244 923 456 791', vagas: { '2026-02-02': 20, '2026-02-03': 25, '2026-02-04': 20 }, ativo: true },
        { id: 4, nome: 'SIAC Huambo', endereco: 'Avenida da Independência, Huambo', telefone: '+244 923 456 792', vagas: { '2026-02-02': 15, '2026-02-03': 20, '2026-02-04': 15 }, ativo: true },
        { id: 5, nome: 'SIAC Lubango', endereco: 'Centro Comercial, Lubango', telefone: '+244 923 456 793', vagas: { '2026-02-02': 10, '2026-02-03': 15, '2026-02-04': 10 }, ativo: true }
    ];
}

function getDefaultServicos() {
    return [
        { id: 'primeira-via', nome: '1ª Via do BI', descricao: 'Emissão do primeiro Bilhete de Identidade', preco: 3500, documentos: ['Certidão de nascimento', 'Fotografia tipo passe', 'Comprovativo de residência'] },
        { id: 'renovacao', nome: 'Renovação de BI', descricao: 'Renovação do Bilhete de Identidade vencido', preco: 2500, documentos: ['BI anterior', 'Fotografia tipo passe', 'Comprovativo de residência atualizado'] },
        { id: 'segunda-via', nome: '2ª Via do BI', descricao: 'Emissão de segunda via em caso de perda ou furto', preco: 5000, documentos: ['Declaração de perda/furto (polícia)', 'Fotografia tipo passe', 'Comprovativo de residência'] }
    ];
}

// ============================================
// SISTEMA DE NOTIFICAÇÕES
// ============================================

const notifications = {
    container: null,

    init: () => {
        if (!notifications.container) {
            notifications.container = document.createElement('div');
            notifications.container.className = 'toast-container';
            document.body.appendChild(notifications.container);
        }
    },

    show: (message, type = 'info', duration = CONFIG.TOAST_DURATION) => {
        notifications.init();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-times-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };

        toast.innerHTML = `
            <span class="toast-icon">${icons[type]}</span>
            <span class="toast-message">${utils.sanitize(message)}</span>
            <button class="toast-close" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: auto; padding: 0.25rem;">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Botão de fechar
        toast.querySelector('.toast-close').addEventListener('click', () => {
            notifications.hide(toast);
        });

        notifications.container.appendChild(toast);

        // Auto-remover
        const timeout = setTimeout(() => {
            notifications.hide(toast);
        }, duration);

        // Pausar ao hover
        toast.addEventListener('mouseenter', () => clearTimeout(timeout));
        toast.addEventListener('mouseleave', () => {
            setTimeout(() => notifications.hide(toast), 2000);
        });

        return toast;
    },

    hide: (toast) => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    },

    success: (message, duration) => notifications.show(message, 'success', duration),
    error: (message, duration) => notifications.show(message, 'error', duration),
    warning: (message, duration) => notifications.show(message, 'warning', duration),
    info: (message, duration) => notifications.show(message, 'info', duration),

    // Notificação com ação
    confirm: (message, onConfirm, onCancel) => {
        notifications.init();

        const toast = document.createElement('div');
        toast.className = 'toast info';
        toast.style.flexDirection = 'column';
        toast.style.alignItems = 'flex-start';
        
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem; width: 100%;">
                <span class="toast-icon"><i class="fas fa-question-circle"></i></span>
                <span class="toast-message">${utils.sanitize(message)}</span>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; margin-left: 2rem;">
                <button class="btn btn-sm btn-primary confirm-yes">Sim</button>
                <button class="btn btn-sm btn-outline confirm-no">Não</button>
            </div>
        `;

        toast.querySelector('.confirm-yes').addEventListener('click', () => {
            notifications.hide(toast);
            if (onConfirm) onConfirm();
        });

        toast.querySelector('.confirm-no').addEventListener('click', () => {
            notifications.hide(toast);
            if (onCancel) onCancel();
        });

        notifications.container.appendChild(toast);
    }
};

// ============================================
// VALIDAÇÃO DE FORMULÁRIOS
// ============================================

const formValidation = {
    validateField: (field, rules = {}) => {
        const value = field.value.trim();
        const errors = [];
        const formGroup = field.closest('.form-group');
        const errorElement = formGroup?.querySelector('.form-error');

        // Validar required primeiro
        if (rules.required && !value) {
            errors.push('Este campo é obrigatório');
        }

        // Outras validações apenas se houver valor
        if (value) {
            if (rules.minLength && value.length < rules.minLength) {
                errors.push(`Mínimo de ${rules.minLength} caracteres`);
            }

            if (rules.maxLength && value.length > rules.maxLength) {
                errors.push(`Máximo de ${rules.maxLength} caracteres`);
            }

            if (rules.min && Number(value) < rules.min) {
                errors.push(`Valor mínimo: ${rules.min}`);
            }

            if (rules.max && Number(value) > rules.max) {
                errors.push(`Valor máximo: ${rules.max}`);
            }

            if (rules.email && !utils.isValidEmail(value)) {
                errors.push('Email inválido');
            }

            if (rules.phone && !utils.isValidPhone(value)) {
                errors.push('Número de telefone inválido');
            }

            if (rules.nif && !utils.isValidNIF(value)) {
                errors.push('NIF inválido (10 dígitos)');
            }

            if (rules.bi && !utils.isValidBI(value)) {
                errors.push('Número de BI inválido');
            }

            if (rules.pattern && !rules.pattern.test(value)) {
                errors.push(rules.patternMessage || 'Formato inválido');
            }

            if (rules.match) {
                const matchField = document.querySelector(rules.match);
                if (matchField && value !== matchField.value) {
                    errors.push('Os campos não coincidem');
                }
            }

            if (rules.custom && typeof rules.custom === 'function') {
                const customError = rules.custom(value);
                if (customError) {
                    errors.push(customError);
                }
            }
        }

        // Atualizar UI
        if (errors.length > 0) {
            field.classList.add('error');
            field.classList.remove('success');
            if (errorElement) {
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errors[0]}`;
                errorElement.style.display = 'flex';
            }
            return { valid: false, error: errors[0] };
        } else {
            field.classList.remove('error');
            field.classList.add('success');
            if (errorElement) {
                errorElement.innerHTML = '';
                errorElement.style.display = 'none';
            }
            return { valid: true };
        }
    },

    validateForm: (form) => {
        const fields = form.querySelectorAll('[data-validate]');
        let isValid = true;
        const results = [];

        fields.forEach(field => {
            let rules;
            try {
                rules = JSON.parse(field.dataset.validate || '{}');
            } catch {
                rules = {};
            }
            const result = formValidation.validateField(field, rules);
            results.push({ field, result });
            if (!result.valid) {
                isValid = false;
            }
        });

        return { valid: isValid, results };
    },

    clearValidation: (form) => {
        const fields = form.querySelectorAll('.form-input, .form-select, .form-textarea');
        fields.forEach(field => {
            field.classList.remove('error', 'success');
            const formGroup = field.closest('.form-group');
            const errorElement = formGroup?.querySelector('.form-error');
            if (errorElement) {
                errorElement.innerHTML = '';
                errorElement.style.display = 'none';
            }
        });
    },

    // Adicionar validação em tempo real
    addLiveValidation: (form) => {
        const fields = form.querySelectorAll('[data-validate]');
        fields.forEach(field => {
            field.addEventListener('blur', () => {
                let rules;
                try {
                    rules = JSON.parse(field.dataset.validate || '{}');
                } catch {
                    rules = {};
                }
                formValidation.validateField(field, rules);
            });

            // Limpar erro ao digitar
            field.addEventListener('input', () => {
                if (field.classList.contains('error')) {
                    const formGroup = field.closest('.form-group');
                    const errorElement = formGroup?.querySelector('.form-error');
                    field.classList.remove('error');
                    if (errorElement) {
                        errorElement.innerHTML = '';
                        errorElement.style.display = 'none';
                    }
                }
            });
        });
    }
};

// ============================================
// NAVEGAÇÃO
// ============================================

const navigation = {
    init: () => {
        // Menu mobile toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const nav = document.querySelector('.nav');

        if (menuToggle && nav) {
            menuToggle.addEventListener('click', () => {
                const isActive = nav.classList.toggle('active');
                menuToggle.setAttribute('aria-expanded', isActive);
                menuToggle.innerHTML = isActive ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });

            // Fechar menu ao clicar fora
            document.addEventListener('click', (e) => {
                if (!nav.contains(e.target) && !menuToggle.contains(e.target) && nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    menuToggle.setAttribute('aria-expanded', 'false');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        }

        // Atualizar link ativo
        navigation.setActiveLink();

        // Smooth scroll para âncoras
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId !== '#') {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        e.preventDefault();
                        utils.scrollTo(targetElement);
                    }
                }
            });
        });
    },

    setActiveLink: () => {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'index.html')) {
                link.classList.add('active');
            }
        });
    },

    // Scroll suave para seção
    scrollToSection: (sectionId) => {
        const section = document.getElementById(sectionId);
        if (section) {
            utils.scrollTo(section);
        }
    }
};

// ============================================
// MODAIS
// ============================================

const modals = {
    open: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focar no primeiro input
            setTimeout(() => {
                const firstInput = modal.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    },

    close: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    closeAll: () => {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    },

    init: () => {
        // Fechar ao clicar no overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Fechar ao clicar no botão de fechar
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal-overlay');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                modals.closeAll();
            }
        });
    }
};

// ============================================
// FAQ ACCORDION
// ============================================

const faq = {
    init: () => {
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.closest('.faq-item');
                const isActive = item.classList.contains('active');

                // Fechar todos (opcional - comportamento accordion)
                // document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));

                // Toggle do item clicado
                if (isActive) {
                    item.classList.remove('active');
                } else {
                    item.classList.add('active');
                }
            });
        });
    }
};

// ============================================
// MÁSCARAS DE INPUT
// ============================================

const masks = {
    init: () => {
        // Telefone
        document.querySelectorAll('[data-mask="phone"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.startsWith('244')) value = value.slice(3);
                if (value.length > 9) value = value.slice(0, 9);
                
                if (value.length >= 3) {
                    value = `+244 ${value.slice(0, 3)} ${value.slice(3, 6)} ${value.slice(6)}`;
                } else if (value.length > 0) {
                    value = `+244 ${value}`;
                }
                
                e.target.value = value.trim();
            });
        });

        // BI
        document.querySelectorAll('[data-mask="bi"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (value.length > 14) value = value.slice(0, 14);
                e.target.value = value;
            });
        });

        // NIF
        document.querySelectorAll('[data-mask="nif"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) value = value.slice(0, 10);
                e.target.value = value;
            });
        });

        // Data
        document.querySelectorAll('[data-mask="date"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 8) value = value.slice(0, 8);
                
                if (value.length >= 5) {
                    value = `${value.slice(0, 2)}/${value.slice(2, 4)}/${value.slice(4)}`;
                } else if (value.length >= 3) {
                    value = `${value.slice(0, 2)}/${value.slice(2)}`;
                }
                
                e.target.value = value;
            });
        });
    }
};

// ============================================
// AUTENTICAÇÃO
// ============================================

const auth = {
    register: (userData) => {
        const users = storage.getUsers();
        
        // Verificar se email já existe
        if (users.find(u => u.email === userData.email)) {
            return { success: false, message: 'Este email já está registado' };
        }

        // Verificar se NIF já existe
        if (users.find(u => u.nif === userData.nif)) {
            return { success: false, message: 'Este NIF já está registado' };
        }

        // Criar novo usuário
        const newUser = {
            id: Date.now(),
            ...userData,
            role: 'user',
            createdAt: new Date().toISOString(),
            ativo: true,
            lastActivity: new Date().toISOString()
        };

        users.push(newUser);
        storage.setUsers(users);

        // Login automático
        session.setUser(newUser);

        return { success: true, message: 'Registo realizado com sucesso!', user: newUser };
    },

    login: (email, password) => {
        const users = storage.getUsers();
        const user = users.find(u => u.email === email && u.password === password);

        if (!user) {
            return { success: false, message: 'Email ou senha incorretos' };
        }

        if (!user.ativo) {
            return { success: false, message: 'Conta desactivada. Contacte o suporte.' };
        }

        // Atualizar última atividade
        user.lastActivity = new Date().toISOString();
        storage.setUsers(users);

        session.setUser(user);
        return { success: true, message: 'Login realizado com sucesso!', user };
    },

    logout: () => {
        session.clearUser();
        window.location.href = 'index.html';
    },

    recoverPassword: (email) => {
        const users = storage.getUsers();
        const user = users.find(u => u.email === email);

        if (!user) {
            return { success: false, message: 'Email não encontrado' };
        }

        // Simular envio de email
        return { success: true, message: 'Instruções de recuperação enviadas para o seu email' };
    },

    updateProfile: (userId, data) => {
        const users = storage.getUsers();
        const index = users.findIndex(u => u.id === userId);

        if (index === -1) {
            return { success: false, message: 'Usuário não encontrado' };
        }

        // Verificar se email já existe (se estiver a alterar)
        if (data.email && data.email !== users[index].email) {
            if (users.find(u => u.email === data.email)) {
                return { success: false, message: 'Este email já está em uso' };
            }
        }

        users[index] = { ...users[index], ...data, updatedAt: new Date().toISOString() };
        storage.setUsers(users);
        
        // Atualizar sessão se for o usuário atual
        const currentUser = session.getUser();
        if (currentUser && currentUser.id === userId) {
            session.setUser(users[index]);
        }

        return { success: true, message: 'Perfil actualizado com sucesso!' };
    },

    changePassword: (userId, currentPassword, newPassword) => {
        const users = storage.getUsers();
        const index = users.findIndex(u => u.id === userId);

        if (index === -1) {
            return { success: false, message: 'Usuário não encontrado' };
        }

        if (users[index].password !== currentPassword) {
            return { success: false, message: 'Senha actual incorreta' };
        }

        users[index].password = newPassword;
        users[index].updatedAt = new Date().toISOString();
        storage.setUsers(users);

        return { success: true, message: 'Senha alterada com sucesso!' };
    }
};

// ============================================
// MARCAÇÕES
// ============================================

const marcacoes = {
    create: (marcacaoData) => {
        const marcacoes = storage.getMarcacoes();
        
        // Verificar se já existe marcação para mesma data/horário/posto
        const exists = marcacoes.find(m => 
            m.postoId === marcacaoData.postoId &&
            m.data === marcacaoData.data &&
            m.horario === marcacaoData.horario &&
            m.status !== 'cancelada'
        );

        if (exists) {
            return { success: false, message: 'Já existe uma marcação para esta data, horário e posto' };
        }

        const novaMarcacao = {
            id: Date.now(),
            codigo: utils.generateCode('MAR'),
            ...marcacaoData,
            status: 'pendente',
            dataCriacao: new Date().toISOString(),
            notificacoes: [],
            historico: [{
                acao: 'criacao',
                data: new Date().toISOString(),
                descricao: 'Marcação criada'
            }]
        };

        marcacoes.push(novaMarcacao);
        storage.setMarcacoes(marcacoes);

        // Atualizar vagas do posto
        const postos = storage.getPostos();
        const posto = postos.find(p => p.id === parseInt(marcacaoData.postoId));
        if (posto && posto.vagas[marcacaoData.data]) {
            posto.vagas[marcacaoData.data]--;
            storage.setPostos(postos);
        }

        // Enviar notificação (simulado)
        notifications.success('Email de confirmação enviado!');

        return { success: true, message: 'Marcação realizada com sucesso!', marcacao: novaMarcacao };
    },

    getByUser: (userId) => {
        const marcacoes = storage.getMarcacoes();
        return marcacoes.filter(m => m.userId === userId).sort((a, b) => 
            new Date(b.dataCriacao) - new Date(a.dataCriacao)
        );
    },

    getById: (id) => {
        const marcacoes = storage.getMarcacoes();
        return marcacoes.find(m => m.id === parseInt(id));
    },

    getAll: () => {
        return storage.getMarcacoes().sort((a, b) => 
            new Date(b.dataCriacao) - new Date(a.dataCriacao)
        );
    },

    cancel: (marcacaoId, motivo = '') => {
        const marcacoes = storage.getMarcacoes();
        const index = marcacoes.findIndex(m => m.id === parseInt(marcacaoId));

        if (index === -1) {
            return { success: false, message: 'Marcação não encontrada' };
        }

        const marcacao = marcacoes[index];
        
        if (marcacao.status === 'cancelada') {
            return { success: false, message: 'Esta marcação já está cancelada' };
        }

        if (marcacao.status === 'concluida') {
            return { success: false, message: 'Não é possível cancelar uma marcação concluída' };
        }

        // Restaurar vaga
        const postos = storage.getPostos();
        const posto = postos.find(p => p.id === parseInt(marcacao.postoId));
        if (posto && posto.vagas[marcacao.data] !== undefined) {
            posto.vagas[marcacao.data]++;
            storage.setPostos(postos);
        }

        marcacoes[index].status = 'cancelada';
        marcacoes[index].dataCancelamento = new Date().toISOString();
        marcacoes[index].motivoCancelamento = motivo;
        marcacoes[index].historico.push({
            acao: 'cancelamento',
            data: new Date().toISOString(),
            descricao: motivo || 'Marcação cancelada'
        });

        storage.setMarcacoes(marcacoes);

        return { success: true, message: 'Marcação cancelada com sucesso!' };
    },

    remarcar: (marcacaoId, novaData, novoHorario) => {
        const marcacoes = storage.getMarcacoes();
        const index = marcacoes.findIndex(m => m.id === parseInt(marcacaoId));

        if (index === -1) {
            return { success: false, message: 'Marcação não encontrada' };
        }

        const marcacao = marcacoes[index];
        
        if (marcacao.status === 'cancelada') {
            return { success: false, message: 'Não é possível remarcar uma marcação cancelada' };
        }

        if (marcacao.status === 'concluida') {
            return { success: false, message: 'Não é possível remarcar uma marcação concluída' };
        }

        // Verificar se nova data/horário está disponível
        const exists = marcacoes.find(m => 
            m.id !== parseInt(marcacaoId) &&
            m.postoId === marcacao.postoId &&
            m.data === novaData &&
            m.horario === novoHorario &&
            m.status !== 'cancelada'
        );

        if (exists) {
            return { success: false, message: 'O horário seleccionado já está ocupado' };
        }

        // Restaurar vaga antiga
        const postos = storage.getPostos();
        const posto = postos.find(p => p.id === parseInt(marcacao.postoId));
        
        if (posto) {
            if (posto.vagas[marcacao.data] !== undefined) {
                posto.vagas[marcacao.data]++;
            }
            if (posto.vagas[novaData] !== undefined) {
                posto.vagas[novaData]--;
            }
            storage.setPostos(postos);
        }

        const dataAntiga = marcacao.data;
        const horarioAntigo = marcacao.horario;

        marcacoes[index].data = novaData;
        marcacoes[index].horario = novoHorario;
        marcacoes[index].dataRemarcacao = new Date().toISOString();
        marcacoes[index].historico.push({
            acao: 'remarcacao',
            data: new Date().toISOString(),
            descricao: `Remarcado de ${dataAntiga} ${horarioAntigo} para ${novaData} ${novoHorario}`
        });

        storage.setMarcacoes(marcacoes);

        return { success: true, message: 'Marcação remarcada com sucesso!' };
    },

    updateStatus: (marcacaoId, novoStatus, observacao = '') => {
        const marcacoes = storage.getMarcacoes();
        const index = marcacoes.findIndex(m => m.id === parseInt(marcacaoId));

        if (index === -1) {
            return { success: false, message: 'Marcação não encontrada' };
        }

        const statusAnterior = marcacoes[index].status;
        marcacoes[index].status = novoStatus;
        
        if (novoStatus === 'concluida') {
            marcacoes[index].dataConclusao = new Date().toISOString();
        }

        marcacoes[index].historico.push({
            acao: 'status_change',
            data: new Date().toISOString(),
            descricao: `Status alterado de "${statusAnterior}" para "${novoStatus}"${observacao ? ': ' + observacao : ''}`
        });

        storage.setMarcacoes(marcacoes);

        return { success: true, message: 'Status actualizado com sucesso!' };
    }
};

// ============================================
// GESTÃO DE POSTOS
// ============================================

const postosManager = {
    create: (postoData) => {
        const postos = storage.getPostos();
        
        // Verificar se nome já existe
        if (postos.find(p => p.nome.toLowerCase() === postoData.nome.toLowerCase())) {
            return { success: false, message: 'Já existe um posto com este nome' };
        }

        const novoPosto = {
            id: Date.now(),
            ...postoData,
            ativo: true,
            vagas: {},
            createdAt: new Date().toISOString()
        };

        postos.push(novoPosto);
        storage.setPostos(postos);

        return { success: true, message: 'Posto criado com sucesso!', posto: novoPosto };
    },

    update: (postoId, data) => {
        const postos = storage.getPostos();
        const index = postos.findIndex(p => p.id === parseInt(postoId));

        if (index === -1) {
            return { success: false, message: 'Posto não encontrado' };
        }

        // Verificar se nome já existe (se estiver a alterar)
        if (data.nome && data.nome !== postos[index].nome) {
            if (postos.find(p => p.nome.toLowerCase() === data.nome.toLowerCase())) {
                return { success: false, message: 'Já existe um posto com este nome' };
            }
        }

        postos[index] = { 
            ...postos[index], 
            ...data, 
            updatedAt: new Date().toISOString() 
        };
        storage.setPostos(postos);

        return { success: true, message: 'Posto actualizado com sucesso!' };
    },

    toggleStatus: (postoId) => {
        const postos = storage.getPostos();
        const index = postos.findIndex(p => p.id === parseInt(postoId));

        if (index === -1) {
            return { success: false, message: 'Posto não encontrado' };
        }

        postos[index].ativo = !postos[index].ativo;
        postos[index].updatedAt = new Date().toISOString();
        storage.setPostos(postos);

        return { 
            success: true, 
            message: `Posto ${postos[index].ativo ? 'activado' : 'inactivado'} com sucesso!` 
        };
    },

    delete: (postoId) => {
        const postos = storage.getPostos();
        const marcacoes = storage.getMarcacoes();
        
        // Verificar se há marcações associadas
        const hasMarcacoes = marcacoes.some(m => m.postoId === parseInt(postoId) && m.status !== 'cancelada');
        
        if (hasMarcacoes) {
            return { success: false, message: 'Não é possível eliminar um posto com marcações activas' };
        }

        const index = postos.findIndex(p => p.id === parseInt(postoId));
        if (index === -1) {
            return { success: false, message: 'Posto não encontrado' };
        }

        postos.splice(index, 1);
        storage.setPostos(postos);

        return { success: true, message: 'Posto eliminado com sucesso!' };
    },

    updateVagas: (postoId, data, vagas) => {
        const postos = storage.getPostos();
        const index = postos.findIndex(p => p.id === parseInt(postoId));

        if (index === -1) {
            return { success: false, message: 'Posto não encontrado' };
        }

        if (!postos[index].vagas) postos[index].vagas = {};
        postos[index].vagas[data] = parseInt(vagas);
        postos[index].updatedAt = new Date().toISOString();
        storage.setPostos(postos);

        return { success: true, message: 'Vagas actualizadas com sucesso!' };
    }
};

// ============================================
// RELATÓRIOS
// ============================================

const relatorios = {
    getEstatisticas: () => {
        const marcacoes = storage.getMarcacoes();
        const postos = storage.getPostos();
        const users = storage.getUsers();

        const hoje = new Date().toISOString().split('T')[0];

        return {
            totalMarcacoes: marcacoes.length,
            marcacoesHoje: marcacoes.filter(m => m.data === hoje).length,
            pendentes: marcacoes.filter(m => m.status === 'pendente').length,
            confirmadas: marcacoes.filter(m => m.status === 'confirmada').length,
            concluidas: marcacoes.filter(m => m.status === 'concluida').length,
            canceladas: marcacoes.filter(m => m.status === 'cancelada').length,
            totalPostos: postos.length,
            postosAtivos: postos.filter(p => p.ativo).length,
            totalUsuarios: users.length,
            usuariosAtivos: users.filter(u => u.ativo).length,
            receitaTotal: marcacoes
                .filter(m => m.status === 'concluida')
                .reduce((sum, m) => sum + (m.valor || 0), 0)
        };
    },

    getOcupacaoPorPosto: (data = new Date().toISOString().split('T')[0]) => {
        const postos = storage.getPostos();
        const marcacoes = storage.getMarcacoes();

        return postos.map(posto => {
            const vagasTotais = posto.vagas[data] || 0;
            const ocupadas = marcacoes.filter(m => 
                m.postoId === posto.id && 
                m.data === data && 
                m.status !== 'cancelada'
            ).length;
            
            return {
                posto: posto.nome,
                vagasTotais,
                ocupadas,
                disponiveis: Math.max(0, vagasTotais - ocupadas),
                taxaOcupacao: vagasTotais > 0 ? Math.round((ocupadas / vagasTotais) * 100) : 0
            };
        });
    },

    getMarcacoesPorPeriodo: (dias = 7) => {
        const marcacoes = storage.getMarcacoes();
        const hoje = new Date();
        const resultado = [];

        for (let i = dias - 1; i >= 0; i--) {
            const data = new Date(hoje);
            data.setDate(data.getDate() - i);
            const dataStr = data.toISOString().split('T')[0];
            
            const count = marcacoes.filter(m => {
                const mData = new Date(m.dataCriacao).toISOString().split('T')[0];
                return mData === dataStr;
            }).length;

            resultado.push({
                data: dataStr,
                quantidade: count
            });
        }

        return resultado;
    },

    getServicosMaisSolicitados: () => {
        const marcacoes = storage.getMarcacoes();
        const servicos = { 'primeira-via': 0, 'renovacao': 0, 'segunda-via': 0 };
        
        marcacoes.forEach(m => {
            if (servicos[m.servico] !== undefined) {
                servicos[m.servico]++;
            }
        });

        return Object.entries(servicos).map(([servico, quantidade]) => ({
            servico,
            quantidade
        })).sort((a, b) => b.quantidade - a.quantidade);
    }
};

// ============================================
// INICIALIZAÇÃO GLOBAL
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar componentes
    navigation.init();
    modals.init();
    faq.init();
    masks.init();
    notifications.init();

    // Verificar autenticação
    const user = session.getUser();
    if (user) {
        document.body.classList.add('logged-in');
        
        // Atualizar UI com dados do usuário
        document.querySelectorAll('.user-name').forEach(el => {
            el.textContent = user.nome;
        });

        // Atualizar última atividade
        session.updateLastActivity();
    }

    // Formulários com validação
    document.querySelectorAll('form[data-validate]').forEach(form => {
        formValidation.addLiveValidation(form);
        
        form.addEventListener('submit', (e) => {
            const validation = formValidation.validateForm(form);
            if (!validation.valid) {
                e.preventDefault();
                
                // Focar no primeiro campo com erro
                const firstError = validation.results.find(r => !r.result.valid);
                if (firstError) {
                    firstError.field.focus();
                    utils.scrollTo(firstError.field.closest('.form-group'));
                }
                
                notifications.error('Por favor, corrija os erros no formulário');
            }
        });
    });

    // Botões de logout
    document.querySelectorAll('[data-action="logout"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            notifications.confirm('Tem certeza que deseja sair?', () => {
                auth.logout();
            });
        });
    });

    // Tooltips
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.classList.add('tooltip');
    });

    // Animações de entrada
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.card, .service-card, .feature-card, .info-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });

    // Adicionar classe de animação
    const style = document.createElement('style');
    style.textContent = `
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    `;
    document.head.appendChild(style);

    console.log('%c🇸🇹 SIAC - Sistema de Agendamento do Bilhete de Identidade', 'color: #CE1126; font-size: 16px; font-weight: bold;');
    console.log('%cVersão ' + CONFIG.VERSION, 'color: #666; font-size: 12px;');
});

// ============================================
// EXPORTAR PARA USO GLOBAL
// ============================================

window.SIAC = {
    CONFIG,
    utils,
    session,
    storage,
    notifications,
    formValidation,
    navigation,
    modals,
    auth,
    marcacoes,
    postosManager,
    relatorios
};
