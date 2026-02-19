<?php
header('Content-Type: application/json');

// 1. URL RAW do seu JSON real (Onde estão os e-mails e datas)
$url_github_json = "https://raw.githubusercontent.com/SEU_USER/REPO/main/seu_arquivo.json";

// 2. Captura o e-mail enviado pelo script
$email_cliente = $_GET['email'] ?? '';

if (empty($email_cliente)) {
    die(json_encode(["autorizado" => false, "msg" => "E-mail vazio"]));
}

// 3. Pega a data real pelo Google (Anti-fraude)
$headers = @get_headers('https://www.google.com', 1);
if (isset($headers['Date'])) {
    $hoje = new DateTime($headers['Date']);
    $hoje->setTimezone(new DateTimeZone('America/Sao_Paulo'));
} else {
    $hoje = new DateTime(); // Fallback caso o Google falhe
}

// 4. Lê o JSON do GitHub
$dados_json = @file_get_contents($url_github_json);
$dados = json_decode($dados_json, true);

if (!$dados) {
    die(json_encode(["autorizado" => false, "msg" => "Erro no banco de dados"]));
}

// 5. Lógica de Verificação
$resposta = ["autorizado" => false, "msg" => "Usuario nao encontrado"];

if (isset($dados['usuarios'][$email_cliente])) {
    $data_limite = new DateTime($dados['usuarios'][$email_cliente]);
    
    if ($hoje <= $data_limite) {
        $diff = $hoje->diff($data_limite);
        $resposta = [
            "autorizado" => true,
            "vencimento" => $data_limite->format('d/m/Y'),
            "dias" => (int)$diff->format("%a")
        ];
    } else {
        $resposta = ["autorizado" => false, "msg" => "Assinatura expirada"];
    }
}

echo json_encode($resposta);
