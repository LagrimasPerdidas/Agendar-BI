<?php
// db.php - Central de Conexão e Migração SIAC/DNAICC

class DB {
    private static $instance = null;

    public static function connect() {
        if (self::$instance === null) {
            try {
                // Define o caminho para o banco na raiz do projeto
                $dbPath = __DIR__ . '/database.db';
                
                self::$instance = new PDO("sqlite:$dbPath");
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Ativar chaves estrangeiras para manter a integridade dos agendamentos
                self::$instance->exec("PRAGMA foreign_keys = ON");

                // Executa a criação das tabelas
                self::setup(self::$instance);

            } catch (PDOException $e) {
                // Se for uma chamada de API, retorna JSON. Caso contrário, mata com erro de texto.
                if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                    header('Content-Type: application/json; charset=utf-8');
                    die(json_encode(["success" => false, "message" => "Erro de conexão: " . $e->getMessage()]));
                }
                die("Erro crítico na base de dados: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private static function setup($db) {
        /* ================= TABELAS (ESTRUTURA) ================= */
        
        // 1. Tabela de Usuários (Incluindo data_nascimento para o registro)
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            telefone TEXT,
            nif TEXT UNIQUE,
            data_nascimento TEXT,
            genero TEXT,
            provincia TEXT,
            municipio TEXT,
            endereco TEXT,
            password TEXT NOT NULL,
            notificacoes INTEGER DEFAULT 1,
            role TEXT DEFAULT 'user',
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME
        )");

        // 2. Tabela para Recuperação de Senha
        $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            token TEXT,
            expires_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // 3. Tabela de Postos de Atendimento (SIACs)
        $db->exec("CREATE TABLE IF NOT EXISTS postos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            endereco TEXT,
            telefone TEXT,
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 4. Tabela de Serviços (BI, Renovação, etc.)
        $db->exec("CREATE TABLE IF NOT EXISTS servicos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            descricao TEXT,
            preco REAL DEFAULT 0,
            ativo INTEGER DEFAULT 1
        )");

        // 5. Tabela de Marcações (Agendamentos)
        $db->exec("CREATE TABLE IF NOT EXISTS marcacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT UNIQUE,
            user_id INTEGER,
            servico_id INTEGER,
            posto_id INTEGER,
            data TEXT NOT NULL,
            horario TEXT NOT NULL,
            status TEXT DEFAULT 'pendente',
            observacao TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (servico_id) REFERENCES servicos(id),
            FOREIGN KEY (posto_id) REFERENCES postos(id)
        )");

        // 6. Tabela de Controle de Vagas por Dia
        $db->exec("CREATE TABLE IF NOT EXISTS vagas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            posto_id INTEGER,
            data DATE,
            quantidade INTEGER,
            FOREIGN KEY (posto_id) REFERENCES postos(id)
        )");

        /* ================= DADOS INICIAIS (SEEDING) ================= */

        // Criar o Administrador Padrão
        $adminEmail = "admin@siac.ao";
        $checkAdmin = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkAdmin->execute([$adminEmail]);

        if (!$checkAdmin->fetch()) {
            $passwordHash = password_hash("Poeta@926", PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nome, email, password, role, ativo) VALUES (?, ?, ?, 'admin', 1)");
            $stmt->execute(["Administrador Geral", $adminEmail, $passwordHash]);
        }

        // Popular Serviços Iniciais
        $db->exec("INSERT OR IGNORE INTO servicos (id, nome, descricao, preco) VALUES
            (1, '1ª Via do BI', 'Emissão inicial de Bilhete de Identidade', 3500),
            (2, 'Renovação', 'Renovação por expiração', 2500),
            (3, '2ª Via', 'Segunda via por perda ou extravio', 5000)");

        // Popular Postos Iniciais
        $db->exec("INSERT OR IGNORE INTO postos (id, nome, endereco, telefone) VALUES
            (1, 'SIAC Luanda - Cazenga', 'Cazenga, Luanda', '+244923456789'),
            (2, 'SIAC Luanda - Maianga', 'Maianga, Luanda', '+244923456790'),
            (3, 'SIAC Benguela', 'Centro Benguela', '+244923456791')");
    }
}

// Inicializa a conexão global para todos os arquivos que derem require_once('db.php')
$db = DB::connect();