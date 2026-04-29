/* ==================================================
   FILE: ClonaUsuarioCRUD.sql

- Clonar usuario(permissoes)
- adicionar na tela de aprovadores
- vincular aos cc de custos(do usuario clonado)
- vincular aos grupos dos usuarios clonados
================================================== */
CREATE OR REPLACE PROCEDURE PRC_INS_MEGAG_DESP_CLONA_APROVADOR(
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
/
