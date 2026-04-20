--INSERT
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_CENTRO_CUSTO(
    p_centrocusto        IN  CONSINCO.ABA_CENTRORESULTADO.CENTRORESULTADO%TYPE,
    p_coddespesa         IN  CONSINCO.MEGAG_DESP.CODDESPESA%TYPE,
    p_usuariosolicitante IN  CONSINCO.MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) AS
    v_descricao VARCHAR2(200);
    v_count     NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM CONSINCO.MEGAG_DESP
    WHERE CODDESPESA = p_coddespesa AND USUARIOSOLICITANTE = p_usuariosolicitante;

    IF v_count = 0 THEN
        s_sfx := 'warning'; s_ico := 'warning'; s_tiporet := 'A'; s_msg := 'Despesa não encontrada.';
        RETURN;
    END IF;

    SELECT DESCRICAO INTO v_descricao FROM CONSINCO.ABA_CENTRORESULTADO WHERE CENTRORESULTADO = p_centrocusto;

    INSERT INTO CONSINCO.MEGAG_DESP_RATEIO(CODDESPESA, CENTROCUSTO, VALORRATEIO)
    VALUES(p_coddespesa, p_centrocusto, 0);

    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Centro de custo inserido.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_CENTRO_CUSTO;
/

--SELECT
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_CENTRO_CUSTO(
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT CENTRORESULTADO, DESCRICAO
        FROM CONSINCO.ABA_CENTRORESULTADO
        ORDER BY DESCRICAO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_CENTRO_CUSTO;
/

--UPDATE
CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP_CENTRO_CUSTO(
    p_centrocusto_atual        IN  CONSINCO.MEGAG_DESP.CENTROCUSTO%TYPE,
    p_centrocusto_novo         IN  CONSINCO.MEGAG_DESP.CENTROCUSTO%TYPE,
    p_descricao_nova           IN  CONSINCO.MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE,
    p_coddespesa               IN  CONSINCO.MEGAG_DESP.CODDESPESA%TYPE,
    p_usuariosolicitante       IN  CONSINCO.MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    s_sfx                      OUT VARCHAR2,
    s_ico                      OUT VARCHAR2,
    s_tiporet                  OUT VARCHAR2,
    s_msg                      OUT VARCHAR2
) AS
BEGIN
    UPDATE CONSINCO.MEGAG_DESP
       SET CENTROCUSTO          = p_centrocusto_novo,
           DESCRICAOCENTROCUSTO = p_descricao_nova
     WHERE CENTROCUSTO        = p_centrocusto_atual
       AND CODDESPESA         = p_coddespesa
       AND USUARIOSOLICITANTE = p_usuariosolicitante;

    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Centro de custo atualizado.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_CENTRO_CUSTO;
/
