# Gerador de QR Code PIX Fixo

**Desenvolvido por [Seu Nome]**

Este é um script PHP simples que gera QR Codes para pagamentos via PIX com valor dinâmico. O QR Code gerado é do tipo PIX estático com valor variável, permitindo que você defina o valor a cada pagamento.

## Como Usar

1. **Configure seus dados PIX**:
   - Abra o arquivo `index.php`
   - Localize a seção `$dados_pix` e substitua com suas informações:
     ```php
     $dados_pix = [
         'chave' => 'SUA_CHAVE_PIX_AQUI', // Ex: CPF, email, telefone ou chave aleatória
         'beneficiario' => 'SEU NOME COMPLETO', // Nome do titular da conta
         'cidade' => 'SUA CIDADE' // Cidade do titular da conta
     ];
     ```

2. **Requisitos**:
   - Servidor PHP (versão 7.0 ou superior)
   - Acesso à internet (para gerar os QR Codes usando a API pública)

3. **Instalação**:
   - Basta fazer upload do arquivo para seu servidor web
   - Acesse via navegador o caminho onde o arquivo foi colocado

## Funcionalidades

- Gera QR Codes PIX com valores entre R$ 2,00 e R$ 1.000,00
- Permite ajustar o tamanho do QR Code (100px a 1000px)
- Exibe os dados do beneficiário para confirmação
- Sistema simples de rate limiting para evitar abuso
- Segurança básica com headers HTTP e sanitização de inputs

## Como Funciona

O script:
1. Recebe o valor desejado via parâmetro GET
2. Monta o payload PIX no formato EMVCo (padrão do Banco Central)
3. Gera o CRC16 necessário para validação
4. Usa a API pública do QRServer.com para gerar a imagem do QR Code
5. Exibe o QR Code na página para download/copia

## Limitações

- Requer conexão com internet para gerar os QR Codes
- Valor mínimo de R$ 2,00 e máximo de R$ 1.000,00 (pode ser ajustado no código)
- Não armazena histórico de pagamentos

## Personalização

Você pode modificar:
- O estilo CSS na seção `<style>` do HTML
- Os valores mínimo/máximo editando as verificações no PHP
- O template HTML conforme sua necessidade

## Segurança

O script inclui:
- Headers de segurança básicos
- Sanitização de inputs
- Rate limiting simples (2 segundos entre requisições)
- Exibição segura dos dados com `htmlspecialchars`

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo LICENSE.md para detalhes.

---

**Criado por Jonas Samuel do Nascimento** - github.com/Jonas21740 - jonassamuel088@gmail.com
