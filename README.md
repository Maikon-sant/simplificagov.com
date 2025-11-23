<div align="center">
  <img src="images/logo-full.png" alt="Logo SimplificaGov" width="200" />
  <h1>SimplificaGov</h1>
  <p>
    <b>Democratizando o acesso à informação governamental através da simplificação com IA.</b>
  </p>
</div>

---

## 📖 Descrição do Projeto

O **SimplificaGov** é uma plataforma inovadora que utiliza Inteligência Artificial para traduzir a complexidade dos documentos legislativos e governamentais para uma linguagem simples e acessível a todos os cidadãos.

Nossa missão é combater a desinformação e aumentar o engajamento cívico, permitindo que qualquer pessoa entenda o que está sendo votado e decidido em Brasília, sem precisar decifrar o "juridiquês".

### Principais Funcionalidades:
- **Tradução de Leis**: Resumos automáticos e simplificados de Projetos de Lei.
- **Resumo Diário**: Atualizações personalizadas via WhatsApp em texto e áudio.
- **Simplinho**: Um assistente virtual carismático que tira dúvidas sobre política.
- **Monitoramento**: Acompanhamento de parlamentares e temas de interesse.
- **Acessibilidade**: Foco total em UX inclusiva, contraste e navegação simplificada.

---

## Membros da Equipe

| Nome | Função | GitHub |
|------|--------|--------|
| **Maysa Santos** | Tech Lead & Fullstack Dev | [@Maysamkt](https://github.com/Maysamkt) |
| **Rafaela Rocha Feijó** | Product Manager | [@Rafaelafeijo](https://github.com/Rafaelafeijo) |
| **Maikon Icaro dos Santos** | AI Engineer | [@Maikon-sant](https://github.com/Maikon-sant) |
| **Jessica Lopes** | Frontend Developer | [@iamdivaloper](https://github.com/iamdivaloper) |

Acesse nossa API: api.simplificagov.com <br>
Acesse o sistema completo: simplificagov.com

## Índice

- [Visão Geral](#visão-geral)
- [Características](#características)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Documentação da API](#documentação-da-api)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Testes](#testes)
- [Segurança](#segurança)
- [Contribuindo](#contribuindo)
- [Licença](#licença)

## Visão Geral

A API SimplificaGov é uma solução completa para democratizar o acesso à informação legislativa brasileira. O sistema permite que cidadãos acompanhem projetos de lei, recebam alertas personalizados, gerenciem favoritos e obtenham análises simplificadas de conteúdo legislativo complexo.

### Principais Funcionalidades

- **Gestão de Projetos de Lei**: Busca avançada, filtros, ordenação e detalhamento completo
- **Sistema de Autenticação**: JWT com renovação automática de tokens
- **Favoritos**: Marcação e acompanhamento de projetos de interesse
- **Alertas Personalizados**: Notificações sobre tramitações, votações e mudanças
- **Analytics de Parlamentares**: Cálculo de engajamento e áreas de foco
- **Preferências de Temas**: Personalização de conteúdo por interesse
- **Estatísticas**: Dashboards e métricas em tempo real
- **Integração com IA**: Resumos simplificados e toolkits de comunicação

## Características

### Versão 2.1.0

- Autenticação JWT completa com refresh tokens
- Sistema de favoritos com atualização automática de relevância
- Alertas personalizados com persistência de leitura
- Analytics de parlamentares com cálculo de engajamento
- Áreas de foco baseadas em análise de palavras-chave
- Busca avançada com múltiplos filtros
- Paginação em todos os endpoints
- Cache de analytics para melhor performance
- Suporte completo a UTF-8

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Extensões PHP: PDO, JSON, cURL, mbstring
- Servidor web (Apache/Nginx) com mod_rewrite habilitado
- (Opcional) OpenAI API Key para funcionalidades de IA

## Instalação

### 1. Clonar o Repositório

```bash
git clone https://github.com/seu-usuario/simplificagov.git
cd simplificagov
```

### 2. Configurar Banco de Dados

Execute o script SQL de criação do banco de dados:

```bash
mysql -u seu_usuario -p nome_do_banco < u744530839_simpificagov.sql
```

Execute o script de atualização para funcionalidades mais recentes:

```bash
mysql -u seu_usuario -p nome_do_banco < database_updates.sql
```

### 3. Configurar Variáveis de Ambiente

Copie o arquivo de exemplo e configure:

```bash
cp config/env.php.example config/env.php
```

Edite `config/env.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_banco');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_CHARSET', 'utf8mb4');

define('OPENAI_API_KEY', 'sua_chave_openai'); // Opcional
define('JWT_SECRET', 'sua_chave_secreta_jwt');
```

### 4. Configurar Servidor Web

#### Apache

Certifique-se de que o `.htaccess` está habilitado e apontando para `index.php`.

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. Verificar Permissões

```bash
chmod 755 index.php
chmod 644 .htaccess
```

## Configuração

### Chave JWT

Gere uma chave secreta forte para JWT:

```php
// Em helpers/jwt.php
define('JWT_SECRET', bin2hex(random_bytes(32)));
```

### Configuração de CORS

Para desenvolvimento, configure CORS em `index.php` ou via `.htaccess`.

### Configuração de Timezone

Configure o timezone em `index.php`:

```php
date_default_timezone_set('America/Sao_Paulo');
```

## Documentação da API

### Base URL

```
https://api.simplificagov.com
```

### Autenticação

A API utiliza JWT (JSON Web Token). Inclua o token no header:

```
Authorization: Bearer {seu_token}
```

### Endpoints Principais

#### Autenticação

- `POST /auth/register` - Registrar novo usuário
- `POST /auth/login` - Autenticar usuário
- `POST /auth/refresh` - Renovar token
- `GET /auth/me` - Dados do usuário autenticado

#### Projetos de Lei

- `GET /leis` - Listar leis com filtros e paginação
- `GET /leis/{id}` - Detalhes de uma lei
- `POST /leis/{id}/traduzir` - Gerar tradução simplificada

#### Favoritos

- `GET /favoritos` - Listar favoritos do usuário
- `POST /favoritos/{pl_id}` - Adicionar favorito
- `DELETE /favoritos/{pl_id}` - Remover favorito
- `GET /favoritos/verificar/{pl_id}` - Verificar se é favorito

#### Alertas

- `GET /alertas` - Listar alertas do usuário
- `POST /alertas` - Criar alerta
- `GET /alertas/{id}` - Detalhes do alerta
- `PUT /alertas/{id}` - Atualizar alerta
- `DELETE /alertas/{id}` - Remover alerta
- `POST /alertas/{id}/read` - Marcar alerta como lido
- `POST /alertas/{id}/ativar` - Ativar alerta
- `POST /alertas/{id}/desativar` - Desativar alerta

#### Parlamentares

- `GET /parlamentares` - Listar parlamentares
- `GET /parlamentares/{id}` - Detalhes do parlamentar
- `GET /parlamentares/{id}?analytics=1` - Detalhes com analytics
- `GET /parlamentares/{id}/analytics` - Analytics do parlamentar
- `POST /parlamentares` - Criar parlamentar (requer autenticação)
- `PUT /parlamentares/{id}` - Atualizar parlamentar
- `DELETE /parlamentares/{id}` - Remover parlamentar
- `POST /parlamentares/{id}/leis/{pl_id}` - Associar lei ao parlamentar

#### Preferências de Temas

- `GET /preferencias-temas` - Listar preferências
- `POST /preferencias-temas` - Adicionar preferência
- `PUT /preferencias-temas/{tema}` - Atualizar preferência
- `DELETE /preferencias-temas/{tema}` - Remover preferência

#### Estatísticas

- `GET /estatisticas` - Estatísticas gerais
- `GET /estatisticas/leis` - Estatísticas de leis
- `GET /estatisticas/cidadaos` - Estatísticas de cidadãos

### Códigos de Status HTTP

- `200` - Sucesso
- `201` - Criado com sucesso
- `400` - Requisição inválida
- `401` - Não autenticado / Token inválido
- `403` - Acesso negado
- `404` - Recurso não encontrado
- `405` - Método não permitido
- `409` - Conflito (ex: email já cadastrado)
- `422` - Erro de validação
- `500` - Erro interno do servidor

### Formato de Resposta

#### Sucesso

```json
{
"success": true,
"data": { ... },
  "message": "Mensagem opcional"
}
```

#### Erro

```json
{
"success": false,
  "message": "Descrição do erro",
  "error": "Detalhes técnicos"
}
```

#### Paginação

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 150,
    "limit": 10,
    "offset": 0,
    "has_next": true,
    "has_prev": false
  }
}
```

## Estrutura do Projeto

```
simplificagov/
├── api/
│   └── default.php
├── config/
│   ├── database.php      # Conexão com banco de dados
│   └── env.php           # Variáveis de ambiente
├── controllers/          # Camada de controle
│   ├── AlertaController.php
│   ├── AuthController.php
│   ├── CidadaoController.php
│   ├── EstatisticaController.php
│   ├── FavoritoController.php
│   ├── LeiController.php
│   ├── ParlamentarController.php
│   └── PreferenciaTemaController.php
├── core/
│   └── Router.php        # Roteador da aplicação
├── helpers/              # Funções auxiliares
│   ├── http.php          # Helpers HTTP
│   ├── jwt.php           # Autenticação JWT
│   ├── response.php      # Formatação de respostas
│   └── validator.php     # Validação de dados
├── middleware/
│   └── AuthMiddleware.php # Middleware de autenticação
├── models/               # Camada de dados
│   ├── AlertaModel.php
│   ├── CidadaoModel.php
│   ├── FavoritoModel.php
│   ├── ParlamentarModel.php
│   ├── PLModel.php
│   └── PreferenciaTemaModel.php
├── routes/               # Definição de rotas
│   ├── alertas.php
│   ├── auth.php
│   ├── cidadao.php
│   ├── estatisticas.php
│   ├── favoritos.php
│   ├── leis.php
│   ├── parlamentares.php
│   └── preferencias-temas.php
├── services/             # Integrações externas
│   ├── CamaraService.php
│   ├── IAService.php
│   └── SenadoService.php
├── database_updates.sql  # Script de atualização do banco
├── error_handler.php     # Tratamento de erros
├── index.php             # Ponto de entrada
├── test_sistema_completo.php # Testes automatizados
├── .htaccess             # Configuração Apache
└── README.md
```

## Testes

### Teste Automatizado

Execute o arquivo de testes completo:

```bash
php test_sistema_completo.php
```

Ou acesse via navegador:

```
https://api.simplificagov.com/test_sistema_completo.php
```

### Teste Manual com cURL

   ```bash
# Registrar usuário
curl -X POST http://localhost/simplificagov/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"nome":"Teste","email":"teste@example.com","senha":"senha123"}'

# Login
curl -X POST http://localhost/simplificagov/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"teste@example.com","senha":"senha123"}'

# Listar leis
curl -X GET http://localhost/simplificagov/api/leis \
  -H "Authorization: Bearer SEU_TOKEN"
```

## Segurança

### Boas Práticas Implementadas

- Autenticação JWT com expiração de tokens
- Hash de senhas com `password_hash()`
- Validação de entrada em todos os endpoints
- Prepared statements para prevenir SQL injection
- Sanitização de dados de saída
- Headers de segurança configurados

### Recomendações

1. **Sempre use HTTPS em produção**
2. **Proteja tokens JWT** - Não armazene em localStorage em produção
3. **Valide dados no cliente** antes de enviar
4. **Implemente rate limiting** para prevenir abuso
5. **Mantenha dependências atualizadas**
6. **Use chaves secretas fortes** para JWT

## Funcionalidades Avançadas

### Analytics de Parlamentares

O sistema calcula automaticamente métricas de engajamento baseadas em:

- Número de projetos apresentados
- Total de visualizações recebidas
- Total de favoritos recebidos
- Análise de palavras-chave dos textos

Fórmula de engajamento:
```
engajamento_score = (projetos × 10) + (visualizações × 0.1) + (favoritos × 5)
```

### Áreas de Foco

O sistema extrai automaticamente áreas de foco dos parlamentares analisando:

- Textos dos projetos de lei
- Ementas e descrições
- Palavras-chave mais frequentes
- Filtragem de stopwords em português

### Persistência de Leitura de Alertas

Os alertas podem ser marcados como lidos, com timestamp de quando foram lidos. Isso permite:

- Filtrar alertas não lidos
- Estatísticas de engajamento
- Melhor experiência do usuário

## Contribuindo

Contribuições são bem-vindas! Por favor:

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Padrões de Código

- Siga PSR-12 para estilo de código PHP
- Adicione comentários em português
- Escreva testes para novas funcionalidades
- Atualize a documentação quando necessário

## Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## Suporte

Para suporte, abra uma issue no GitHub ou entre em contato através do email: maikonicaro04@gmail.com

## Changelog

### Versão 1.0.0

- Funcionalidades básicas de leis e cidadãos
- Integração com APIs da Câmara e Senado
- Autenticação JWT completa
- Sistema de favoritos
- Sistema de alertas
- Preferências de temas
- Busca avançada de leis
- Estatísticas em tempo real
- Paginação em todos os endpoints
- Suporte a caracteres UTF-8
- Adicionado campo `focus` (JSON) em parlamentares
- Implementado sistema de leitura de alertas
- Adicionado cálculo de analytics de parlamentares
- Implementado extração automática de áreas de foco
- Melhorado sistema de cache de analytics
- Adicionado endpoint dedicado para analytics


---

**Versão da API:** 1.0.0  
**Última atualização:** 2025-11-23  
**Base URL:** `https://api.simplificagov.com/`
