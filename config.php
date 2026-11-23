<?php
// ============================================================
// CONFIGURAÇÃO DO SITE  (é o único arquivo que você precisa editar)
// ============================================================

// ---- BANCO DE DADOS LOCAL / NUVEM (MySQL) ----
$config = [
    'host'    => '127.0.0.1',
    'porta'   => 3306,
    'usuario' => 'root',
    'senha'   => '',
    'banco'   => 'mpd_nexus',
    'ssl'     => false,
];

// ---- CONTATOS QUE APARECEM NO SITE ----
$contato = [
    'telefone'      => '(11) 99999-9999',
    'whatsapp'      => '5511999999999',   // só números, com 55 + DDD
    'email'         => 'contato@mpdnexus.com.br',
    'atendimento'   => 'Grande ABC - Mauá, Santo André, São Bernardo e região',
];

// ---- IMAGENS POR LINK (nada fica salvo no computador) ----
// Para trocar uma foto, é só colar outro link aqui.
$imagens = [
    'hero'    => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=1600&q=70',
    'servico' => 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=1000&q=70',
];

// ---- E-MAIL (confirmação de cadastro e "esqueci minha senha") ----
// Deixe 'host' VAZIO para o MODO TESTE: o site não envia e-mail e mostra o link na própria tela
// (ótimo para apresentar o TCC sem configurar nada).
// Para enviar e-mail de verdade, use um SMTP. Exemplo com Gmail:
//   host = smtp.gmail.com | porta = 465 | seguranca = ssl | usuario = seu@gmail.com
//   senha = "senha de app" do Google (Conta Google > Segurança > Senhas de app)
// Dica: use variáveis de ambiente (SMTP_HOST, SMTP_PORTA, SMTP_SEGURANCA, SMTP_USUARIO,
// SMTP_SENHA, SMTP_REMETENTE, SITE_URL) para não deixar a senha no código.
$smtp = [
    'host'      => 'smtp.gmail.com',
    'porta'     => 465,
    'seguranca' => 'ssl',
    'usuario'   => '',
    'senha'     => '', // Sem espaços
    'remetente' => '',
    'nome'      => 'MPD Nexus',
    'verificar_certificado' => false,
    'url_site'  => '',
    'exigir_confirmacao' => true,
];