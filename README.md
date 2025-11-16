# API de Gerenciamento de Tarefas - PHP Puro

Sistema de gerenciamento de tarefas com autenticação JWT, desenvolvido em PHP puro para rodar no XAMPP sem configurações adicionais.

## 📋 Requisitos

- XAMPP (Apache + MySQL + PHP 7.4+)
- Nenhuma configuração adicional necessária

## 🚀 Instalação

1. **Clone ou extraia o projeto** na pasta `htdocs` do XAMPP:
```
   C:\xampp\htdocs\seu_projeto\
```

2. **Inicie o Apache e MySQL** no painel de controle do XAMPP

3. **Crie o banco de dados**:
   - Acesse: `http://localhost/phpmyadmin`
   - Clique em "SQL"
   - Cole e execute o script SQL fornecido (criar database, tables, etc.)

4. **Configure as credenciais do banco** (se necessário):
   - Abra o arquivo: `config/database.php`
   - Ajuste as variáveis se seu MySQL tiver senha ou configurações diferentes:
```php
     private $host = 'localhost';
     private $db_name = 'picinato_fatec_gt';
     private $username = 'root';
     private $password = ''; // Coloque sua senha aqui se houver
```

5. **Acesse a API**:
```
   http://localhost/seu_projeto/
```

## 📁 Estrutura do Projeto
```
seu_projeto/
│
├── index.php                 # Página inicial com documentação da API
│
├── config/
│   ├── database.php          # Configuração de conexão com banco
│   └── jwt.php               # Funções de geração e validação de JWT
│
├── models/
│   ├── User.php              # Model de usuários
│   ├── Status.php            # Model de status
│   └── Task.php              # Model de tarefas
│
└── api/
    ├── auth/
    │   ├── register.php      # Registro de usuário
    │   └── login.php         # Login de usuário
    │
    ├── user/
    │   ├── update.php        # Atualizar dados do usuário
    │   └── delete.php        # Deletar conta (soft delete)
    │
    ├── status/
    │   ├── create.php        # Criar status
    │   ├── read.php          # Listar todos os status
    │   ├── update.php        # Atualizar status
    │   └── delete.php        # Deletar status (hard delete)
    │
    └── task/
        ├── create.php        # Criar tarefa
        ├── read.php          # Listar tarefas (com filtros)
        ├── read_one.php      # Buscar tarefa por ID
        ├── update.php        # Atualizar tarefa
        └── delete.php        # Deletar tarefa (soft delete)
```

## 🔐 Autenticação

A API usa **JWT (JSON Web Token)** para autenticação.

### Fluxo de autenticação:
1. Registre um usuário ou faça login
2. Receba o token JWT no response
3. Use o token em todas as requisições protegidas no header:
```
   Authorization: Bearer {seu_token_aqui}
```

## 📡 Endpoints da API

### **Autenticação**

#### Registrar Usuário
```http
POST /api/auth/register.php
Content-Type: application/json

{
  "name": "João Silva",
  "password": "senha123",
  "photo": "https://exemplo.com/foto.jpg" // opcional
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user_id": "uuid-do-usuario"
}
```

#### Login
```http
POST /api/auth/login.php
Content-Type: application/json

{
  "name": "João Silva",
  "password": "senha123"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user_id": "uuid-do-usuario"
}
```

---

### **Usuário** (Requer autenticação)

#### Atualizar Dados
```http
PUT /api/user/update.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Pedro",           // opcional
  "password": "novaSenha123",     // opcional
  "photo": "https://novo.jpg"     // opcional
}
```

#### Deletar Conta
```http
DELETE /api/user/delete.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "password": "senha123"  // obrigatório para confirmação
}
```

---

### **Status** (Requer autenticação)

#### Criar Status
```http
POST /api/status/create.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Em Progresso",
  "stage": "DOING"  // TO DO | DOING | DONE
}
```

#### Listar Todos os Status
```http
GET /api/status/read.php
Authorization: Bearer {token}
```

**Response:**
```json
[
  {
    "id": "uuid",
    "name": "A Fazer",
    "stage": "TO DO",
    "created_at": "2025-11-16 10:30:00",
    "updated_at": "2025-11-16 10:30:00"
  }
]
```

#### Atualizar Status
```http
PUT /api/status/update.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": "uuid-do-status",
  "name": "Concluído",
  "stage": "DONE"
}
```

#### Deletar Status
```http
DELETE /api/status/delete.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": "uuid-do-status"
}
```

---

### **Tarefas** (Requer autenticação)

#### Criar Tarefa
```http
POST /api/task/create.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "status_id": "uuid-do-status",
  "title": "Implementar login",
  "description": "Criar tela de login com validação",  // opcional
  "due_date": "2025-12-31 23:59:59"                    // opcional
}
```

#### Buscar Tarefa por ID
```http
GET /api/task/read_one.php?id={uuid-da-tarefa}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "uuid",
  "user_id": "uuid",
  "status_id": "uuid",
  "status_name": "Em Progresso",
  "title": "Implementar login",
  "description": "Criar tela de login",
  "due_date": "2025-12-31 23:59:59",
  "created_at": "2025-11-16 10:00:00",
  "updated_at": "2025-11-16 10:00:00",
  "deleted_at": null
}
```

#### Listar Tarefas (com filtros)
```http
GET /api/task/read.php?search=login&order_by=title&order_dir=ASC
Authorization: Bearer {token}
```

**Parâmetros de query:**
- `search` (opcional): Busca em title, description e status_name
- `order_by` (opcional): Campo para ordenar (id, title, description, due_date, created_at, updated_at, status_id, status_name)
- `order_dir` (opcional): Direção da ordenação (ASC ou DESC)

**Response:**
```json
[
  {
    "id": "uuid",
    "user_id": "uuid",
    "status_id": "uuid",
    "status_name": "Em Progresso",
    "title": "Implementar login",
    "description": "Criar tela de login",
    "due_date": "2025-12-31 23:59:59",
    "created_at": "2025-11-16 10:00:00",
    "updated_at": "2025-11-16 10:00:00",
    "deleted_at": null
  }
]
```

#### Atualizar Tarefa
```http
PUT /api/task/update.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": "uuid-da-tarefa",
  "status_id": "novo-uuid-status",      // opcional
  "title": "Novo título",               // opcional
  "description": "Nova descrição",      // opcional
  "due_date": "2025-12-25 18:00:00"    // opcional
}
```

#### Deletar Tarefa
```http
DELETE /api/task/delete.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": "uuid-da-tarefa"
}
```

---

## 🗑️ Soft Delete

O sistema implementa **soft delete** para usuários e tarefas:

- Quando um usuário é deletado, o campo `deleted_at` é preenchido
- Todas as tarefas do usuário também são marcadas como deletadas (cascata)
- Registros com soft delete não aparecem nas consultas
- **Status não usa soft delete** (hard delete)

---

## 🔧 Testando a API

### Usando cURL:

**Registrar:**
```bash
curl -X POST http://localhost/seu_projeto/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"name":"teste","password":"123456"}'
```

**Login:**
```bash
curl -X POST http://localhost/seu_projeto/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"name":"teste","password":"123456"}'
```

**Listar tarefas (com token):**
```bash
curl -X GET http://localhost/seu_projeto/api/task/read.php \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Usando Postman ou Insomnia:

1. Importe a coleção de requisições
2. Configure a variável de ambiente `base_url` como `http://localhost/seu_projeto`
3. Após login/registro, copie o token e cole no header Authorization

---

## 🛡️ Segurança

- Senhas são criptografadas com `password_hash()` (bcrypt)
- JWT para autenticação stateless
- Validação de token em todas as rotas protegidas
- SQL preparado para prevenir SQL Injection
- CORS habilitado para desenvolvimento

⚠️ **Atenção:** Em produção, mude a chave secreta do JWT em `config/jwt.php`

---

## 🐛 Troubleshooting

### Erro de conexão com banco:
- Verifique se o MySQL está rodando no XAMPP
- Confirme as credenciais em `config/database.php`
- Verifique se o banco `picinato_fatec_gt` foi criado

### Erro 401 Unauthorized:
- Verifique se o token está sendo enviado corretamente no header
- Formato: `Authorization: Bearer {token}`
- Certifique-se de que o token não expirou ou foi modificado

### Erro 404 Not Found:
- Verifique se a URL está correta
- Certifique-se de que os arquivos estão na pasta correta do htdocs
