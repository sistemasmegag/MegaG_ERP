CREATE OR REPLACE PACKAGE BODY CONSINCO.PKG_MEGAG_DESP_CADASTRO IS

/* ==================================================
   FILE: AprovadoresCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_APROVADORES(
    p_sequsuario           IN MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_centrocusto          IN MEGAG_DESP_APROVADORES.CENTROCUSTO%TYPE,
    p_seqcentroresultado   IN MEGAG_DESP_APROVADORES.SEQCENTRORESULTADO%TYPE,
    p_nome       		   IN MEGAG_DESP_APROVADORES.NOME%TYPE,
    p_sequusuarioalt       IN MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    p_dtaalteracao         IN MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL
)
AS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    INSERT INTO MEGAG_DESP_APROVADORES(
        SEQUSUARIO,
        CENTROCUSTO,
        SEQCENTRORESULTADO,
        SEQUSUARIOALTERACAO,
        NOME,
        DTAINCLUSAO,
        DTAALTERACAO
    )
    VALUES(
        p_sequsuario,
        p_centrocusto,
        p_seqcentroresultado,   
        p_sequusuarioalt,
        p_nome,
        SYSDATE,
        p_dtaalteracao
    );
END PRC_INS_MEGAG_DESP_APROVADORES;

-- SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_APROVADORES(
    p_nome IN GE_USUARIO.NOME%TYPE,
    p_cursor OUT SYS_REFCURSOR
)
AS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    OPEN p_cursor FOR
        SELECT t.SEQUSUARIO,
               t.CENTROCUSTO,
               t.SEQCENTRORESULTADO,
               t.SEQUSUARIOALTERACAO,
			   t.NOME,
               t.DTAINCLUSAO,
               t.DTAALTERACAO
        FROM MEGAG_DESP_APROVADORES t
        JOIN GE_USUARIO u
          ON t.SEQUSUARIO = u.SEQUSUARIO
        WHERE u.NOME = p_nome;
END PRC_LIST_MEGAG_DESP_APROVADORES;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_APROVADORES(
    p_sequsuario           IN MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_centrocusto          IN MEGAG_DESP_APROVADORES.CENTROCUSTO%TYPE,
    p_seqcentroresultado   IN MEGAG_DESP_APROVADORES.SEQCENTRORESULTADO%TYPE,
    p_nome                 IN MEGAG_DESP_APROVADORES.NOME%TYPE,
    p_sequusuarioalt       IN MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    p_dtaalteracao         IN MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL,
    p_rows_affected        OUT NUMBER
)
AS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    UPDATE MEGAG_DESP_APROVADORES
       SET CENTROCUSTO         = p_centrocusto,
           SEQCENTRORESULTADO  = p_seqcentroresultado,
           SEQUSUARIOALTERACAO = p_sequusuarioalt,
           NOME                = p_nome,
           DTAALTERACAO        = NVL(p_dtaalteracao, SYSDATE)
     WHERE SEQUSUARIO = p_sequ suario;

    p_rows_affected := SQL%ROWCOUNT;

    COMMIT;
END PRC_UPD_MEGAG_DESP_APROVADORES;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_APROVADORES(
    p_nome IN VARCHAR2
)
AS
    v_sequsuario MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE;
BEGIN
/*
* REGRA DE NEGÓCIO 
*/
    -- Busca o código do usuário pelo nome
    SELECT SEQUSUARIO
    INTO v_sequsuario
    FROM GE_USUARIO
    WHERE NOME = p_nome;
    DELETE FROM MEGAG_DESP_APROVADORES
    WHERE SEQUSUARIO = v_sequsuario;

    COMMIT;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        DBMS_OUTPUT.PUT_LINE('Usuário não encontrado: ' || p_nome);
END PRC_DEL_MEGAG_DESP_APROVADORES;

/* ==================================================
   FILE: DespesaCRUD.sql
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP(
    p_USUARIOSOLICITANTE  IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_USUARIOAPROVADOR    IN MEGAG_DESP.USUARIOAPROVADOR%TYPE DEFAULT NULL,
    p_CODTIPODESPESA      IN MEGAG_DESP.CODTIPODESPESA%TYPE, --O PHP envia o ID escolhido(value)
    p_PAGO                IN MEGAG_DESP.PAGO%TYPE DEFAULT 'N',
    p_VLRRATDESPESA       IN MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR          IN MEGAG_DESP.FORNECEDOR%TYPE DEFAULT NULL,
    p_NOMEARQUIVO         IN MEGAG_DESP.NOMEARQUIVO%TYPE DEFAULT NULL,
    p_OBSERVACAO          IN MEGAG_DESP.OBSERVACAO%TYPE DEFAULT NULL,
    p_SEQCENTRORESULTADO  IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE,
    p_CENTROCUSTO         IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS              IN MEGAG_DESP.STATUS%TYPE DEFAULT 'LANCADO',
    p_CODDESPESA_OUT      OUT MEGAG_DESP.CODDESPESA%TYPE
)
IS
    -- Variável interna para guardar a descrição encontrada
    p_DESCRICAO MEGAG_DESP.DESCRICAO%TYPE;
BEGIN
    -- BUSCA AUTOMÁTICA: 
    -- A Procedure vai na tabela de tipos e pega a descrição correta
    SELECT DESCRICAO --label
      INTO p_DESCRICAO 
      FROM TB_TESTE_TIPO_DESPESA 
     WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    -- INSERÇÃO:
    -- Agora usamos o p_CODTIPODESPESA (que veio do PHP) 
    -- e o p_DESCRICAO (que o banco acabou de achar)
    INSERT INTO MEGAG_DESP(
        USUARIOSOLICITANTE,
        USUARIOAPROVADOR,
        CODTIPODESPESA,
        DESCRICAO,
        PAGO,
        VLRRATDESPESA,
        FORNECEDOR,
        NOMEARQUIVO,
        OBSERVACAO,
        SEQCENTRORESULTADO,
        CENTROCUSTO,
        STATUS
    )
    VALUES(
        p_USUARIOSOLICITANTE,
        p_USUARIOAPROVADOR,
        p_CODTIPODESPESA,
        p_DESCRICAO, -- <--- Inserindo a descrição que o banco buscou
        p_PAGO,
        p_VLRRATDESPESA,
        p_FORNECEDOR,
        p_NOMEARQUIVO,
        p_OBSERVACAO,
        p_SEQCENTRORESULTADO,
        p_CENTROCUSTO,
        p_STATUS
    )
    RETURNING CODDESPESA INTO p_CODDESPESA_OUT;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        -- Caso o PHP envie um código que não existe na TB_TESTE_TIPO_DESPESA
        RAISE_APPLICATION_ERROR(-20001, 'Erro: O Tipo de Despesa informado não existe.');
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20002, 'Erro ao inserir despesa: ' || SQLERRM);
END PRC_INS_MEGAG_DESP;

--SELECT 
PROCEDURE PRC_LIST_MEGAG_DESP(
    p_USUARIOSOLICITANTE IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE, -- Sempre obrigatório
    p_DESCRICAO          IN MEGAG_DESP.DESCRICAO%TYPE DEFAULT NULL,
    p_STATUS             IN MEGAG_DESP.STATUS%TYPE DEFAULT NULL, --'LANCADO','APROVACAO','APROVADO','REJEITADO'
    p_RESULT             OUT SYS_REFCURSOR
)
IS
BEGIN
    OPEN p_RESULT FOR
        SELECT *
          FROM MEGAG_DESP
         WHERE USUARIOSOLICITANTE = p_USUARIOSOLICITANTE
           -- Filtro de Descrição: Busca parcial e case-insensitive
           AND (p_DESCRICAO IS NULL OR UPPER(DESCRICAO) LIKE '%' || UPPER(p_DESCRICAO) || '%')
           -- Filtro de Status: Busca exata ('LANCADO','APROVACAO','APROVADO','REJEITADO')
           AND (p_STATUS IS NOT NULL OR STATUS = p_STATUS)
         ORDER BY DTAINCLUSAO DESC;
END PRC_LIST_MEGAG_DESP;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP(
    p_CODDESPESA         IN MEGAG_DESP.CODDESPESA%TYPE,
    p_USUARIOSOLICITANTE IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA     IN MEGAG_DESP.CODTIPODESPESA%TYPE,
    p_DESCRICAO          IN MEGAG_DESP.DESCRICAO%TYPE,
    p_VLRRATDESPESA      IN MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR         IN MEGAG_DESP.FORNECEDOR%TYPE,
    p_NOMEARQUIVO        IN MEGAG_DESP.NOMEARQUIVO%TYPE,
    p_OBSERVACAO         IN MEGAG_DESP.OBSERVACAO%TYPE,
    p_SEQCENTRORESULTADO IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE,
    p_CENTROCUSTO        IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS             IN MEGAG_DESP.STATUS%TYPE
)
IS
BEGIN
    UPDATE MEGAG_DESP
       SET USUARIOSOLICITANTE = p_USUARIOSOLICITANTE,
    	   CODTIPODESPESA     = p_CODTIPODESPESA,
           DESCRICAO          = p_DESCRICAO,
           VLRRATDESPESA      = p_VLRRATDESPESA,
           FORNECEDOR         = p_FORNECEDOR,
           NOMEARQUIVO        = p_NOMEARQUIVO,
           OBSERVACAO         = p_OBSERVACAO,
           SEQCENTRORESULTADO = p_SEQCENTRORESULTADO,
           CENTROCUSTO        = p_CENTROCUSTO,
           STATUS             = p_STATUS,
           DTAALTERACAO       = SYSDATE
     WHERE CODDESPESA = p_CODDESPESA
     AND STATUS = 'LANCADO';

IF SQL%NOTFOUND THEN
        RAISE_APPLICATION_ERROR(-20001, 'ATUALIZAÇÃO NEGADA: Registro já passou pela aprovação do gestor.');
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20002, 'Erro ao atualizar despesa: ' || SQLERRM);
END PRC_UPD_MEGAG_DESP;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP(
    p_CODDESPESA IN MEGAG_DESP.CODDESPESA%TYPE,
	p_DESCRICAO IN MEGAG_DESP.DESCRICAO%TYPE,
	p_USUARIOSOLICITANTE IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE
)
IS
    v_coddespesa MEGAG_DESP.CODDESPESA%TYPE;
BEGIN
	SELECT CODDESPESA 
		INTO v_coddespesa
	FROM MEGAG_DESP 
	WHERE CODDESPESA = p_CODDESPESA 
		AND USUARIOSOLICITANTE = p_USUARIOSOLICITANTE; 
		
	DELETE FROM MEGAG_DESP
		WHERE CODDESPESA = p_CODDESPESA;

END PRC_DEL_MEGAG_DESP;

/* ==================================================
   FILE: TipoDespesaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_TIPO(
    p_DESCRICAO IN MEGAG_DESP_TIPO.DESCRICAO%TYPE
)
IS
BEGIN
    INSERT INTO MEGAG_DESP_TIPO(
        DESCRICAO
)
    VALUES(
        p_DESCRICAO
);
    
END PRC_INS_MEGAG_DESP_TIPO;

--SELECT 
PROCEDURE PRC_LIST_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN  MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE,
    p_DESCRICAO      IN  MEGAG_DESP_TIPO.DESCRICAO%TYPE, 
    p_RESULT         OUT SYS_REFCURSOR
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM MEGAG_DESP_TIPO
        --caso não coloque a despesa trazer tudo:
         WHERE (p_CODTIPODESPESA IS NULL OR CODTIPODESPESA = p_CODTIPODESPESA)
           AND (p_DESCRICAO IS NULL OR DESCRICAO LIKE '%' || p_DESCRICAO || '%');
END PRC_LIST_MEGAG_DESP_TIPO;

--UPDATE

PROCEDURE PRC_UPD_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE,
    p_DESCRICAO      IN MEGAG_DESP_TIPO.DESCRICAO%TYPE
)
IS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    UPDATE MEGAG_DESP_TIPO
       SET DESCRICAO  = p_DESCRICAO,
           DTAALTERACAO = SYSDATE
     WHERE CODTIPODESPESA = p_CODTIPODESPESA;

END PRC_UPD_MEGAG_DESP_TIPO;

--DELETE

PROCEDURE PRC_DEL_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE
)
IS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    DELETE FROM MEGAG_DESP_TIPO
     WHERE CODTIPODESPESA = p_CODTIPODESPESA;

END PRC_DEL_MEGAG_DESP_TIPO;

/* ==================================================
   FILE: CentroCustoDespesaCRUD.sql
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP_CENTRO_CUSTO(
    p_centrocusto        IN ABA_CENTRORESULTADO.CENTRORESULTADO%TYPE,
    p_coddespesa         IN MEGAG_DESP.CODDESPESA%TYPE,
    p_usuariosolicitante IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE
)
AS
    v_descricao  ABA_CENTRORESULTADO.DESCRICAO%TYPE;
    v_seqcentro  ABA_CENTRORESULTADO.SEQCENTRORESULTADO%TYPE;
    v_count      NUMBER;
BEGIN
    -- Verifica se existe registro da despesa e se pertence ao usuário
    SELECT COUNT(*)
    INTO v_count
    FROM MEGAG_DESP
    WHERE CODDESPESA = p_coddespesa
      AND USUARIOSOLICITANTE = p_usuariosolicitante;

    IF v_count = 0 THEN
        RAISE_APPLICATION_ERROR(-20030, 'A despesa não existe ou não pertence ao usuário.');
    END IF

    -- Busca os dados do centro de resultado
    SELECT DESCRICAO, SEQCENTRORESULTADO
    INTO v_descricao, v_seqcentro
    FROM ABA_CENTRORESULTADO
    WHERE CENTRORESULTADO = p_centrocusto;

    -- Insere apenas os campos do centro de custo
    INSERT INTO MEGAG_DESP(
        CENTROCUSTO,
        SEQCENTRORESULTADO,
        DESCRICAOCENTROCUSTO
    )
    VALUES(
        p_centrocusto,
        v_seqcentro,
        v_descricao
    );

END PRC_INS_MEGAG_DESP_CENTRO_CUSTO;

PROCEDURE PRC_LIST_MEGAG_DESP_CENTRORESULTADO(
    p_cursor OUT SYS_REFCURSOR
)
AS
BEGIN
    OPEN p_cursor FOR
        SELECT 
            CENTRORESULTADO,
            DESCRICAO
        FROM 
            ABA_CENTRORESULTADO
        ORDER BY DESCRICAO;
END PRC_LIST_MEGAG_DESP_CENTRORESULTADO;

PROCEDURE PRC_UPD_MEGAG_DESP(
    p_centrocusto_atual        IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_seqcentroresultado_atual IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE,
    p_centrocusto_novo         IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_seqcentroresultado_novo  IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE,
    p_descricao_nova           IN MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE,
    p_coddespesa               IN MEGAG_DESP.CODDESPESA%TYPE,
    p_usuariosolicitante       IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE
)
AS
    v_count NUMBER;
BEGIN
    -- Verifica se o registro existe e pertence ao usuário
    SELECT COUNT(*)
    INTO v_count
    FROM MEGAG_DESP
    WHERE CENTROCUSTO = p_centrocusto_atual
      AND SEQCENTRORESULTADO = p_seqcentroresultado_atual
      AND CODDESPESA = p_coddespesa
      AND USUARIOSOLICITANTE = p_usuariosolicitante;

    IF v_count = 0 THEN
        RAISE_APPLICATION_ERROR(-20011, 'Registro não encontrado ou não pertence ao usuário.');
    END IF;

    -- Atualiza apenas os campos permitidos
    UPDATE MEGAG_DESP
    SET CENTROCUSTO = p_centrocusto_novo,
        SEQCENTRORESULTADO = p_seqcentroresultado_novo,
        DESCRICAOCENTROCUSTO = p_descricao_nova
    WHERE CENTROCUSTO = p_centrocusto_atual
      AND SEQCENTRORESULTADO = p_seqcentroresultado_atual
      AND CODDESPESA = p_coddespesa
      AND USUARIOSOLICITANTE = p_usuariosolicitante;

END PRC_UPD_MEGAG_DESP;

PROCEDURE PRC_DEL_MEGAG_DESP(
    p_centrocusto       IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_seqcentroresultado IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE
)
AS
BEGIN
    DELETE FROM MEGAG_DESP
    WHERE CENTROCUSTO = p_centrocusto
      AND SEQCENTRORESULTADO = p_seqcentroresultado;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20010, 'Registro não encontrado para exclusão.');
    END IF;

END;

/* ==================================================
   IMPLEMENTAR PROCEDURES FALTANTES DA ESPECIFICACAO:
   (COLE AQUI O CODIGO DE: PRC_INS_MEGAG_DESP_ARQUIVO, ETC...)
================================================== */

--PROC DE APROVAÇÃO(lista)
PROCEDURE PRC_LIST_MEGAG_DESP_APROVACAO(
    p_sequsuario  IN MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE, -- Usuário logado no sistema no momento.
    p_cursor      OUT SYS_REFCURSOR
) AS
    v_existe NUMBER;
BEGIN
    -- Verifica se o usuário é um aprovador
    SELECT COUNT(*)
    INTO v_existe
    FROM MEGAG_DESP_APROVADORES
    WHERE sequsuario = p_sequsuario;

    IF v_existe > 0 THEN
        -- Caso seja um aprovador faz LOOP para ler lista de despesa(s) vinculada(s) ao(s) centro(s) de custo(s)
        OPEN p_cursor FOR
            SELECT desp.CODDESPESA         
					,desp.USUARIOSOLICITANTE 
					,desp.USUARIOAPROVADOR   
					,desp.CODTIPODESPESA     
					,desp.PAGO               
					,desp.VLRRATDESPESA      
					,desp.DESCRICAO          
					,desp.FORNECEDOR         
					,desp.DTAINCLUSAO        
					,desp.DTAALTERACAO       
					,desp.CODARQUIVO         
					,desp.NOMEARQUIVO        
					,desp.OBSERVACAO      
					,desp.SEQCENTRORESULTADO 
					,desp.CENTROCUSTO        
					,desp.STATUS  
            FROM MEGAG_DESP desp
            INNER JOIN MEGAG_DESP_APROVADORES aprov
               ON desp.CENTROCUSTO = aprov.CENTROCUSTO
            WHERE desp.status = 'LANCADO' -- Verifica o status da despesa
              AND aprov.sequsuario = p_sequsuario;
    ELSE
        -- Retorna o cursor vazio se não for aprovador (evita erro de ponteiro nulo)
        OPEN p_cursor FOR 
            SELECT * FROM MEGAG_DESP WHERE 1 = 0;
    END IF;
END PRC_LIST_MEGAG_DESP_APROVACAO;

/*
	*
* update de aprovação
* Usuario valida ou não a despesa e o sistema altera o status retornando como 'APROVADO' OU 'REJEITADO'
* Pegar como base o update de despesa;
	*
*/
--PROC DE APROVAÇÃO(Atualiza)
PROCEDURE PRC_UPD_MEGAG_DESP_APROVACAO(
    p_coddespesa     IN MEGAG_DESP.CODDESPESA%TYPE,
    p_sequsuario     IN MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_novo_status    IN MEGAG_DESP.STATUS%TYPE,
	p_pago           IN MEGAG_DESP.PAGO%TYPE,
    p_msg_retorno    OUT VARCHAR2
) AS
    v_solicitante NUMBER;
BEGIN
    -- 1. Busca quem foi o solicitante desta despesa
    SELECT USUARIOSOLICITANTE 
    INTO v_solicitante
    FROM MEGAG_DESP 
    WHERE CODDESPESA = p_coddespesa;

    -- 2. Regra de Segregação de Funções: 
    -- Se o aprovador for o mesmo que solicitou, bloqueia a ação.
    IF v_solicitante = p_sequsuario THEN
        p_msg_retorno := 'Erro: O solicitante não pode aprovar a própria despesa.';
        RETURN; -- Interrompe a execução aqui
    END IF;

    -- 3. Executa o Update com validação de alçada (Centro de Custo)
    UPDATE MEGAG_DESP desp
    SET desp.USUARIOAPROVADOR = p_sequsuario,
		desp.STATUS           = p_novo_status,
		desp.PAGO             = p_pago,
        desp.DTAALTERACAO     = SYSDATE
    WHERE desp.CODDESPESA = p_coddespesa
      AND desp.STATUS = 'LANCADO'
      AND EXISTS (
          SELECT 1 
          FROM MEGAG_DESP_APROVADORES aprov
          WHERE aprov.CENTROCUSTO = desp.CENTROCUSTO
            AND aprov.sequsuario = p_sequsuario
      );

    -- 4. Valida se o registro foi atualizado
    IF SQL%ROWCOUNT > 0 THEN
        p_msg_retorno := 'Sucesso: Registro processado como ' || p_novo_status;
        COMMIT;
    ELSE
        p_msg_retorno := 'Erro: Sem permissão para este Centro de Custo ou despesa já processada.';
        ROLLBACK;
    END IF;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        p_msg_retorno := 'Erro: Código de despesa inexistente.';
    WHEN OTHERS THEN
        ROLLBACK;
        p_msg_retorno := 'Erro crítico: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_APROVACAO;

/* ==================================================
   FILE: ArquivoCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_ARQUIVO(
    p_CODDESPESA       IN MEGAG_DESP_ARQUIVO.CODDESPESA%TYPE,
    p_NOMEARQUIVO      IN MEGAG_DESP_ARQUIVO.NOMEARQUIVO%TYPE,
    p_TIPOARQUIVO      IN MEGAG_DESP_ARQUIVO.TIPOARQUIVO%TYPE DEFAULT NULL,
    p_CODARQUIVO_OUT   OUT MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE
)
IS
BEGIN
/*
* REGRA DE NEGÓCIO
* loop para o usuário inserir quantos arquivos desejar vinculados a despesa.Será feito no back-end
*/
    INSERT INTO MEGAG_DESP_ARQUIVO(
        CODDESPESA,
        NOMEARQUIVO,
        TIPOARQUIVO
    )
    VALUES(
        p_CODDESPESA,
        p_NOMEARQUIVO,
        p_TIPOARQUIVO
    )
    RETURNING CODARQUIVO INTO p_CODARQUIVO_OUT;

END PRC_INS_MEGAG_DESP_ARQUIVO;

--SELECT 
PROCEDURE PRC_SEL_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO   IN MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE DEFAULT NULL,
    p_CODDESPESA   IN MEGAG_DESP_ARQUIVO.CODDESPESA%TYPE DEFAULT NULL,
    p_RESULT       OUT SYS_REFCURSOR
)
IS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    OPEN p_RESULT FOR
        SELECT CODARQUIVO,
               CODDESPESA,
               NOMEARQUIVO,
               TIPOARQUIVO,
               DTAINCLUSAO,
               DTAALTERACAO
          FROM MEGAG_DESP_ARQUIVO
         WHERE (p_CODARQUIVO IS NULL OR CODARQUIVO = p_CODARQUIVO)
           AND (p_CODDESPESA IS NULL OR CODDESPESA = p_CODDESPESA);

END PRC_SEL_MEGAG_DESP_ARQUIVO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO   IN MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE,
    p_NOMEARQUIVO  IN MEGAG_DESP_ARQUIVO.NOMEARQUIVO%TYPE,
    p_TIPOARQUIVO  IN MEGAG_DESP_ARQUIVO.TIPOARQUIVO%TYPE DEFAULT NULL
)
IS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    UPDATE MEGAG_DESP_ARQUIVO
       SET NOMEARQUIVO  = p_NOMEARQUIVO,
           TIPOARQUIVO  = p_TIPOARQUIVO,
           DTAALTERACAO = SYSDATE
     WHERE CODARQUIVO = p_CODARQUIVO;

END PRC_UPD_MEGAG_DESP_ARQUIVO;


--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO IN MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE
)
IS
BEGIN
/*
* REGRA DE NEGÓCIO
*/
    DELETE FROM MEGAG_DESP_ARQUIVO
     WHERE CODARQUIVO = p_CODARQUIVO;

END PRC_DEL_MEGAG_DESP_ARQUIVO;


END PKG_MEGAG_DESP_CADASTRO;
/
