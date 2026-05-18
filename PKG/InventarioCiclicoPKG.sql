/* ============================================================
   Package ERP/App - Inventario Ciclico para App Conferencia V3.1
   Execute no schema da aplicacao apos PKG/InventarioCiclicoERP.sql.

   Contrato sugerido:
   - ERP salva planos com PRC_SALVAR_PLANO, PRC_LIMPAR_ITENS_PLANO e PRC_SALVAR_ITEM_PLANO
   - ERP libera/reabre com PRC_ALTERAR_STATUS_PLANO
   - App consome com PRC_APP_LISTAR_LIBERADOS e PRC_APP_BAIXAR_PLANO
   - App devolve aprovacao/rejeicao/solicitacao com PRC_APP_REGISTRAR_LOTE
   ============================================================ */

CREATE OR REPLACE PACKAGE PKG_MEGAG_INV_CICLICO IS

PROCEDURE PRC_PROXIMO_ID_PLANO(
    p_id_plano OUT MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
);

PROCEDURE PRC_SALVAR_PLANO(
    p_id_plano       IN OUT MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_descricao      IN     MEGAG_INV_PLANOS.DESCRICAO%TYPE,
    p_deposito       IN     MEGAG_INV_PLANOS.DEPOSITO%TYPE,
    p_contagem_atual IN     MEGAG_INV_PLANOS.CONTAGEM_ATUAL%TYPE DEFAULT 1,
    p_observacao     IN     MEGAG_INV_PLANOS.OBSERVACAO%TYPE DEFAULT NULL,
    p_usuario        IN     MEGAG_INV_PLANOS.USUARIO_CRIACAO%TYPE DEFAULT 'SISTEMA',
    p_commit         IN     CHAR DEFAULT 'S',
    s_sfx            OUT    VARCHAR2,
    s_ico            OUT    VARCHAR2,
    s_tiporet        OUT    VARCHAR2,
    s_msg            OUT    VARCHAR2
);

PROCEDURE PRC_LIMPAR_ITENS_PLANO(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_commit   IN  CHAR DEFAULT 'S',
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
);

PROCEDURE PRC_SALVAR_ITEM_PLANO(
    p_id_plano        IN  MEGAG_INV_PLANO_ITENS.ID_PLANO%TYPE,
    p_endereco        IN  MEGAG_INV_PLANO_ITENS.ENDERECO%TYPE,
    p_codproduto      IN  MEGAG_INV_PLANO_ITENS.CODPRODUTO%TYPE,
    p_descricao       IN  MEGAG_INV_PLANO_ITENS.DESCRICAO%TYPE,
    p_quantidadebase  IN  MEGAG_INV_PLANO_ITENS.QUANTIDADEBASE%TYPE DEFAULT 0,
    p_unidadebase     IN  MEGAG_INV_PLANO_ITENS.UNIDADEBASE%TYPE DEFAULT NULL,
    p_validadebase    IN  MEGAG_INV_PLANO_ITENS.VALIDADEBASE%TYPE DEFAULT NULL,
    p_valorunitario   IN  MEGAG_INV_PLANO_ITENS.VALORUNITARIO%TYPE DEFAULT 0,
    p_eanunidade      IN  MEGAG_INV_PLANO_ITENS.EANUNIDADE%TYPE DEFAULT NULL,
    p_eancaixa        IN  MEGAG_INV_PLANO_ITENS.EANCAIXA%TYPE DEFAULT NULL,
    p_rua             IN  MEGAG_INV_PLANO_ITENS.RUA%TYPE DEFAULT NULL,
    p_lado            IN  MEGAG_INV_PLANO_ITENS.LADO%TYPE DEFAULT NULL,
    p_ativo           IN  MEGAG_INV_PLANO_ITENS.ATIVO%TYPE DEFAULT 'S',
    p_ordem           IN  MEGAG_INV_PLANO_ITENS.ORDEM%TYPE DEFAULT NULL,
    p_commit          IN  CHAR DEFAULT 'S',
    s_sfx             OUT VARCHAR2,
    s_ico             OUT VARCHAR2,
    s_tiporet         OUT VARCHAR2,
    s_msg             OUT VARCHAR2
);

PROCEDURE PRC_ALTERAR_STATUS_PLANO(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_status   IN  MEGAG_INV_PLANOS.STATUS%TYPE,
    p_usuario  IN  MEGAG_INV_PLANOS.USUARIO_ATUALIZACAO%TYPE DEFAULT 'SISTEMA',
    p_commit   IN  CHAR DEFAULT 'S',
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
);

PROCEDURE PRC_LISTAR_PLANOS_ERP(
    p_status  IN  MEGAG_INV_PLANOS.STATUS%TYPE DEFAULT NULL,
    p_result  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
);

PROCEDURE PRC_OBTER_PLANO_ERP(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_plano    OUT SYS_REFCURSOR,
    p_itens    OUT SYS_REFCURSOR,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
);

PROCEDURE PRC_APP_LISTAR_LIBERADOS(
    p_result  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
);

PROCEDURE PRC_APP_BAIXAR_PLANO(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_plano    OUT SYS_REFCURSOR,
    p_grupos   OUT SYS_REFCURSOR,
    p_itens    OUT SYS_REFCURSOR,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
);

PROCEDURE PRC_APP_REGISTRAR_LOTE(
    p_id_plano      IN  MEGAG_INV_LOTES_APP.ID_PLANO%TYPE,
    p_status        IN  MEGAG_INV_LOTES_APP.STATUS%TYPE,
    p_payload_json  IN  CLOB,
    p_usuario       IN  MEGAG_INV_LOTES_APP.USUARIO_RECEBIMENTO%TYPE DEFAULT 'APP',
    p_id_lote       OUT MEGAG_INV_LOTES_APP.ID_LOTE%TYPE,
    p_commit        IN  CHAR DEFAULT 'S',
    s_sfx           OUT VARCHAR2,
    s_ico           OUT VARCHAR2,
    s_tiporet       OUT VARCHAR2,
    s_msg           OUT VARCHAR2
);

PROCEDURE PRC_LISTAR_LOTES(
    p_id_plano IN  MEGAG_INV_LOTES_APP.ID_PLANO%TYPE DEFAULT NULL,
    p_result   OUT SYS_REFCURSOR,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
);

END PKG_MEGAG_INV_CICLICO;
/

CREATE OR REPLACE PACKAGE BODY PKG_MEGAG_INV_CICLICO IS

PROCEDURE SET_OK(
    p_msg     IN  VARCHAR2,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) IS
BEGIN
    s_sfx     := 'success';
    s_ico     := 'success';
    s_tiporet := 'S';
    s_msg     := p_msg;
END;

PROCEDURE SET_ALERT(
    p_msg     IN  VARCHAR2,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) IS
BEGIN
    s_sfx     := 'warning';
    s_ico     := 'warning';
    s_tiporet := 'A';
    s_msg     := p_msg;
END;

PROCEDURE SET_ERR(
    p_msg     IN  VARCHAR2,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) IS
BEGIN
    s_sfx     := 'error';
    s_ico     := 'danger';
    s_tiporet := 'E';
    s_msg     := p_msg;
END;

FUNCTION FN_EXTRAI_RUA(p_endereco IN VARCHAR2) RETURN VARCHAR2 IS
    v_endereco VARCHAR2(80) := TRIM(p_endereco);
    v_primeiro VARCHAR2(80);
    v_rua      VARCHAR2(20);
BEGIN
    IF v_endereco IS NULL THEN
        RETURN '0';
    END IF;

    v_primeiro := REGEXP_SUBSTR(v_endereco, '^[^.\-/ ]+');
    v_rua := REGEXP_REPLACE(NVL(v_primeiro, v_endereco), '[^0-9]', '');
    RETURN NVL(LTRIM(v_rua, '0'), v_rua);
END;

FUNCTION FN_LADO_RUA(p_rua IN VARCHAR2) RETURN VARCHAR2 IS
    v_num NUMBER;
BEGIN
    v_num := TO_NUMBER(NVL(REGEXP_REPLACE(p_rua, '[^0-9]', ''), '0'));
    IF v_num > 0 AND MOD(v_num, 2) = 0 THEN
        RETURN 'PAR';
    END IF;
    RETURN 'IMPAR';
EXCEPTION
    WHEN OTHERS THEN
        RETURN 'IMPAR';
END;

FUNCTION FN_GRUPO(p_rua IN VARCHAR2, p_lado IN VARCHAR2) RETURN VARCHAR2 IS
    v_rua VARCHAR2(20);
BEGIN
    v_rua := TRIM(p_rua);
    IF v_rua IS NULL THEN
        v_rua := '0';
    END IF;

    RETURN 'R' || v_rua ||
           CASE WHEN UPPER(TRIM(p_lado)) = 'PAR' THEN '-P' ELSE '-I' END;
END;

PROCEDURE PRC_PROXIMO_ID_PLANO(
    p_id_plano OUT MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) IS
    v_prefix VARCHAR2(20);
    v_next   NUMBER;
BEGIN
    v_prefix := 'INV-' || TO_CHAR(SYSDATE, 'YYYY-MM') || '-';

    SELECT NVL(MAX(TO_NUMBER(SUBSTR(ID_PLANO, -3))), 0) + 1
      INTO v_next
      FROM MEGAG_INV_PLANOS
     WHERE ID_PLANO LIKE v_prefix || '%';

    p_id_plano := v_prefix || LPAD(v_next, 3, '0');
    SET_OK('Proximo ID gerado com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        SET_ERR('PROXIMO ID PLANO - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_PROXIMO_ID_PLANO;

PROCEDURE PRC_SALVAR_PLANO(
    p_id_plano       IN OUT MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_descricao      IN     MEGAG_INV_PLANOS.DESCRICAO%TYPE,
    p_deposito       IN     MEGAG_INV_PLANOS.DEPOSITO%TYPE,
    p_contagem_atual IN     MEGAG_INV_PLANOS.CONTAGEM_ATUAL%TYPE DEFAULT 1,
    p_observacao     IN     MEGAG_INV_PLANOS.OBSERVACAO%TYPE DEFAULT NULL,
    p_usuario        IN     MEGAG_INV_PLANOS.USUARIO_CRIACAO%TYPE DEFAULT 'SISTEMA',
    p_commit         IN     CHAR DEFAULT 'S',
    s_sfx            OUT    VARCHAR2,
    s_ico            OUT    VARCHAR2,
    s_tiporet        OUT    VARCHAR2,
    s_msg            OUT    VARCHAR2
) IS
    v_count  NUMBER;
    v_status MEGAG_INV_PLANOS.STATUS%TYPE;
BEGIN
    IF TRIM(p_descricao) IS NULL OR TRIM(p_deposito) IS NULL THEN
        SET_ALERT('Informe descricao e deposito do plano.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    IF p_id_plano IS NULL THEN
        PRC_PROXIMO_ID_PLANO(p_id_plano, s_sfx, s_ico, s_tiporet, s_msg);
        IF s_tiporet = 'E' THEN
            RETURN;
        END IF;
    END IF;

    SELECT COUNT(*), MAX(STATUS)
      INTO v_count, v_status
      FROM MEGAG_INV_PLANOS
     WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    IF v_status = 'LIBERADO' THEN
        SET_ALERT('Plano liberado nao pode ser editado. Volte para rascunho antes de alterar.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    p_id_plano := UPPER(TRIM(p_id_plano));

    IF v_count = 0 THEN
        INSERT INTO MEGAG_INV_PLANOS (
            ID_PLANO, DESCRICAO, DEPOSITO, CONTAGEM_ATUAL, STATUS,
            GERADO_EM, USUARIO_CRIACAO, OBSERVACAO
        ) VALUES (
            p_id_plano, TRIM(p_descricao), TRIM(p_deposito), LEAST(GREATEST(NVL(p_contagem_atual, 1), 1), 3), 'RASCUNHO',
            SYSDATE, UPPER(NVL(TRIM(p_usuario), 'SISTEMA')), p_observacao
        );
    ELSE
        UPDATE MEGAG_INV_PLANOS
           SET DESCRICAO           = TRIM(p_descricao),
               DEPOSITO            = TRIM(p_deposito),
               CONTAGEM_ATUAL      = LEAST(GREATEST(NVL(p_contagem_atual, 1), 1), 3),
               OBSERVACAO          = p_observacao,
               ATUALIZADO_EM       = SYSDATE,
               USUARIO_ATUALIZACAO = UPPER(NVL(TRIM(p_usuario), 'SISTEMA'))
         WHERE ID_PLANO = p_id_plano;
    END IF;

    IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
        COMMIT;
    END IF;

    SET_OK('Plano salvo com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
            ROLLBACK;
        END IF;
        SET_ERR('SALVAR PLANO - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_SALVAR_PLANO;

PROCEDURE PRC_LIMPAR_ITENS_PLANO(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_commit   IN  CHAR DEFAULT 'S',
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) IS
BEGIN
    DELETE FROM MEGAG_INV_PLANO_ITENS
     WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
        COMMIT;
    END IF;

    SET_OK('Itens do plano removidos com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
            ROLLBACK;
        END IF;
        SET_ERR('LIMPAR ITENS PLANO - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_LIMPAR_ITENS_PLANO;

PROCEDURE PRC_SALVAR_ITEM_PLANO(
    p_id_plano        IN  MEGAG_INV_PLANO_ITENS.ID_PLANO%TYPE,
    p_endereco        IN  MEGAG_INV_PLANO_ITENS.ENDERECO%TYPE,
    p_codproduto      IN  MEGAG_INV_PLANO_ITENS.CODPRODUTO%TYPE,
    p_descricao       IN  MEGAG_INV_PLANO_ITENS.DESCRICAO%TYPE,
    p_quantidadebase  IN  MEGAG_INV_PLANO_ITENS.QUANTIDADEBASE%TYPE DEFAULT 0,
    p_unidadebase     IN  MEGAG_INV_PLANO_ITENS.UNIDADEBASE%TYPE DEFAULT NULL,
    p_validadebase    IN  MEGAG_INV_PLANO_ITENS.VALIDADEBASE%TYPE DEFAULT NULL,
    p_valorunitario   IN  MEGAG_INV_PLANO_ITENS.VALORUNITARIO%TYPE DEFAULT 0,
    p_eanunidade      IN  MEGAG_INV_PLANO_ITENS.EANUNIDADE%TYPE DEFAULT NULL,
    p_eancaixa        IN  MEGAG_INV_PLANO_ITENS.EANCAIXA%TYPE DEFAULT NULL,
    p_rua             IN  MEGAG_INV_PLANO_ITENS.RUA%TYPE DEFAULT NULL,
    p_lado            IN  MEGAG_INV_PLANO_ITENS.LADO%TYPE DEFAULT NULL,
    p_ativo           IN  MEGAG_INV_PLANO_ITENS.ATIVO%TYPE DEFAULT 'S',
    p_ordem           IN  MEGAG_INV_PLANO_ITENS.ORDEM%TYPE DEFAULT NULL,
    p_commit          IN  CHAR DEFAULT 'S',
    s_sfx             OUT VARCHAR2,
    s_ico             OUT VARCHAR2,
    s_tiporet         OUT VARCHAR2,
    s_msg             OUT VARCHAR2
) IS
    v_status MEGAG_INV_PLANOS.STATUS%TYPE;
    v_rua    MEGAG_INV_PLANO_ITENS.RUA%TYPE;
    v_lado   MEGAG_INV_PLANO_ITENS.LADO%TYPE;
BEGIN
    IF TRIM(p_id_plano) IS NULL OR TRIM(p_endereco) IS NULL OR TRIM(p_codproduto) IS NULL OR TRIM(p_descricao) IS NULL THEN
        SET_ALERT('Informe plano, endereco, produto e descricao.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    SELECT STATUS
      INTO v_status
      FROM MEGAG_INV_PLANOS
     WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    IF v_status = 'LIBERADO' THEN
        SET_ALERT('Plano liberado nao pode receber alteracao de itens.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    v_rua := TRIM(p_rua);
    IF v_rua IS NULL THEN
        v_rua := FN_EXTRAI_RUA(p_endereco);
    END IF;
    v_lado := CASE WHEN UPPER(TRIM(p_lado)) = 'PAR' THEN 'PAR'
                   WHEN UPPER(TRIM(p_lado)) = 'IMPAR' THEN 'IMPAR'
                   ELSE FN_LADO_RUA(v_rua)
              END;

    INSERT INTO MEGAG_INV_PLANO_ITENS (
        ID_PLANO, GRUPO_ID, RUA, LADO, ENDERECO, CODPRODUTO,
        DESCRICAO, EANUNIDADE, EANCAIXA, QUANTIDADEBASE, UNIDADEBASE,
        VALIDADEBASE, VALORUNITARIO, ATIVO, ORDEM
    ) VALUES (
        UPPER(TRIM(p_id_plano)), FN_GRUPO(v_rua, v_lado), v_rua, v_lado, TRIM(p_endereco), TRIM(p_codproduto),
        TRIM(p_descricao), TRIM(p_eanunidade), TRIM(p_eancaixa), NVL(p_quantidadebase, 0), TRIM(p_unidadebase),
        p_validadebase, NVL(p_valorunitario, 0), CASE WHEN UPPER(TRIM(p_ativo)) = 'N' THEN 'N' ELSE 'S' END, NVL(p_ordem, 0)
    );

    IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
        COMMIT;
    END IF;

    SET_OK('Item salvo com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        SET_ALERT('Plano nao encontrado para incluir item.', s_sfx, s_ico, s_tiporet, s_msg);
    WHEN OTHERS THEN
        IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
            ROLLBACK;
        END IF;
        SET_ERR('SALVAR ITEM PLANO - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_SALVAR_ITEM_PLANO;

PROCEDURE PRC_ALTERAR_STATUS_PLANO(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_status   IN  MEGAG_INV_PLANOS.STATUS%TYPE,
    p_usuario  IN  MEGAG_INV_PLANOS.USUARIO_ATUALIZACAO%TYPE DEFAULT 'SISTEMA',
    p_commit   IN  CHAR DEFAULT 'S',
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) IS
    v_status MEGAG_INV_PLANOS.STATUS%TYPE;
    v_itens  NUMBER;
BEGIN
    v_status := UPPER(TRIM(p_status));

    IF v_status NOT IN ('RASCUNHO', 'LIBERADO', 'EM_CONTAGEM', 'EM_REVISAO', 'FINALIZADO', 'CANCELADO') THEN
        SET_ALERT('Status invalido para o plano.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    IF v_status = 'LIBERADO' THEN
        SELECT COUNT(*)
          INTO v_itens
          FROM MEGAG_INV_PLANO_ITENS
         WHERE ID_PLANO = UPPER(TRIM(p_id_plano))
           AND NVL(ATIVO, 'S') = 'S';

        IF v_itens = 0 THEN
            SET_ALERT('Inclua ao menos um item ativo antes de liberar.', s_sfx, s_ico, s_tiporet, s_msg);
            RETURN;
        END IF;
    END IF;

    UPDATE MEGAG_INV_PLANOS
       SET STATUS               = v_status,
           LIBERADO_EM          = CASE WHEN v_status = 'LIBERADO' THEN SYSDATE ELSE NULL END,
           USUARIO_LIBERACAO    = CASE WHEN v_status = 'LIBERADO' THEN UPPER(NVL(TRIM(p_usuario), 'SISTEMA')) ELSE USUARIO_LIBERACAO END,
           ATUALIZADO_EM        = SYSDATE,
           USUARIO_ATUALIZACAO  = UPPER(NVL(TRIM(p_usuario), 'SISTEMA'))
     WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    IF SQL%ROWCOUNT = 0 THEN
        SET_ALERT('Plano nao encontrado.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
        COMMIT;
    END IF;

    SET_OK('Status do plano atualizado com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
            ROLLBACK;
        END IF;
        SET_ERR('ALTERAR STATUS PLANO - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_ALTERAR_STATUS_PLANO;

PROCEDURE PRC_LISTAR_PLANOS_ERP(
    p_status  IN  MEGAG_INV_PLANOS.STATUS%TYPE DEFAULT NULL,
    p_result  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) IS
BEGIN
    OPEN p_result FOR
        SELECT p.ID_PLANO, p.DESCRICAO, p.DEPOSITO, p.CONTAGEM_ATUAL, p.STATUS,
               TO_CHAR(p.GERADO_EM, 'YYYY-MM-DD HH24:MI:SS') AS GERADO_EM,
               TO_CHAR(p.LIBERADO_EM, 'YYYY-MM-DD HH24:MI:SS') AS LIBERADO_EM,
               p.USUARIO_CRIACAO, p.USUARIO_LIBERACAO, p.OBSERVACAO,
               (SELECT COUNT(*) FROM MEGAG_INV_PLANO_ITENS i WHERE i.ID_PLANO = p.ID_PLANO) AS QTD_ITENS,
               (SELECT COUNT(DISTINCT i.GRUPO_ID) FROM MEGAG_INV_PLANO_ITENS i WHERE i.ID_PLANO = p.ID_PLANO AND NVL(i.ATIVO, 'S') = 'S') AS QTD_GRUPOS
          FROM MEGAG_INV_PLANOS p
         WHERE p_status IS NULL OR p.STATUS = UPPER(TRIM(p_status))
         ORDER BY p.GERADO_EM DESC, p.ID_PLANO DESC;

    SET_OK('Consulta realizada com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        SET_ERR('LISTAR PLANOS ERP - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_LISTAR_PLANOS_ERP;

PROCEDURE PRC_OBTER_PLANO_ERP(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_plano    OUT SYS_REFCURSOR,
    p_itens    OUT SYS_REFCURSOR,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) IS
BEGIN
    OPEN p_plano FOR
        SELECT ID_PLANO, DESCRICAO, DEPOSITO, CONTAGEM_ATUAL, STATUS,
               TO_CHAR(GERADO_EM, 'YYYY-MM-DD HH24:MI:SS') AS GERADO_EM,
               TO_CHAR(LIBERADO_EM, 'YYYY-MM-DD HH24:MI:SS') AS LIBERADO_EM,
               USUARIO_CRIACAO, USUARIO_LIBERACAO, OBSERVACAO
          FROM MEGAG_INV_PLANOS
         WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    OPEN p_itens FOR
        SELECT ID_ITEM, ID_PLANO, GRUPO_ID, RUA, LADO, ENDERECO, CODPRODUTO,
               DESCRICAO, EANUNIDADE, EANCAIXA, QUANTIDADEBASE, UNIDADEBASE,
               TO_CHAR(VALIDADEBASE, 'YYYY-MM-DD') AS VALIDADEBASE,
               TO_CHAR(VALIDADEBASE, 'DD/MM/YYYY') AS VALIDADEBASE_BR,
               VALORUNITARIO, ATIVO, ORDEM
          FROM MEGAG_INV_PLANO_ITENS
         WHERE ID_PLANO = UPPER(TRIM(p_id_plano))
         ORDER BY RUA, LADO, ENDERECO, CODPRODUTO, ID_ITEM;

    SET_OK('Consulta realizada com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        SET_ERR('OBTER PLANO ERP - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_OBTER_PLANO_ERP;

PROCEDURE PRC_APP_LISTAR_LIBERADOS(
    p_result  OUT SYS_REFCURSOR,
    s_sfx     OUT VARCHAR2,
    s_ico     OUT VARCHAR2,
    s_tiporet OUT VARCHAR2,
    s_msg     OUT VARCHAR2
) IS
BEGIN
    PRC_LISTAR_PLANOS_ERP('LIBERADO', p_result, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_APP_LISTAR_LIBERADOS;

PROCEDURE PRC_APP_BAIXAR_PLANO(
    p_id_plano IN  MEGAG_INV_PLANOS.ID_PLANO%TYPE,
    p_plano    OUT SYS_REFCURSOR,
    p_grupos   OUT SYS_REFCURSOR,
    p_itens    OUT SYS_REFCURSOR,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) IS
    v_status MEGAG_INV_PLANOS.STATUS%TYPE;
BEGIN
    SELECT STATUS
      INTO v_status
      FROM MEGAG_INV_PLANOS
     WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    IF v_status <> 'LIBERADO' THEN
        SET_ALERT('Plano ainda nao liberado para o app.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    OPEN p_plano FOR
        SELECT ID_PLANO AS id,
               DESCRICAO AS descricao,
               DEPOSITO AS deposito,
               CONTAGEM_ATUAL AS contagemAtual,
               STATUS AS status,
               TO_CHAR(GERADO_EM, 'YYYY-MM-DD HH24:MI:SS') AS geradoEm
          FROM MEGAG_INV_PLANOS
         WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    OPEN p_grupos FOR
        SELECT GRUPO_ID AS id,
               RUA AS rua,
               LADO AS lado,
               'Rua ' || RUA || ' (' || LOWER(LADO) || ')' AS descricao
          FROM MEGAG_INV_PLANO_ITENS
         WHERE ID_PLANO = UPPER(TRIM(p_id_plano))
           AND NVL(ATIVO, 'S') = 'S'
         GROUP BY GRUPO_ID, RUA, LADO
         ORDER BY RUA, LADO, GRUPO_ID;

    OPEN p_itens FOR
        SELECT GRUPO_ID AS grupoId,
               ENDERECO AS codigo,
               CODPRODUTO AS codProduto,
               DESCRICAO AS descricao,
               NVL(EANUNIDADE, '') AS eanUnidade,
               NVL(EANCAIXA, '') AS eanCaixa,
               QUANTIDADEBASE AS quantidadeBase,
               NVL(UNIDADEBASE, '') AS unidadeBase,
               TO_CHAR(VALIDADEBASE, 'DD/MM/YYYY') AS validadeBase,
               VALORUNITARIO AS valorUnitario
          FROM MEGAG_INV_PLANO_ITENS
         WHERE ID_PLANO = UPPER(TRIM(p_id_plano))
           AND NVL(ATIVO, 'S') = 'S'
         ORDER BY RUA, LADO, ENDERECO, CODPRODUTO, ID_ITEM;

    SET_OK('Plano liberado retornado com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        SET_ALERT('Plano nao encontrado.', s_sfx, s_ico, s_tiporet, s_msg);
    WHEN OTHERS THEN
        SET_ERR('APP BAIXAR PLANO - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_APP_BAIXAR_PLANO;

PROCEDURE PRC_APP_REGISTRAR_LOTE(
    p_id_plano      IN  MEGAG_INV_LOTES_APP.ID_PLANO%TYPE,
    p_status        IN  MEGAG_INV_LOTES_APP.STATUS%TYPE,
    p_payload_json  IN  CLOB,
    p_usuario       IN  MEGAG_INV_LOTES_APP.USUARIO_RECEBIMENTO%TYPE DEFAULT 'APP',
    p_id_lote       OUT MEGAG_INV_LOTES_APP.ID_LOTE%TYPE,
    p_commit        IN  CHAR DEFAULT 'S',
    s_sfx           OUT VARCHAR2,
    s_ico           OUT VARCHAR2,
    s_tiporet       OUT VARCHAR2,
    s_msg           OUT VARCHAR2
) IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
      INTO v_count
      FROM MEGAG_INV_PLANOS
     WHERE ID_PLANO = UPPER(TRIM(p_id_plano));

    IF v_count = 0 THEN
        SET_ALERT('Plano nao encontrado para registrar lote.', s_sfx, s_ico, s_tiporet, s_msg);
        RETURN;
    END IF;

    INSERT INTO MEGAG_INV_LOTES_APP (
        ID_PLANO, STATUS, PAYLOAD_JSON, RECEBIDO_EM, USUARIO_RECEBIMENTO
    ) VALUES (
        UPPER(TRIM(p_id_plano)), UPPER(TRIM(p_status)), p_payload_json, SYSDATE, UPPER(NVL(TRIM(p_usuario), 'APP'))
    )
    RETURNING ID_LOTE INTO p_id_lote;

    IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
        COMMIT;
    END IF;

    SET_OK('Lote registrado com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        IF UPPER(NVL(p_commit, 'S')) = 'S' THEN
            ROLLBACK;
        END IF;
        SET_ERR('APP REGISTRAR LOTE - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_APP_REGISTRAR_LOTE;

PROCEDURE PRC_LISTAR_LOTES(
    p_id_plano IN  MEGAG_INV_LOTES_APP.ID_PLANO%TYPE DEFAULT NULL,
    p_result   OUT SYS_REFCURSOR,
    s_sfx      OUT VARCHAR2,
    s_ico      OUT VARCHAR2,
    s_tiporet  OUT VARCHAR2,
    s_msg      OUT VARCHAR2
) IS
BEGIN
    OPEN p_result FOR
        SELECT ID_LOTE, ID_PLANO, STATUS,
               TO_CHAR(RECEBIDO_EM, 'YYYY-MM-DD HH24:MI:SS') AS RECEBIDO_EM,
               USUARIO_RECEBIMENTO,
               TO_CHAR(PROCESSADO_EM, 'YYYY-MM-DD HH24:MI:SS') AS PROCESSADO_EM,
               MSG_PROCESSAMENTO
          FROM MEGAG_INV_LOTES_APP
         WHERE p_id_plano IS NULL OR ID_PLANO = UPPER(TRIM(p_id_plano))
         ORDER BY RECEBIDO_EM DESC, ID_LOTE DESC;

    SET_OK('Lotes consultados com sucesso.', s_sfx, s_ico, s_tiporet, s_msg);
EXCEPTION
    WHEN OTHERS THEN
        SET_ERR('LISTAR LOTES - Erro: ' || SQLERRM, s_sfx, s_ico, s_tiporet, s_msg);
END PRC_LISTAR_LOTES;

END PKG_MEGAG_INV_CICLICO;
/
