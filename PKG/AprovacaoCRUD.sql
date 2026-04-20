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
BEGIN
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

    COMMIT;

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
