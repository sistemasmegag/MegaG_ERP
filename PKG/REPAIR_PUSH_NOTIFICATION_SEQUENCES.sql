-- Reparo para producao quando as tabelas ja existem, mas as sequences nao.
-- Execute conectado no schema configurado como DB_SCHEMA.

DECLARE
    v_count NUMBER;
    v_start NUMBER;
BEGIN
    SELECT COUNT(1)
      INTO v_count
      FROM USER_SEQUENCES
     WHERE SEQUENCE_NAME = 'SEQ_MEGAG_PUSH_TOKENS';

    IF v_count = 0 THEN
        SELECT NVL(MAX(ID), 0) + 1
          INTO v_start
          FROM MEGAG_PUSH_TOKENS;

        EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_MEGAG_PUSH_TOKENS START WITH '
            || v_start || ' INCREMENT BY 1 NOCACHE NOCYCLE';
    END IF;
END;
/

DECLARE
    v_count NUMBER;
    v_start NUMBER;
BEGIN
    SELECT COUNT(1)
      INTO v_count
      FROM USER_SEQUENCES
     WHERE SEQUENCE_NAME = 'SEQ_MEGAG_TASK_NOTIFICACOES';

    IF v_count = 0 THEN
        SELECT NVL(MAX(ID), 0) + 1
          INTO v_start
          FROM MEGAG_TASK_NOTIFICACOES;

        EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_MEGAG_TASK_NOTIFICACOES START WITH '
            || v_start || ' INCREMENT BY 1 NOCACHE NOCYCLE';
    END IF;
END;
/

