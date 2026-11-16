<?php
// Recebe os formulários do site ASAS (candidatura e contato) e envia por e-mail.

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

function ir(string $destino): void
{
    header('Location: ' . $destino, true, 303);
    exit;
}

// Uma linha: sem quebras nem caracteres de controle, para não permitir injeção de cabeçalho.
function linha(string $nome, int $max = 200): string
{
    $valor = isset($_POST[$nome]) && is_string($_POST[$nome]) ? $_POST[$nome] : '';
    $valor = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $valor) ?? '';
    return mb_substr(trim($valor), 0, $max, 'UTF-8');
}

// Texto longo: mantém as quebras de linha.
function texto(string $nome, int $max = 3000): string
{
    $valor = isset($_POST[$nome]) && is_string($_POST[$nome]) ? $_POST[$nome] : '';
    $valor = str_replace(["\r\n", "\r"], "\n", $valor);
    $valor = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]+/u', ' ', $valor) ?? '';
    return mb_substr(trim($valor), 0, $max, 'UTF-8');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ir('index.html');
}

$tipo = linha('formulario', 20);
if ($tipo !== 'candidatura' && $tipo !== 'contato') {
    ir('index.html');
}

$voltar = $tipo === 'candidatura' ? 'participar.html' : 'contato.html';
$sucesso = $tipo === 'candidatura' ? 'candidatura-recebida.html' : 'mensagem-recebida.html';

// Campo invisível: pessoas não preenchem, robôs sim.
if (linha('website_confirmacao') !== '') {
    ir($sucesso);
}

$nome = linha('nome', 120);
$email = linha('email', 160);
$whatsapp = linha('whatsapp', 30);
$aceite = linha('aceite', 5);

$valido = $nome !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false && $aceite === 'sim';

if ($tipo === 'candidatura') {
    $empresa = linha('empresa', 160);
    $setor = linha('setor', 60);
    $site = linha('site', 200);
    $faturamento = linha('faturamento', 60);
    $cidade = linha('cidade', 120);
    $indicacao = linha('indicacao', 160);

    $valido = $valido && $whatsapp !== '' && $empresa !== '' && $setor !== '' && $faturamento !== '' && $cidade !== '';

    $assuntoEmail = 'Nova candidatura: ' . $nome . ' (' . $empresa . ')';
    $corpo = "Nova candidatura recebida pelo site do ASAS.\n\n"
        . "Nome: $nome\n"
        . "WhatsApp: $whatsapp\n"
        . "E-mail corporativo: $email\n"
        . "Empresa: $empresa\n"
        . "Setor: $setor\n"
        . 'Site: ' . ($site !== '' ? $site : 'não informado') . "\n"
        . "Faturamento médio anual: $faturamento\n"
        . "Cidade: $cidade\n"
        . 'Quem indicou: ' . ($indicacao !== '' ? $indicacao : 'não informado') . "\n";
} else {
    $assuntos = [
        'indicacao' => 'Indicar uma empresária',
        'encontro' => 'Próximos encontros',
        'parcerias' => 'Parcerias',
        'imprensa' => 'Imprensa',
        'outro' => 'Outro assunto',
    ];
    $chave = linha('assunto', 20);
    $mensagem = texto('mensagem', 3000);

    $valido = $valido && isset($assuntos[$chave]) && $mensagem !== '';

    $assuntoEmail = 'Contato pelo site: ' . ($assuntos[$chave] ?? 'sem assunto');
    $corpo = "Nova mensagem recebida pelo site do ASAS.\n\n"
        . "Nome: $nome\n"
        . "E-mail: $email\n"
        . 'WhatsApp: ' . ($whatsapp !== '' ? $whatsapp : 'não informado') . "\n"
        . 'Assunto: ' . ($assuntos[$chave] ?? '') . "\n\n"
        . "Mensagem:\n$mensagem\n";
}

if (!$valido) {
    ir($voltar . '?erro=campos');
}

$corpo .= "\nRecebido em " . date('d/m/Y H:i') . ".\n";

$destino = (string) ($config['destino'] ?? '');
$remetente = (string) ($config['remetente'] ?? '');
$nomeRemetente = (string) ($config['nome_remetente'] ?? 'Site ASAS');
$smtp = is_array($config['smtp'] ?? null) ? $config['smtp'] : [];

if (strpos($destino, 'PREENCHER') !== false || strpos($remetente, 'PREENCHER') !== false
    || filter_var($destino, FILTER_VALIDATE_EMAIL) === false || filter_var($remetente, FILTER_VALIDATE_EMAIL) === false) {
    error_log('Site ASAS: preencha "destino" e "remetente" em config.php.');
    ir($voltar . '?erro=envio');
}

$enviado = false;

try {
    if (!empty($smtp['usuario']) && !empty($smtp['senha'])) {
        require __DIR__ . '/lib/phpmailer/Exception.php';
        require __DIR__ . '/lib/phpmailer/PHPMailer.php';
        require __DIR__ . '/lib/phpmailer/SMTP.php';

        $correio = new PHPMailer\PHPMailer\PHPMailer(true);
        $correio->isSMTP();
        $correio->Host = (string) ($smtp['host'] ?? 'smtp.hostinger.com');
        $correio->Port = (int) ($smtp['porta'] ?? 465);
        $correio->SMTPAuth = true;
        $correio->Username = (string) $smtp['usuario'];
        $correio->Password = (string) $smtp['senha'];
        $correio->SMTPSecure = (($smtp['seguranca'] ?? 'ssl') === 'tls')
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $correio->CharSet = 'UTF-8';
        $correio->Timeout = 15;
        $correio->setFrom($remetente, $nomeRemetente);
        $correio->addAddress($destino);
        $correio->addReplyTo($email, $nome);
        $correio->Subject = $assuntoEmail;
        $correio->Body = $corpo;
        $correio->isHTML(false);
        $enviado = $correio->send();
    } else {
        $cabecalhos = [
            'From: ' . mb_encode_mimeheader($nomeRemetente, 'UTF-8') . ' <' . $remetente . '>',
            'Reply-To: ' . $email,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $enviado = mail($destino, mb_encode_mimeheader($assuntoEmail, 'UTF-8'), $corpo, implode("\r\n", $cabecalhos));
    }
} catch (Throwable $falha) {
    error_log('Site ASAS: falha no envio do formulário. ' . $falha->getMessage());
    $enviado = false;
}

ir($enviado ? $sucesso : $voltar . '?erro=envio');
