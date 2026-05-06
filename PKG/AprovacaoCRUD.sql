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
    v_codpolitica CONSINCO.MEGAG_DESP.CODPOLITICA%TYPE;
BEGIN
    SELECT CODPOLITICA
      INTO v_codpolitica
      FROM CONSINCO.MEGAG_DESP
     WHERE CODDESPESA = p_coddespesa;

    -- Loop em TODOS os centros de custo envolvidos (Rateio + Principal)
    FOR v_cc IN (
        SELECT DISTINCT CENTROCUSTO
        FROM (
            SELECT CENTROCUSTO
            FROM CONSINCO.MEGAG_DESP_RATEIO 
            WHERE CODDESPESA = p_coddespesa
            UNION
            SELECT d.CENTROCUSTO
            FROM CONSINCO.MEGAG_DESP d
            WHERE d.CODDESPESA = p_coddespesa
              AND NOT EXISTS (SELECT 1 FROM CONSINCO.MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = d.CODDESPESA)
        )
    ) LOOP
        -- Busca aprovadores configurados para CADA centro de custo
        FOR r_aprov IN (
            SELECT DISTINCT sequsuario, nivel_aprovacao
            FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO
            WHERE CENTROCUSTO = v_cc.CENTROCUSTO
              AND CODPOLITICA = v_codpolitica
            ORDER BY NIVEL_APROVACAO
        ) LOOP
            -- Idempotência: Evita duplicar registros se a proc for chamada de novo
            INSERT INTO CONSINCO.MEGAG_DESP_APROVACAO(
                CODDESPESA, CENTROCUSTO, USUARIOAPROVADOR,
                STATUS, DTAACAO, OBSERVACAO, NIVEL_APROVACAO
            )
            SELECT
                p_coddespesa, v_cc.CENTROCUSTO, r_aprov.sequsuario,
                'LANCADO', SYSDATE, NULL, r_aprov.nivel_aprovacao
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1
                FROM CONSINCO.MEGAG_DESP_APROVACAO ex
                WHERE ex.CODDESPESA       = p_coddespesa
                  AND ex.CENTROCUSTO      = v_cc.CENTROCUSTO
                  AND ex.USUARIOAPROVADOR = r_aprov.sequsuario
                  AND ex.NIVEL_APROVACAO  = r_aprov.nivel_aprovacao
            );
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
        WHERE desp.STATUS NOT IN ('REJEITADO')
          AND desp.USUARIOSOLICITANTE <> p_sequsuario
          AND a.SEQUSUARIO = p_sequsuario
          AND NOT EXISTS (
              SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO apr
              WHERE apr.CODDESPESA      = cc.CODDESPESA
                AND apr.CENTROCUSTO     = cc.CENTROCUSTO
                AND apr.USUARIOAPROVADOR = p_sequsuario
                AND apr.STATUS           IN ('APROVADO', 'REJEITADO'))
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
    v_solicitante    CONSINCO.MEGAG_DESP.USUARIOSOLICITANTE%TYPE;
    v_status_atual   CONSINCO.MEGAG_DESP.STATUS%TYPE;
    v_codpolitica    CONSINCO.MEGAG_DESP.CODPOLITICA%TYPE;
    v_processou_algo NUMBER := 0;
    v_nivel_atual    CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.NIVEL_APROVACAO%TYPE;
    v_codgrupo       CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO.CODGRUPO%TYPE;
    v_usuario_valido NUMBER;
    v_count          NUMBER;

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
    SELECT USUARIOSOLICITANTE, STATUS, CODPOLITICA
    INTO v_solicitante, v_status_atual, v_codpolitica
    FROM CONSINCO.MEGAG_DESP WHERE CODDESPESA = p_coddespesa FOR UPDATE;

    IF v_status_atual IN ('APROVADO','REJEITADO') THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Despesa ja finalizada com status: ' || v_status_atual;
        RETURN;
    END IF;

    IF v_solicitante = p_sequsuario THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Solicitante nao pode aprovar a propria despesa.';
        RETURN;
    END IF;

    FOR v_cc IN c_cc_pendentes LOOP
        SELECT MIN(pg.NIVEL_APROVACAO)
        INTO v_nivel_atual
        FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pg
        WHERE pg.CODPOLITICA = v_codpolitica
          AND pg.CENTROCUSTO = v_cc.CENTROCUSTO
          AND NOT EXISTS (
              SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO a
              WHERE a.CODDESPESA      = p_coddespesa
                AND a.CENTROCUSTO     = pg.CENTROCUSTO
                AND a.NIVEL_APROVACAO = pg.NIVEL_APROVACAO
                AND a.STATUS          = 'APROVADO'
                AND EXISTS (
                    SELECT 1 FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p2
                    WHERE p2.CODGRUPO    = pg.CODGRUPO
                      AND p2.SEQUSUARIO  = a.USUARIOAPROVADOR
                      AND p2.CODPOLITICA = pg.CODPOLITICA
                      AND p2.CENTROCUSTO = pg.CENTROCUSTO)
          );

        IF v_nivel_atual IS NULL THEN
            CONTINUE;
        END IF;

        SELECT pg.CODGRUPO
        INTO v_codgrupo
        FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pg
        WHERE pg.CODPOLITICA     = v_codpolitica
          AND pg.CENTROCUSTO     = v_cc.CENTROCUSTO
          AND pg.NIVEL_APROVACAO = v_nivel_atual
          AND ROWNUM = 1;

        SELECT COUNT(*) INTO v_usuario_valido
        FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p
        WHERE p.SEQUSUARIO  = p_sequsuario
          AND p.CENTROCUSTO = v_cc.CENTROCUSTO
          AND p.CODGRUPO    = v_codgrupo
          AND p.CODPOLITICA = v_codpolitica;

        IF v_usuario_valido > 0 THEN
            SELECT COUNT(*) INTO v_count
            FROM CONSINCO.MEGAG_DESP_APROVACAO apr
            WHERE apr.CODDESPESA      = p_coddespesa
              AND apr.CENTROCUSTO     = v_cc.CENTROCUSTO
              AND apr.NIVEL_APROVACAO = v_nivel_atual
              AND apr.STATUS          = 'APROVADO'
              AND EXISTS (
                  SELECT 1 FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p2
                  WHERE p2.SEQUSUARIO  = apr.USUARIOAPROVADOR
                    AND p2.CODGRUPO    = v_codgrupo
                    AND p2.CODPOLITICA = v_codpolitica
                    AND p2.CENTROCUSTO = v_cc.CENTROCUSTO
              );

            IF v_count > 0 THEN
                CONTINUE;
            END IF;

            UPDATE CONSINCO.MEGAG_DESP_APROVACAO
               SET STATUS           = p_status,
                   DTAACAO          = SYSDATE,
                   OBSERVACAO       = p_observacao
             WHERE CODDESPESA      = p_coddespesa
               AND CENTROCUSTO     = v_cc.CENTROCUSTO
               AND NIVEL_APROVACAO = v_nivel_atual
               AND USUARIOAPROVADOR = p_sequsuario
               AND STATUS          = 'LANCADO';

            IF SQL%ROWCOUNT = 0 THEN
                SELECT COUNT(*) INTO v_count
                FROM CONSINCO.MEGAG_DESP_APROVACAO apr
                WHERE apr.CODDESPESA      = p_coddespesa
                  AND apr.CENTROCUSTO     = v_cc.CENTROCUSTO
                  AND apr.NIVEL_APROVACAO = v_nivel_atual
                  AND apr.STATUS          IN ('APROVADO', 'REJEITADO')
                  AND EXISTS (
                      SELECT 1 FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p2
                      WHERE p2.SEQUSUARIO  = apr.USUARIOAPROVADOR
                        AND p2.CODGRUPO    = v_codgrupo
                        AND p2.CODPOLITICA = v_codpolitica
                        AND p2.CENTROCUSTO = v_cc.CENTROCUSTO
                  );

                IF v_count = 0 THEN
                    INSERT INTO CONSINCO.MEGAG_DESP_APROVACAO(
                        CODDESPESA, CENTROCUSTO, USUARIOAPROVADOR,
                        STATUS, DTAACAO, OBSERVACAO, NIVEL_APROVACAO
                    ) VALUES (
                        p_coddespesa, v_cc.CENTROCUSTO, p_sequsuario,
                        p_status, SYSDATE, p_observacao, v_nivel_atual
                    );
                ELSE
                    CONTINUE;
                END IF;
            END IF;

            v_processou_algo := 1;

            IF p_status = 'REJEITADO' THEN
                UPDATE CONSINCO.MEGAG_DESP
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
    END LOOP;

    IF v_processou_algo = 0 THEN
        s_sfx     := 'warning';
        s_ico     := 'warning';
        s_tiporet := 'A';
        s_msg     := 'Sem permissao ou fora da ordem de aprovacao.';
        RETURN;
    END IF;

    DECLARE
        v_restante NUMBER;
    BEGIN
        WITH CC_DESPESA AS (
            SELECT CENTROCUSTO FROM CONSINCO.MEGAG_DESP_RATEIO WHERE CODDESPESA = p_coddespesa
            UNION
            SELECT CENTROCUSTO FROM CONSINCO.MEGAG_DESP
            WHERE CODDESPESA = p_coddespesa
              AND NOT EXISTS (SELECT 1 FROM CONSINCO.MEGAG_DESP_RATEIO r WHERE r.CODDESPESA = p_coddespesa)
        )
        SELECT COUNT(*) INTO v_restante
        FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO pg
        WHERE pg.CODPOLITICA = v_codpolitica
          AND pg.CENTROCUSTO IN (SELECT CENTROCUSTO FROM CC_DESPESA)
          AND NOT EXISTS (
              SELECT 1 FROM CONSINCO.MEGAG_DESP_APROVACAO a
              WHERE a.CODDESPESA      = p_coddespesa
                AND a.CENTROCUSTO     = pg.CENTROCUSTO
                AND a.NIVEL_APROVACAO = pg.NIVEL_APROVACAO
                AND a.STATUS          = 'APROVADO'
                AND EXISTS (
                    SELECT 1 FROM CONSINCO.MEGAG_DESP_POLIT_CENTRO_CUSTO p2
                    WHERE p2.CODGRUPO    = pg.CODGRUPO
                      AND p2.SEQUSUARIO  = a.USUARIOAPROVADOR
                      AND p2.CODPOLITICA = pg.CODPOLITICA
                      AND p2.CENTROCUSTO = pg.CENTROCUSTO));

        IF v_restante = 0 THEN
            UPDATE CONSINCO.MEGAG_DESP
               SET STATUS = 'APROVADO', PAGO = p_pago, DTAALTERACAO = SYSDATE
             WHERE CODDESPESA = p_coddespesa;
            s_sfx     := 'success';
            s_ico     := 'success';
            s_tiporet := 'S';
            s_msg     := 'Despesa aprovada com sucesso.';
        ELSE
            IF v_status_atual = 'LANCADO' THEN
                UPDATE CONSINCO.MEGAG_DESP
                   SET STATUS = 'APROVACAO', DTAALTERACAO = SYSDATE
                 WHERE CODDESPESA = p_coddespesa;
            END IF;
            s_sfx     := 'success';
            s_ico     := 'success';
            s_tiporet := 'S';
            s_msg     := 'Aprovacao registrada. Aguardando proximos niveis.';
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
