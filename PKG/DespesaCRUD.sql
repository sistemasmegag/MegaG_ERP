/* ==================================================
   FILE: DespesaCRUD.sql
================================================== */
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP(
    p_USUARIOSOLICITANTE   IN  CONSINCO.MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA       IN  CONSINCO.MEGAG_DESP.CODTIPODESPESA%TYPE,
    p_PAGO                 IN  CONSINCO.MEGAG_DESP.PAGO%TYPE              DEFAULT 'N',
    p_VLRRATDESPESA        IN  CONSINCO.MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR           IN  CONSINCO.MEGAG_DESP.FORNECEDOR%TYPE        DEFAULT NULL,
    p_NOMEARQUIVO          IN  CONSINCO.MEGAG_DESP.NOMEARQUIVO%TYPE       DEFAULT NULL,
    p_OBSERVACAO           IN  CONSINCO.MEGAG_DESP.OBSERVACAO%TYPE        DEFAULT NULL,
    p_CENTROCUSTO          IN  CONSINCO.MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS               IN  CONSINCO.MEGAG_DESP.STATUS%TYPE            DEFAULT 'LANCADO',
    p_DESCRICAOCENTROCUSTO IN  CONSINCO.MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE DEFAULT NULL,
    p_CODPOLITICA          IN  CONSINCO.MEGAG_DESP.CODPOLITICA%TYPE       DEFAULT NULL,
    p_DTAVENCIMENTO        IN  CONSINCO.MEGAG_DESP.DTAVENCIMENTO%TYPE     DEFAULT NULL,
    p_DTADESPESA           IN  CONSINCO.MEGAG_DESP.DTADESPESA%TYPE        DEFAULT NULL,
    p_CODDESPESA_OUT       OUT CONSINCO.MEGAG_DESP.CODDESPESA%TYPE,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
    v_desc_tipo       VARCHAR2(200);
    v_count_aprovador NUMBER;
BEGIN
    -- Busca descrição da categoria
    SELECT DESCRICAO INTO v_desc_tipo FROM CONSINCO.MEGAG_DESP_TIPO WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    -- Validação: Verifica se existe pelo menos 1 aprovador configurado para o CC principal
    SELECT COUNT(*) INTO v_count_aprovador
    FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p
    WHERE TO_CHAR(p.CENTROCUSTO) = TO_CHAR(p_CENTROCUSTO);

    IF v_count_aprovador = 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'Centro de Custo ' || p_CENTROCUSTO || ' não possui aprovadores configurados.');
    END IF;

    INSERT INTO CONSINCO.MEGAG_DESP(
        USUARIOSOLICITANTE, CODTIPODESPESA, DESCRICAO, PAGO,
        VLRRATDESPESA, FORNECEDOR, NOMEARQUIVO, OBSERVACAO,
        CENTROCUSTO, STATUS, DESCRICAOCENTROCUSTO, CODPOLITICA, 
		DTAVENCIMENTO, DTADESPESA, DTAINCLUSAO
    ) VALUES (
        p_USUARIOSOLICITANTE, p_CODTIPODESPESA, v_desc_tipo, p_PAGO,
        p_VLRRATDESPESA, p_FORNECEDOR, p_NOMEARQUIVO, p_OBSERVACAO,
		p_CENTROCUSTO, p_STATUS, p_DESCRICAOCENTROCUSTO, p_CODPOLITICA,
		p_DTAVENCIMENTO, p_DTADESPESA, SYSDATE
    )
    RETURNING CODDESPESA INTO p_CODDESPESA_OUT;

    -- REMOVIDO: Chamada interna de PRC_INS_MEGAG_DESP_APROVACAO
    -- Motivo: Evitar duplicidade de trilhas quando há rateio. 
    -- A API chamará este motor após inserir os rateios e dar COMMIT.

    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S';
    s_msg := 'Despesa #' || p_CODDESPESA_OUT || ' criada.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP;
/

CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP(
    p_USUARIOSOLICITANTE   IN  CONSINCO.GE_USUARIO.SEQUSUARIO%TYPE,
    p_STATUS               IN  CONSINCO.MEGAG_DESP.STATUS%TYPE            DEFAULT NULL,
    p_RESULT               OUT SYS_REFCURSOR,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM CONSINCO.MEGAG_DESP
         WHERE USUARIOSOLICITANTE = p_USUARIOSOLICITANTE
           AND (p_STATUS IS NULL OR STATUS = p_STATUS)
         ORDER BY DTAINCLUSAO DESC;

    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP;
/
