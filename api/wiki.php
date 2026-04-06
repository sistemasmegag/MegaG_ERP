<?php
// api/wiki.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../helpers/functions.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['sucesso' => false, 'msg' => 'Usuário não autenticado.']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

// MOCK DATA PARA DEMONSTRAÇÃO IMEDIATA (Substitua por SQL quando criar a tabela TBL_WIKI_ARTIGOS)
// Estrutura esperada: ID, TITULO, CONTEUDO, CATEGORIA, AUTOR, CRIADO_EM, VISUALIZACOES
$artigos = [
    ['ID' => 1, 'TITULO' => 'Como configurar o novo ERP', 'CONTEUDO' => 'Neste guia, você aprenderá a configurar as parametrizações iniciais do ERP, incluindo cadastro de filiais, parâmetros de impostos e usuários.', 'CATEGORIA' => 'SISTEMA', 'AUTOR' => 'Admin', 'CRIADO_EM' => '2026-02-20', 'VISUALIZACOES' => 145],
    ['ID' => 2, 'TITULO' => 'Política de Férias 2026', 'CONTEUDO' => 'Todas as solicitações de férias devem ser feitas com 30 dias de antecedência mínima através do módulo de RH. Não é permitido tirar férias em época de fechamento contábil (dias 01 a 05).', 'CATEGORIA' => 'RH', 'AUTOR' => 'RH Dept', 'CRIADO_EM' => '2026-02-22', 'VISUALIZACOES' => 56],
    ['ID' => 3, 'TITULO' => 'Troubleshooting: Impressora de Etiquetas', 'CONTEUDO' => 'Se a impressora Zebra não estiver imprimindo ou a luz vermelha estiver piscando: 1) Verifique o cabo de rede. 2) Faça o recalibre do ribbon e etiqueta segurando o botão Cancel por 5 segundos. 3) Reinicie o spooler do Windows.', 'CATEGORIA' => 'TI', 'AUTOR' => 'Felipe', 'CRIADO_EM' => '2026-02-24', 'VISUALIZACOES' => 89],
    ['ID' => 4, 'TITULO' => 'Roteiro de Vendas: Objecões Comuns', 'CONTEUDO' => 'Quando o cliente falar que está caro, destaque o ROI e o suporte 24h. Quando falar que já tem fornecedor, pergunte sobre o tempo de resposta atual deles.', 'CATEGORIA' => 'COMERCIAL', 'AUTOR' => 'Gestor Comercial', 'CRIADO_EM' => '2026-02-25', 'VISUALIZACOES' => 34]
];

try {
    if ($metodo === 'GET') {
        $acao = $_GET['acao'] ?? 'LISTAR';

        if ($acao === 'CATEGORIAS') {
            // Conta artigos por categoria
            $categorias = [
                ['ID' => 'SISTEMA', 'NOME' => 'Sistema & Manuais', 'ICONE' => 'bi-laptop', 'COUNT' => 1],
                ['ID' => 'RH', 'NOME' => 'Recursos Humanos', 'ICONE' => 'bi-people', 'COUNT' => 1],
                ['ID' => 'TI', 'NOME' => 'Suporte TI', 'ICONE' => 'bi-router', 'COUNT' => 1],
                ['ID' => 'COMERCIAL', 'NOME' => 'Vendas & CRM', 'ICONE' => 'bi-graph-up', 'COUNT' => 1],
            ];
            echo json_encode(['sucesso' => true, 'dados' => $categorias]);
            exit;
        }

        // Listar artigos detalhados (opcional: filtrar por categoria ou busca)
        $filtro = $_GET['categoria'] ?? '';
        $busca = $_GET['busca'] ?? '';

        $resultados = [];
        foreach ($artigos as $artigo) {
            $matchCat = $filtro === '' || $artigo['CATEGORIA'] === $filtro;
            $matchBusca = $busca === '' || stripos($artigo['TITULO'], $busca) !== false || stripos($artigo['CONTEUDO'], $busca) !== false;

            if ($matchCat && $matchBusca) {
                // Simula resumo se a ação for listar (sem ver completo)
                $artigo['RESUMO'] = substr($artigo['CONTEUDO'], 0, 100) . '...';
                $resultados[] = $artigo;
            }
        }

        echo json_encode(['sucesso' => true, 'dados' => $resultados]);
        exit;
    }

    if ($metodo === 'POST' || $metodo === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['sucesso' => true, 'msg' => 'Artigo salvo com sucesso!', 'id' => rand(100, 999)]);
        exit;
    }

    if ($metodo === 'DELETE') {
        echo json_encode(['sucesso' => true, 'msg' => 'Artigo removido com sucesso.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'msg' => 'Erro interno na API WIKI: ' . $e->getMessage()]);
}
