# MilkTime 🍼

## 📋 Sobre o Projeto

MilkTime é uma aplicação web para monitoramento e registro de alimentação infantil. Desenvolvida com foco em usabilidade e design moderno, a aplicação permite aos pais e cuidadores registrar, acompanhar e analisar os padrões de alimentação do bebê de forma simples e intuitiva.

### 🌟 Características Principais

- Registro de alimentação por tipo (peito ou fórmula)
- Monitoramento de quantidade e horários
- Estatísticas diárias e semanais
- Interface responsiva com modo escuro
- Design moderno com animações e efeitos visuais
- Alertas inteligentes baseados nos padrões de alimentação
- Visualização de histórico recente

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP**: Linguagem principal para processamento no servidor
- **SQLite**: Banco de dados leve e portátil para armazenamento dos registros
- **API RESTful**: Endpoints para obtenção de estatísticas e status

### Frontend
- **HTML5/CSS3**: Estrutura e estilização da interface
- **JavaScript**: Interatividade e funcionalidades dinâmicas
- **Materialize CSS**: Framework para componentes de interface
- **Animações CSS**: Transições e efeitos visuais
- **Modo escuro**: Alternância automática de tema

## 📊 Funcionalidades Detalhadas

### Registro de Alimentação
- Seleção rápida entre peito e fórmula
- Configuração de quantidade em ml
- Seleção de data e hora
- Confirmação visual após registro

### Estatísticas
- Total diário de alimentação
- Intervalo médio entre mamadas
- Estatísticas das últimas 24 horas
- Proporção entre peito e fórmula
- Alertas inteligentes baseados em padrões

### Histórico
- Visualização dos registros recentes
- Edição e exclusão de registros
- Detalhes completos de cada alimentação

### Status do Bebê
- Indicador visual do estado atual
- Alertas quando necessário alimentar
- Monitoramento de volume total diário

## 🚀 Instalação e Uso

### Requisitos
- Servidor web com suporte a PHP 7.4+
- Extensão SQLite para PHP habilitada

### Passos para Instalação

1. Clone o repositório ou faça o download dos arquivos
   ```
   git clone https://github.com/seu-usuario/milktime.git
   ```

2. Coloque os arquivos em seu servidor web

3. Acesse a aplicação através do navegador
   ```
   http://seu-servidor/milktime/
   ```

4. O banco de dados será criado automaticamente no primeiro acesso

## 🔧 Estrutura do Projeto

```
milktime/
├── index.php          # Página principal e lógica de interface
├── api.php            # Endpoints da API para estatísticas e status
├── db.php             # Conexão com o banco de dados
├── style.css          # Estilos principais
├── style-extra.css    # Estilos adicionais e animações
├── logo.svg           # Logo do aplicativo
├── mamadas.db         # Banco de dados SQLite
└── fix-mamadas.js     # Scripts auxiliares
```

## 📱 Compatibilidade

- Responsivo para dispositivos móveis e desktop
- Testado nos navegadores: Chrome, Firefox, Safari e Edge
- Otimizado para uso em smartphones durante a alimentação

## 🤝 Contribuição

Contribuições são bem-vindas! Para contribuir:

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Faça commit das alterações (`git commit -m 'Adiciona nova funcionalidade'`)
4. Faça push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.
---

Desenvolvido com ❤️ para facilitar o acompanhamento da alimentação infantil.
