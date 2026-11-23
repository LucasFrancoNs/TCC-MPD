<?php
require_once 'conexao.php';
require_once 'funcoes.php';
obrigarProfissional();

$profissional = meuProfissional($conexao);
if (!$profissional) {
    header('Location: painel_profissional.php');
    exit;
}

$clienteId = intval($_GET['cliente'] ?? 0);

$stmt = $conexao->prepare("SELECT id, nome FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $clienteId);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

if (!$cliente) {
    header('Location: painel_profissional.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($mensagem !== '') {
        // Aqui quem escreve é o profissional de verdade (sem resposta automática,
        // essa simulação fica só do lado do cliente em chat.php).
        $stmt = $conexao->prepare("INSERT INTO mensagens (usuario_id, profissional_id, autor, mensagem) VALUES (?, ?, 'profissional', ?)");
        $stmt->bind_param('iis', $clienteId, $profissional['id'], $mensagem);
        $stmt->execute();
    }

    header('Location: chat_profissional.php?cliente=' . $clienteId);
    exit;
}

$stmt = $conexao->prepare("SELECT * FROM mensagens WHERE usuario_id = ? AND profissional_id = ? ORDER BY criado_em, id");
$stmt->bind_param('ii', $clienteId, $profissional['id']);
$stmt->execute();
$mensagens = $stmt->get_result();

$titulo = 'Chat com ' . $cliente['nome'] . ' - MPD Nexus';
include 'includes/inicio_html.php';
include 'includes/cabecalho.php';
?>

<main class="chat-pagina">
    <div class="chat-caixa">
        <div class="chat-topo">
            <div class="avatar"><?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?></div>
            <div>
                <h2><?php echo textoSeguro($cliente['nome']); ?></h2>
                <p>Cliente</p>
            </div>
            <a class="botao-link" style="margin-left:auto" href="painel_profissional.php">← Voltar ao painel</a>
        </div>

        <div class="mensagens">
            <?php if ($mensagens->num_rows === 0): ?>
                <p class="texto-cinza">Nenhuma mensagem ainda com este cliente.</p>
            <?php endif; ?>

            <?php while ($mensagem = $mensagens->fetch_assoc()): ?>
                <div class="balao <?php echo $mensagem['autor'] === 'profissional' ? 'minha' : 'dele'; ?>">
                    <p><?php echo textoSeguro($mensagem['mensagem']); ?></p>
                    <small><?php echo date('H:i', strtotime($mensagem['criado_em'])); ?></small>
                </div>
            <?php endwhile; ?>
        </div>

        <form class="enviar-mensagem" method="post">
            <input type="text" name="mensagem" placeholder="Digite uma mensagem" autocomplete="off" required>
            <button type="submit">Enviar</button>
        </form>
    </div>
</main>

<?php include 'includes/rodape.php'; ?>
