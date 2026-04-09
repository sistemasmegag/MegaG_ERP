CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_CENTRO_CUSTO(
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
/

CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_CENTRO_CUSTO(
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
/

CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP_CENTRO_CUSTO(
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
/

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
/

--PROC DE APROVAÇÃO(lista)
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_APROVACAO(
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
            ON p.SEQUSUARIO = a.SEQUSUARIO AND p.CENTROCUSTO = a.CENTROCUSTO
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
/

	/*
	* update de aprovação
	* Usuario valida ou não a despesa e o sistema altera o status retornando como 'APROVADO' OU 'REJEITADO'
	*/
--PROC DE APROVAÇÃO(Atualiza)
CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP_APROVACAO(
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
          ON p.SEQUSUARIO = a.SEQUSUARIO
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
/
