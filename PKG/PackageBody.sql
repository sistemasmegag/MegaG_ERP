CREATE OR REPLACE PACKAGE BODY PKG_MEGAG_DESP_CADASTRO IS

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
    p_dtaalteracao         IN MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL,
	p_codgrupo			   IN MEGAG_DESP_APROVADORES.CODGRUPO%TYPE
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
        DTAALTERACAO,
		CODGRUPO
    )
    VALUES(
        p_sequsuario,
        p_centrocusto,
        p_seqcentroresultado,
        p_sequusuarioalt,
        p_nome,
        SYSDATE,
        p_dtaalteracao,
		p_codgrupo);
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
               t.DTAALTERACAO,
			   t.CODGRUPO
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
	p_codgrupo			   IN MEGAG_DESP_APROVADORES.CODGRUPO%TYPE,
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
           DTAALTERACAO        = NVL(p_dtaalteracao, SYSDATE),
		   CODGRUPO			   = p_codgrupo
     WHERE SEQUSUARIO = p_sequsuario;

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
    p_USUARIOSOLICITANTE  	IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA      	IN MEGAG_DESP.CODTIPODESPESA%TYPE, --O PHP envia o ID escolhido(value)
    p_PAGO                	IN MEGAG_DESP.PAGO%TYPE DEFAULT 'N',
    p_VLRRATDESPESA       	IN MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR          	IN MEGAG_DESP.FORNECEDOR%TYPE DEFAULT NULL,
    p_NOMEARQUIVO         	IN MEGAG_DESP.NOMEARQUIVO%TYPE DEFAULT NULL,
    p_OBSERVACAO          	IN MEGAG_DESP.OBSERVACAO%TYPE DEFAULT NULL,
    p_SEQCENTRORESULTADO  	IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE,
    p_CENTROCUSTO         	IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS              	IN MEGAG_DESP.STATUS%TYPE DEFAULT 'LANCADO',
	p_DESCRICAOCENTROCUSTO  IN MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE DEFAULT NULL,
	p_CODPOLITICA           IN MEGAG_DESP.CODPOLITICA%TYPE DEFAULT NULL,
	p_DTAVENCIMENTO			IN MEGAG_DESP.DTAVENCIMENTO%TYPE DEFAULT NULL,
	p_DTADESPESA			IN MEGAG_DESP.DTADESPESA%TYPE DEFAULT NULL,
    p_CODDESPESA_OUT        OUT MEGAG_DESP.CODDESPESA%TYPE
)
IS
    -- Variável interna para guardar a descrição encontrada
    p_DESCRICAO MEGAG_DESP.DESCRICAO%TYPE;
BEGIN
    -- BUSCA AUTOMÁTICA:
    -- A Procedure vai na tabela de tipos e pega a descrição correta
    SELECT DESCRICAO --label
      INTO p_DESCRICAO
      FROM MEGAG_DESP_TIPO
     WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    -- INSERÇÃO:
    -- Agora usamos o p_CODTIPODESPESA (que veio do PHP)
    -- e o p_DESCRICAO (que o banco acabou de achar)
    INSERT INTO MEGAG_DESP(
        USUARIOSOLICITANTE,
        CODTIPODESPESA,
        DESCRICAO,
        PAGO,
        VLRRATDESPESA,
        FORNECEDOR,
        NOMEARQUIVO,
        OBSERVACAO,
        SEQCENTRORESULTADO,
        CENTROCUSTO,
        STATUS,
		DESCRICAOCENTROCUSTO,
		CODPOLITICA,
		DTAVENCIMENTO,
		DTADESPESA
    )
    VALUES(
        p_USUARIOSOLICITANTE,
        p_CODTIPODESPESA,
        p_DESCRICAO,
        p_PAGO,
        p_VLRRATDESPESA,
        p_FORNECEDOR,
        p_NOMEARQUIVO,
        p_OBSERVACAO,
        p_SEQCENTRORESULTADO,
        p_CENTROCUSTO,
        p_STATUS,
		p_DESCRICAOCENTROCUSTO,
		p_CODPOLITICA,
		p_DTAVENCIMENTO,
		p_DTADESPESA
    )
    RETURNING CODDESPESA INTO p_CODDESPESA_OUT;

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RAISE_APPLICATION_ERROR(-20001, 'Erro: O Tipo de Despesa informado não existe.');
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20002, 'Erro ao inserir despesa: ' || SQLERRM);
END PRC_INS_MEGAG_DESP;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP(
	p_CODDESPESA		 IN MEGAG_DESP.CODDESPESA%TYPE,
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
           AND (p_CODDESPESA IS NULL OR CODDESPESA = p_CODDESPESA)
           -- Filtro de Descrição: Busca parcial e case-insensitive
           AND (p_DESCRICAO IS NULL OR UPPER(DESCRICAO) LIKE '%' || UPPER(p_DESCRICAO) || '%')
           -- Filtro de Status: Busca exata ('LANCADO','APROVACAO','APROVADO','REJEITADO')
           AND (p_STATUS IS NULL OR STATUS = p_STATUS)
         ORDER BY DTAINCLUSAO DESC;
END PRC_LIST_MEGAG_DESP;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP(
    p_CODDESPESA         	IN MEGAG_DESP.CODDESPESA%TYPE,
    p_USUARIOSOLICITANTE 	IN MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA     	IN MEGAG_DESP.CODTIPODESPESA%TYPE,
    p_DESCRICAO          	IN MEGAG_DESP.DESCRICAO%TYPE,
    p_VLRRATDESPESA      	IN MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR         	IN MEGAG_DESP.FORNECEDOR%TYPE,
    p_NOMEARQUIVO        	IN MEGAG_DESP.NOMEARQUIVO%TYPE,
    p_OBSERVACAO         	IN MEGAG_DESP.OBSERVACAO%TYPE,
    p_SEQCENTRORESULTADO 	IN MEGAG_DESP.SEQCENTRORESULTADO%TYPE,
    p_CENTROCUSTO        	IN MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS             	IN MEGAG_DESP.STATUS%TYPE,
	p_DESCRICAOCENTROCUSTO  IN MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE,
	p_DTAVENCIMENTO			IN MEGAG_DESP.DTAVENCIMENTO%TYPE DEFAULT NULL,
	p_DTADESPESA			IN MEGAG_DESP.DTADESPESA%TYPE DEFAULT NULL
)
IS
BEGIN
    UPDATE MEGAG_DESP
       SET USUARIOSOLICITANTE 	= p_USUARIOSOLICITANTE,
    	   CODTIPODESPESA     	= p_CODTIPODESPESA,
           DESCRICAO          	= p_DESCRICAO,
           VLRRATDESPESA      	= p_VLRRATDESPESA,
           FORNECEDOR         	= p_FORNECEDOR,
           NOMEARQUIVO        	= p_NOMEARQUIVO,
           OBSERVACAO         	= p_OBSERVACAO,
           SEQCENTRORESULTADO 	= p_SEQCENTRORESULTADO,
           CENTROCUSTO        	= p_CENTROCUSTO,
           STATUS             	= p_STATUS,
		   DESCRICAOCENTROCUSTO = p_DESCRICAOCENTROCUSTO,
           DTAALTERACAO       	= SYSDATE,
		   DTAVENCIMENTO 		= p_DTAVENCIMENTO,
		   DTADESPESA 			= p_DTADESPESA
     WHERE CODDESPESA = p_CODDESPESA
     AND STATUS = 'LANCADO';

IF SQL%NOTFOUND THEN
        RAISE_APPLICATION_ERROR(-20001, 'ATUALIZAÇÃO NEGADA: Registro já passou pela aprovação do gestor.');
    END IF;
	COMMIT;

EXCEPTION
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20002, 'Erro ao atualizar despesa: ' || SQLERRM);
END PRC_UPD_MEGAG_DESP;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP(
    p_CODDESPESA IN MEGAG_DESP.CODDESPESA%TYPE,
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
    END IF;

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

PROCEDURE PRC_LIST_MEGAG_DESP_CENTRO_CUSTO(
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
END PRC_LIST_MEGAG_DESP_CENTRO_CUSTO;

PROCEDURE PRC_UPD_MEGAG_DESP_CENTRO_CUSTO(
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

END PRC_UPD_MEGAG_DESP_CENTRO_CUSTO;

/*PROCEDURE PRC_DEL_MEGAG_DESP_CENTRO_CUSTO(
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

END PRC_DEL_MEGAG_DESP_CENTRO_CUSTO;
*/

--PROC DE APROVAÇÃO(lista)
PROCEDURE PRC_LIST_MEGAG_DESP_APROVACAO(
    p_sequsuario  IN MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_cursor      OUT SYS_REFCURSOR
) AS
    v_existe NUMBER;
BEGIN
    -- Verifica se o usuário é um aprovador em pelo menos 1 CC
    SELECT COUNT(*)
    INTO v_existe
    FROM MEGAG_DESP_APROVADORES
    WHERE SEQUSUARIO = p_sequsuario;

    IF v_existe > 0 THEN
        -- Lista despesas Lançadas/Pendentes que o usuário tem direito e é a vez dele na hierarquia
        OPEN p_cursor FOR
        SELECT DISTINCT
               desp.CODDESPESA,
               desp.USUARIOSOLICITANTE,
               desp.CODTIPODESPESA,
               desp.PAGO,
               desp.VLRRATDESPESA,
               desp.DESCRICAO,
               desp.FORNECEDOR,
               desp.DTAINCLUSAO,
               desp.DTAALTERACAO,
               desp.CODARQUIVO,
               desp.NOMEARQUIVO,
               desp.OBSERVACAO,
               desp.SEQCENTRORESULTADO,
               desp.CENTROCUSTO,
               desp.STATUS
        FROM MEGAG_DESP desp
        INNER JOIN MEGAG_DESP_RATEIO r
            ON r.CODDESPESA = desp.CODDESPESA
        INNER JOIN MEGAG_DESP_APROVADORES a
            ON a.CENTROCUSTO = r.CENTROCUSTO
        INNER JOIN MEGAG_DESP_POLIT_CENTRO_CUSTO p
            ON p.CODGRUPO = a.CODGRUPO AND p.CENTROCUSTO = a.CENTROCUSTO
        -- Pode ser que a aplicação tenha outros status intermediários além de LANCADO
        WHERE desp.STATUS NOT IN ('APROVADO', 'REJEITADO')
          -- 1 Não listar se a despesa foi feita pelo próprio usuário logado
          AND desp.USUARIOSOLICITANTE <> p_sequsuario
          -- Filtra para cruzar com o usuário logado
          AND a.SEQUSUARIO = p_sequsuario
          -- Bloqueia centros de custo do rateio que o usuário JÁ aprovou ou rejeitou
          AND NOT EXISTS (
              SELECT 1 FROM MEGAG_DESP_APROVACAO apr
              WHERE apr.CODDESPESA = r.CODDESPESA
                AND apr.CENTROCUSTO = r.CENTROCUSTO
                AND apr.USUARIOAPROVADOR = p_sequsuario
          )
          -- 2 A despesa só aparece se for a vez do nível dele para este Rateio
          AND p.NIVEL_APROVACAO <= (
              SELECT NVL(MAX(apr_nivel.NIVEL_APROVACAO), 0) + 1
              FROM MEGAG_DESP_APROVACAO apr_nivel
              WHERE apr_nivel.CODDESPESA = desp.CODDESPESA
                AND apr_nivel.CENTROCUSTO = r.CENTROCUSTO
                AND apr_nivel.STATUS = 'APROVADO'
          );
    ELSE
        -- 3 Retorna cursor vazio com as MESMAS colunas
        OPEN p_cursor FOR
            SELECT desp.CODDESPESA,
                   desp.USUARIOSOLICITANTE,
                   desp.CODTIPODESPESA,
                   desp.PAGO,
                   desp.VLRRATDESPESA,
                   desp.DESCRICAO,
                   desp.FORNECEDOR,
                   desp.DTAINCLUSAO,
                   desp.DTAALTERACAO,
                   desp.CODARQUIVO,
                   desp.NOMEARQUIVO,
                   desp.OBSERVACAO,
                   desp.SEQCENTRORESULTADO,
                   desp.CENTROCUSTO,
                   desp.STATUS
            FROM MEGAG_DESP desp
            WHERE 1 = 0;
    END IF;

END PRC_LIST_MEGAG_DESP_APROVACAO;

	/*
	* update de aprovação
	* Usuario valida ou não a despesa e o sistema altera o status retornando como 'APROVADO' OU 'REJEITADO'
	*/
--PROC DE APROVAÇÃO(Atualiza)
PROCEDURE PRC_UPD_MEGAG_DESP_APROVACAO(
    p_coddespesa     IN MEGAG_DESP.CODDESPESA%TYPE,
    p_sequsuario     IN MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_status         IN MEGAG_DESP_APROVACAO.STATUS%TYPE,
    p_pago           IN MEGAG_DESP.PAGO%TYPE,
    p_observacao     IN MEGAG_DESP.OBSERVACAO%TYPE,
    p_msg_retorno    OUT VARCHAR2
) AS
    v_solicitante        NUMBER;
    v_status_atual       MEGAG_DESP.STATUS%TYPE;
    v_max_nivel_aprovado NUMBER;
    v_total_aprovadores  NUMBER;
    v_total_respostas    NUMBER;
    v_processou_algo     NUMBER := 0;

    -- Cursor: todos os CCs que o usuário pode aprovar e ainda não aprovou
    CURSOR c_cc_pendentes IS
        SELECT r.CENTROCUSTO, p.NIVEL_APROVACAO
        FROM MEGAG_DESP_RATEIO r
        JOIN MEGAG_DESP_APROVADORES a
          ON a.CENTROCUSTO = r.CENTROCUSTO
        JOIN MEGAG_DESP_POLIT_CENTRO_CUSTO p
          ON p.CODGRUPO = a.CODGRUPO
          AND p.CENTROCUSTO = a.CENTROCUSTO
        WHERE r.CODDESPESA = p_coddespesa
          AND a.SEQUSUARIO = p_sequsuario
          AND NOT EXISTS (
              SELECT 1 FROM MEGAG_DESP_APROVACAO apr
              WHERE apr.CODDESPESA = r.CODDESPESA
                AND apr.CENTROCUSTO = r.CENTROCUSTO
                AND apr.USUARIOAPROVADOR = p_sequsuario
          );
BEGIN
    -- 1- Lock da despesa para concorrência e checagem de status
    SELECT USUARIOSOLICITANTE, STATUS
    INTO v_solicitante, v_status_atual
    FROM MEGAG_DESP
    WHERE CODDESPESA = p_coddespesa
    FOR UPDATE;

    -- Trava de segurança: Se a despesa já foi finalizada, aborta.
    IF v_status_atual IN ('APROVADO', 'REJEITADO') THEN
        p_msg_retorno := 'Atenção: Esta despesa já encontra-se finalizada (' || v_status_atual || ').';
        RETURN;
    END IF;

    -- 2- Impedir auto aprovação
    IF v_solicitante = p_sequsuario THEN
        p_msg_retorno := 'Erro: solicitante não pode aprovar própria despesa';
        RETURN;
    END IF;

    -- 3- Loop em todos os centros de custo pendentes do usuário
    FOR v_cc IN c_cc_pendentes LOOP

        -- Validar hierarquia apenas dentro daquele CC
        SELECT NVL(MAX(NIVEL_APROVACAO),0)
        INTO v_max_nivel_aprovado
        FROM MEGAG_DESP_APROVACAO
        WHERE CODDESPESA = p_coddespesa
          AND CENTROCUSTO = v_cc.CENTROCUSTO
          AND STATUS = 'APROVADO';

        -- Se o nível do usuário está liberado
        IF v_cc.NIVEL_APROVACAO <= v_max_nivel_aprovado + 1 THEN

            -- 4- Registrar aprovação/reprovação
            INSERT INTO MEGAG_DESP_APROVACAO(
                CODDESPESA,
                CENTROCUSTO,
                USUARIOAPROVADOR,
                STATUS,
                DTAACAO,
                OBSERVACAO,
                NIVEL_APROVACAO
            )
            VALUES(
                p_coddespesa,
                v_cc.CENTROCUSTO,
                p_sequsuario,
                p_status,
                SYSDATE,
                p_observacao,
                v_cc.NIVEL_APROVACAO
            );

            v_processou_algo := 1;

            -- 5- Rejeição imediata se status for REJEITADO
            IF p_status = 'REJEITADO' THEN
                UPDATE MEGAG_DESP
                SET STATUS = 'REJEITADO',
                    DTAALTERACAO = SYSDATE
                WHERE CODDESPESA = p_coddespesa;

                p_msg_retorno := 'Despesa rejeitada';
                RETURN;
            END IF;
        END IF;

    END LOOP;

    -- 6- Se nada processou, usuário não podia aprovar
    IF v_processou_algo = 0 THEN
        p_msg_retorno := 'Você já aprovou, ou os setores aguardam aprovadores de níveis inferiores.';
        RETURN;
    END IF;

    -- 7- Contar total de assinaturas necessárias
    SELECT COUNT(*)
    INTO v_total_aprovadores
    FROM MEGAG_DESP_RATEIO r
    JOIN MEGAG_DESP_APROVADORES a
      ON a.CENTROCUSTO = r.CENTROCUSTO
    WHERE r.CODDESPESA = p_coddespesa;

    -- 8- Contar respostas registradas
    SELECT COUNT(*)
    INTO v_total_respostas
    FROM MEGAG_DESP_APROVACAO
    WHERE CODDESPESA = p_coddespesa;

    -- 9- Finalizar despesa se todos aprovaram
    IF v_total_respostas >= v_total_aprovadores THEN
        UPDATE MEGAG_DESP
        SET STATUS = 'APROVADO',
            PAGO = p_pago,
            DTAALTERACAO = SYSDATE
        WHERE CODDESPESA = p_coddespesa;

        p_msg_retorno := 'Despesa aprovada por todos!';
    ELSE
        p_msg_retorno := 'Aprovação registrada. Aguardando demais aprovadores';
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro inesperado: ' || SQLERRM;
        ROLLBACK;
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

/* ==================================================
   FILE: PolíticaCRUD.sql
================================================== */
--insert
PROCEDURE PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codgrupo           IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario		 IN MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto        IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_descricao          IN MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE,
    p_nivel_aprovacao    IN MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_msg_retorno        OUT VARCHAR2
) AS
BEGIN
    INSERT INTO MEGAG_DESP_POLIT_CENTRO_CUSTO(
        CODGRUPO, SEQUSUARIO, CENTROCUSTO, DESCRICAO, DTAINCLUSAO, NIVEL_APROVACAO
    ) VALUES (
        p_codgrupo, p_sequsuario, p_centrocusto, p_descricao, SYSDATE, p_nivel_aprovacao
    );

    p_msg_retorno := 'Inclusão realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao incluir: ' || SQLERRM;
END;

--LIST
PROCEDURE PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT p.CODPOLITICA,
               p.CODGRUPO,
               g.NOMEGRUPO,
               p.CENTROCUSTO,
               p.SEQUSUARIO,
               p.DESCRICAO,
               p.DTAINCLUSAO,
               p.NIVEL_APROVACAO
        FROM MEGAG_DESP_POLIT_CENTRO_CUSTO p
        JOIN MEGAG_DESP_GRUPO g ON p.CODGRUPO = g.CODGRUPO
        ORDER BY p.CENTROCUSTO, p.NIVEL_APROVACAO;
END;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica        IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE,
    p_codgrupo           IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario 	     IN MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto        IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_descricao          IN MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE,
    p_nivel_aprovacao    IN MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_msg_retorno        OUT VARCHAR2
) AS
BEGIN
    UPDATE MEGAG_DESP_POLIT_CENTRO_CUSTO
    SET CODGRUPO = p_codgrupo,
        CENTROCUSTO = p_centrocusto,
        SEQUSUARIO = p_sequsuario,
        DESCRICAO = p_descricao,
        NIVEL_APROVACAO = p_nivel_aprovacao
    WHERE CODPOLITICA = p_codpolitica;

    IF SQL%ROWCOUNT = 0 THEN
        p_msg_retorno := 'Nenhum registro encontrado para atualização.';
    ELSE
        p_msg_retorno := 'Registro atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao atualizar: ' || SQLERRM;
END;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE,
    p_msg_retorno OUT VARCHAR2
) AS
BEGIN
    DELETE FROM MEGAG_DESP_POLIT_CENTRO_CUSTO
    WHERE CODPOLITICA = p_codpolitica;

    IF SQL%ROWCOUNT = 0 THEN
        p_msg_retorno := 'Nenhum registro encontrado para deleção.';
    ELSE
        p_msg_retorno := 'Registro excluído com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao excluir: ' || SQLERRM;
END;

/* ==================================================
   FILE: GrupoCRUD.sql
================================================== */

--insert
PROCEDURE PRC_INS_MEGAG_DESP_GRUPO(
    p_nomegrupo     IN MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    p_msg_retorno   OUT VARCHAR2
) AS
BEGIN

    INSERT INTO MEGAG_DESP_GRUPO(
        NOMEGRUPO
    )
    VALUES(
        p_nomegrupo
    );

    p_msg_retorno := 'Grupo incluído com sucesso.';

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao incluir grupo: ' || SQLERRM;
END;

-- LIST
PROCEDURE PRC_LIST_MEGAG_DESP_GRUPO(
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN

    OPEN p_cursor FOR
        SELECT
            CODGRUPO,
            NOMEGRUPO
        FROM MEGAG_DESP_GRUPO
        ORDER BY NOMEGRUPO;

END;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_GRUPO(
    p_codgrupo      IN MEGAG_DESP_GRUPO.CODGRUPO%TYPE,
    p_nomegrupo     IN MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    p_msg_retorno   OUT VARCHAR2
) AS
BEGIN

    UPDATE MEGAG_DESP_GRUPO
       SET NOMEGRUPO = p_nomegrupo
     WHERE CODGRUPO = p_codgrupo;

    IF SQL%ROWCOUNT = 0 THEN
        p_msg_retorno := 'Nenhum grupo encontrado para atualização.';
    ELSE
        p_msg_retorno := 'Grupo atualizado com sucesso.';
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao atualizar grupo: ' || SQLERRM;
END;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_GRUPO(
    p_codgrupo      IN MEGAG_DESP_GRUPO.CODGRUPO%TYPE,
    p_msg_retorno   OUT VARCHAR2
) AS
BEGIN

    DELETE FROM MEGAG_DESP_GRUPO
     WHERE CODGRUPO = p_codgrupo;

    IF SQL%ROWCOUNT = 0 THEN
        p_msg_retorno := 'Nenhum grupo encontrado para exclusão.';
    ELSE
        p_msg_retorno := 'Grupo excluído com sucesso.';
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao excluir grupo: ' || SQLERRM;
END;


/* ==================================================
   FILE: RateioCRUD.sql
================================================== */

--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_RATEIO(
    p_coddespesa         IN MEGAG_DESP_RATEIO.CODDESPESA%TYPE,
    p_seqcentroresultado IN MEGAG_DESP_RATEIO.SEQCENTRORESULTADO%TYPE,
    p_centrocusto        IN MEGAG_DESP_RATEIO.CENTROCUSTO%TYPE,
    p_valorrateio        IN MEGAG_DESP_RATEIO.VALORRATEIO%TYPE,
    p_codrateio          OUT NUMBER
) AS
BEGIN
    INSERT INTO MEGAG_DESP_RATEIO (
        CODDESPESA, SEQCENTRORESULTADO, CENTROCUSTO, VALORRATEIO
    ) VALUES (
        p_coddespesa, p_seqcentroresultado, p_centrocusto, p_valorrateio
    )
    RETURNING CODRATEIO INTO p_codrateio;
END PRC_INS_MEGAG_DESP_RATEIO;

--LIST
PROCEDURE PRC_LIST_MEGAG_DESP_RATEIO(
    p_coddespesa IN  MEGAG_DESP_RATEIO.CODDESPESA%TYPE,
    p_cursor     OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR
    SELECT CODRATEIO, CODDESPESA, SEQCENTRORESULTADO, CENTROCUSTO, VALORRATEIO
    FROM MEGAG_DESP_RATEIO
    WHERE CODDESPESA = p_coddespesa
    ORDER BY CENTROCUSTO;
END PRC_LIST_MEGAG_DESP_RATEIO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_RATEIO(
    p_codrateio   IN MEGAG_DESP_RATEIO.CODRATEIO%TYPE,
    p_valorrateio IN MEGAG_DESP_RATEIO.VALORRATEIO%TYPE
) AS
BEGIN
    UPDATE MEGAG_DESP_RATEIO
    SET VALORRATEIO = p_valorrateio
    WHERE CODRATEIO = p_codrateio;
END PRC_UPD_MEGAG_DESP_RATEIO;

--DELETAR
PROCEDURE PRC_DEL_MEGAG_DESP_RATEIO(
    p_codrateio IN MEGAG_DESP_RATEIO.CODRATEIO%TYPE
) AS
BEGIN
    DELETE FROM MEGAG_DESP_RATEIO
    WHERE CODRATEIO = p_codrateio;
END PRC_DEL_MEGAG_DESP_RATEIO;

END PKG_MEGAG_DESP_CADASTRO;
