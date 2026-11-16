<?php
// Configuração do envio dos formulários do site ASAS.
// Preencha os campos abaixo antes de publicar. Este arquivo não aparece para quem visita o site.

return [
    // Caixa que recebe as candidaturas e as mensagens de contato.
    'destino' => 'PREENCHER@movimentoasas.com.br',

    // Caixa que envia os avisos. Precisa existir no painel de e-mail da Hostinger.
    'remetente' => 'PREENCHER@movimentoasas.com.br',
    'nome_remetente' => 'Site ASAS',

    // Envio autenticado (recomendado pela Hostinger).
    // Use os dados que aparecem no painel da Hostinger, em E-mails, na configuração da caixa remetente.
    // Se "usuario" ou "senha" ficarem vazios, o site tenta o envio simples do servidor.
    'smtp' => [
        'host' => 'smtp.hostinger.com',
        'porta' => 465,
        'seguranca' => 'ssl', // "ssl" para a porta 465, "tls" para a porta 587
        'usuario' => '',      // o endereço completo da caixa remetente
        'senha' => '',        // a senha dessa caixa
    ],
];
