CREATE OR REPLACE PACKAGE BODY CONSINCO.PKG_MEGAG_DESP_CADASTRO IS

/* ==================================================
   FILE: AprovadoresCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_APROVADORES(
    p_sequsuario           IN  MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_centrocusto          IN  MEGAG_DESP_APROVADORES.CENTROCUSTO%TYPE,
    p_nome                 IN  MEGAG_DESP_APROVADORES.NOME%TYPE,
    p_sequusuarioalt       IN  MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    p_dtaalteracao         IN  MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL,
    p_codgrupo             IN  MEGAG_DESP_APROVADORES.CODGRUPO%TYPE,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) AS
BEGIN
    INSERT INTO MEGAG_DESP_APROVADORES(
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

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_APROVADORES(
    p_nome    IN  GE_USUARIO.NOME%TYPE,
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
        FROM MEGAG_DESP_APROVADORES t
        JOIN GE_USUARIO u ON t.SEQUSUARIO = u.SEQUSUARIO
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

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_APROVADORES(
    p_sequsuario           IN  MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_centrocusto          IN  MEGAG_DESP_APROVADORES.CENTROCUSTO%TYPE,
    p_nome                 IN  MEGAG_DESP_APROVADORES.NOME%TYPE,
    p_sequusuarioalt       IN  MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    p_dtaalteracao         IN  MEGAG_DESP_APROVADORES.DTAALTERACAO%TYPE DEFAULT NULL,
    p_codgrupo             IN  MEGAG_DESP_APROVADORES.CODGRUPO%TYPE,
    p_rows_affected        OUT NUMBER,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) AS
BEGIN
    UPDATE MEGAG_DESP_APROVADORES
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

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_APROVADORES(
    p_nome    IN  VARCHAR2,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
    v_sequsuario MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE;
BEGIN
    SELECT SEQUSUARIO INTO v_sequsuario
    FROM GE_USUARIO WHERE NOME = p_nome;

    DELETE FROM MEGAG_DESP_APROVADORES
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

/* ==================================================
   FILE: ClonaUsuarioCRUD.sql
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP_CLONA_APROVADOR(
    p_sequsuario_origem  IN  MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_sequsuario_destino IN  MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_sequusuarioalt     IN  MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) AS
    v_nome_destino       GE_USUARIO.NOME%TYPE;
    v_count_origem       NUMBER;
    v_count_aprov_insert NUMBER := 0;
    v_count_polit_insert NUMBER := 0;
BEGIN
    IF p_sequsuario_origem = p_sequsuario_destino THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Usuário origem e destino devem ser diferentes.';
        RETURN;
    END IF;

    SELECT COUNT(*)
      INTO v_count_origem
      FROM MEGAG_DESP_APROVADORES
     WHERE SEQUSUARIO = p_sequsuario_origem;

    IF v_count_origem = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Usuário origem não possui vínculos de aprovador para clonar.';
        RETURN;
    END IF;

    SELECT NOME
      INTO v_nome_destino
      FROM GE_USUARIO
     WHERE SEQUSUARIO = p_sequsuario_destino;

    INSERT INTO MEGAG_DESP_APROVADORES (
        SEQUSUARIO,
        CENTROCUSTO,
        SEQUSUARIOALTERACAO,
        NOME,
        DTAINCLUSAO,
        DTAALTERACAO,
        CODGRUPO
    )
    SELECT
        p_sequsuario_destino,
        orig.CENTROCUSTO,
        p_sequusuarioalt,
        v_nome_destino,
        SYSDATE,
        NULL,
        orig.CODGRUPO
    FROM MEGAG_DESP_APROVADORES orig
    WHERE orig.SEQUSUARIO = p_sequsuario_origem
      AND NOT EXISTS (
          SELECT 1
            FROM MEGAG_DESP_APROVADORES dest
           WHERE dest.SEQUSUARIO  = p_sequsuario_destino
             AND dest.CENTROCUSTO = orig.CENTROCUSTO
             AND (dest.CODGRUPO = orig.CODGRUPO OR (dest.CODGRUPO IS NULL AND orig.CODGRUPO IS NULL))
      );

    v_count_aprov_insert := SQL%ROWCOUNT;

    INSERT INTO MEGAG_DESP_POLIT_CENTRO_CUSTO (
        CODPOLITICA,
        CODGRUPO,
        SEQUSUARIO,
        CENTROCUSTO,
        NIVEL_APROVACAO,
        DESCRICAO,
        DTAINCLUSAO
    )
    SELECT
        orig.CODPOLITICA,
        orig.CODGRUPO,
        p_sequsuario_destino,
        orig.CENTROCUSTO,
        orig.NIVEL_APROVACAO,
        orig.DESCRICAO,
        SYSDATE
    FROM MEGAG_DESP_POLIT_CENTRO_CUSTO orig
    WHERE orig.SEQUSUARIO = p_sequsuario_origem
      AND NOT EXISTS (
          SELECT 1
            FROM MEGAG_DESP_POLIT_CENTRO_CUSTO dest
           WHERE dest.SEQUSUARIO  = p_sequsuario_destino
             AND dest.CODPOLITICA = orig.CODPOLITICA
             AND dest.CENTROCUSTO = orig.CENTROCUSTO
             AND (dest.CODGRUPO = orig.CODGRUPO OR (dest.CODGRUPO IS NULL AND orig.CODGRUPO IS NULL))
      );

    v_count_polit_insert := SQL%ROWCOUNT;

    COMMIT;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Usuário clonado com sucesso. '
                 || v_count_aprov_insert || ' vínculo(s) de aprovador e '
                 || v_count_polit_insert || ' vínculo(s) de política copiado(s).';

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        ROLLBACK;
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'CLONAR APROVADOR - Usuário destino não encontrado: '
                     || p_sequsuario_destino;
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'CLONAR APROVADOR - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_CLONA_APROVADOR;

/* ==================================================
   FILE: DespesaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP(
    p_USUARIOSOLICITANTE   IN  MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA       IN  MEGAG_DESP.CODTIPODESPESA%TYPE,
    p_PAGO                 IN  MEGAG_DESP.PAGO%TYPE              DEFAULT 'N',
    p_VLRRATDESPESA        IN  MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR           IN  MEGAG_DESP.FORNECEDOR%TYPE        DEFAULT NULL,
    p_NOMEARQUIVO          IN  MEGAG_DESP.NOMEARQUIVO%TYPE       DEFAULT NULL,
    p_OBSERVACAO           IN  MEGAG_DESP.OBSERVACAO%TYPE        DEFAULT NULL,
    p_CENTROCUSTO          IN  MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS               IN  MEGAG_DESP.STATUS%TYPE            DEFAULT 'LANCADO',
    p_DESCRICAOCENTROCUSTO IN  MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE DEFAULT NULL,
    p_CODPOLITICA          IN  MEGAG_DESP.CODPOLITICA%TYPE       DEFAULT NULL,
    p_DTAVENCIMENTO        IN  MEGAG_DESP.DTAVENCIMENTO%TYPE     DEFAULT NULL,
    p_DTADESPESA           IN  MEGAG_DESP.DTADESPESA%TYPE        DEFAULT NULL,
    p_CODDESPESA_OUT       OUT MEGAG_DESP.CODDESPESA%TYPE,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
    p_DESCRICAO       MEGAG_DESP.DESCRICAO%TYPE;
    v_count_aprovador NUMBER;
    v_sfx_ap          VARCHAR2(20);
    v_ico_ap          VARCHAR2(20);
    v_tiporet_ap      VARCHAR2(1);
    v_msg_ap          VARCHAR2(500);
BEGIN
    IF p_CODPOLITICA IS NULL THEN
        RAISE_APPLICATION_ERROR(-20004, 'Política de aprovação não informada.');
    END IF;

    SELECT DESCRICAO INTO p_DESCRICAO
    FROM MEGAG_DESP_TIPO
    WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    SELECT COUNT(*) INTO v_count_aprovador
    FROM MEGAG_DESP_POLIT_CENTRO_CUSTO p
    JOIN MEGAG_DESP_APROVADORES a
        ON a.CODGRUPO    = p.CODGRUPO
       AND a.CENTROCUSTO = p.CENTROCUSTO
    WHERE p.CODPOLITICA = p_CODPOLITICA
      AND p.CENTROCUSTO = p_CENTROCUSTO;

    IF v_count_aprovador = 0 THEN
        RAISE_APPLICATION_ERROR(-20003,
            'Não há aprovadores para a política e centro de custo informado.');
    END IF;

    INSERT INTO MEGAG_DESP(
        USUARIOSOLICITANTE, CODTIPODESPESA, DESCRICAO, PAGO,
        VLRRATDESPESA, FORNECEDOR, NOMEARQUIVO, OBSERVACAO,
        CENTROCUSTO, STATUS,
        DESCRICAOCENTROCUSTO, CODPOLITICA, DTAVENCIMENTO, DTADESPESA
    ) VALUES (
        p_USUARIOSOLICITANTE, p_CODTIPODESPESA, p_DESCRICAO, p_PAGO,
        p_VLRRATDESPESA, p_FORNECEDOR, p_NOMEARQUIVO, p_OBSERVACAO,
        p_CENTROCUSTO, p_STATUS,
        p_DESCRICAOCENTROCUSTO, p_CODPOLITICA, p_DTAVENCIMENTO, p_DTADESPESA
    )
    RETURNING CODDESPESA INTO p_CODDESPESA_OUT;

    PRC_INS_MEGAG_DESP_APROVACAO(
        p_coddespesa => p_CODDESPESA_OUT,
        s_sfx        => v_sfx_ap,
        s_ico        => v_ico_ap,
        s_tiporet    => v_tiporet_ap,
        s_msg        => v_msg_ap
    );

    IF v_tiporet_ap = 'E' THEN
        RAISE_APPLICATION_ERROR(-20005,
            'Erro ao criar aprovações: ' || v_msg_ap);
    END IF;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Despesa inserida com sucesso. Código: ' || p_CODDESPESA_OUT;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'PRC_INS_MEGAG_DESP - Tipo de despesa não encontrado.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR DESPESA - Erro ao inserir despesa: ' || SQLERRM;
END PRC_INS_MEGAG_DESP;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP(
    p_CODDESPESA           IN  MEGAG_DESP.CODDESPESA%TYPE,
    p_USUARIOSOLICITANTE   IN  MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_DESCRICAO            IN  MEGAG_DESP.DESCRICAO%TYPE         DEFAULT NULL,
    p_STATUS               IN  MEGAG_DESP.STATUS%TYPE            DEFAULT NULL,
    p_RESULT               OUT SYS_REFCURSOR,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM MEGAG_DESP
         WHERE (p_CODDESPESA IS NULL OR CODDESPESA = p_CODDESPESA)
           AND USUARIOSOLICITANTE = p_USUARIOSOLICITANTE
           AND (p_DESCRICAO IS NULL OR UPPER(DESCRICAO) LIKE '%' || UPPER(p_DESCRICAO) || '%')
           AND (p_STATUS IS NULL OR STATUS = p_STATUS)
         ORDER BY DTAINCLUSAO DESC;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR DESPESA - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP(
    p_CODDESPESA           IN  MEGAG_DESP.CODDESPESA%TYPE,
    p_USUARIOSOLICITANTE   IN  MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA       IN  MEGAG_DESP.CODTIPODESPESA%TYPE,
    p_DESCRICAO            IN  MEGAG_DESP.DESCRICAO%TYPE,
    p_VLRRATDESPESA        IN  MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR           IN  MEGAG_DESP.FORNECEDOR%TYPE,
    p_NOMEARQUIVO          IN  MEGAG_DESP.NOMEARQUIVO%TYPE,
    p_OBSERVACAO           IN  MEGAG_DESP.OBSERVACAO%TYPE,
    p_CENTROCUSTO          IN  MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS               IN  MEGAG_DESP.STATUS%TYPE,
    p_DESCRICAOCENTROCUSTO IN  MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE,
    p_DTAVENCIMENTO        IN  MEGAG_DESP.DTAVENCIMENTO%TYPE     DEFAULT NULL,
    p_DTADESPESA           IN  MEGAG_DESP.DTADESPESA%TYPE        DEFAULT NULL,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
BEGIN
    UPDATE MEGAG_DESP
       SET USUARIOSOLICITANTE  = p_USUARIOSOLICITANTE,
           CODTIPODESPESA      = p_CODTIPODESPESA,
           DESCRICAO           = p_DESCRICAO,
           VLRRATDESPESA       = p_VLRRATDESPESA,
           FORNECEDOR          = p_FORNECEDOR,
           NOMEARQUIVO         = p_NOMEARQUIVO,
           OBSERVACAO          = p_OBSERVACAO,
           CENTROCUSTO         = p_CENTROCUSTO,
           STATUS              = p_STATUS,
           DESCRICAOCENTROCUSTO= p_DESCRICAOCENTROCUSTO,
           DTAALTERACAO        = SYSDATE,
           DTAVENCIMENTO       = p_DTAVENCIMENTO,
           DTADESPESA          = p_DTADESPESA
     WHERE CODDESPESA = p_CODDESPESA
       AND STATUS = 'LANCADO';

    IF SQL%NOTFOUND THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'ATUALIZAR DESPESA - Atualização negada: registro já passou pela aprovação.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Despesa atualizada com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR DESPESA - Erro ao atualizar despesa: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP(
    p_CODDESPESA         IN  MEGAG_DESP.CODDESPESA%TYPE,
    p_USUARIOSOLICITANTE IN  MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) IS
    v_coddespesa MEGAG_DESP.CODDESPESA%TYPE;
BEGIN
    SELECT CODDESPESA INTO v_coddespesa
    FROM MEGAG_DESP
    WHERE CODDESPESA          = p_CODDESPESA
      AND USUARIOSOLICITANTE  = p_USUARIOSOLICITANTE;

    DELETE FROM MEGAG_DESP WHERE CODDESPESA = p_CODDESPESA;
    COMMIT;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Despesa removida com sucesso.';
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'DELETAR DESPESA - Despesa não encontrada ou não pertence ao usuário.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR DESPESA - Erro ao remover despesa: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP;

/* ==================================================
   FILE: TipoDespesaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_TIPO(
    p_DESCRICAO IN  MEGAG_DESP_TIPO.DESCRICAO%TYPE,
    s_sfx       OUT VARCHAR2,
    s_ico       OUT VARCHAR2,
    s_tiporet   OUT VARCHAR2,
    s_msg       OUT VARCHAR2
) IS
BEGIN
    INSERT INTO MEGAG_DESP_TIPO(DESCRICAO) VALUES(p_DESCRICAO);

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Tipo de despesa inserido com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR TIPO DESPESA - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_TIPO;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN  MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE,
    p_DESCRICAO      IN  MEGAG_DESP_TIPO.DESCRICAO%TYPE,
    p_RESULT         OUT SYS_REFCURSOR,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM MEGAG_DESP_TIPO
         WHERE (p_CODTIPODESPESA IS NULL OR CODTIPODESPESA = p_CODTIPODESPESA)
           AND (p_DESCRICAO IS NULL OR DESCRICAO LIKE '%' || p_DESCRICAO || '%');

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR TIPO DESPESA - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_TIPO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN  MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE,
    p_DESCRICAO      IN  MEGAG_DESP_TIPO.DESCRICAO%TYPE,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) IS
BEGIN
    UPDATE MEGAG_DESP_TIPO
       SET DESCRICAO   = p_DESCRICAO,
           DTAALTERACAO = SYSDATE
     WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Tipo de despesa não encontrado.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Tipo de despesa atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR TIPO DESPESA - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_TIPO;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_TIPO(
    p_CODTIPODESPESA IN  MEGAG_DESP_TIPO.CODTIPODESPESA%TYPE,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) IS
BEGIN
    DELETE FROM MEGAG_DESP_TIPO WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Tipo de despesa não encontrado.';
    ELSE
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Tipo de despesa removido com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR TIPO DESPESA - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_TIPO;

/* ==================================================
   FILE: CentroCustoDespesaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_CENTRO_CUSTO(
    p_centrocusto        IN  ABA_CENTRORESULTADO.CENTRORESULTADO%TYPE,
    p_coddespesa         IN  MEGAG_DESP.CODDESPESA%TYPE,
    p_usuariosolicitante IN  MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) AS
    v_descricao ABA_CENTRORESULTADO.DESCRICAO%TYPE;
    v_count     NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM MEGAG_DESP
    WHERE CODDESPESA = p_coddespesa AND USUARIOSOLICITANTE = p_usuariosolicitante;

    IF v_count = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'A despesa não existe ou não pertence ao usuário.';
        RETURN;
    END IF;

    SELECT DESCRICAO
    INTO v_descricao
    FROM ABA_CENTRORESULTADO
    WHERE CENTRORESULTADO = p_centrocusto;

    INSERT INTO MEGAG_DESP(CENTROCUSTO, DESCRICAOCENTROCUSTO)
    VALUES(p_centrocusto, v_descricao);

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Centro de custo inserido com sucesso.';
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'INSERIR CENTRO DE CUSTO DESPESA - Centro de custo não encontrado.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR CENTRO DE CUSTO DESPESA - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_CENTRO_CUSTO;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_CENTRO_CUSTO(
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT CENTRORESULTADO, DESCRICAO
        FROM ABA_CENTRORESULTADO
        ORDER BY DESCRICAO;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR CENTRO DE CUSTO DESPESA - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_CENTRO_CUSTO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_CENTRO_CUSTO(
    p_centrocusto_atual        IN  MEGAG_DESP.CENTROCUSTO%TYPE,
    p_centrocusto_novo         IN  MEGAG_DESP.CENTROCUSTO%TYPE,
    p_descricao_nova           IN  MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE,
    p_coddespesa               IN  MEGAG_DESP.CODDESPESA%TYPE,
    p_usuariosolicitante       IN  MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    s_sfx                      OUT VARCHAR2,
    s_ico                      OUT VARCHAR2,
    s_tiporet                  OUT VARCHAR2,
    s_msg                      OUT VARCHAR2
) AS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM MEGAG_DESP
    WHERE CENTROCUSTO        = p_centrocusto_atual
      AND CODDESPESA         = p_coddespesa
      AND USUARIOSOLICITANTE = p_usuariosolicitante;

    IF v_count = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Registro não encontrado ou não pertence ao usuário.';
        RETURN;
    END IF;

    UPDATE MEGAG_DESP
       SET CENTROCUSTO          = p_centrocusto_novo,
           DESCRICAOCENTROCUSTO = p_descricao_nova
     WHERE CENTROCUSTO        = p_centrocusto_atual
       AND CODDESPESA         = p_coddespesa
       AND USUARIOSOLICITANTE = p_usuariosolicitante;

    COMMIT;
    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Centro de custo atualizado com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR CENTRO DE CUSTO DESPESA - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_CENTRO_CUSTO;

/* ==================================================
   FILE: AprovacaoCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_APROVACAO(
    p_coddespesa IN  MEGAG_DESP.CODDESPESA%TYPE,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) IS
    v_codpolitica MEGAG_DESP.CODPOLITICA%TYPE;
    v_qtd_aprovadores NUMBER;
BEGIN
    SELECT CODPOLITICA
      INTO v_codpolitica
      FROM MEGAG_DESP
     WHERE CODDESPESA = p_coddespesa;

    FOR v_cc IN (
        SELECT CENTROCUSTO
          FROM MEGAG_DESP_RATEIO
         WHERE CODDESPESA = p_coddespesa
        UNION
        SELECT d.CENTROCUSTO
          FROM MEGAG_DESP d
         WHERE d.CODDESPESA = p_coddespesa
           AND NOT EXISTS (
               SELECT 1
                 FROM MEGAG_DESP_RATEIO r
                WHERE r.CODDESPESA = d.CODDESPESA
           )
    ) LOOP
        SELECT COUNT(*)
          INTO v_qtd_aprovadores
          FROM MEGAG_DESP_POLIT_CENTRO_CUSTO
         WHERE CENTROCUSTO = v_cc.CENTROCUSTO
           AND CODPOLITICA = v_codpolitica;

        IF v_qtd_aprovadores = 0 THEN
            RAISE_APPLICATION_ERROR(-20014,
                'Nao ha aprovadores configurados para o centro de custo ' || v_cc.CENTROCUSTO || '.');
        END IF;

        FOR r_aprov IN (
            SELECT sequsuario, nivel_aprovacao
              FROM MEGAG_DESP_POLIT_CENTRO_CUSTO
             WHERE CENTROCUSTO = v_cc.CENTROCUSTO
               AND CODPOLITICA = v_codpolitica
             ORDER BY NIVEL_APROVACAO
        ) LOOP
            INSERT INTO MEGAG_DESP_APROVACAO(
                CODDESPESA, CENTROCUSTO, USUARIOAPROVADOR,
                STATUS, DTAACAO, OBSERVACAO, NIVEL_APROVACAO
            )
            SELECT p_coddespesa, v_cc.CENTROCUSTO, r_aprov.sequsuario,
                   'LANCADO', SYSDATE, NULL, r_aprov.nivel_aprovacao
              FROM DUAL
             WHERE NOT EXISTS (
                   SELECT 1
                     FROM MEGAG_DESP_APROVACAO apr
                    WHERE apr.CODDESPESA       = p_coddespesa
                      AND apr.CENTROCUSTO      = v_cc.CENTROCUSTO
                      AND apr.USUARIOAPROVADOR = r_aprov.sequsuario
                      AND apr.NIVEL_APROVACAO  = r_aprov.nivel_aprovacao
             );
        END LOOP;
    END LOOP;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Aprovações criadas com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR APROVACAO - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_APROVACAO;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_APROVACAO(
    p_sequsuario IN  MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_cursor     OUT SYS_REFCURSOR,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) AS
    v_existe NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_existe
    FROM MEGAG_DESP_APROVADORES WHERE SEQUSUARIO = p_sequsuario;

    IF v_existe > 0 THEN
        OPEN p_cursor FOR
        WITH CC_DESPESA AS (
            SELECT CODDESPESA, CENTROCUSTO FROM MEGAG_DESP_RATEIO
            UNION
            SELECT d.CODDESPESA, d.CENTROCUSTO FROM MEGAG_DESP d
            WHERE NOT EXISTS (
                SELECT 1 FROM MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
        )
        SELECT DISTINCT desp.*
        FROM MEGAG_DESP desp
        JOIN CC_DESPESA cc ON cc.CODDESPESA = desp.CODDESPESA
        JOIN MEGAG_DESP_APROVADORES a ON a.CENTROCUSTO = cc.CENTROCUSTO
        JOIN MEGAG_DESP_POLIT_CENTRO_CUSTO p
            ON p.CODGRUPO = a.CODGRUPO AND p.CENTROCUSTO = a.CENTROCUSTO
        WHERE desp.STATUS NOT IN ('REJEITADO')
          AND desp.USUARIOSOLICITANTE <> p_sequsuario
          AND a.SEQUSUARIO = p_sequsuario
          AND NOT EXISTS (
              SELECT 1 FROM MEGAG_DESP_APROVACAO apr
              WHERE apr.CODDESPESA      = cc.CODDESPESA
                AND apr.CENTROCUSTO     = cc.CENTROCUSTO
                AND apr.USUARIOAPROVADOR = p_sequsuario)
          AND p.NIVEL_APROVACAO <= (
              SELECT NVL(MAX(apr_nivel.NIVEL_APROVACAO), 0) + 1
              FROM MEGAG_DESP_APROVACAO apr_nivel
              WHERE apr_nivel.CODDESPESA  = cc.CODDESPESA
                AND apr_nivel.CENTROCUSTO = cc.CENTROCUSTO
                AND apr_nivel.STATUS      = 'APROVADO')
        ORDER BY desp.DTAINCLUSAO DESC;
    ELSE
        OPEN p_cursor FOR SELECT * FROM MEGAG_DESP WHERE 1 = 0;
    END IF;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR APROVACAO - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_APROVACAO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_APROVACAO(
    p_coddespesa  IN  MEGAG_DESP.CODDESPESA%TYPE,
    p_sequsuario  IN  MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_status      IN  MEGAG_DESP_APROVACAO.STATUS%TYPE,
    p_pago        IN  MEGAG_DESP.PAGO%TYPE,
    p_observacao  IN  MEGAG_DESP.OBSERVACAO%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
    v_solicitante    MEGAG_DESP.USUARIOSOLICITANTE%TYPE;
    v_status_atual   MEGAG_DESP.STATUS%TYPE;
    v_codpolitica    MEGAG_DESP.CODPOLITICA%TYPE;
    v_processou_algo NUMBER := 0;
    v_nivel_atual    MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE;
    v_codgrupo       MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE;
    v_usuario_valido NUMBER;

    CURSOR c_cc_pendentes IS
        WITH CC_DESPESA AS (
            SELECT CODDESPESA, CENTROCUSTO FROM MEGAG_DESP_RATEIO
            WHERE CODDESPESA = p_coddespesa
            UNION
            SELECT d.CODDESPESA, d.CENTROCUSTO FROM MEGAG_DESP d
            WHERE d.CODDESPESA = p_coddespesa
              AND NOT EXISTS (
                  SELECT 1 FROM MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
        )
        SELECT DISTINCT cc.CENTROCUSTO FROM CC_DESPESA cc;
BEGIN
    SELECT USUARIOSOLICITANTE, STATUS, CODPOLITICA
    INTO v_solicitante, v_status_atual, v_codpolitica
    FROM MEGAG_DESP WHERE CODDESPESA = p_coddespesa FOR UPDATE;

    IF v_status_atual IN ('APROVADO','REJEITADO') THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Despesa já finalizada com status: ' || v_status_atual;
        RETURN;
    END IF;

    IF v_solicitante = p_sequsuario THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Solicitante não pode aprovar a própria despesa.';
        RETURN;
    END IF;

    FOR v_cc IN c_cc_pendentes LOOP
        DECLARE
            CURSOR c_niveis IS
                SELECT pg.NIVEL_APROVACAO, pg.CODGRUPO
                FROM MEGAG_DESP_POLIT_CENTRO_CUSTO pg
                WHERE pg.CODPOLITICA = v_codpolitica
                  AND pg.SEQUSUARIO  = p_sequsuario
                  AND pg.CENTROCUSTO = v_cc.CENTROCUSTO
                ORDER BY pg.NIVEL_APROVACAO;
            v_aprovado NUMBER;
        BEGIN
            v_nivel_atual := NULL;
            v_codgrupo    := NULL;
            FOR r IN c_niveis LOOP
                SELECT COUNT(*) INTO v_aprovado
                FROM MEGAG_DESP_APROVACAO a
                WHERE a.CODDESPESA       = p_coddespesa
                  AND a.CENTROCUSTO      = v_cc.CENTROCUSTO
                  AND a.USUARIOAPROVADOR  = p_sequsuario
                  AND a.NIVEL_APROVACAO  = r.NIVEL_APROVACAO;
                IF v_aprovado = 0 THEN
                    v_nivel_atual := r.NIVEL_APROVACAO;
                    v_codgrupo    := r.CODGRUPO;
                    EXIT;
                END IF;
            END LOOP;
        END;

        SELECT COUNT(*) INTO v_usuario_valido
        FROM MEGAG_DESP_POLIT_CENTRO_CUSTO p
        WHERE p.SEQUSUARIO  = p_sequsuario
          AND p.CENTROCUSTO = v_cc.CENTROCUSTO
          AND p.CODGRUPO    = v_codgrupo
          AND p.CODPOLITICA = v_codpolitica;

        IF v_usuario_valido > 0 THEN
            DECLARE
                v_count NUMBER;
            BEGIN
                SELECT COUNT(*) INTO v_count
                FROM MEGAG_DESP_APROVACAO apr
                WHERE apr.CODDESPESA      = p_coddespesa
                  AND apr.CENTROCUSTO     = v_cc.CENTROCUSTO
                  AND apr.USUARIOAPROVADOR = p_sequsuario;

                IF v_count = 0 THEN
                    INSERT INTO MEGAG_DESP_APROVACAO(
                        CODDESPESA, CENTROCUSTO, USUARIOAPROVADOR,
                        STATUS, DTAACAO, OBSERVACAO, NIVEL_APROVACAO
                    ) VALUES (
                        p_coddespesa, v_cc.CENTROCUSTO, p_sequsuario,
                        p_status, SYSDATE, p_observacao, v_nivel_atual
                    );

                    v_processou_algo := 1;

                    IF p_status = 'REJEITADO' THEN
                        UPDATE MEGAG_DESP
                           SET STATUS = 'REJEITADO', DTAALTERACAO = SYSDATE
                         WHERE CODDESPESA = p_coddespesa;
                        COMMIT;
                        s_sfx     := 'success';
                        s_ico     := 'success';
                        s_tiporet := 'S';
                        s_msg     := 'Despesa rejeitada com sucesso.';
                        RETURN;
                    END IF;
                END IF;
            END;
        END IF;
    END LOOP;

    IF v_processou_algo = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Sem permissão ou fora da ordem de aprovação.';
        RETURN;
    END IF;

    DECLARE
        v_restante NUMBER;
    BEGIN
        WITH CC_DESPESA AS (
            SELECT CENTROCUSTO FROM MEGAG_DESP_RATEIO WHERE CODDESPESA = p_coddespesa
            UNION
            SELECT CENTROCUSTO FROM MEGAG_DESP
            WHERE CODDESPESA = p_coddespesa
              AND NOT EXISTS (SELECT 1 FROM MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = p_coddespesa)
        )
        SELECT COUNT(*) INTO v_restante
        FROM MEGAG_DESP_POLIT_CENTRO_CUSTO pg
        WHERE pg.CODPOLITICA = v_codpolitica
          AND pg.CENTROCUSTO IN (SELECT CENTROCUSTO FROM CC_DESPESA)
          AND NOT EXISTS (
              SELECT 1 FROM MEGAG_DESP_APROVACAO a
              WHERE a.CODDESPESA      = p_coddespesa
                AND a.CENTROCUSTO     = pg.CENTROCUSTO
                AND a.NIVEL_APROVACAO = pg.NIVEL_APROVACAO
                AND a.STATUS          = 'APROVADO'
                AND EXISTS (
                    SELECT 1 FROM MEGAG_DESP_POLIT_CENTRO_CUSTO p2
                    WHERE p2.CODGRUPO    = pg.CODGRUPO
                      AND p2.SEQUSUARIO  = a.USUARIOAPROVADOR));

        IF v_restante = 0 THEN
            UPDATE MEGAG_DESP
               SET STATUS = 'APROVADO', PAGO = p_pago, DTAALTERACAO = SYSDATE
             WHERE CODDESPESA = p_coddespesa;
            s_sfx     := 'success';
            s_ico     := 'success';
            s_tiporet := 'S';
            s_msg     := 'Despesa aprovada com sucesso.';
        ELSE
            IF v_status_atual = 'LANCADO' THEN
                UPDATE MEGAG_DESP
                   SET STATUS = 'APROVACAO', DTAALTERACAO = SYSDATE
                 WHERE CODDESPESA = p_coddespesa;
            END IF;
            s_sfx     := 'success';
            s_ico     := 'success';
            s_tiporet := 'S';
            s_msg     := 'Aprovação registrada. Aguardando próximos níveis.';
        END IF;
    END;

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR APROVACAO - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_APROVACAO;

PROCEDURE PRC_REGERAR_MEGAG_DESP_APROVACAO(
    p_coddespesa IN MEGAG_DESP.CODDESPESA%TYPE,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) AS
    v_status MEGAG_DESP.STATUS%TYPE;
BEGIN
    SELECT STATUS
      INTO v_status
      FROM MEGAG_DESP
     WHERE CODDESPESA = p_coddespesa;

    IF v_status <> 'LANCADO' THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Aprovacoes nao regeneradas: despesa ja esta em processo de aprovacao.';
        RETURN;
    END IF;

    DELETE FROM MEGAG_DESP_APROVACAO
     WHERE CODDESPESA = p_coddespesa;

    PRC_INS_MEGAG_DESP_APROVACAO(
        p_coddespesa => p_coddespesa,
        s_sfx        => s_sfx,
        s_ico        => s_ico,
        s_tiporet    => s_tiporet,
        s_msg        => s_msg
    );

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'REGERAR APROVACAO - Despesa nao encontrada.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'REGERAR APROVACAO - Erro: ' || SQLERRM;
END PRC_REGERAR_MEGAG_DESP_APROVACAO;

/* ==================================================
   FILE: ArquivoCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_ARQUIVO(
    p_CODDESPESA     IN  MEGAG_DESP_ARQUIVO.CODDESPESA%TYPE,
    p_NOMEARQUIVO    IN  MEGAG_DESP_ARQUIVO.NOMEARQUIVO%TYPE,
    p_TIPOARQUIVO    IN  MEGAG_DESP_ARQUIVO.TIPOARQUIVO%TYPE DEFAULT NULL,
    p_CODARQUIVO_OUT OUT MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) IS
BEGIN
    INSERT INTO MEGAG_DESP_ARQUIVO(CODDESPESA, NOMEARQUIVO, TIPOARQUIVO)
    VALUES(p_CODDESPESA, p_NOMEARQUIVO, p_TIPOARQUIVO)
    RETURNING CODARQUIVO INTO p_CODARQUIVO_OUT;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Arquivo inserido com sucesso. Código: ' || p_CODARQUIVO_OUT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR ARQUIVO - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_ARQUIVO;

--SELECT
PROCEDURE PRC_SEL_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO IN  MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE DEFAULT NULL,
    p_CODDESPESA IN  MEGAG_DESP_ARQUIVO.CODDESPESA%TYPE DEFAULT NULL,
    p_RESULT     OUT SYS_REFCURSOR,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT CODARQUIVO, CODDESPESA, NOMEARQUIVO,
               TIPOARQUIVO, DTAINCLUSAO, DTAALTERACAO
        FROM MEGAG_DESP_ARQUIVO
        WHERE (p_CODARQUIVO IS NULL OR CODARQUIVO = p_CODARQUIVO)
          AND (p_CODDESPESA IS NULL OR CODDESPESA = p_CODDESPESA);

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR ARQUIVO - Erro: ' || SQLERRM;
END PRC_SEL_MEGAG_DESP_ARQUIVO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO  IN  MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE,
    p_NOMEARQUIVO IN  MEGAG_DESP_ARQUIVO.NOMEARQUIVO%TYPE,
    p_TIPOARQUIVO IN  MEGAG_DESP_ARQUIVO.TIPOARQUIVO%TYPE DEFAULT NULL,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) IS
BEGIN
    UPDATE MEGAG_DESP_ARQUIVO
       SET NOMEARQUIVO  = p_NOMEARQUIVO,
           TIPOARQUIVO  = p_TIPOARQUIVO,
           DTAALTERACAO = SYSDATE
     WHERE CODARQUIVO = p_CODARQUIVO;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Arquivo não encontrado.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Arquivo atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR ARQUIVO - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_ARQUIVO;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO IN  MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) IS
BEGIN
    DELETE FROM MEGAG_DESP_ARQUIVO WHERE CODARQUIVO = p_CODARQUIVO;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Arquivo não encontrado.';
    ELSE
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Arquivo removido com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR ARQUIVO - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_ARQUIVO;

/* ==================================================
   FILE: PolíticaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica      IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE,
    p_codgrupo         IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario       IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto      IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_nivel_aprovacao  IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_descricao        IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE DEFAULT NULL,
    p_codpolit_cc      OUT MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx              OUT VARCHAR2,
    s_ico              OUT VARCHAR2,
    s_tiporet          OUT VARCHAR2,
    s_msg              OUT VARCHAR2
) AS
    v_pol NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_pol
    FROM MEGAG_DESP_POLITICA WHERE CODPOLITICA = p_codpolitica;

    IF v_pol = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'CODPOLITICA ' || p_codpolitica || ' não existe.';
        RETURN;
    END IF;

    INSERT INTO MEGAG_DESP_POLIT_CENTRO_CUSTO(
        CODPOLITICA, CODGRUPO, SEQUSUARIO, CENTROCUSTO,
        NIVEL_APROVACAO, DESCRICAO, DTAINCLUSAO
    ) VALUES (
        p_codpolitica, p_codgrupo, p_sequsuario, p_centrocusto,
        p_nivel_aprovacao, p_descricao, SYSDATE
    )
    RETURNING CODPOLIT_CC INTO p_codpolit_cc;

    COMMIT;
    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Vínculo incluído com sucesso. CODPOLIT_CC = ' || p_codpolit_cc;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'INSERIR POLITICA CENTRO DE CUSTO - Vínculo duplicado.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR POLITICA CENTRO DE CUSTO - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO;

--LIST
PROCEDURE PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE DEFAULT NULL,
    p_cursor      OUT SYS_REFCURSOR,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT pc.CODPOLIT_CC, pc.CODPOLITICA, pol.DESCRICAO AS DESCRICAO_POLITICA,
               pc.CODGRUPO, g.NOMEGRUPO, pc.SEQUSUARIO, pc.CENTROCUSTO,
               pc.NIVEL_APROVACAO, pc.DESCRICAO, pc.DTAINCLUSAO
        FROM MEGAG_DESP_POLIT_CENTRO_CUSTO pc
        JOIN MEGAG_DESP_POLITICA  pol ON pol.CODPOLITICA = pc.CODPOLITICA
        JOIN MEGAG_DESP_GRUPO       g ON g.CODGRUPO      = pc.CODGRUPO
        WHERE (p_codpolitica IS NULL OR pc.CODPOLITICA = p_codpolitica)
        ORDER BY pc.CODPOLITICA, pc.NIVEL_APROVACAO, pc.CODGRUPO;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR POLITICA CENTRO DE CUSTO - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolit_cc      IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    p_codgrupo         IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario       IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto      IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_nivel_aprovacao  IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_descricao        IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE DEFAULT NULL,
    s_sfx              OUT VARCHAR2,
    s_ico              OUT VARCHAR2,
    s_tiporet          OUT VARCHAR2,
    s_msg              OUT VARCHAR2
) AS
BEGIN
    UPDATE MEGAG_DESP_POLIT_CENTRO_CUSTO
       SET CODGRUPO        = p_codgrupo,
           SEQUSUARIO      = p_sequsuario,
           CENTROCUSTO     = p_centrocusto,
           NIVEL_APROVACAO = p_nivel_aprovacao,
           DESCRICAO       = p_descricao,
           DTAALTERACAO    = SYSDATE
     WHERE CODPOLIT_CC = p_codpolit_cc;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Vínculo não encontrado.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Vínculo atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'ATUALIZAR POLITICA CENTRO DE CUSTO - Vínculo duplicado após alteração.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR POLITICA CENTRO DE CUSTO - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_POLIT_CENTRO_CUSTO;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolit_cc IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    DELETE FROM MEGAG_DESP_POLIT_CENTRO_CUSTO WHERE CODPOLIT_CC = p_codpolit_cc;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Vínculo não encontrado.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Vínculo excluído com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR POLITICA CENTRO DE CUSTO - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO;

/* ==================================================
   FILE: GrupoCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_GRUPO(
    p_nomegrupo    IN  MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    p_dtainclusao  IN  MEGAG_DESP_GRUPO.DTAINCLUSAO%TYPE,
    p_dtaalteracao IN  MEGAG_DESP_GRUPO.DTAALTERACAO%TYPE,
    s_sfx          OUT VARCHAR2,
    s_ico          OUT VARCHAR2,
    s_tiporet      OUT VARCHAR2,
    s_msg          OUT VARCHAR2
) AS
BEGIN
    INSERT INTO MEGAG_DESP_GRUPO(NOMEGRUPO, DTAINCLUSAO)
    VALUES(p_nomegrupo, SYSDATE);

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Grupo incluído com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR GRUPO - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_GRUPO;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_GRUPO(
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT CODGRUPO, NOMEGRUPO, DTAINCLUSAO, DTAALTERACAO
        FROM MEGAG_DESP_GRUPO ORDER BY NOMEGRUPO;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR GRUPO - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_GRUPO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_GRUPO(
    p_codgrupo  IN  MEGAG_DESP_GRUPO.CODGRUPO%TYPE,
    p_nomegrupo IN  MEGAG_DESP_GRUPO.NOMEGRUPO%TYPE,
    s_sfx       OUT VARCHAR2,
    s_ico       OUT VARCHAR2,
    s_tiporet   OUT VARCHAR2,
    s_msg       OUT VARCHAR2
) AS
BEGIN
    UPDATE MEGAG_DESP_GRUPO
       SET NOMEGRUPO    = p_nomegrupo,
           DTAALTERACAO = SYSDATE
     WHERE CODGRUPO = p_codgrupo;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Nenhum grupo encontrado para atualização.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Grupo atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR GRUPO - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_GRUPO;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_GRUPO(
    p_codgrupo IN  MEGAG_DESP_GRUPO.CODGRUPO%TYPE,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) AS
BEGIN
    DELETE FROM MEGAG_DESP_GRUPO WHERE CODGRUPO = p_codgrupo;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Nenhum grupo encontrado para exclusão.';
    ELSE
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Grupo excluído com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR GRUPO - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_GRUPO;

/* ==================================================
   FILE: RateioCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_RATEIO(
    p_coddespesa         IN  MEGAG_DESP_RATEIO.CODDESPESA%TYPE,
    p_centrocusto        IN  MEGAG_DESP_RATEIO.CENTROCUSTO%TYPE,
    p_valorrateio        IN  MEGAG_DESP_RATEIO.VALORRATEIO%TYPE,
    p_codrateio          OUT NUMBER,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) AS
BEGIN
    -- Observação: no snippet do DBA, ele usa p_codrateio OUT NUMBER (que é CODRATEIO)
    INSERT INTO MEGAG_DESP_RATEIO(
        CODDESPESA, CENTROCUSTO, VALORRATEIO
    ) VALUES (
        p_coddespesa, p_centrocusto, p_valorrateio
    )
    RETURNING CODRATEIO INTO p_codrateio;

    PRC_REGERAR_MEGAG_DESP_APROVACAO(
        p_coddespesa => p_coddespesa,
        s_sfx        => s_sfx,
        s_ico        => s_ico,
        s_tiporet    => s_tiporet,
        s_msg        => s_msg
    );

    IF s_tiporet <> 'S' THEN
        RAISE_APPLICATION_ERROR(-20011, s_msg);
    END IF;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Rateio inserido com sucesso. Código: ' || p_codrateio;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR RATEIO - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_RATEIO;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_RATEIO(
    p_coddespesa IN  MEGAG_DESP_RATEIO.CODDESPESA%TYPE,
    p_cursor     OUT SYS_REFCURSOR,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT CODRATEIO, CODDESPESA, CENTROCUSTO, VALORRATEIO
        FROM MEGAG_DESP_RATEIO
        WHERE CODDESPESA = p_coddespesa
        ORDER BY CENTROCUSTO;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR RATEIO - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_RATEIO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_RATEIO(
    p_codrateio   IN  MEGAG_DESP_RATEIO.CODRATEIO%TYPE,
    p_valorrateio IN  MEGAG_DESP_RATEIO.VALORRATEIO%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
    v_coddespesa MEGAG_DESP_RATEIO.CODDESPESA%TYPE;
BEGIN
    SELECT CODDESPESA
      INTO v_coddespesa
      FROM MEGAG_DESP_RATEIO
     WHERE CODRATEIO = p_codrateio;

    UPDATE MEGAG_DESP_RATEIO
       SET VALORRATEIO = p_valorrateio
     WHERE CODRATEIO = p_codrateio;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Rateio não encontrado.';
    ELSE
        PRC_REGERAR_MEGAG_DESP_APROVACAO(
            p_coddespesa => v_coddespesa,
            s_sfx        => s_sfx,
            s_ico        => s_ico,
            s_tiporet    => s_tiporet,
            s_msg        => s_msg
        );

        IF s_tiporet <> 'S' THEN
            RAISE_APPLICATION_ERROR(-20012, s_msg);
        END IF;

        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Rateio atualizado com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR RATEIO - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_RATEIO;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_RATEIO(
    p_codrateio IN  MEGAG_DESP_RATEIO.CODRATEIO%TYPE,
    s_sfx       OUT VARCHAR2,
    s_ico       OUT VARCHAR2,
    s_tiporet   OUT VARCHAR2,
    s_msg       OUT VARCHAR2
) AS
    v_coddespesa MEGAG_DESP_RATEIO.CODDESPESA%TYPE;
BEGIN
    SELECT CODDESPESA
      INTO v_coddespesa
      FROM MEGAG_DESP_RATEIO
     WHERE CODRATEIO = p_codrateio;

    DELETE FROM MEGAG_DESP_RATEIO WHERE CODRATEIO = p_codrateio;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Rateio não encontrado.';
    ELSE
        PRC_REGERAR_MEGAG_DESP_APROVACAO(
            p_coddespesa => v_coddespesa,
            s_sfx        => s_sfx,
            s_ico        => s_ico,
            s_tiporet    => s_tiporet,
            s_msg        => s_msg
        );

        IF s_tiporet <> 'S' THEN
            RAISE_APPLICATION_ERROR(-20013, s_msg);
        END IF;

        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Rateio removido com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR RATEIO - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_RATEIO;

/* ==================================================
   FILE: FornecedorCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_FORNECEDOR(
    p_NOMERAZAO      IN GE_PESSOA.NOMERAZAO%TYPE,
    p_FANTASIA       IN GE_PESSOA.FANTASIA%TYPE,
    p_PALAVRACHAVE   IN GE_PESSOA.PALAVRACHAVE%TYPE,
    p_CEP            IN GE_PESSOA.CEP%TYPE,
    p_FISICAJURIDICA IN GE_PESSOA.FISICAJURIDICA%TYPE,
    p_SEXO           IN GE_PESSOA.SEXO%TYPE,
    p_NROCGCCPF      IN GE_PESSOA.NROCGCCPF%TYPE,
    p_DIGCGCCPF      IN GE_PESSOA.DIGCGCCPF%TYPE,
    p_CIDADE         IN GE_PESSOA.CIDADE%TYPE,
    p_UF             IN GE_PESSOA.UF%TYPE,
    p_BAIRRO         IN GE_PESSOA.BAIRRO%TYPE,
    p_LOGRADOURO     IN GE_PESSOA.LOGRADOURO%TYPE,
    p_NROLOGRADOURO  IN GE_PESSOA.NROLOGRADOURO%TYPE,
    p_FONEDDD1       IN GE_PESSOA.FONEDDD1%TYPE,
    p_FONENRO1       IN GE_PESSOA.FONENRO1%TYPE,
    p_EMAIL          IN GE_PESSOA.EMAIL%TYPE,
    p_INSCRICAORG    IN GE_PESSOA.INSCRICAORG%TYPE,
    p_DTAATIVACAO    IN GE_PESSOA.DTAATIVACAO%TYPE,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) AS
    v_seqpessoa  NUMBER;
    v_qtd        NUMBER;
    v_seqedi     NUMBER;
    v_seqcidade  GE_PESSOA.SEQCIDADE%TYPE;
    v_cidade     GE_PESSOA.CIDADE%TYPE;
BEGIN
    -- 1. Verifica duplicidade
    SELECT COUNT(*), NVL(MAX(p.seqpessoa), 0)
      INTO v_qtd, v_seqpessoa
      FROM ge_pessoa p
     WHERE p.nrocgccpf = p_NROCGCCPF
       AND p.digcgccpf = p_DIGCGCCPF;

    IF v_qtd > 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Fornecedor ja cadastrado. SeqPessoa: ' || v_seqpessoa;
        RETURN;
    END IF;

    -- 2. Busca seqcidade e cidade
    SELECT NVL(MAX(c.seqcidade), 0)
      INTO v_seqcidade
      FROM ge_cidade c
     WHERE UPPER(c.cidade) = UPPER(p_CIDADE)
       AND c.uf = UPPER(p_UF);

    SELECT NVL(MAX(c.cidade), p_CIDADE)
      INTO v_cidade
      FROM ge_cidade c
     WHERE UPPER(c.cidade) = UPPER(p_CIDADE)
       AND c.uf = UPPER(p_UF);

    -- 3. Gera SEQEDI
    SELECT VORTICE.VTCS_GE_PESSOA.NEXTVAL
      INTO v_seqedi
      FROM dual;

    -- 4. INSERT na EDI
    INSERT INTO edi_ge_pessoa (
        SEQEDI, VERSAO, STATUS, EDISTATUS,
        NOMERAZAO, FANTASIA, PALAVRACHAVE,
        CEP, FISICAJURIDICA, SEXO,
        ORIGEM, DTAINCLUSAO, USUINCLUSAO,
        TIPOCLIENTE, NROEMPRESA,
        NROCGCCPF, DIGCGCCPF,
        CIDADE, SEQCIDADE, UF,
        BAIRRO, LOGRADOURO, NROLOGRADOURO,
        FONEDDD1, FONENRO1,
        EMAIL, INSCRICAORG,
        DTAATIVACAO
    ) VALUES (
        v_seqedi, 0, 'P', 'L',
        UPPER(SUBSTR(p_NOMERAZAO,    1, 100)),
        UPPER(SUBSTR(p_FANTASIA,     1, 35)),
        UPPER(SUBSTR(p_PALAVRACHAVE, 1, 35)),
        REGEXP_REPLACE(p_CEP, '[^0-9]'),
        UPPER(p_FISICAJURIDICA),
        DECODE(UPPER(p_FISICAJURIDICA), 'J', NULL, SUBSTR(UPPER(p_SEXO), 1, 1)),
        'MEGAG', SYSDATE, 'MEGAGIMPORT',
        0, 2,
        p_NROCGCCPF, p_DIGCGCCPF,
        v_cidade, v_seqcidade, UPPER(p_UF),
        UPPER(SUBSTR(p_BAIRRO,      1, 30)),
        UPPER(SUBSTR(p_LOGRADOURO,  1, 35)),
        p_NROLOGRADOURO,
        REGEXP_REPLACE(p_FONEDDD1, '[^0-9]'),
        REGEXP_REPLACE(p_FONENRO1, '[^0-9]'),
        UPPER(SUBSTR(p_EMAIL,       1, 50)),
        UPPER(SUBSTR(p_INSCRICAORG, 1, 20)),
        p_DTAATIVACAO
    );

    COMMIT;

    -- 5. Processa via EDI
    pkg_edi_importacao.edip_ge_pessoa(v_seqedi, 'S');
    COMMIT;

    -- 6. Verifica se criou na GE_PESSOA
    SELECT NVL(MAX(p.seqpessoa), 0)
      INTO v_seqpessoa
      FROM ge_pessoa p
     WHERE p.nrocgccpf = p_NROCGCCPF
       AND p.digcgccpf = p_DIGCGCCPF;

    IF v_seqpessoa > 0 THEN
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Fornecedor cadastrado com sucesso. SeqPessoa: ' || v_seqpessoa;
    ELSE
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'Registro inserido na EDI mas nao processado.';
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_FORNECEDOR;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_FORNECEDOR(
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT SEQPESSOA, NROCGCCPF, DIGCGCCPF, NOMERAZAO, FANTASIA,
               LOGRADOURO, BAIRRO, CIDADE, UF, CEP, FISICAJURIDICA,
               FONEDDD1, FONENRO1, DTAHORAINCLUSAO, DATAHORAALTERACAO
        FROM GE_PESSOA ORDER BY NOMERAZAO;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR FORNECEDOR - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_FORNECEDOR;

/* ==================================================
   FILE: PoliticaMaeCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_POLITICA(
    p_descricao         IN  MEGAG_DESP_POLITICA.DESCRICAO%TYPE,
    p_codgrupo          IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario        IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto       IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_nivel_aprovacao   IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_descricao_vinculo IN  MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE DEFAULT NULL,
    p_codpolitica       OUT MEGAG_DESP_POLITICA.CODPOLITICA%TYPE,
    p_codpolit_cc       OUT MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
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
    INSERT INTO MEGAG_DESP_POLITICA(DESCRICAO)
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
        RAISE_APPLICATION_ERROR(-20010,
            'Erro ao criar vínculo: ' || v_msg_filho);
    END IF;

    COMMIT;
    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Política e vínculo criados. CODPOLITICA = '
                 || p_codpolitica || ' | CODPOLIT_CC = ' || p_codpolit_cc;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR POLITICA - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_POLITICA;

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_POLITICA(
    p_codpolitica IN  MEGAG_DESP_POLITICA.CODPOLITICA%TYPE DEFAULT NULL,
    p_cursor      OUT SYS_REFCURSOR,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT CODPOLITICA, DESCRICAO, DTAINCLUSAO, DTAALTERACAO
        FROM MEGAG_DESP_POLITICA
        WHERE (p_codpolitica IS NULL OR CODPOLITICA = p_codpolitica)
        ORDER BY CODPOLITICA;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Consulta realizada com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'LISTAR POLITICA - Erro: ' || SQLERRM;
END PRC_LIST_MEGAG_DESP_POLITICA;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_POLITICA(
    p_codpolitica IN  MEGAG_DESP_POLITICA.CODPOLITICA%TYPE,
    p_descricao   IN  MEGAG_DESP_POLITICA.DESCRICAO%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    UPDATE MEGAG_DESP_POLITICA
       SET DESCRICAO    = p_descricao,
           DTAALTERACAO = SYSDATE
     WHERE CODPOLITICA = p_codpolitica;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Política não encontrada.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Política atualizada com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'ATUALIZAR POLITICA - Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_POLITICA;

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_POLITICA(
    p_codpolitica IN  MEGAG_DESP_POLITICA.CODPOLITICA%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
    v_filhos NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_filhos
    FROM MEGAG_DESP_POLIT_CENTRO_CUSTO
    WHERE CODPOLITICA = p_codpolitica;

    IF v_filhos > 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Não é possível excluir: existem ' || v_filhos
                     || ' vínculo(s) cadastrado(s) para esta política.';
        RETURN;
    END IF;

    DELETE FROM MEGAG_DESP_POLITICA WHERE CODPOLITICA = p_codpolitica;

    IF SQL%ROWCOUNT = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Política não encontrada.';
    ELSE
        COMMIT;
        s_sfx     := 'success';
        s_ico     := 'success';
        s_tiporet := 'S';
        s_msg     := 'Política excluída com sucesso.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'DELETAR POLITICA - Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_POLITICA;

END PKG_MEGAG_DESP_CADASTRO;
