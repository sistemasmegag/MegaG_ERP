--INSERT
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_GRUPO(
    p_nomegrupo    IN  CONSINCO.MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    p_dtainclusao  IN  CONSINCO.MEGAG_DESP_GRUPO.DTAINCLUSAO%TYPE,
    p_dtaalteracao IN  CONSINCO.MEGAG_DESP_GRUPO.DTAALTERACAO%TYPE,
    s_sfx          OUT VARCHAR2,
    s_ico          OUT VARCHAR2,
    s_tiporet      OUT VARCHAR2,
    s_msg          OUT VARCHAR2
) AS
BEGIN
    INSERT INTO CONSINCO.MEGAG_DESP_GRUPO(NOMEGRUPO, DTAINCLUSAO)
    VALUES(p_nomegrupo, SYSDATE);
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Grupo incluído.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_GRUPO;
/

--LIST
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_GRUPO(
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT * FROM CONSINCO.MEGAG_DESP_GRUPO ORDER BY NOMEGRUPO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_GRUPO;
/
