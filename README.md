# Foco Multimídia — API de Hotelaria

API REST desenvolvida como solução para o desafio técnico da Foco Multimídia.

O projeto realiza a importação de dados hoteleiros a partir de arquivos XML, persiste essas informações em banco de dados e disponibiliza APIs REST para gerenciamento de quartos e criação de reservas.

O projeto também implementa autenticação baseada em tokens, documentação interativa com Swagger/OpenAPI 3, testes automatizados e execução agendada da importação dos arquivos XML.

---

## 🚀 Tecnologias

* PHP 8.4+
* Laravel 13
* SQLite
* Eloquent ORM
* Laravel Sanctum
* Pest / PHPUnit
* Laravel Scheduler
* Swagger / OpenAPI 3
* Git
* XML
* JSON
* REST API

---

## 📋 Requisitos

Para executar o projeto, é necessário possuir:

* PHP 8.4 ou superior
* Composer
* Git
* Extensão SQLite habilitada no PHP

---

## 📁 Estrutura do projeto

```text
TesteTecnico_Foco/
├── database/
│   └── xml/
│       ├── hotels.xml
│       ├── rooms.xml
│       └── reserves.xml
├── foco-api/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── OpenApi/
│   │   └── Services/
│   ├── config/
│   │   └── l5-swagger.php
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   ├── console.php
│   │   └── web.php
│   ├── storage/
│   ├── tests/
│   │   ├── Feature/
│   │   └── Unit/
│   ├── artisan
│   ├── composer.json
│   └── .env.example
├── Desafio.md
├── README.md
└── .gitignore
```

---

# ⚙️ Instalação

Clone o repositório:

```bash
git clone <URL_DO_REPOSITORIO>
```

Entre no diretório do projeto:

```bash
cd TesteTecnico_Foco/foco-api
```

Instale as dependências:

```bash
composer install
```

Copie o arquivo de ambiente:

```bash
cp .env.example .env
```

No Windows PowerShell, caso necessário:

```powershell
Copy-Item .env.example .env
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

---

# 🗄️ Banco de dados

O projeto utiliza SQLite para facilitar a execução do desafio.

Crie o arquivo:

```text
database/database.sqlite
```

Depois execute as migrations:

```bash
php artisan migrate
```

Para recriar o banco do zero:

```bash
php artisan migrate:fresh
```

---

# 🧩 Modelo de dados

O banco foi modelado a partir dos arquivos XML fornecidos no desafio.

As principais entidades são:

* `hotels`
* `rooms`
* `reserves`
* `guests`
* `dailies`
* `payments`

Também existem as tabelas padrão utilizadas pelo Laravel e Sanctum, como:

* `users`
* `personal_access_tokens`

### Relacionamentos

```text
Hotel
 ├── hasMany Rooms
 └── hasMany Reserves

Room
 ├── belongsTo Hotel
 └── hasMany Reserves

Reserve
 ├── belongsTo Hotel
 ├── belongsTo Room
 ├── hasMany Guests
 ├── hasMany Dailies
 └── hasMany Payments
```

As chaves estrangeiras utilizam integridade referencial e exclusão em cascata quando aplicável.

---

# 📥 Importação dos arquivos XML

A aplicação possui um comando Artisan responsável por importar os arquivos XML fornecidos pelo desafio.

Os arquivos utilizados estão em:

```text
database/xml/
├── hotels.xml
├── rooms.xml
└── reserves.xml
```

Para executar a importação:

```bash
php artisan xml:import
```

O processo:

1. Lê os arquivos XML.
2. Importa os hotéis.
3. Importa os quartos.
4. Importa as reservas.
5. Importa os hóspedes.
6. Importa as diárias.
7. Importa os pagamentos.
8. Valida os relacionamentos entre hotel, quarto e reserva.
9. Registra inconsistências nos logs.
10. Executa toda a operação dentro de uma transação.

---

## 🔄 Idempotência

A importação foi desenvolvida para ser executada mais de uma vez sem gerar registros duplicados.

Os IDs presentes nos XMLs são preservados para hotéis, quartos e reservas.

Antes de recriar os dados dependentes de uma reserva, como hóspedes, diárias e pagamentos, os registros anteriores são removidos e recriados.

Dessa forma, executar:

```bash
php artisan xml:import
```

novamente mantém os dados consistentes.

---

## 📊 Dados importados

Após a importação dos XMLs fornecidos pelo desafio, foram validados os seguintes registros:

| Entidade   | Quantidade |
| ---------- | ---------: |
| Hotéis     |          3 |
| Quartos    |          6 |
| Reservas   |          6 |
| Hóspedes   |          6 |
| Diárias    |         18 |
| Pagamentos |          1 |

---

# ⚠️ Tratamento de inconsistências

Durante a importação foi identificada uma inconsistência na reserva de ID `6`.

A reserva possui:

```text
Check-in:  2022-10-01
Check-out: 2022-10-04
```

Porém, uma das diárias possui a data:

```text
2022-12-03
```

O sistema **não altera silenciosamente o dado original**.

A decisão adotada foi:

* preservar o dado recebido;
* registrar um warning no log;
* continuar a importação.

O warning pode ser encontrado em:

```text
storage/logs/laravel.log
```

Essa abordagem evita assumir uma correção que não pode ser determinada com segurança apenas a partir do XML.

---

# ⏰ Agendamento da importação

A importação foi integrada ao Laravel Scheduler.

O agendamento atual é:

```text
Diariamente
```

O comando utilizado é:

```bash
php artisan xml:import
```

O agendamento utiliza:

```php
Schedule::command('xml:import')
    ->daily()
    ->withoutOverlapping();
```

O `withoutOverlapping()` evita que uma nova execução seja iniciada enquanto outra execução do mesmo comando ainda estiver em andamento.

Para visualizar os agendamentos:

```bash
php artisan schedule:list
```

---

# 🔐 Autenticação

A API utiliza **Laravel Sanctum** para autenticação baseada em Bearer Token.

O fluxo é:

```text
Cliente
   ↓
POST /api/login
   ↓
E-mail + senha
   ↓
Laravel Sanctum
   ↓
Bearer Token
   ↓
Endpoints protegidos
```

### Login

Endpoint:

```http
POST /api/login
```

Exemplo:

```json
{
    "email": "usuario@example.com",
    "password": "12345678"
}
```

Resposta:

```json
{
    "token": "TOKEN_GERADO",
    "token_type": "Bearer"
}
```

Para acessar endpoints protegidos:

```http
Authorization: Bearer TOKEN_GERADO
```

---

# 🛏️ API de quartos

A API possui CRUD completo para quartos.

### Listar quartos

```http
GET /api/rooms
```

### Consultar quarto

```http
GET /api/rooms/{id}
```

### Criar quarto

```http
POST /api/rooms
```

### Atualizar quarto

```http
PUT /api/rooms/{id}
```

### Excluir quarto

```http
DELETE /api/rooms/{id}
```

Todos os endpoints de quartos exigem autenticação.

---

# 📅 API de reservas

A API possui endpoint para criação de reservas.

```http
POST /api/reserves
```

Exemplo:

```json
{
    "hotel_id": 1,
    "room_id": 1,
    "check_in": "2026-10-10",
    "check_out": "2026-10-12",
    "total": 500
}
```

Antes de criar a reserva, a aplicação valida:

* existência do hotel;
* existência do quarto;
* relacionamento entre quarto e hotel;
* validade das datas;
* `check_out` posterior ao `check_in`;
* conflito de reserva para o mesmo quarto.

Em caso de conflito de datas, a API retorna:

```http
422 Unprocessable Entity
```

---

# 🏗️ Arquitetura da aplicação

A aplicação utiliza uma separação de responsabilidades baseada na estrutura do Laravel.

```text
HTTP Request
     ↓
Controller
     ↓
Form Request
     ↓
Service
     ↓
Model / Eloquent
     ↓
Database
```

### Controllers

Responsáveis por receber as requisições HTTP e retornar as respostas JSON.

### Form Requests

Responsáveis pela validação dos dados recebidos pela API.

### Services

Responsáveis pelas regras de negócio que não devem ficar diretamente nos Controllers.

### Models

Representam as entidades persistidas no banco de dados e seus relacionamentos.

### Commands

Responsáveis pela execução da importação dos arquivos XML.

---

# 📚 Swagger / OpenAPI 3

A API possui documentação interativa utilizando Swagger/OpenAPI 3.

Para gerar a documentação:

```bash
php artisan l5-swagger:generate
```

Depois execute a aplicação:

```bash
php artisan serve
```

Acesse:

```text
http://127.0.0.1:8000/api/documentation
```

A documentação permite visualizar:

* endpoints;
* métodos HTTP;
* parâmetros;
* exemplos de requisições;
* respostas;
* autenticação Bearer;
* códigos HTTP utilizados.

O botão **Authorize** pode ser utilizado para informar o Bearer Token e testar os endpoints protegidos.

---

# 🧪 Testes automatizados

Os testes automatizados são executados com:

```bash
php artisan test
```

Resultado atual:

```text
Tests: 14 passed
Assertions: 37
```

Os testes cobrem principalmente:

* autenticação;
* credenciais inválidas;
* proteção dos endpoints;
* acesso autenticado;
* criação de reservas;
* validações;
* conflitos de reserva;
* regras de negócio.

---

# 🔄 Fluxos principais

## Fluxo de importação

```text
XML
 ↓
XmlImportService
 ↓
Validação
 ↓
Transação
 ↓
Eloquent
 ↓
SQLite
 ↓
Logs
```

## Fluxo de autenticação

```text
POST /api/login
 ↓
Validação
 ↓
User
 ↓
Sanctum
 ↓
Bearer Token
```

## Fluxo de reserva

```text
POST /api/reserves
 ↓
Form Request
 ↓
Validação
 ↓
ReserveService
 ↓
Validação do hotel/quarto
 ↓
Verificação de conflito
 ↓
Reserve
 ↓
SQLite
 ↓
JSON
```

---

# 📝 Versionamento

O projeto utiliza Git para controle de versão.

As principais etapas do desenvolvimento foram organizadas em commits separados, contemplando:

* inicialização do projeto;
* modelagem do banco;
* importação dos XMLs;
* CRUD de quartos;
* API de reservas;
* testes automatizados;
* agendamento do importador;
* documentação;
* Swagger/OpenAPI;
* autenticação com Sanctum.

Exemplo de consulta do histórico:

```bash
git log --oneline
```

---

# ▶️ Executando o projeto

Após configurar o ambiente:

```bash
cd foco-api
```

Execute:

```bash
php artisan migrate
```

Importe os XMLs:

```bash
php artisan xml:import
```

Gere a documentação:

```bash
php artisan l5-swagger:generate
```

Execute os testes:

```bash
php artisan test
```

Inicie o servidor:

```bash
php artisan serve
```

A API estará disponível em:

```text
http://127.0.0.1:8000
```

Swagger:

```text
http://127.0.0.1:8000/api/documentation
```

---

# 🔧 Variáveis de ambiente

As principais configurações ficam no arquivo:

```text
foco-api/.env
```

Exemplo para SQLite:

```env
DB_CONNECTION=sqlite
```

O arquivo `.env` não deve ser versionado.

---

# ✅ Requisitos do desafio

| Requisito                                | Status |
| ---------------------------------------- | ------ |
| Desenvolver em PHP/Laravel               | ✅      |
| Modelar banco a partir dos XMLs          | ✅      |
| Importar XML                             | ✅      |
| Persistir dados no banco                 | ✅      |
| Executar importação via comando          | ✅      |
| Possibilitar execução via CRON/Scheduler | ✅      |
| API REST                                 | ✅      |
| Respostas em JSON                        | ✅      |
| CRUD de quartos                          | ✅      |
| Endpoint de criação de reserva           | ✅      |
| Documentação do processo                 | ✅      |
| Versionamento Git                        | ✅      |
| Swagger/OpenAPI 3                        | ✅      |
| PHPUnit/Pest                             | ✅      |
| Autenticação                             | ✅      |
| Validação de conflitos de reserva        | ✅      |
| Tratamento de inconsistências nos XMLs   | ✅      |

---

# ⭐ Diferenciais implementados

Além dos requisitos principais, foram implementados:

* Laravel Sanctum;
* autenticação Bearer Token;
* Swagger/OpenAPI 3;
* testes automatizados;
* separação de regras de negócio em Services;
* Form Requests para validação;
* Laravel Scheduler;
* proteção contra execução simultânea do importador;
* transação durante importação;
* importação idempotente;
* logs para inconsistências;
* validação de disponibilidade do quarto durante criação da reserva;
* controle de status HTTP;
* respostas padronizadas em JSON;
* versionamento organizado com Git.

---

# 🚀 Possíveis evoluções

Como próximos passos, a aplicação poderia evoluir com:

* consulta específica de disponibilidade de quartos;
* gerenciamento completo de hotéis;
* gerenciamento de usuários;
* perfis e permissões;
* fluxo completo de pagamentos;
* descontos e cupons;
* promoções;
* taxas e juros;
* logs estruturados;
* Docker;
* pipeline CI/CD;
* maior cobertura de testes;
* deploy em ambiente AWS;
* monitoramento e observabilidade.

---

# 👨‍💻 Sobre o projeto

Projeto desenvolvido por **Rafael Santos** como parte de um desafio técnico para a Foco Multimídia.

O objetivo foi construir uma API funcional utilizando Laravel, aplicando conceitos de:

* desenvolvimento de APIs REST;
* modelagem de dados;
* integração com XML;
* persistência em banco de dados;
* autenticação;
* validação;
* testes automatizados;
* documentação;
* versionamento de código;
* organização de regras de negócio.
