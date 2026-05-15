/admissoes/
│
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── solicitacao_nova.php
├── solicitacao_visualizar.php
├── solicitacao_salvar.php
├── solicitacao_status.php
│
├── /config/
│   ├── config.php
│   ├── database.php
│   └── mail.php
│
├── /includes/
│   ├── header.php
│   ├── footer.php
│   ├── menu.php
│   ├── auth.php
│   ├── permissoes.php
│   └── funcoes.php
│
├── /ajax/
│   ├── kanban_listar.php
│   ├── mudar_status.php
│   └── buscar_funcionario.php
│
├── /classes/
│   ├── Usuario.php
│   ├── Solicitacao.php
│   ├── Funcionario.php
│   ├── LogSolicitacao.php
│   └── Mailer.php
│
├── /templates/
│   ├── email_nova_solicitacao.php
│   ├── email_status_andamento.php
│   └── email_status_resolvido.php
│
├── /assets/
│   ├── /css/
│   │   └── style.css
│   ├── /js/
│   │   ├── app.js
│   │   └── kanban.js
│   └── /img/
│
└── /uploads/

Função de cada parte
Arquivos principais
•   login.php: tela de login
•   dashboard.php: Kanban com Aberto / Em andamento / Resolvido
•   solicitacao_nova.php: formulário para abrir solicitação
•   solicitacao_visualizar.php: ver detalhes da solicitação
•   solicitacao_salvar.php: grava nova solicitação
•   solicitacao_status.php: processa mudança de status
•   logout.php: encerra sessão
Config
•   config.php: constantes do sistema
•   database.php: conexão PDO/MySQL
•   mail.php: configuração SMTP
Includes
•   auth.php: valida login
•   permissoes.php: regras por perfil
•   funcoes.php: helpers diversos
Ajax
•   kanban_listar.php: carrega cards
•   mudar_status.php: troca status com validação
•   buscar_funcionario.php: consulta cadastro de funcionário
Classes
•   Usuario.php: login/perfil
•   Solicitacao.php: CRUD das solicitações
•   Funcionario.php: cadastro/consulta dos funcionários
•   LogSolicitacao.php: histórico de alterações
•   Mailer.php: centraliza envio de e-mails
Perfis de acesso

Sugestão de perfis no banco:

•   diretor
•   rh
•   dho
•   gerente
•   supervisor

Regras:

•   diretor: aprova de aberto para em_andamento
•   rh/dho: podem concluir e preencher observações
•   gerente/supervisor: apenas abrem solicitação e acompanham as próprias