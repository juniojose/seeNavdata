# seeNavdata 🚀

Uma ferramenta de diagnóstico leve e moderna, desenvolvida em **PHP 8.2**, projetada para capturar e exibir todas as informações públicas disponíveis de uma conexão web, tanto do lado do servidor (Server-Side) quanto do cliente (Client-Side).

## 📋 Sobre o Projeto

O **seeNavdata** foi criado para auxiliar desenvolvedores a entenderem quais dados estão acessíveis durante uma requisição HTTP. É uma ferramenta essencial para depuração de headers, validação de variáveis de ambiente e mapeamento de capacidades do navegador, facilitando a construção de lógicas de validação e segurança em outras aplicações.

## ✨ Funcionalidades

### 🖥️ Lado do Servidor (PHP)
- **Identificação de Conexão:** Endereço IP real, porta remota e protocolo.
- **Requisição HTTP:** Método utilizado (GET, POST, etc.) e User-Agent bruto.
- **Cabeçalhos (Headers):** Listagem completa de todos os headers HTTP enviados pelo navegador.
- **Variáveis de Ambiente:** Dump formatado da superglobal `$_SERVER`.

### 📱 Lado do Cliente (JavaScript)
- **Hardware & Tela:** Resolução total, área útil, profundidade de cor e pixel ratio.
- **Localização & Idioma:** Fuso horário do sistema e idiomas preferenciais.
- **Capacidades do Navegador:** Status de cookies, plataforma e motor do browser.
- **Preferências:** Detecção de tema do sistema (Dark/Light Mode).

## 🚀 Como Executar

### Pré-requisitos
- Servidor Web (Apache2 recomendado).
- PHP 8.2 ou superior.

### Instalação
1. Clone este repositório para o diretório raiz do seu servidor (ex: `/var/www/html/`):
   ```bash
   git clone https://github.com/juniojose/seeNavdata.git
   ```
2. Acesse via navegador:
   ```
   http://<domain>/seeNavdata
   ```

## 🛠️ Tecnologias Utilizadas
- **PHP 8.2:** Processamento de dados do servidor.
- **Bootstrap 5:** Interface responsiva e moderna.
- **JavaScript (Vanilla):** Coleta de metadados do navegador.
- **CSS3:** Customizações de interface.

---
Desenvolvido para fins de diagnóstico e desenvolvimento de software.
