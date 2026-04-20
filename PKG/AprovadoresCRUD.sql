--INSERT
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_APROVADORES(
    p_sequsuario           IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_centrocusto          IN  CONSINCO.MEGAG_DESP_APROVADORES.CENTROCUSTO%TYPE,
    p_nome                 IN  CONSINCO.MEGAG_DESP_APROVADORES.NOME%TYPE,
    p_sequusuarioalt       IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    p_dtaalteracao         IN  CONSINCO.MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL,
    p_codgrupo             IN  CONSINCO.MEGAG_DESP_APROVADORES.CODGRUPO%TYPE,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) AS
BEGIN
    INSERT INTO CONSINCO.MEGAG_DESP_APROVADORES(
        SEQUSUARIO, CENTROCUSTO, SEQUSUARIOALTERACAO, NOME, DTAINCLUSAO, DTAALTERACAO, CODGRUPO
    ) VALUES (
        p_sequsuario, p_centrocusto, p_sequusuarioalt, p_nome, SYSDATE, p_dtaalteracao, p_codgrupo
    );

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Aprovador inserido com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR APROVADORES - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_APROVADORES;
/

--SELECT
CREATE OR REPLACE PROCEDURE PRC_LIST_MEGAG_DESP_APROVADORES(
    p_nome    IN  CONSINCO.GE_USUARIO.NOME%TYPE,
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT t.SEQUSUARIO, t.CENTROCUSTO, t.SEQUSUARIOALTERACAO, t.NOME, t.DTAINCLUSAO,
               t.DTAALTERACAO, t.CODGRUPO
        FROM CONSINCO.MEGAG_DESP_APROVADORES t
        JOIN CONSINCO.GE_USUARIO u ON t.SEQUSUARIO = u.SEQUSUARIO
        WHERE u.NOME = p_nome;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR APROVADORES - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_APROVADORES;
/

--UPDATE
CREATE OR REPLACE PROCEDURE PRC_UPD_MEGAG_DESP_APROVADORES(
    p_sequsuario           IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_centrocusto          IN  CONSINCO.MEGAG_DESP_APROVADORES.CENTROCUSTO%TYPE,
    p_nome                 IN  CONSINCO.MEGAG_DESP_APROVADORES.NOME%TYPE,
    p_sequusuarioalt       IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    p_dtaalteracao         IN  CONSINCO.MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL,
    p_codgrupo             IN  CONSINCO.MEGAG_DESP_APROVADORES.CODGRUPO%TYPE,
    p_rows_affected        OUT NUMBER,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) AS
BEGIN
    UPDATE CONSINCO.MEGAG_DESP_APROVADORES
       SET CENTROCUSTO         = p_centrocusto,
           SEQUSUARIOALTERACAO = p_sequusuarioalt,
           NOME                = p_nome,
           DTAALTERACAO        = NVL(p_dtaalteracao, SYSDATE),
           CODGRUPO            = p_codgrupo
     WHERE SEQUSUARIO = p_sequsuario;

    p_rows_affected := SQL%ROWCOUNT;
    COMMIT;

    IF p_rows_affected = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Nenhum aprovador encontrado para atualização.';
    ELSE
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Aprovador atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR APROVADORES - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_APROVADORES;
/

--DELETE
CREATE OR REPLACE PROCEDURE PRC_DEL_MEGAG_DESP_APROVADORES(
    p_nome    IN  VARCHAR2,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
    v_sequsuario NUMBER;
BEGIN
    SELECT SEQUSUARIO INTO v_sequsuario
    FROM CONSINCO.GE_USUARIO WHERE NOME = p_nome;

    DELETE FROM CONSINCO.MEGAG_DESP_APROVADORES
    WHERE SEQUSUARIO = v_sequsuario;

    COMMIT;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Aprovador removido com sucesso.';
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Usuário não encontrado: ' || p_nome;
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR APROVADORES - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_APROVADORES;
/
