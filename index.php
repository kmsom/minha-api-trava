<?php
header('Content-Type: application/json');

// 1. URL RAW do seu JSON real
$url_github_json = "https://raw.githubusercontent.com/kmsom/pix.assistindo.premium/main/cliente.json";

// 2. Captura o e-mail
$email_cliente = $_GET['email'] ?? '';

// 3. Lê o JSON do seu GitHub
$dados_json = @file_get_contents($url_github_json);
$dados = json_decode($dados_json, true);

if (!$dados) {
    die(json_encode(["autorizado" => false, "msg" => "Erro ao ler banco de dados"]));
}

// --- NOVIDADE: VERIFICAÇÃO DE STATUS ---
if (isset($dados['status']) && $dados['status'] == 0) {
    // Se o status for 0, envia a mensagem de manutenção e para aqui
    $mensagem = $dados['msg'] ?? "SISTEMA EM MANUTENÇÃO";
    die(json_encode(["autorizado" => false, "msg" => $mensagem]));
}

// 4. Se o status for 1, continua a verificação normal...
if (empty($email_cliente)) {
    die(json_encode(["autorizado" => false, "msg" => "E-mail não fornecido"]));
}

// Pega data real (Google)
$headers = @get_headers('https://www.google.com', 1);
$hoje = isset($headers['Date']) ? new DateTime($headers['Date']) : new DateTime();
$hoje->setTimezone(new DateTimeZone('America/Sao_Paulo'));

$resposta = ["autorizado" => false, "msg" => "Usuário não encontrado"];

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
        $resposta = ["autorizado" => false, "msg" => "Sua licença expirou"];
    }
}

echo json_encode($resposta);
