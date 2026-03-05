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
    END IF

    -- Busca os dados do centro de resultado
    SELECT DESCRICAO, SEQCENTRORESULTADO
    INTO v_descricao, v_seqcentro
    FROM ABA_CENTRORESULTADO
    WHERE CENTRORESULTADO = p_centrocusto

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

CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_CENTRORESULTADO(
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
/

CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP(
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
    END IF

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
/

CREATE OR REPLACE PROCEDURE PRC_DEL_MEGAG_DESP(
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
/