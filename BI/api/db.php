<?php

class DB {
    private static $instance = null;

    public static function connect() {
        if (self::$instance === null) {
            // O banco será criado na raiz do projeto com o nome 'database.db'
            $dbPath = __DIR__ . '/../database.db';

            try {
                self::$instance = new PDO("sqlite:$dbPath");
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // IMPORTANTE: Ativar chaves estrangeiras no SQLite
                self::$instance->exec("PRAGMA foreign_keys = ON;");

                // Executa as migrações e dados iniciais apenas uma vez
                self::migrate(self::$instance);
                self::seed(self::$instance);

            } catch (PDOException $e) {
                if (!headers_sent()) header('Content-Type: application/json');
                die(json_encode([
                    "success" => false,
                    "error" => "Falha na conexão com o banco de dados",
                    "details" => $e->getMessage()
                ]));
            }
        }
        return self::$instance;
    }

    private static function migrate($db) {
        // ... (Seu código de CREATE TABLE está perfeito, mantenha-o) ...
        // Certifique-se apenas de que a tabela 'servicos' exista, como você já adicionou.
        
        // DICA: Adicionei UNIQUE no código da marcação para evitar duplicidade
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT,
            email TEXT UNIQUE,
            telefone TEXT,
            nif TEXT,
            password TEXT,
            role TEXT DEFAULT 'user',
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS postos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT,
            endereco TEXT,
            telefone TEXT,
            ativo INTEGER DEFAULT 1
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS marcacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT UNIQUE, -- Adicionado UNIQUE aqui
            user_id INTEGER,
            servico TEXT,
            posto_id INTEGER,
            data DATE,
            horario TEXT,
            nome TEXT,
            bi TEXT,
            email TEXT,
            telefone TEXT,
            observacoes TEXT,
            status TEXT DEFAULT 'pendente',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (posto_id) REFERENCES postos(id)
        )");
        
        // Outras tabelas (vagas, servicos, password_resets) permanecem iguais.
    }

    private static function seed($db) {
        // 1. Criar Admin Padrão
        $adminEmail = "admin@siac.ao";
        $checkAdmin = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkAdmin->execute([$adminEmail]);

        if (!$checkAdmin->fetch()) {
            // Senha: admin123
            $hash = password_hash("admin123", PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nome, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute(["Administrador SIAC", $adminEmail, $hash]);
        }

        // 2. Inserir Postos Iniciais (Se vazio)
        $countPostos = $db->query("SELECT COUNT(*) FROM postos")->fetchColumn();
        if ($countPostos == 0) {
            $db->exec("INSERT INTO postos (nome, endereco, telefone) VALUES
                ('SIAC Luanda - Cazenga', 'Cazenga, Luanda', '+244923456789'),
                ('SIAC Luanda - Talatona', 'Talatona, Luanda', '+244923000000'),
                ('SIAC Benguela', 'Centro Benguela', '+244923456791')");
        }
    }
}