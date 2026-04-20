--INSERT
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_TIPO(
    p_DESCRICAO IN  CONSINCO.MEGAG_DESP_TIPO.DESCRICAO%TYPE,
    s_sfx       OUT VARCHAR2,
    s_ico       OUT VARCHAR2,
    s_tiporet   OUT VARCHAR2,
    s_msg       OUT VARCHAR2
) IS
BEGIN
    INSERT INTO CONSINCO.MEGAG_DESP_TIPO(DESCRICAO) VALUES(p_DESCRICAO);
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Tipo de despesa inserido.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_TIPO;
/

--SELECT
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN  CONSINCO.MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE,
    p_DESCRICAO      IN  CONSINCO.MEGAG_DESP_TIPO.DESCRICAO%TYPE,
    p_RESULT         OUT SYS_REFCURSOR,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM CONSINCO.MEGAG_DESP_TIPO
         WHERE (p_CODTIPODESPESA IS NULL OR CODTIPODESPESA = p_CODTIPODESPESA)
           AND (p_DESCRICAO IS NULL OR DESCRICAO LIKE '%' || p_DESCRICAO || '%');
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_TIPO;
/
