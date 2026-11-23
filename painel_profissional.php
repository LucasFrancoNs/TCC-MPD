<?php
require_once 'conexao.php';
require_once 'funcoes.php';
obrigarProfissional();

$erro = '';
$sucesso = '';
$profissional = meuProfissional($conexao);

// Status que o profissional pode escolher manualmente.
// "pago" fica de fora porque esse status só deve mudar quando o cliente paga de verdade.
$statusPermitidos = ['aguardando', 'aceito', 'em_andamento', 'concluido', 'cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ---------- Criar o perfil profissional na primeira vez ----------
    if ($acao === 'criar_perfil' && !$profissional) {
        $nome = trim($_POST['nome'] ?? '');
        $especialidade = trim($_POST['especialidade'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $experiencia = max(0, intval($_POST['experiencia_anos'] ?? 1));
        $valorVisita = str_replace(',', '.', trim($_POST['valor_visita'] ?? '100'));
        $valorVisita = is_numeric($valorVisita) ? floatval($valorVisita) : 100.0;

        if ($nome === '' || $especialidade === '' || $cidade === '') {
            $erro = 'Preencha nome, especialidade e cidade para criar seu perfil.';
        } else {
            $stmt = $conexao->prepare("INSERT INTO profissionais (usuario_id, nome, especialidade, cidade, bio, experiencia_anos, valor_visita, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('issssid', $_SESSION['usuario_id'], $nome, $especialidade, $cidade, $bio, $experiencia, $valorVisita);

            if ($stmt->execute()) {
                $profissional = meuProfissional($conexao);
                $sucesso = 'Perfil profissional criado! Agora você já aparece nas buscas.';
            } else {
                $erro = (isset($conexao->errno) && $conexao->errno === 1062)
                    ? 'Já existe um profissional cadastrado com esse nome. Tente um nome um pouco diferente.'
                    : 'Não foi possível criar o perfil agora. Tente novamente.';
            }
        }
    }

    // ---------- Editar dados do perfil já existente ----------
    if ($acao === 'editar_perfil' && $profissional) {
        $especialidade = trim($_POST['especialidade'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $experiencia = max(0, intval($_POST['experiencia_anos'] ?? 1));
        $valorVisita = str_replace(',', '.', trim($_POST['valor_visita'] ?? '100'));
        $valorVisita = is_numeric($valorVisita) ? floatval($valorVisita) : $profissional['valor_visita'];

        if ($especialidade === '' || $cidade === '') {
            $erro = 'Preencha ao menos a especialidade e a cidade.';
        } else {
            $stmt = $conexao->prepare("UPDATE profissionais SET especialidade = ?, cidade = ?, bio = ?, experiencia_anos = ?, valor_visita = ? WHERE id = ?");
            $stmt->bind_param('sssidi', $especialidade, $cidade, $bio, $experiencia, $valorVisita, $profissional['id']);
            $stmt->execute();
            $profissional = meuProfissional($conexao);
            $sucesso = 'Perfil atualizado.';
        }
    }

    // ---------- Atualizar status de uma solicitação ----------
    if ($acao === 'atualizar_status' && $profissional) {
        $solicitacaoId = intval($_POST['solicitacao_id'] ?? 0);
        $novoStatus = $_POST['status'] ?? '';

        if (in_array($novoStatus, $statusPermitidos, true)) {
            // Confere que a solicitação é mesmo deste profissional antes de alterar.
            $stmt = $conexao->prepare("UPDATE solicitacoes SET status = ? WHERE id = ? AND profissional_id = ? AND status <> 'pago'");
            $stmt->bind_param('sii', $novoStatus, $solicitacaoId, $profissional['id']);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $sucesso = 'Status atualizado.';
            } else {
                $erro = 'Não foi possível atualizar (a solicitação já pode estar paga, ou não é sua).';
            }
        }
    }
}

$estatisticas = ['total' => 0, 'pendentes' => 0, 'concluidos' => 0];
$solicitacoes = null;

if ($profissional) {
    $stmt = $conexao->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status IN ('aguardando','aceito','em_andamento') THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status IN ('concluido','pago') THEN 1 ELSE 0 END) AS concluidos
         FROM solicitacoes WHERE profissional_id = ?"
    );
    $stmt->bind_param('i', $profissional['id']);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $estatisticas = [
        'total' => (int) $linha['total'],
        'pendentes' => (int) $linha['pendentes'],
        'concluidos' => (int) $linha['concluidos'],
    ];

    $stmt = $conexao->prepare(
        "SELECT s.*, u.nome AS cliente_nome, u.id AS cliente_id, sv.nome AS servico
         FROM solicitacoes s
         JOIN usuarios u ON u.id = s.usuario_id
         LEFT JOIN servicos sv ON sv.id = s.servico_id
         WHERE s.profissional_id = ?
         ORDER BY s.criado_em DESC"
    );
    $stmt->bind_param('i', $profissional['id']);
    $stmt->execute();
    $solicitacoes = $stmt->get_result();
}

$rotulosStatus = [
    'aguardando' => 'Aguardando resposta',
    'aceito' => 'Aceito',
    'em_andamento' => 'Em andamento',
    'concluido' => 'Concluído',
    'pago' => 'Pago',
    'cancelado' => 'Cancelado',
];

$titulo = 'Painel do profissional - MPD Nexus';
include 'includes/inicio_html.php';
include 'includes/cabecalho.php';
?>

<main class="secao">
    <div class="container">
        <p class="destaque">ÁREA DO PROFISSIONAL</p>
        <h1>Painel do profissional</h1>

        <?php if ($erro !== ''): ?>
            <div class="mensagem erro"><?php echo textoSeguro($erro); ?></div>
        <?php endif; ?>
        <?php if ($sucesso !== ''): ?>
            <div class="mensagem sucesso"><?php echo textoSeguro($sucesso); ?></div>
        <?php endif; ?>

        <?php if (!$profissional): ?>
            <!-- Conta é do tipo profissional mas ainda não tem perfil na vitrine -->
            <div class="formulario-card largo espaco-topo">
                <h1>Complete seu perfil profissional</h1>
                <p>Esses dados são o que os clientes veem ao buscar um profissional. Leva menos de um minuto.</p>

                <form method="post">
                    <input type="hidden" name="acao" value="criar_perfil">

                    <label for="nome">Nome (como vai aparecer para os clientes)</label>
                    <input type="text" id="nome" name="nome" required>

                    <label for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" placeholder="Ex: Eletricista, Encanador..." required>

                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" required>

                    <label for="bio">Breve descrição</label>
                    <textarea id="bio" name="bio" rows="3" placeholder="Conte um pouco da sua experiência"></textarea>

                    <label for="experiencia_anos">Anos de experiência</label>
                    <input type="number" id="experiencia_anos" name="experiencia_anos" min="0" value="1">

                    <label for="valor_visita">Valor da visita (R$)</label>
                    <input type="text" id="valor_visita" name="valor_visita" value="100,00">

                    <button class="botao laranja largura-total" type="submit">Criar meu perfil</button>
                </form>
            </div>

        <?php else: ?>

            <div class="grid-estatisticas">
                <div class="estatistica">
                    <strong><?php echo $estatisticas['total']; ?></strong>
                    <span>Pedidos recebidos</span>
                </div>
                <div class="estatistica">
                    <strong><?php echo $estatisticas['pendentes']; ?></strong>
                    <span>Em andamento</span>
                </div>
                <div class="estatistica">
                    <strong><?php echo $estatisticas['concluidos']; ?></strong>
                    <span>Concluídos</span>
                </div>
                <div class="estatistica">
                    <strong><?php echo number_format($profissional['nota'], 1); ?> ★</strong>
                    <span>Sua nota</span>
                </div>
            </div>

            <div class="perfil-grid">
                <div class="painel">
                    <h2>Seus pedidos</h2>

                    <?php if (!$solicitacoes || $solicitacoes->num_rows === 0): ?>
                        <p>Ainda não chegou nenhum pedido. Assim que um cliente te contratar, ele aparece aqui.</p>
                    <?php endif; ?>

                    <?php while ($pedido = $solicitacoes->fetch_assoc()): ?>
                        <div class="pedido-painel">
                            <div>
                                <h3><?php echo textoSeguro($pedido['servico'] ?: 'Serviço'); ?></h3>
                                <p>Cliente: <?php echo textoSeguro($pedido['cliente_nome']); ?> · <?php echo textoSeguro($pedido['endereco']); ?></p>
                                <p><?php echo textoSeguro($pedido['descricao']); ?></p>
                                <span class="status"><?php echo textoSeguro($rotulosStatus[$pedido['status']] ?? $pedido['status']); ?></span>
                                &nbsp;<strong>R$ <?php echo number_format($pedido['valor'], 2, ',', '.'); ?></strong>
                            </div>

                            <div>
                                <a class="botao-pequeno" href="chat_profissional.php?cliente=<?php echo $pedido['cliente_id']; ?>">Chat</a>

                                <?php if (!in_array($pedido['status'], ['pago', 'cancelado'], true)): ?>
                                    <form class="form-status" method="post">
                                        <input type="hidden" name="acao" value="atualizar_status">
                                        <input type="hidden" name="solicitacao_id" value="<?php echo $pedido['id']; ?>">
                                        <select name="status">
                                            <?php foreach (['aguardando', 'aceito', 'em_andamento', 'concluido', 'cancelado'] as $opcao): ?>
                                                <option value="<?php echo $opcao; ?>" <?php echo $opcao === $pedido['status'] ? 'selected' : ''; ?>>
                                                    <?php echo textoSeguro($rotulosStatus[$opcao]); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="botao-pequeno" type="submit">Salvar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="painel">
                    <h2>Editar meu perfil</h2>
                    <form method="post">
                        <input type="hidden" name="acao" value="editar_perfil">

                        <label for="e_especialidade">Especialidade</label>
                        <input type="text" id="e_especialidade" name="especialidade" value="<?php echo textoSeguro($profissional['especialidade']); ?>" required>

                        <label for="e_cidade">Cidade</label>
                        <input type="text" id="e_cidade" name="cidade" value="<?php echo textoSeguro($profissional['cidade']); ?>" required>

                        <label for="e_bio">Breve descrição</label>
                        <textarea id="e_bio" name="bio" rows="3"><?php echo textoSeguro($profissional['bio']); ?></textarea>

                        <label for="e_experiencia">Anos de experiência</label>
                        <input type="number" id="e_experiencia" name="experiencia_anos" min="0" value="<?php echo (int) $profissional['experiencia_anos']; ?>">

                        <label for="e_valor">Valor da visita (R$)</label>
                        <input type="text" id="e_valor" name="valor_visita" value="<?php echo number_format($profissional['valor_visita'], 2, ',', '.'); ?>">

                        <button class="botao-pequeno largura-total" type="submit">Salvar alterações</button>
                    </form>

                    <p class="espaco-topo"><a class="botao-link" href="profissional.php?id=<?php echo $profissional['id']; ?>">Ver como os clientes veem seu perfil →</a></p>
                </div>
            </div>

        <?php endif; ?>
    </div>
</main>

<?php include 'includes/rodape.php'; ?>
