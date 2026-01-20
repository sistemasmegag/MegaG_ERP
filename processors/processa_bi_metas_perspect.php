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
  $table="$owner.MEGAG_BI_METAS_PERSPECT";
  $usuario=$_SESSION['usuario']??'SYSTEM';

  $sheet=IOFactory::load($path)->getActiveSheet();
  $rows=$sheet->getHighestRow();
  $cols=$sheet->getHighestColumn();

  $hdr=$sheet->rangeToArray("A1:$cols"."1")[0];
  $map=[];
  foreach($hdr as $i=>$h){
    $map[strtoupper(trim($h))]=$i;
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
      $stmt->execute([
        ':CODMETA'=>$row[$map['CODMETA']],
        ':PERSPEC'=>$row[$map['PERSPEC']],
        ':DATA'=>Date::excelToDateTimeObject($row[$map['DATA']])->format('Y-m-d'),
        ':STATUS'=>$row[$map['STATUS']],
        ':ATUALIZACAO'=>Date::excelToDateTimeObject($row[$map['ATUALIZACAO']])->format('Y-m-d H:i:s')
      ]);
      $ok++;
    }catch(Exception $e){
      $fail++;
      sse("Linha $r erro: ".$e->getMessage(),'erro');
    }
  }

  sse("Finalizado | Sucesso: $ok | Falhas: $fail",$fail?'aviso':'sucesso');
  close_sse();

}catch(Exception $e){
  sse("ERRO CRÍTICO: ".$e->getMessage(),'erro');
  close_sse();
}
