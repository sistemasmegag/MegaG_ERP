<?php
session_start();

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

function sse($msg,$tipo='sistema'){
  echo "data: ".json_encode(['msg'=>$msg,'tipo'=>$tipo],JSON_UNESCAPED_UNICODE)."\n\n";
  @flush();
}
function close_sse(){
  echo "event: close\ndata: {}\n\n";
  @flush();
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/db_config/db_connect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

try{
  if(empty($_SESSION['logado'])) throw new Exception('Sessão expirada');

  $arquivo=basename($_GET['arquivo']??'');
  $path=__DIR__.'/uploads/'.$arquivo;
  if(!file_exists($path)) throw new Exception('Arquivo não encontrado');

  $owner='CONSINCO';

  // ============================================================
  // TABELA BASE (sem owner) - usada para executar a função
  // megag_fn_tabs_importacao_sqlexec('<tabela>')
  // ============================================================
  $importTableBase='MEGAG_BI_METAS_PERSPECT';

  $table="$owner.$importTableBase";
  $usuario=$_SESSION['usuario']??'SYSTEM';

  $sheet=IOFactory::load($path)->getActiveSheet();
  $rows=$sheet->getHighestRow();
  $cols=$sheet->getHighestColumn();

  $hdr=$sheet->rangeToArray("A1:$cols"."1")[0];
  $map=[];
  foreach($hdr as $i=>$h){
    $map[strtoupper(trim((string)$h))]=$i;
  }

  $req=['CODMETA','PERSPEC','DATA','STATUS','ATUALIZACAO'];
  foreach($req as $c){
    if(!isset($map[$c])) throw new Exception("Coluna {$c} ausente");
  }

  $sql="
MERGE INTO $table t
USING (
  SELECT :CODMETA CODMETA, :PERSPEC PERSPEC FROM dual
) s
ON (t.CODMETA=s.CODMETA AND t.PERSPEC=s.PERSPEC)
WHEN MATCHED THEN UPDATE SET
  t.DATA=:DATA,
  t.STATUS=:STATUS,
  t.ATUALIZACAO=:ATUALIZACAO
WHEN NOT MATCHED THEN INSERT
 (CODMETA,PERSPEC,DATA,STATUS,ATUALIZACAO)
 VALUES
 (:CODMETA,:PERSPEC,:DATA,:STATUS,:ATUALIZACAO)
";

  $stmt=$conn->prepare($sql);
  $ok=$fail=0;

  for($r=2;$r<=$rows;$r++){
    $row=$sheet->rangeToArray("A$r:$cols$r")[0];
    if(!array_filter($row)) continue;

    try{

      // DATA (coluna DATA) - tenta converter com segurança
      $rawData = $row[$map['DATA']] ?? null;
      $dataStr = null;
      if ($rawData !== null && $rawData !== '') {
        if (is_numeric($rawData)) {
          $dataStr = Date::excelToDateTimeObject((float)$rawData)->format('Y-m-d');
        } else {
          // tenta interpretar texto
          $txt = trim((string)$rawData);
          $dt = DateTime::createFromFormat('d/m/Y', $txt) ?: DateTime::createFromFormat('Y-m-d', $txt);
          $dataStr = $dt ? $dt->format('Y-m-d') : null;
        }
      }

      // ATUALIZACAO (coluna ATUALIZACAO) - tenta converter com segurança
      $rawAtual = $row[$map['ATUALIZACAO']] ?? null;
      $atualStr = null;
      if ($rawAtual !== null && $rawAtual !== '') {
        if (is_numeric($rawAtual)) {
          $atualStr = Date::excelToDateTimeObject((float)$rawAtual)->format('Y-m-d H:i:s');
        } else {
          $txt = trim((string)$rawAtual);
          $txt = str_replace('T',' ',$txt);
          $dt = DateTime::createFromFormat('d/m/Y H:i:s', $txt)
             ?: DateTime::createFromFormat('d/m/Y H:i', $txt)
             ?: DateTime::createFromFormat('d/m/Y', $txt)
             ?: DateTime::createFromFormat('Y-m-d H:i:s', $txt)
             ?: DateTime::createFromFormat('Y-m-d H:i', $txt)
             ?: DateTime::createFromFormat('Y-m-d', $txt);
          if ($dt && strlen($txt) <= 10) $dt->setTime(0,0,0);
          $atualStr = $dt ? $dt->format('Y-m-d H:i:s') : null;
        }
      }

      // (mantém exatamente seu MERGE com bind simples)
      $stmt->execute([
        ':CODMETA'=>$row[$map['CODMETA']],
        ':PERSPEC'=>$row[$map['PERSPEC']],
        ':DATA'=>$dataStr,
        ':STATUS'=>$row[$map['STATUS']],
        ':ATUALIZACAO'=>$atualStr
      ]);

      $ok++;

      if ($ok % 50 === 0) {
        sse("Processadas {$ok} linhas com sucesso...",'sistema');
      }

    }catch(Exception $e){
      $fail++;
      sse("Linha $r erro: ".$e->getMessage(),'erro');
    }
  }

  // ============================================================
  // PÓS-IMPORTAÇÃO: EXECUTA FUNÇÃO APÓS FINALIZAR AS GRAVAÇÕES
  // select megag_fn_tabs_importacao_sqlexec('megag_bi_metas_perspect') from dual;
  // ============================================================
  try{
    sse("Executando rotina pós-importação: megag_fn_tabs_importacao_sqlexec...",'sistema');

    $importTableFn = preg_replace('/[^A-Z0-9_]/','',strtoupper($importTableBase));
    $importTableFnLower = strtolower($importTableFn);

    $stmtFn = $conn->prepare("SELECT megag_fn_tabs_importacao_sqlexec(:p_table) AS RET FROM dual");
    $stmtFn->execute([':p_table'=>$importTableFnLower]);

    $ret = $stmtFn->fetch(PDO::FETCH_ASSOC);
    $retMsg = '';

    if (is_array($ret)) {
      if (isset($ret['RET'])) $retMsg = (string)$ret['RET'];
      elseif (isset($ret['ret'])) $retMsg = (string)$ret['ret'];
      else {
        $first = array_values($ret);
        $retMsg = isset($first[0]) ? (string)$first[0] : '';
      }
    }

    if ($retMsg !== '') sse("Rotina pós-importação finalizada. Retorno: {$retMsg}",'sucesso');
    else sse("Rotina pós-importação finalizada com sucesso.",'sucesso');

  }catch(Exception $e){
    sse("Falha na rotina pós-importação: ".$e->getMessage(),'erro');
  }

  sse("Finalizado | Sucesso: $ok | Falhas: $fail",$fail?'aviso':'sucesso');
  close_sse();

}catch(Exception $e){
  sse("ERRO CRÍTICO: ".$e->getMessage(),'erro');
  close_sse();
}
