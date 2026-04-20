--INSERT
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_POLITICA(
    p_descricao         IN  CONSINCO.MEGAG_DESP_POLITICA.DESCRICAO%TYPE,
    p_codgrupo          IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario        IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto       IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_nivel_aprovacao   IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_descricao_vinculo IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE DEFAULT NULL,
    p_codpolitica       OUT CONSINCO.MEGAG_DESP_POLITICA.CODPOLITICA%TYPE,
    p_codpolit_cc       OUT CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx               OUT VARCHAR2,
    s_ico               OUT VARCHAR2,
    s_tiporet           OUT VARCHAR2,
    s_msg               OUT VARCHAR2
) AS
    v_sfx_filho     VARCHAR2(20);
    v_ico_filho     VARCHAR2(20);
    v_tiporet_filho VARCHAR2(1);
    v_msg_filho     VARCHAR2(500);
BEGIN
    INSERT INTO CONSINCO.MEGAG_DESP_POLITICA(DESCRICAO)
    VALUES(p_descricao)
    RETURNING CODPOLITICA INTO p_codpolitica;

    PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
        p_codpolitica     => p_codpolitica,
        p_codgrupo        => p_codgrupo,
        p_sequsuario      => p_sequsuario,
        p_centrocusto     => p_centrocusto,
        p_nivel_aprovacao => p_nivel_aprovacao,
        p_descricao       => p_descricao_vinculo,
        p_codpolit_cc     => p_codpolit_cc,
        s_sfx             => v_sfx_filho,
        s_ico             => v_ico_filho,
        s_tiporet         => v_tiporet_filho,
        s_msg             => v_msg_filho
    );

    IF v_tiporet_filho = 'E' THEN
        RAISE_APPLICATION_ERROR(-20010, 'Erro ao criar vínculo: ' || v_msg_filho);
    END IF;

    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Política criada.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_POLITICA;
/

--SELECT
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_POLITICA(
    p_codpolitica IN  CONSINCO.MEGAG_DESP_POLITICA.CODPOLITICA%TYPE DEFAULT NULL,
    p_cursor      OUT SYS_REFCURSOR,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT * FROM CONSINCO.MEGAG_DESP_POLITICA
        WHERE (p_codpolitica IS NULL OR CODPOLITICA = p_codpolitica)
        ORDER BY CODPOLITICA;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_POLITICA;
/
