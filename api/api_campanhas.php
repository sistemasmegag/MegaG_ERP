<?php
require_once __DIR__ . '/../bootstrap/db.php';
require_once __DIR__ . '/mg_api_bootstrap.php'; // sets json headers and session check

$conn = mg_get_global_pdo();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_campanhas':
        try {
            $stmt = $conn->query("SELECT * FROM CONSINCO.MEGAG_CAMPFORN ORDER BY CODCAMPANHA DESC");
            $list = $stmt->fetchAll();
            echo json_encode(['sucesso' => true, 'campanhas' => $list]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;

    case 'get_campanha_full':
        try {
            $cod = $_GET['cod'] ?? '';
            if(!$cod) throw new Exception('Código não informado');

            // 1. Basico
            $st1 = $conn->prepare("SELECT * FROM CONSINCO.MEGAG_CAMPFORN WHERE CODCAMPANHA = :p_cod");
            $st1->execute([':p_cod' => $cod]);
            $basico = $st1->fetch();

            // 2. Metas (IDs)
            $st2 = $conn->prepare("SELECT CODMETA FROM CONSINCO.MEGAG_CAMPFORNCAMPMETA WHERE CODCAMPANHA = :p_cod");
            $st2->execute([':p_cod' => $cod]);
            $metas = $st2->fetchAll(PDO::FETCH_COLUMN);

            // 3. Produtos
            $st3 = $conn->prepare("SELECT a.CODPRODUTO as SEQPRODUTO, b.DESCCOMPLETA, a.CODMETA 
                                   FROM CONSINCO.MEGAG_CAMPFORNMETAPROD a
                                   JOIN CONSINCO.MAP_PRODUTO b ON a.CODPRODUTO = b.SEQPRODUTO
                                   WHERE a.CODCAMPANHA = :p_cod");
            $st3->execute([':p_cod' => $cod]);
            $raw_prods = $st3->fetchAll();
            
            // Agrupa metas por produto
            $prods = [];
            foreach($raw_prods as $rp) {
                if(!isset($prods[$rp['SEQPRODUTO']])) {
                    $prods[$rp['SEQPRODUTO']] = [
                        'seq' => $rp['SEQPRODUTO'],
                        'nome' => $rp['DESCCOMPLETA'],
                        'metas' => []
                    ];
                }
                $prods[$rp['SEQPRODUTO']]['metas'][] = (string)$rp['CODMETA'];
            }

            // 4. Premios
            $st4 = $conn->prepare("SELECT * FROM CONSINCO.MEGAG_CAMPFORNGRPREM WHERE CODCAMPANHA = :p_cod ORDER BY CODGRUPO, POSICAO");
            $st4->execute([':p_cod' => $cod]);
            $premios = $st4->fetchAll();

            echo json_encode([
                'sucesso' => true,
                'basico' => $basico,
                'metas' => $metas,
                'produtos' => array_values($prods),
                'premios' => $premios
            ]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;

    case 'get_next_id':
        try {
            $res = $conn->query("SELECT MAX(CODCAMPANHA) + 1 FROM CONSINCO.MEGAG_CAMPFORN")->fetchColumn();
            $next = $res ?: 1;
            echo json_encode(['sucesso' => true, 'next_id' => $next]);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;

    case 'get_product':
        try {
            $seq = $_GET['seq'] ?? '';
            if(!$seq) throw new Exception('Seqproduto não informado');
            
            $stmt = $conn->prepare("SELECT DESCCOMPLETA FROM CONSINCO.MAP_PRODUTO WHERE SEQPRODUTO = :p_seq");
            $stmt->execute([':p_seq' => $seq]);
            $prod = $stmt->fetch();
            
            if($prod) {
                echo json_encode(['sucesso' => true, 'nome' => $prod['DESCCOMPLETA']]);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Produto não encontrado']);
            }
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;

    case 'save_campanha':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $nome = $data['campanha'] ?? '';
            $dtIni = $data['dtainicial'] ?? '';
            $dtFim = $data['dtafinal'] ?? '';
            $qtdMetas = $data['qtdminmetas'] ?? 0;
            $metas = $data['metas'] ?? [];
            $usuario = $_SESSION['usuario'] ?? 'SYSTEM';

            if (!$nome || !$dtIni || !$dtFim) {
                throw new Exception('Nome e datas são obrigatórios');
            }

            $codCampanha = !empty($data['codcampanha']) ? (int)$data['codcampanha'] : null;

            if ($codCampanha) {
                $exists = $conn->prepare("SELECT COUNT(*) FROM CONSINCO.MEGAG_CAMPFORN WHERE CODCAMPANHA = :cod");
                $exists->execute([':cod' => $codCampanha]);
                
                if ($exists->fetchColumn() > 0) {
                    $sql = "UPDATE CONSINCO.MEGAG_CAMPFORN 
                            SET CAMPANHA = :p_nome, DTAINICIAL = TO_DATE(:p_dtini, 'YYYY-MM-DD'), DTAFINAL = TO_DATE(:p_dtfim, 'YYYY-MM-DD'),
                                QTDMINMETAS = :p_qtd, TIPOPREMIO = NULL
                            WHERE CODCAMPANHA = :p_cod";
                } else {
                    $sql = "INSERT INTO CONSINCO.MEGAG_CAMPFORN (CODCAMPANHA, CAMPANHA, DTAINICIAL, DTAFINAL, STATUS, USUINCLUSAO, DTAINCLUSAO, QTDMINMETAS, TIPOPREMIO)
                            VALUES (:p_cod, :p_nome, TO_DATE(:p_dtini, 'YYYY-MM-DD'), TO_DATE(:p_dtfim, 'YYYY-MM-DD'), 'A', :p_usr, SYSDATE, :p_qtd, NULL)";
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':p_cod' => $codCampanha,
                    ':p_nome' => $nome,
                    ':p_dtini' => $dtIni,
                    ':p_dtfim' => $dtFim,
                    ':p_qtd' => $qtdMetas,
                    ':p_usr' => $usuario
                ]);
            } else {
                // Modo Novo: Sempre MAX + 1
                $resNext = $conn->query("SELECT MAX(CODCAMPANHA) + 1 FROM CONSINCO.MEGAG_CAMPFORN")->fetchColumn();
                $codCampanha = $resNext ?: 1;

                $sql = "INSERT INTO CONSINCO.MEGAG_CAMPFORN (CODCAMPANHA, CAMPANHA, DTAINICIAL, DTAFINAL, STATUS, USUINCLUSAO, DTAINCLUSAO, QTDMINMETAS, TIPOPREMIO)
                        VALUES (:p_cod, :p_nome, TO_DATE(:p_dtini, 'YYYY-MM-DD'), TO_DATE(:p_dtfim, 'YYYY-MM-DD'), 'A', :p_usr, SYSDATE, :p_qtd, NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':p_cod' => $codCampanha,
                    ':p_nome' => $nome,
                    ':p_dtini' => $dtIni,
                    ':p_dtfim' => $dtFim,
                    ':p_qtd' => $qtdMetas,
                    ':p_usr' => $usuario
                ]);
            }

            // Sincroniza Metas (MEGAG_CAMPFORNCAMPMETA)
            if(!empty($metas)) {
                // Remove antigas para evitar duplicidade no salvamento
                $del = $conn->prepare("DELETE FROM CONSINCO.MEGAG_CAMPFORNCAMPMETA WHERE CODCAMPANHA = :p_cod");
                $del->execute([':p_cod' => $codCampanha]);

                $insMeta = $conn->prepare("INSERT INTO CONSINCO.MEGAG_CAMPFORNCAMPMETA (CODCAMPANHA, CODMETA, STATUS) VALUES (:p_cod, :p_meta, 'A')");
                foreach($metas as $mId) {
                    $insMeta->execute([':p_cod' => $codCampanha, ':p_meta' => $mId]);
                }
            }
            echo json_encode(['sucesso' => true, 'codcampanha' => $codCampanha, 'msg' => 'Campanha salva com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        break;

    case 'save_premios':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $codCampanha = $data['codcampanha'] ?? null;
            $premios = $data['premios'] ?? [];
            if (!$codCampanha) throw new Exception('Código da campanha não informado');

            $conn->beginTransaction();
            $conn->prepare("DELETE FROM CONSINCO.MEGAG_CAMPFORNGRPREM WHERE CODCAMPANHA = :cod")->execute([':cod' => $codCampanha]);

            $sql = "INSERT INTO CONSINCO.MEGAG_CAMPFORNGRPREM (CODCAMPANHA, CODGRUPO, PREMIODESC, VLRPREMIO, POSICAO)
                    VALUES (:p_cod, :p_grp, :p_desc, :p_vlr, :p_pos)";
            $stmt = $conn->prepare($sql);

            foreach ($premios as $p) {
                $stmt->execute([
                    ':p_cod' => $codCampanha,
                    ':p_grp' => $p['codgrupo'],
                    ':p_desc' => $p['premiodesc'],
                    ':p_vlr' => $p['vlrpremio'],
                    ':p_pos' => $p['posicao']
                ]);
            }
            $conn->commit();
            echo json_encode(['sucesso' => true, 'msg' => 'Premiações salvas com sucesso!']);
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        break;

    case 'save_produtos':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $codCampanha = $data['codcampanha'] ?? null;
            $vinc_produtos = $data['produtos'] ?? []; // [{seq, metas: [1,2]}]
            if (!$codCampanha) throw new Exception('Código da campanha não informado');

            $conn->beginTransaction();
            
            // Remove vínculos anteriores de produtos dessa campanha
            $conn->prepare("DELETE FROM CONSINCO.MEGAG_CAMPFORNMETAPROD WHERE CODCAMPANHA = :p_cod")
                 ->execute([':p_cod' => $codCampanha]);

            $ins = $conn->prepare("INSERT INTO CONSINCO.MEGAG_CAMPFORNMETAPROD (CODCAMPANHA, CODMETA, CODPRODUTO) VALUES (:p_cod, :p_meta, :p_prod)");
            
            foreach ($vinc_produtos as $vp) {
                $pId = $vp['seq'];
                foreach ($vp['metas'] as $mId) {
                    $ins->execute([
                        ':p_cod' => $codCampanha,
                        ':p_meta' => $mId,
                        ':p_prod' => $pId
                    ]);
                }
            }

            $conn->commit();
            echo json_encode(['sucesso' => true, 'msg' => 'Vínculos de produtos salvos com sucesso!']);
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Ação inválida']);
        break;
}
