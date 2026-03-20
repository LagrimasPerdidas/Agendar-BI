/**
 * ============================================
 * SIAC - Sistema de Agendamento BI
 * Versão 3.0 (Arquitetura Profissional)
 * ============================================
 */

/* ================= CORE ================= */

class Config {
    static APP_NAME = 'SIAC - BI';
    static VERSION = '3.0.0';
    static STORAGE_PREFIX = 'siac_';
}

/* ================= ERROR HANDLER ================= */

class ErrorHandler {
    static handle(error, context = '') {
        console.error(`[${context}]`, error);
        return {
            success: false,
            message: error.message || 'Erro interno do sistema'
        };
    }
}

/* ================= STORAGE SERVICE ================= */

class StorageService {
    static get(key) {
        try {
            return JSON.parse(localStorage.getItem(Config.STORAGE_PREFIX + key));
        } catch {
            return null;
        }
    }

    static set(key, value) {
        localStorage.setItem(Config.STORAGE_PREFIX + key, JSON.stringify(value));
    }

    static remove(key) {
        localStorage.removeItem(Config.STORAGE_PREFIX + key);
    }
}

/* ================= UTILS ================= */

class Utils {
    static generateId() {
        return crypto.randomUUID();
    }

    static formatDate(date) {
        return new Date(date).toLocaleDateString('pt-AO');
    }

    static sanitize(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/* ================= AUTH SERVICE ================= */

class AuthService {
    static register(userData) {
        try {
            const users = StorageService.get('users') || [];

            if (users.some(u => u.email === userData.email)) {
                throw new Error('Email já existe');
            }

            const user = {
                id: Utils.generateId(),
                ...userData,
                createdAt: new Date().toISOString()
            };

            users.push(user);
            StorageService.set('users', users);

            return { success: true, user };
        } catch (err) {
            return ErrorHandler.handle(err, 'AuthService.register');
        }
    }

    static login(email, password) {
        try {
            const users = StorageService.get('users') || [];
            const user = users.find(u => u.email === email && u.password === password);

            if (!user) throw new Error('Credenciais inválidas');

            StorageService.set('session', user);

            return { success: true, user };
        } catch (err) {
            return ErrorHandler.handle(err, 'AuthService.login');
        }
    }

    static logout() {
        StorageService.remove('session');
    }

    static currentUser() {
        return StorageService.get('session');
    }
}

/* ================= APPOINTMENT SERVICE ================= */

class AppointmentService {
    static create(data) {
        try {
            const list = StorageService.get('appointments') || [];

            const exists = list.find(a =>
                a.date === data.date &&
                a.time === data.time &&
                a.postoId === data.postoId
            );

            if (exists) throw new Error('Horário já ocupado');

            const appointment = {
                id: Utils.generateId(),
                ...data,
                createdAt: new Date().toISOString(),
                status: 'pending'
            };

            list.push(appointment);
            StorageService.set('appointments', list);

            return { success: true, appointment };
        } catch (err) {
            return ErrorHandler.handle(err, 'AppointmentService.create');
        }
    }

    static listByUser(userId) {
        const list = StorageService.get('appointments') || [];
        return list.filter(a => a.userId === userId);
    }
}

/* ================= UI NOTIFICATIONS ================= */

class NotificationService {
    static show(message, type = 'info') {
        console.log(`[${type.toUpperCase()}] ${message}`);
        // aqui pode integrar com toast UI
    }
}

/* ================= FORM VALIDATION ================= */

class Validator {
    static required(value) {
        return value ? null : 'Campo obrigatório';
    }

    static email(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
            ? null
            : 'Email inválido';
    }
}

/* ================= APP INIT ================= */

class App {
    static init() {
        console.log(`${Config.APP_NAME} v${Config.VERSION} iniciado`);

        const user = AuthService.currentUser();

        if (user) {
            document.body.classList.add('logged');
        }
    }
}

document.addEventListener('DOMContentLoaded', App.init);

/* ================= EXPORT ================= */

window.SIAC = {
    AuthService,
    AppointmentService,
    NotificationService,
    Validator
};