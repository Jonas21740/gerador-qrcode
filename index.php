<?php
// qrcode-pix-fixo.php

// CONFIGURAÇÕES DE SEGURANÇA
ini_set('display_errors', 0);
error_reporting(0);

// Cabeçalhos HTTP de segurança
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'; img-src *");

// Rate limit simples por IP
$ip = $_SERVER['REMOTE_ADDR'];
$log_file = sys_get_temp_dir() . "/qr_pix_rate_" . md5($ip);
$tempo_atual = time();
$limite_segundos = 2;

if (file_exists($log_file)) {
    $ultima_requisicao = (int)file_get_contents($log_file);
    if (($tempo_atual - $ultima_requisicao) < $limite_segundos) {
        http_response_code(429);
        die("Aguarde $limite_segundos segundos para gerar outro QR Code.");
    }
}
file_put_contents($log_file, $tempo_atual);

// Dados FIXOS da conta PIX - SUBSTITUA COM SEUS DADOS
$dados_pix = [
    'chave' => 'SUA_CHAVE_PIX_AQUI', // Ex: CPF, email, telefone ou chave aleatória
    'beneficiario' => 'SEU NOME COMPLETO', // Nome do titular da conta
    'cidade' => 'SUA CIDADE' // Cidade do titular da conta
];

// Função para montar cada campo no padrão EMVCo (TLV)
function emv($id, $value) {
    $len = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
    return $id . $len . $value;
}

// Função para calcular CRC16
function calcular_crc16($payload) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

// Processar requisição
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['valor'])) {
    $valor_input = filter_input(INPUT_GET, 'valor', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $tamanho = filter_input(INPUT_GET, 'tamanho', FILTER_VALIDATE_INT, [
        'options' => ['default' => 300, 'min_range' => 100, 'max_range' => 1000]
    ]);

    $valor = (float) str_replace(',', '.', $valor_input);

    if ($valor < 2 || $valor > 1000) {
        http_response_code(400);
        die("Valor deve ser entre R$ 2,00 e R$ 1.000,00");
    }

    $valor_str = number_format($valor, 2, '.', '');

    // Montar payload
    $merchant_account_info = emv('00', 'BR.GOV.BCB.PIX')
                           . emv('01', $dados_pix['chave']);

    $payload = emv('00', '01')
             . emv('26', $merchant_account_info)
             . emv('52', '0000')
             . emv('53', '986')
             . emv('54', $valor_str)
             . emv('58', 'BR')
             . emv('59', $dados_pix['beneficiario'])
             . emv('60', $dados_pix['cidade'])
             . emv('62', emv('05', '***'))
             . '6304';

    $crc = calcular_crc16($payload);
    $payload .= $crc;

    // Gerar QR Code
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$tamanho}x{$tamanho}&data=" . urlencode($payload);
    
    header('Content-Type: image/png');
    readfile($url);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador PIX Fixo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 20px auto; padding: 20px; }
        h1 { color: #0066cc; text-align: center; }
        .card { background: #f9f9f9; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #0066cc; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; width: 100%; }
        .qr-result { margin-top: 20px; text-align: center; }
        .info { background: #f0f8ff; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Pagamento via PIX</h1>
        <div class="info">
            <h3>Dados do Beneficiário</h3>
            <p><strong>Nome:</strong> <?= htmlspecialchars($dados_pix['beneficiario']) ?></p>
            <p><strong>Chave PIX:</strong> <?= htmlspecialchars($dados_pix['chave']) ?></p>
            <p><strong>Cidade:</strong> <?= htmlspecialchars($dados_pix['cidade']) ?></p>
        </div>
        <form method="get" action="">
            <div class="form-group">
                <label for="valor">Valor (R$):</label>
                <input type="text" id="valor" name="valor" 
                       placeholder="Ex: 50.00" required
                       pattern="\d+([\.,]\d{1,2})?" 
                       title="Use valores entre 2.00 e 1000.00">
            </div>
            <div class="form-group">
                <label for="tamanho">Tamanho do QR Code (100-1000px):</label>
                <input type="number" id="tamanho" name="tamanho" 
                       min="100" max="1000" value="300">
            </div>
            <button type="submit">Gerar QR Code</button>
        </form>

        <?php if (isset($_GET['valor'])): ?>
        <div class="qr-result">
            <h3>QR Code para R$ <?= htmlspecialchars(number_format($_GET['valor'], 2, ',', '.')) ?></h3>
            <img src="?valor=<?= urlencode($_GET['valor']) ?>&tamanho=<?= htmlspecialchars($_GET['tamanho'] ?? 300) ?>" 
                 alt="QR Code PIX">
            <p><small>Clique com o botão direito para salvar</small></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>