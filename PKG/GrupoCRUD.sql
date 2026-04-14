--insert
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_GRUPO(
    p_nomegrupo     IN MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    p_msg_retorno   OUT VARCHAR2
) AS
BEGIN

    INSERT INTO MEGAG_DESP_GRUPO(
        NOMEGRUPO
    )
    VALUES(
        p_nomegrupo
    );

    p_msg_retorno := 'Grupo incluído com sucesso.';

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao incluir grupo: ' || SQLERRM;
END;
/

-- LIST
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_GRUPO(
    p_cursor OUT SYS_REFCURSOR
) AS
BEGIN

    OPEN p_cursor FOR
        SELECT
            CODGRUPO,
            NOMEGRUPO
        FROM MEGAG_DESP_GRUPO
        ORDER BY NOMEGRUPO;

END;
/

--UPDATE
CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP_GRUPO(
    p_codgrupo      IN MEGAG_DESP_GRUPO.CODGRUPO%TYPE,
    p_nomegrupo     IN MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    p_msg_retorno   OUT VARCHAR2
) AS
BEGIN

    UPDATE MEGAG_DESP_GRUPO
       SET NOMEGRUPO = p_nomegrupo
     WHERE CODGRUPO = p_codgrupo;

    IF SQL%ROWCOUNT = 0 THEN
        p_msg_retorno := 'Nenhum grupo encontrado para atualização.';
    ELSE
        p_msg_retorno := 'Grupo atualizado com sucesso.';
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao atualizar grupo: ' || SQLERRM;
END;
/

--DELETE
CREATE OR REPLACE PROCEDURE PRC_DEL_MEGAG_DESP_GRUPO(
    p_codgrupo      IN MEGAG_DESP_GRUPO.CODGRUPO%TYPE,
    p_msg_retorno   OUT VARCHAR2
) AS
BEGIN

    DELETE FROM MEGAG_DESP_GRUPO
     WHERE CODGRUPO = p_codgrupo;

    IF SQL%ROWCOUNT = 0 THEN
        p_msg_retorno := 'Nenhum grupo encontrado para exclusão.';
    ELSE
        p_msg_retorno := 'Grupo excluído com sucesso.';
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        p_msg_retorno := 'Erro ao excluir grupo: ' || SQLERRM;
END;
/
