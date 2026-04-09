<?php
header('Content-Type: application/json');

// 1. URL RAW do seu JSON
$url_github_json = "https://raw.githubusercontent.com/kmsom/pix.assistindo.premium/main/cliente.json";

// 2. Captura o e-mail
$email_cliente = $_GET['email'] ?? '';

// 3. Lê o JSON do seu GitHub
$dados_json = @file_get_contents($url_github_json);
$dados = json_decode($dados_json, true);

if (!$dados) {
    die(json_encode(["autorizado" => false, "msg" => "Erro ao ler banco de dados"]));
}

// --- VERIFICAÇÃO DE STATUS DO SISTEMA ---
if (isset($dados['status']) && $dados['status'] == 0) {
    $mensagem = $dados['msg'] ?? "SISTEMA EM MANUTENÇÃO";
    die(json_encode(["autorizado" => false, "msg" => $mensagem]));
}

if (empty($email_cliente)) {
    die(json_encode(["autorizado" => false, "msg" => "E-mail não fornecido"]));
}

// --- CORREÇÃO DO ERRO DATETIME (ARRAY) ---
$headers = @get_headers('https://www.google.com', 1);
$date_header = $headers['Date'] ?? null;

// Se o Google retornar um array de datas, pegamos a primeira
if (is_array($date_header)) {
    $date_header = $date_header[0];
}

try {
    // Tenta criar a data com o cabeçalho, se falhar usa a hora do servidor
    $hoje = $date_header ? new DateTime($date_header) : new DateTime();
} catch (Exception $e) {
    $hoje = new DateTime();
}

$hoje->setTimezone(new DateTimeZone('America/Sao_Paulo'));

$resposta = ["autorizado" => false, "msg" => "Usuário não encontrado"];

// 4. Verificação do usuário
if (isset($dados['usuarios'][$email_cliente])) {
    try {
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
    } catch (Exception $e) {
        $resposta = ["autorizado" => false, "msg" => "Erro no formato da data cadastrada"];
    }
}

echo json_encode($resposta);
