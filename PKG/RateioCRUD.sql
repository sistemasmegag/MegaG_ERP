/* ==================================================
   FILE: RateioCRUD.sql
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP_RATEIO(
    p_coddespesa         IN  CONSINCO.MEGAG_DESP_RATEIO.CODDESPESA%TYPE,
    p_centrocusto        IN  CONSINCO.MEGAG_DESP_RATEIO.CENTROCUSTO%TYPE,
    p_valorrateio        IN  CONSINCO.MEGAG_DESP_RATEIO.VALORRATEIO%TYPE,
    p_codrateio          OUT NUMBER,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) AS
BEGIN
    INSERT INTO CONSINCO.MEGAG_DESP_RATEIO(
        CODDESPESA, CENTROCUSTO, VALORRATEIO
    ) VALUES (
        p_coddespesa, p_centrocusto, p_valorrateio
    )
    RETURNING CODRATEIO INTO p_codrateio;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Rateio inserido: ' || p_codrateio;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_RATEIO;

PROCEDURE PRC_LIST_MEGAG_DESP_RATEIO(
    p_coddespesa IN  CONSINCO.MEGAG_DESP_RATEIO.CODDESPESA%TYPE,
    p_cursor     OUT SYS_REFCURSOR,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT * FROM CONSINCO.MEGAG_DESP_RATEIO
        WHERE CODDESPESA = p_coddespesa
        ORDER BY CENTROCUSTO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_RATEIO;
