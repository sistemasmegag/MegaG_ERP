CREATE OR REPLACE PACKAGE BODY CONSINCO.PKG_MEGAG_DESP_CADASTRO IS

/* ==================================================
   FILE: AprovadoresCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_APROVADORES(
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

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_APROVADORES(
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

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_APROVADORES(
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

--DELETE
PROCEDURE PRC_DEL_MEGAG_DESP_APROVADORES(
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

/* ==================================================
   FILE: ClonaUsuarioCRUD.sql
================================================== */
/* ==================================================
   FILE: ClonaUsuarioCRUD.sql

- Clonar usuario(permissoes)
- adicionar na tela de aprovadores
- vincular aos cc de custos(do usuario clonado)
- vincular aos grupos dos usuarios clonados
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP_CLONA_APROVADOR(
    p_sequsuario_origem  IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_sequsuario_destino IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_sequusuarioalt     IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIOALTERACAO%TYPE,
    s_sfx                OUT VARCHAR2,
    s_ico                OUT VARCHAR2,
    s_tiporet            OUT VARCHAR2,
    s_msg                OUT VARCHAR2
) AS
    v_nome_destino       CONSINCO.GE_USUARIO.NOME%TYPE;
    v_count_origem       NUMBER;
    v_count_aprov_insert NUMBER := 0;
    v_count_polit_insert NUMBER := 0;
BEGIN
    IF p_sequsuario_origem = p_sequsuario_destino THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Usuario origem e destino devem ser diferentes.';
        RETURN;
    END IF;

    SELECT COUNT(*)
      INTO v_count_origem
      FROM CONSINCO.MEGAG_DESP_APROVADORES
     WHERE SEQUSUARIO = p_sequsuario_origem;

    IF v_count_origem = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Usuario origem nao possui vinculos de aprovador para clonar.';
        RETURN;
    END IF;

    SELECT NOME
      INTO v_nome_destino
      FROM CONSINCO.GE_USUARIO
     WHERE SEQUSUARIO = p_sequsuario_destino;

    INSERT INTO CONSINCO.MEGAG_DESP_APROVADORES (
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
    FROM CONSINCO.MEGAG_DESP_APROVADORES orig
    WHERE orig.SEQUSUARIO = p_sequsuario_origem
      AND NOT EXISTS (
          SELECT 1
            FROM CONSINCO.MEGAG_DESP_APROVADORES dest
           WHERE dest.SEQUSUARIO  = p_sequsuario_destino
             AND dest.CENTROCUSTO = orig.CENTROCUSTO
             AND (dest.CODGRUPO = orig.CODGRUPO OR (dest.CODGRUPO IS NULL AND orig.CODGRUPO IS NULL))
      );

    v_count_aprov_insert := SQL%ROWCOUNT;

    INSERT INTO CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO (
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
    FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO orig
    WHERE orig.SEQUSUARIO = p_sequsuario_origem
      AND NOT EXISTS (
          SELECT 1
            FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO dest
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
    s_msg     := 'Usuario clonado com sucesso. '
                 || v_count_aprov_insert || ' vinculo(s) de aprovador e '
                 || v_count_polit_insert || ' vinculo(s) de politica copiado(s).';

EXCEPTION
    WHEN NO_DATA_FOUND THEN
        ROLLBACK;
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'CLONAR APROVADOR - Usuario destino nao encontrado: '
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
/* ==================================================
   FILE: DespesaCRUD.sql
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP(
    p_USUARIOSOLICITANTE   IN  CONSINCO.MEGAG_DESP.USUARIOSOLICITANTE%TYPE,
    p_CODTIPODESPESA       IN  CONSINCO.MEGAG_DESP.CODTIPODESPESA%TYPE,
    p_PAGO                 IN  CONSINCO.MEGAG_DESP.PAGO%TYPE              DEFAULT 'N',
    p_VLRRATDESPESA        IN  CONSINCO.MEGAG_DESP.VLRRATDESPESA%TYPE,
    p_FORNECEDOR           IN  CONSINCO.MEGAG_DESP.FORNECEDOR%TYPE        DEFAULT NULL,
    p_NOMEARQUIVO          IN  CONSINCO.MEGAG_DESP.NOMEARQUIVO%TYPE       DEFAULT NULL,
    p_OBSERVACAO           IN  CONSINCO.MEGAG_DESP.OBSERVACAO%TYPE        DEFAULT NULL,
    p_CENTROCUSTO          IN  CONSINCO.MEGAG_DESP.CENTROCUSTO%TYPE,
    p_STATUS               IN  CONSINCO.MEGAG_DESP.STATUS%TYPE            DEFAULT 'LANCADO',
    p_DESCRICAOCENTROCUSTO IN  CONSINCO.MEGAG_DESP.DESCRICAOCENTROCUSTO%TYPE DEFAULT NULL,
    p_CODPOLITICA          IN  CONSINCO.MEGAG_DESP.CODPOLITICA%TYPE       DEFAULT NULL,
    p_DTAVENCIMENTO        IN  CONSINCO.MEGAG_DESP.DTAVENCIMENTO%TYPE     DEFAULT NULL,
    p_DTADESPESA           IN  CONSINCO.MEGAG_DESP.DTADESPESA%TYPE        DEFAULT NULL,
    p_CODDESPESA_OUT       OUT CONSINCO.MEGAG_DESP.CODDESPESA%TYPE,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
    v_desc_tipo       VARCHAR2(200);
    v_count_aprovador NUMBER;
BEGIN
    IF p_CODPOLITICA IS NULL THEN
        RAISE_APPLICATION_ERROR(-20004, 'Politica de aprovacao nao informada.');
    END IF;

    -- Busca descrição da categoria
    SELECT DESCRICAO INTO v_desc_tipo FROM CONSINCO.MEGAG_DESP_TIPO WHERE CODTIPODESPESA = p_CODTIPODESPESA;

    -- Validação: Verifica se existe pelo menos 1 aprovador configurado para o CC principal
    SELECT COUNT(*) INTO v_count_aprovador
    FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p
    JOIN CONSINCO.MEGAG_DESP_APROVADORES a
      ON a.CODGRUPO = p.CODGRUPO
     AND a.CENTROCUSTO = p.CENTROCUSTO
     AND a.SEQUSUARIO = p.SEQUSUARIO
    WHERE p.CODPOLITICA = p_CODPOLITICA
      AND TO_CHAR(p.CENTROCUSTO) = TO_CHAR(p_CENTROCUSTO);

    IF v_count_aprovador = 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'Centro de Custo ' || p_CENTROCUSTO || ' não possui aprovadores configurados.');
    END IF;

    INSERT INTO CONSINCO.MEGAG_DESP(
        USUARIOSOLICITANTE, CODTIPODESPESA, DESCRICAO, PAGO,
        VLRRATDESPESA, FORNECEDOR, NOMEARQUIVO, OBSERVACAO,
        CENTROCUSTO, STATUS, DESCRICAOCENTROCUSTO, CODPOLITICA, 
		DTAVENCIMENTO, DTADESPESA, DTAINCLUSAO
    ) VALUES (
        p_USUARIOSOLICITANTE, p_CODTIPODESPESA, v_desc_tipo, p_PAGO,
        p_VLRRATDESPESA, p_FORNECEDOR, p_NOMEARQUIVO, p_OBSERVACAO,
		p_CENTROCUSTO, p_STATUS, p_DESCRICAOCENTROCUSTO, p_CODPOLITICA,
		p_DTAVENCIMENTO, p_DTADESPESA, SYSDATE
    )
    RETURNING CODDESPESA INTO p_CODDESPESA_OUT;

    -- REMOVIDO: Chamada interna de PRC_INS_MEGAG_DESP_APROVACAO
    -- Motivo: Evitar duplicidade de trilhas quando há rateio. 
    -- A API chamará este motor após inserir os rateios e dar COMMIT.

    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S';
    s_msg := 'Despesa #' || p_CODDESPESA_OUT || ' criada.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP;

PROCEDURE PRC_LIST_MEGAG_DESP(
    p_USUARIOSOLICITANTE   IN  CONSINCO.GE_USUARIO.SEQUSUARIO%TYPE,
    p_STATUS               IN  CONSINCO.MEGAG_DESP.STATUS%TYPE            DEFAULT NULL,
    p_RESULT               OUT SYS_REFCURSOR,
    s_sfx                  OUT VARCHAR2,
    s_ico                  OUT VARCHAR2,
    s_tiporet              OUT VARCHAR2,
    s_msg                  OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM CONSINCO.MEGAG_DESP
         WHERE USUARIOSOLICITANTE = p_USUARIOSOLICITANTE
           AND (p_STATUS IS NULL OR STATUS = p_STATUS)
         ORDER BY DTAINCLUSAO DESC;

    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP;

/* ==================================================
   FILE: TipoDespesaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_TIPO(
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

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_TIPO(
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

/* ==================================================
   FILE: CentroCustoDespesaCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_CENTRO_CUSTO(
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
        FROM CONSINCO.ABA_CENTRORESULTADO
        ORDER BY DESCRICAO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_CENTRO_CUSTO;

--UPDATE
PROCEDURE PRC_UPD_MEGAG_DESP_CENTRO_CUSTO(
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

/* ==================================================
   FILE: AprovacaoCRUD.sql
================================================== */
/* ==================================================
   FILE: AprovacaoCRUD.sql
   Ajustado para: 
   1. Suporte a Esquema CONSINCO
   2. Níveis de aprovação independentes por CC
   3. Suporte a Rateio completo
================================================== */

PROCEDURE PRC_INS_MEGAG_DESP_APROVACAO(
    p_coddespesa IN  CONSINCO.MEGAG_DESP.CODDESPESA%TYPE,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) IS
    v_existe NUMBER;
    v_codpolitica CONSINCO.MEGAG_DESP.CODPOLITICA%TYPE;
BEGIN
    SELECT CODPOLITICA
      INTO v_codpolitica
      FROM CONSINCO.MEGAG_DESP
     WHERE CODDESPESA = p_coddespesa;

    -- Loop em TODOS os centros de custo envolvidos (Rateio + Principal)
    FOR r_cc IN (
        SELECT DISTINCT TRIM(TO_CHAR(CENTROCUSTO)) as CENTROCUSTO 
        FROM CONSINCO.MEGAG_DESP_RATEIO 
        WHERE CODDESPESA = p_coddespesa
        UNION
        SELECT DISTINCT TRIM(TO_CHAR(d.CENTROCUSTO)) as CENTROCUSTO 
        FROM CONSINCO.MEGAG_DESP d
        WHERE d.CODDESPESA = p_coddespesa
          AND NOT EXISTS (SELECT 1 FROM CONSINCO.MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
    ) LOOP
        -- Busca aprovadores configurados para CADA centro de custo
        FOR r_aprov IN (
            SELECT SEQUSUARIO, NIVEL_APROVACAO
            FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO
            WHERE TRIM(TO_CHAR(CENTROCUSTO)) = TRIM(TO_CHAR(r_cc.CENTROCUSTO))
              AND CODPOLITICA = v_codpolitica
            ORDER BY NIVEL_APROVACAO
        ) LOOP
            -- Idempotência: Evita duplicar registros se a proc for chamada de novo
            SELECT COUNT(*) INTO v_existe
            FROM CONSINCO.MEGAG_DESP_APROVACAO
            WHERE CODDESPESA       = p_coddespesa
              AND TRIM(TO_CHAR(CENTROCUSTO)) = TRIM(TO_CHAR(r_cc.CENTROCUSTO))
              AND USUARIOAPROVADOR = r_aprov.SEQUSUARIO
              AND NIVEL_APROVACAO  = r_aprov.NIVEL_APROVACAO;

            IF v_existe = 0 THEN
                INSERT INTO CONSINCO.MEGAG_DESP_APROVACAO(
                    CODDESPESA, CENTROCUSTO, USUARIOAPROVADOR,
                    STATUS, DTAACAO, OBSERVACAO, NIVEL_APROVACAO
                ) VALUES (
                    p_coddespesa, r_cc.CENTROCUSTO, r_aprov.SEQUSUARIO,
                    'LANCADO', SYSDATE, NULL, r_aprov.NIVEL_APROVACAO
                );
            END IF;
        END LOOP;
    END LOOP;

    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := 'Aprovações geradas com sucesso.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx     := 'error';
        s_ico     := 'danger';
        s_tiporet := 'E';
        s_msg     := 'INSERIR APROVACAO - Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_APROVACAO;

PROCEDURE PRC_LIST_MEGAG_DESP_APROVACAO(
    p_sequsuario IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_cursor     OUT SYS_REFCURSOR,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) AS
    v_existe NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_existe
    FROM CONSINCO.MEGAG_DESP_APROVADORES WHERE SEQUSUARIO = p_sequsuario;

    IF v_existe > 0 THEN
        OPEN p_cursor FOR
        WITH CC_DESPESA AS (
            SELECT CODDESPESA, CENTROCUSTO FROM CONSINCO.MEGAG_DESP_RATEIO
            UNION
            SELECT d.CODDESPESA, d.CENTROCUSTO FROM CONSINCO.MEGAG_DESP d
            WHERE NOT EXISTS (
                SELECT 1 FROM CONSINCO.MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
        )
        SELECT DISTINCT desp.*
        FROM CONSINCO.MEGAG_DESP desp
        JOIN CC_DESPESA cc ON cc.CODDESPESA = desp.CODDESPESA
        JOIN CONSINCO.MEGAG_DESP_APROVADORES a ON a.CENTROCUSTO = cc.CENTROCUSTO
        JOIN CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p
            ON p.CODGRUPO = a.CODGRUPO AND p.CENTROCUSTO = a.CENTROCUSTO
        WHERE desp.STATUS NOT IN ('REJEITADO', 'APROVADO')
          AND desp.USUARIOSOLICITANTE <> p_sequsuario
          AND a.SEQUSUARIO = p_sequsuario
          AND NOT EXISTS (
              SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO apr
              WHERE apr.CODDESPESA      = cc.CODDESPESA
                AND apr.CENTROCUSTO     = cc.CENTROCUSTO
                AND apr.USUARIOAPROVADOR = p_sequsuario
                AND apr.STATUS           = 'APROVADO')
          AND NOT EXISTS (
              -- Valida se é a vez do usuário (não há níveis inferiores pendentes para este CC)
              SELECT 1 FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p_prev
              WHERE p_prev.CENTROCUSTO = cc.CENTROCUSTO
                AND p_prev.NIVEL_APROVACAO < p.NIVEL_APROVACAO
                AND NOT EXISTS (
                    SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO a_prev
                    WHERE a_prev.CODDESPESA    = cc.CODDESPESA
                      AND a_prev.CENTROCUSTO   = cc.CENTROCUSTO
                      AND a_prev.NIVEL_APROVACAO = p_prev.NIVEL_APROVACAO
                      AND a_prev.STATUS        = 'APROVADO'
                )
          )
        ORDER BY desp.DTAINCLUSAO DESC;
    ELSE
        OPEN p_cursor FOR SELECT * FROM CONSINCO.MEGAG_DESP WHERE 1 = 0;
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

PROCEDURE PRC_UPD_MEGAG_DESP_APROVACAO(
    p_coddespesa  IN  CONSINCO.MEGAG_DESP.CODDESPESA%TYPE,
    p_sequsuario  IN  CONSINCO.MEGAG_DESP_APROVADORES.SEQUSUARIO%TYPE,
    p_status      IN  CONSINCO.MEGAG_DESP_APROVACAO.STATUS%TYPE,
    p_pago        IN  CONSINCO.MEGAG_DESP.PAGO%TYPE,
    p_observacao  IN  CONSINCO.MEGAG_DESP.OBSERVACAO%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
    v_status_atual   CONSINCO.MEGAG_DESP.STATUS%TYPE;
    v_processou_algo NUMBER := 0;
    v_nivel_atual    NUMBER;
    v_codgrupo       NUMBER;

    CURSOR c_cc_pendentes IS
        WITH CC_DESPESA AS (
            SELECT CODDESPESA, CENTROCUSTO FROM CONSINCO.MEGAG_DESP_RATEIO
            WHERE CODDESPESA = p_coddespesa
            UNION
            SELECT d.CODDESPESA, d.CENTROCUSTO FROM CONSINCO.MEGAG_DESP d
            WHERE d.CODDESPESA = p_coddespesa
              AND NOT EXISTS (
                  SELECT 1 FROM CONSINCO.MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
        )
        SELECT DISTINCT cc.CENTROCUSTO FROM CC_DESPESA cc;
BEGIN
    SELECT STATUS INTO v_status_atual
    FROM CONSINCO.MEGAG_DESP WHERE CODDESPESA = p_coddespesa FOR UPDATE;

    IF v_status_atual IN ('APROVADO','REJEITADO') THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Despesa já finalizada com status: ' || v_status_atual;
        RETURN;
    END IF;

    FOR v_cc IN c_cc_pendentes LOOP
        DECLARE
            CURSOR c_niveis IS
                SELECT pg.NIVEL_APROVACAO, pg.CODGRUPO
                FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pg
                WHERE pg.SEQUSUARIO  = p_sequsuario
                  AND TO_CHAR(pg.CENTROCUSTO) = TO_CHAR(v_cc.CENTROCUSTO)
                ORDER BY pg.NIVEL_APROVACAO;
            v_aprovado NUMBER;
        BEGIN
            FOR r IN c_niveis LOOP
                -- Verifica se níveis inferiores do mesmo CC estão ok
                DECLARE
                    v_pendente_inf NUMBER;
                BEGIN
                    SELECT COUNT(*) INTO v_pendente_inf
                    FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p_inf
                    WHERE TO_CHAR(p_inf.CENTROCUSTO) = TO_CHAR(v_cc.CENTROCUSTO)
                      AND p_inf.NIVEL_APROVACAO < r.NIVEL_APROVACAO
                      AND NOT EXISTS (
                          SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO a_inf
                          WHERE a_inf.CODDESPESA = p_coddespesa
                            AND TO_CHAR(a_inf.CENTROCUSTO) = TO_CHAR(v_cc.CENTROCUSTO)
                            AND a_inf.NIVEL_APROVACAO = p_inf.NIVEL_APROVACAO
                            AND a_inf.STATUS = 'APROVADO'
                      );

                    IF v_pendente_inf = 0 THEN
                        -- Verifica se o próprio usuário já aprovou este nível
                        SELECT COUNT(*) INTO v_aprovado
                        FROM CONSINCO.MEGAG_DESP_APROVACAO a
                        WHERE a.CODDESPESA       = p_coddespesa
                          AND TO_CHAR(a.CENTROCUSTO) = TO_CHAR(v_cc.CENTROCUSTO)
                          AND a.USUARIOAPROVADOR  = p_sequsuario
                          AND a.NIVEL_APROVACAO  = r.NIVEL_APROVACAO
                          AND a.STATUS           = 'APROVADO';

                        IF v_aprovado = 0 THEN
                            -- Registra Aprovação
                             UPDATE CONSINCO.MEGAG_DESP_APROVACAO
                                SET STATUS = p_status, DTAACAO = SYSDATE, OBSERVACAO = p_observacao
                              WHERE CODDESPESA = p_coddespesa
                                AND TRIM(TO_CHAR(CENTROCUSTO)) = TRIM(TO_CHAR(v_cc.CENTROCUSTO))
                                AND USUARIOAPROVADOR = p_sequsuario
                                AND STATUS = 'LANCADO';

                            IF SQL%ROWCOUNT = 0 THEN
                                INSERT INTO CONSINCO.MEGAG_DESP_APROVACAO(
                                    CODDESPESA, CENTROCUSTO, USUARIOAPROVADOR,
                                    STATUS, DTAACAO, OBSERVACAO, NIVEL_APROVACAO
                                ) VALUES (
                                    p_coddespesa, v_cc.CENTROCUSTO, p_sequsuario,
                                    p_status, SYSDATE, p_observacao, r.NIVEL_APROVACAO
                                );
                            END IF;
                            v_processou_algo := 1;
                            EXIT;
                        END IF;
                    END IF;
                END;
            END LOOP;
        END;
    END LOOP;

    IF v_processou_algo = 1 THEN
        -- Verifica se TODOS os níveis de TODOS os CCs foram aprovados
        DECLARE
            v_restante NUMBER;
        BEGIN
            SELECT COUNT(*) INTO v_restante
            FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pg
            WHERE pg.CENTROCUSTO IN (
                SELECT CENTROCUSTO FROM CONSINCO.MEGAG_DESP_RATEIO WHERE CODDESPESA = p_coddespesa
                UNION
                SELECT CENTROCUSTO FROM CONSINCO.MEGAG_DESP WHERE CODDESPESA = p_coddespesa
            )
            AND NOT EXISTS (
                SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO a
                WHERE a.CODDESPESA      = p_coddespesa
                  AND TRIM(TO_CHAR(a.CENTROCUSTO)) = TRIM(TO_CHAR(pg.CENTROCUSTO))
                  AND a.NIVEL_APROVACAO = pg.NIVEL_APROVACAO
                  AND a.STATUS          = 'APROVADO'
            );

            IF v_restante = 0 AND p_status = 'APROVADO' THEN
                UPDATE CONSINCO.MEGAG_DESP SET STATUS = 'APROVADO', PAGO = p_pago, DTAALTERACAO = SYSDATE WHERE CODDESPESA = p_coddespesa;
                s_msg := 'Despesa 100% aprovada.';
            ELSIF p_status = 'REJEITADO' THEN
                UPDATE CONSINCO.MEGAG_DESP SET STATUS = 'REJEITADO', DTAALTERACAO = SYSDATE WHERE CODDESPESA = p_coddespesa;
                s_msg := 'Despesa rejeitada.';
            ELSE
                UPDATE CONSINCO.MEGAG_DESP SET STATUS = 'APROVACAO', DTAALTERACAO = SYSDATE WHERE CODDESPESA = p_coddespesa;
                s_msg := 'Aprovação registrada. Aguardando próximos.';
            END IF;
            
            COMMIT;
            s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S';
        END;
    ELSE
        s_sfx := 'warning'; s_ico := 'warning'; s_tiporet := 'A'; s_msg := 'Sem permissão ou fora de ordem.';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_UPD_MEGAG_DESP_APROVACAO;

/* ==================================================
   FILE: ArquivoCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_ARQUIVO(
    p_CODDESPESA     IN  CONSINCO.MEGAG_DESP_ARQUIVO.CODDESPESA%TYPE,
    p_NOMEARQUIVO    IN  CONSINCO.MEGAG_DESP_ARQUIVO.NOMEARQUIVO%TYPE,
    p_TIPOARQUIVO    IN  CONSINCO.MEGAG_DESP_ARQUIVO.TIPOARQUIVO%TYPE DEFAULT NULL,
    p_CODARQUIVO_OUT OUT CONSINCO.MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) IS
BEGIN
    INSERT INTO CONSINCO.MEGAG_DESP_ARQUIVO(CODDESPESA, NOMEARQUIVO, TIPOARQUIVO)
    VALUES(p_CODDESPESA, p_NOMEARQUIVO, p_TIPOARQUIVO)
    RETURNING CODARQUIVO INTO p_CODARQUIVO_OUT;

    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Arquivo inserido.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_ARQUIVO;

--SELECT
PROCEDURE PRC_SEL_MEGAG_DESP_ARQUIVO(
    p_CODARQUIVO IN  CONSINCO.MEGAG_DESP_ARQUIVO.CODARQUIVO%TYPE DEFAULT NULL,
    p_CODDESPESA IN  CONSINCO.MEGAG_DESP_ARQUIVO.CODDESPESA%TYPE DEFAULT NULL,
    p_RESULT     OUT SYS_REFCURSOR,
    s_sfx        OUT VARCHAR2,
    s_ico        OUT VARCHAR2,
    s_tiporet    OUT VARCHAR2,
    s_msg        OUT VARCHAR2
) IS
BEGIN
    OPEN p_RESULT FOR
        SELECT * FROM CONSINCO.MEGAG_DESP_ARQUIVO
        WHERE (p_CODARQUIVO IS NULL OR CODARQUIVO = p_CODARQUIVO)
          AND (p_CODDESPESA IS NULL OR CODDESPESA = p_CODDESPESA);
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_SEL_MEGAG_DESP_ARQUIVO;

/* ==================================================
   FILE: PolíticaCRUD.sql
================================================== */
/* ==================================================
   FILE: PoliticaCCCRUD.sql (Mapeado como PoliticaCRUD.sql)
================================================== */

PROCEDURE PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica      IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE,
    p_codgrupo         IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE,
    p_sequsuario       IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.SEQUSUARIO%TYPE,
    p_centrocusto      IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CENTROCUSTO%TYPE,
    p_nivel_aprovacao  IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE,
    p_descricao        IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.DESCRICAO%TYPE DEFAULT NULL,
    p_codpolit_cc      OUT CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx              OUT VARCHAR2,
    s_ico              OUT VARCHAR2,
    s_tiporet          OUT VARCHAR2,
    s_msg              OUT VARCHAR2
) AS
    v_pol NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_pol
    FROM CONSINCO.MEGAG_DESP_POLITICA WHERE CODPOLITICA = p_codpolitica;

    IF v_pol = 0 THEN
        s_sfx := 'warning'; s_ico := 'warning'; s_tiporet := 'A'; s_msg := 'CODPOLITICA ' || p_codpolitica || ' não existe.';
        RETURN;
    END IF;

    INSERT INTO CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO(
        CODPOLITICA, CODGRUPO, SEQUSUARIO, CENTROCUSTO,
        NIVEL_APROVACAO, DESCRICAO, DTAINCLUSAO
    ) VALUES (
        p_codpolitica, p_codgrupo, p_sequsuario, p_centrocusto,
        p_nivel_aprovacao, p_descricao, SYSDATE
    )
    RETURNING CODPOLIT_CC INTO p_codpolit_cc;

    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Vínculo incluído.';
EXCEPTION
    WHEN DUP_VAL_ON_INDEX THEN
        s_sfx := 'warning'; s_ico := 'warning'; s_tiporet := 'A'; s_msg := 'Vínculo duplicado.';
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_POLIT_CENTRO_CUSTO;

PROCEDURE PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolitica IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLITICA%TYPE DEFAULT NULL,
    p_cursor      OUT SYS_REFCURSOR,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR
        SELECT pc.*, pol.DESCRICAO AS DESCRICAO_POLITICA, g.NOMEGRUPO
        FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pc
        JOIN CONSINCO.MEGAG_DESP_POLITICA pol ON pol.CODPOLITICA = pc.CODPOLITICA
        JOIN CONSINCO.MEGAG_DESP_GRUPO g ON g.CODGRUPO = pc.CODGRUPO
        WHERE (p_codpolitica IS NULL OR pc.CODPOLITICA = p_codpolitica)
        ORDER BY pc.CODPOLITICA, pc.NIVEL_APROVACAO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_POLIT_CENTRO_CUSTO;

PROCEDURE PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO(
    p_codpolit_cc IN  CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODPOLIT_CC%TYPE,
    s_sfx         OUT VARCHAR2,
    s_ico         OUT VARCHAR2,
    s_tiporet     OUT VARCHAR2,
    s_msg         OUT VARCHAR2
) AS
BEGIN
    DELETE FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO WHERE CODPOLIT_CC = p_codpolit_cc;
    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Vínculo excluído.';
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_DEL_MEGAG_DESP_POLIT_CENTRO_CUSTO;

/* ==================================================
   FILE: GrupoCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_GRUPO(
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

--LIST
PROCEDURE PRC_LIST_MEGAG_DESP_GRUPO(
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

/* ==================================================
   FILE: RateioCRUD.sql
================================================== */
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

/* ==================================================
   FILE: FornecedorCRUD.sql
================================================== */
PROCEDURE PRC_INS_MEGAG_DESP_FORNECEDOR(
    p_NOMERAZAO      IN CONSINCO.GE_PESSOA.NOMERAZAO%TYPE,
    p_FANTASIA       IN CONSINCO.GE_PESSOA.FANTASIA%TYPE,
    p_PALAVRACHAVE   IN CONSINCO.GE_PESSOA.PALAVRACHAVE%TYPE,
    p_CEP            IN CONSINCO.GE_PESSOA.CEP%TYPE,
    p_FISICAJURIDICA IN CONSINCO.GE_PESSOA.FISICAJURIDICA%TYPE,
    p_SEXO           IN CONSINCO.GE_PESSOA.SEXO%TYPE,
    p_NROCGCCPF      IN CONSINCO.GE_PESSOA.NROCGCCPF%TYPE,
    p_DIGCGCCPF      IN CONSINCO.GE_PESSOA.DIGCGCCPF%TYPE,
    p_CIDADE         IN CONSINCO.GE_PESSOA.CIDADE%TYPE,
    p_UF             IN CONSINCO.GE_PESSOA.UF%TYPE,
    p_BAIRRO         IN CONSINCO.GE_PESSOA.BAIRRO%TYPE,
    p_LOGRADOURO     IN CONSINCO.GE_PESSOA.LOGRADOURO%TYPE,
    p_NROLOGRADOURO  IN CONSINCO.GE_PESSOA.NROLOGRADOURO%TYPE,
    p_FONEDDD1       IN CONSINCO.GE_PESSOA.FONEDDD1%TYPE,
    p_FONENRO1       IN CONSINCO.GE_PESSOA.FONENRO1%TYPE,
    p_EMAIL          IN CONSINCO.GE_PESSOA.EMAIL%TYPE,
    p_INSCRICAORG    IN CONSINCO.GE_PESSOA.INSCRICAORG%TYPE,
    p_DTAATIVACAO    IN CONSINCO.GE_PESSOA.DTAATIVACAO%TYPE,
    s_sfx            OUT VARCHAR2,
    s_ico            OUT VARCHAR2,
    s_tiporet        OUT VARCHAR2,
    s_msg            OUT VARCHAR2
) AS
    v_seqpessoa NUMBER;
BEGIN
    INSERT INTO CONSINCO.GE_PESSOA (NOMERAZAO, FANTASIA, NROCGCCPF, DIGCGCCPF)
    VALUES (p_NOMERAZAO, p_FANTASIA, p_NROCGCCPF, p_DIGCGCCPF)
    RETURNING SEQPESSOA INTO v_seqpessoa;
    
    COMMIT;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'Fornecedor cadastrado: ' || v_seqpessoa;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        s_sfx := 'error'; s_ico := 'danger'; s_tiporet := 'E'; s_msg := 'Erro: ' || SQLERRM;
END PRC_INS_MEGAG_DESP_FORNECEDOR;

PROCEDURE PRC_LIST_MEGAG_DESP_FORNECEDOR(
    p_cursor  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) AS
BEGIN
    OPEN p_cursor FOR SELECT * FROM CONSINCO.GE_PESSOA ORDER BY NOMERAZAO;
    s_sfx := 'success'; s_ico := 'success'; s_tiporet := 'S'; s_msg := 'OK';
END PRC_LIST_MEGAG_DESP_FORNECEDOR;

/* ==================================================
   FILE: PoliticaMaeCRUD.sql
================================================== */
--INSERT
PROCEDURE PRC_INS_MEGAG_DESP_POLITICA(
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

--SELECT
PROCEDURE PRC_LIST_MEGAG_DESP_POLITICA(
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

END PKG_MEGAG_DESP_CADASTRO;
