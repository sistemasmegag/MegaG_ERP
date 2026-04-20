/* ==================================================
   FILE: PoliticaCCCRUD.sql (Mapeado como PoliticaCRUD.sql)
================================================== */

CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica      IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE,
    p_codgrupo         IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario       IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto      IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_nivel_aprovacao  IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_descricao        IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE DEFAULT NULL,
    p_codpolit_cc      OUT CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx              OUT VARCHAR2,
    s_ico              OUT VARCHAR2,
    s_tiporet          OUT VARCHAR2,
    s_msg              OUT VARCHAR2
) AS
    v_pol NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_pol
    FROM CONSINCO.MEGAG_DESP_POLITICA WHERE CODPOLITICA = p_codpolitica;

    IF v_pol = 0 THEN
        s_sfx := 'warning'; s_ico := 'warning'; s_tiporet := 'A'; s_msg := 'CODPOLITICA ' || p_codpolitica || ' não existe.';
        RETURN;
    END IF;

    INSERT INTO CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO(
        CODPOLITICA, CODGRUPO, SEQUSUARIO, CENTROCUSTO,
        NIVEL_APROVACAO, DESCRICAO, DTAINCLUSAO
    ) VALUES (
        p_codpolitica, p_codgrupo, p_sequsuario, p_centrocusto,
        p_nivel_aprovacao, p_descricao, SYSDATE
    )
    RETURNING CODPOLIT_CC INTO p_codpolit_cc;

    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Vínculo incluído.';
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        s_sfx := 'warning'; s_ico := 'warning'; s_tiporet := 'A'; s_msg := 'Vínculo duplicado.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO;
/

CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE DEFAULT NULL,
    p_cursor      OUT SYS_REFCURSOR,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT pc.*, pol.DESCRICAO AS DESCRICAO_POLITICA, g.NOMEGRUPO
        FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pc
        JOIN CONSINCO.MEGAG_DESP_POLITICA pol ON pol.CODPOLITICA = pc.CODPOLITICA
        JOIN CONSINCO.MEGAG_DESP_GRUPO g ON g.CODGRUPO = pc.CODGRUPO
        WHERE (p_codpolitica IS NULL OR pc.CODPOLITICA = p_codpolitica)
        ORDER BY pc.CODPOLITICA, pc.NIVEL_APROVACAO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO;
/

CREATE OR REPLACE PROCEDURE PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolit_cc IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    DELETE FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO WHERE CODPOLIT_CC = p_codpolit_cc;
    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Vínculo excluído.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO;
/
