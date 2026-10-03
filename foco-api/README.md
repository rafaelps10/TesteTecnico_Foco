# Foco Multimídia — API de Hotelaria

API REST desenvolvida como solução para o desafio técnico da Foco Multimídia.

O projeto realiza a importação de dados hoteleiros a partir de arquivos XML, persiste essas informações em banco de dados e disponibiliza APIs REST para gerenciamento de quartos e criação de reservas.

## 🚀 Tecnologias

* PHP 8.4+
* Laravel 13
* SQLite
* Eloquent ORM
* Pest / PHPUnit
* Laravel Scheduler
* Git
* XML
* JSON
* REST API

## 📋 Requisitos

Para executar o projeto, é necessário possuir:

* PHP 8.4 ou superior
* Composer
* Git
* Extensão SQLite habilitada no PHP

## 📁 Estrutura do projeto

```text
foco-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Models/
│   └── Services/
├── database/
│   └── migrations/
├── routes/
│   ├── api.php
│   └── console.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── artisan
├── composer.json
└── README.md
```

Os arquivos XML utilizados na importação ficam no diretório:

```text
../database/xml/
├── hotels.xml
├── rooms.xml
└── reserves.xml
```

## ⚙️ Instalação

Clone o projeto:

```bash
git clone <URL_DO_REPOSITORIO>
```

Entre no diretório da aplicação:

```bash
cd foco-api
```

Instale as dependências:

```bash
composer install
```

Crie o arquivo `.env`:

```bash
cp .env.example .env
```

No Windows/PowerShell, caso necessário:

```powershell
Copy-Item .env.example .env
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

## 🗄️ Banco de dados

O projeto utiliza SQLite.

Crie o arquivo do banco, caso ele ainda não exista:

```powershell
New-Item database/database.sqlite -ItemType File
```

Execute as migrations:

```bash
php artisan migrate
```

As migrations criam as seguintes tabelas:

```text
hotels
rooms
reserves
guests
dailies
payments
```

### Relacionamentos

```text
Hotel
 ├── Rooms
 └── Reserves
       ├── Guests
       ├── Dailies
       └── Payments

Room
 └── Reserves
```

## 📥 Importação dos XMLs

A aplicação possui o comando Artisan:

```bash
php artisan xml:import
```

O comando recupera os dados dos arquivos XML e persiste as informações no banco de dados.

Os dados importados incluem:

* hotéis;
* quartos;
* reservas;
* hóspedes;
* diárias;
* pagamentos.

### Idempotência

A importação foi implementada de forma idempotente.

Isso significa que executar:

```bash
php artisan xml:import
```

mais de uma vez não deve gerar registros duplicados dos dados importados.

### Validações da importação

Durante a importação são verificadas as relações entre:

* hotel;
* quarto;
* reserva.

Também é registrada uma mensagem de log quando uma diária possui uma data fora do período da reserva.

A informação original do XML é preservada, sem correção silenciosa do dado.

## ⏰ Scheduler / CRON

A importação foi configurada no Laravel Scheduler em:

```text
routes/console.php
```

Configuração atual:

```php
Schedule::command('xml:import')
    ->daily()
    ->withoutOverlapping();
```

Isso configura a execução diária do comando:

```bash
php artisan xml:import
```

O `withoutOverlapping()` evita que uma nova execução seja iniciada enquanto outra importação ainda estiver em andamento.

### Verificar tarefas agendadas

```bash
php artisan schedule:list
```

### Executar o Scheduler manualmente

```bash
php artisan schedule:run
```

Em produção, o sistema operacional deve executar o Scheduler do Laravel periodicamente.

Exemplo de configuração do CRON em ambientes Linux:

```cron
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

## 🛏️ API de quartos

A API disponibiliza operações CRUD para quartos.

### Listar quartos

```http
GET /api/rooms
```

### Consultar um quarto

```http
GET /api/rooms/{id}
```

### Cadastrar quarto

```http
POST /api/rooms
Content-Type: application/json
```

Exemplo:

```json
{
    "hotel_id": 1,
    "name": "Room 10 Hotel 1"
}
```

### Atualizar quarto

```http
PUT /api/rooms/{id}
Content-Type: application/json
```

Exemplo:

```json
{
    "hotel_id": 1,
    "name": "Room 10 Updated"
}
```

### Excluir quarto

```http
DELETE /api/rooms/{id}
```

## 🏨 API de reservas

A aplicação disponibiliza um endpoint REST para criação de reservas.

### Criar reserva

```http
POST /api/reserves
Content-Type: application/json
```

Exemplo:

```json
{
    "hotel_id": 1,
    "room_id": 2,
    "check_in": "2026-12-10",
    "check_out": "2026-12-12",
    "total": 500.00
}
```

### Resposta de sucesso

HTTP:

```text
201 Created
```

Exemplo:

```json
{
    "message": "Reserva criada com sucesso.",
    "data": {
        "hotel_id": 1,
        "room_id": 2,
        "check_in": "2026-12-10",
        "check_out": "2026-12-12",
        "total": "500.00"
    }
}
```

## 🔎 Regras de reserva

Antes de criar uma reserva, a API verifica:

### Hotel existente

O `hotel_id` informado deve existir.

### Quarto existente

O `room_id` informado deve existir.

### Relação entre hotel e quarto

O quarto deve pertencer ao hotel informado.

### Datas

A data de checkout deve ser posterior à data de check-in.

### Disponibilidade

A API verifica se já existe uma reserva para o mesmo quarto no período informado.

Reservas consecutivas são permitidas.

Exemplo:

```text
Reserva 1: 10/12 → 12/12
Reserva 2:          12/12 → 14/12
```

Esse cenário não é considerado conflito.

Já uma sobreposição:

```text
Reserva 1: 10/12 → 12/12
Reserva 2:      11/12 → 13/12
```

é rejeitada.

Nesse caso a API retorna:

```text
422 Unprocessable Entity
```

```json
{
    "message": "O quarto não está disponível para o período informado."
}
```

## 🧪 Testes automatizados

Os testes foram implementados utilizando Pest sobre a infraestrutura do PHPUnit.

Executar todos os testes:

```bash
php artisan test
```

Resultado atual:

```text
Tests: 10 passed
Assertions: 30
```

Os testes cobrem, entre outros cenários:

* criação de reserva válida;
* hotel inexistente;
* quarto inexistente;
* quarto pertencente a outro hotel;
* data de checkout inválida;
* valor total negativo;
* conflito de reservas;
* reserva em período disponível.

Os testes de integração utilizam `RefreshDatabase` para manter o banco de testes isolado.

## 🧱 Arquitetura da reserva

A criação de reservas utiliza responsabilidades separadas.

### Form Request

`StoreReserveRequest`

Responsável pela validação dos dados recebidos pela API.

### Controller

`ReserveController`

Responsável pelo fluxo HTTP da requisição.

### Service

`ReserveService`

Responsável pelas regras de negócio relacionadas à reserva, como:

* verificar se o quarto pertence ao hotel;
* verificar conflito de datas;
* criar a reserva.

Essa separação mantém o Controller mais simples e facilita a manutenção e os testes.

## 🔄 Fluxo da importação

```text
Arquivos XML
     ↓
XmlImportCommand
     ↓
XmlImportService
     ↓
Validação dos relacionamentos
     ↓
Persistência no banco
     ↓
Logs de inconsistências
```

## 🔄 Fluxo da criação de reserva

```text
Cliente
   ↓
POST /api/reserves
   ↓
StoreReserveRequest
   ↓
ReserveController
   ↓
ReserveService
   ├── valida hotel/quarto
   ├── verifica disponibilidade
   └── cria reserva
   ↓
Banco de dados
   ↓
Resposta JSON
```

## 📝 Versionamento

O projeto utiliza Git para versionamento do código e das alterações de banco através das migrations.

As principais etapas foram organizadas em commits independentes:

```text
chore: initialize Laravel technical challenge
feat: implement database model and XML import foundation
feat: implement rooms CRUD API
feat: implement reservation API
test: add reservation feature tests
feat: schedule XML import
```

## ▶️ Executando a aplicação

Inicie o servidor local:

```bash
php artisan serve
```

A aplicação ficará disponível em:

```text
http://127.0.0.1:8000
```

## 🔐 Configurações de ambiente

Informações sensíveis e configurações específicas do ambiente devem permanecer no arquivo `.env`.

O arquivo `.env` não deve ser versionado.

Para configurar um novo ambiente, utilize:

```bash
cp .env.example .env
php artisan key:generate
```

## 📌 Observações

Este projeto foi desenvolvido como solução para um desafio técnico da Foco Multimídia, tendo como foco os requisitos obrigatórios de:

* modelagem do banco;
* importação de XML;
* execução automatizada da importação;
* API REST de quartos;
* API REST de reservas;
* respostas JSON;
* testes automatizados;
* versionamento do projeto.

Funcionalidades adicionais podem ser incorporadas posteriormente, como documentação OpenAPI/Swagger, autenticação e autorização, Docker, logs estruturados e outras regras de negócio.

````