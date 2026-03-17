--insert
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codgrupo           IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_centrocusto        IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_seqcentroresultado IN MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQCENTRORESULTADO%TYPE,
    p_descricao          IN MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE,
    p_nivel_aprovacao    IN MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_msg_retorno        OUT VARCHAR2
) AS
BEGIN
    INSERT INTO MEGAG_DESP_POLIT_CENTRO_CUSTO(
        CODGRUPO, CENTROCUSTO, SEQCENTRORESULTADO, DESCRICAO, DTAINCLUSAO, NIVEL_APROVACAO
    ) VALUES (
        p_codgrupo, p_centrocusto, p_seqcentroresultado, p_descricao, SYSDATE, p_nivel_aprovacao
    );

    p_msg_retorno := 'Inclusão realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao incluir: ' || SQLERRM;
END;
/

--LIST
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT p.CODPOLITICA,
               p.CODGRUPO,
               g.NOMEGRUPO,
               p.CENTROCUSTO,
               p.SEQCENTRORESULTADO,
               p.DESCRICAO,
               p.DTAINCLUSAO,
               p.NIVEL_APROVACAO
        FROM MEGAG_DESP_POLIT_CENTRO_CUSTO p
        JOIN MEGAG_DESP_GRUPO g ON p.CODGRUPO = g.CODGRUPO
        ORDER BY p.CENTROCUSTO, p.NIVEL_APROVACAO;
END;
/

--UPDATE
CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica        IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE,
    p_codgrupo           IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_centrocusto        IN MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_seqcentroresultado IN MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQCENTRORESULTADO%TYPE,
    p_descricao          IN MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE,
    p_nivel_aprovacao    IN MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_msg_retorno        OUT VARCHAR2
) AS
BEGIN
    UPDATE MEGAG_DESP_POLIT_CENTRO_CUSTO
    SET CODGRUPO = p_codgrupo,
        CENTROCUSTO = p_centrocusto,
        SEQCENTRORESULTADO = p_seqcentroresultado,
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
/

--DELETE
CREATE OR REPLACE PROCEDURE PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(
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
/
