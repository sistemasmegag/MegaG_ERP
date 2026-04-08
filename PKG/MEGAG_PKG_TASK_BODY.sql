CREATE OR REPLACE PACKAGE BODY MEGAG_PKG_TASK IS

  PROCEDURE set_success(
    p_ok  OUT VARCHAR2,
    p_err OUT VARCHAR2
  ) IS
  BEGIN
    p_ok  := 'S';
    p_err := NULL;
  END;

  PROCEDURE set_error(
    p_message IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  ) IS
  BEGIN
    p_ok  := 'N';
    p_err := SUBSTR(p_message, 1, 4000);
  END;

  PROCEDURE proc_spaces_create(
    p_nome       IN VARCHAR2,
    p_criado_por IN VARCHAR2,
    p_id         OUT NUMBER,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  ) IS
  BEGIN
    INSERT INTO megag_task_spaces (
      id, nome, ativo, criado_por, criado_em
    ) VALUES (
      seq_megag_task_spaces.NEXTVAL, TRIM(p_nome), 'S', p_criado_por, SYSDATE
    )
    RETURNING id INTO p_id;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_spaces_set_ativo(
    p_id    IN NUMBER,
    p_ativo IN VARCHAR2,
    p_ok    OUT VARCHAR2,
    p_err   OUT VARCHAR2
  ) IS
  BEGIN
    UPDATE megag_task_spaces
       SET ativo = NVL(p_ativo, 'S'),
           atualizado_em = SYSDATE
     WHERE id = p_id;

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20001, 'Space nao encontrado.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_lists_create(
    p_space_id   IN NUMBER,
    p_nome       IN VARCHAR2,
    p_ordem      IN NUMBER,
    p_criado_por IN VARCHAR2,
    p_id         OUT NUMBER,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  ) IS
  BEGIN
    INSERT INTO megag_task_lists (
      id, space_id, nome, ordem, ativo, criado_por, criado_em
    ) VALUES (
      seq_megag_task_lists.NEXTVAL, p_space_id, TRIM(p_nome), NVL(p_ordem, 0), 'S', p_criado_por, SYSDATE
    )
    RETURNING id INTO p_id;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_lists_set_ativo(
    p_id    IN NUMBER,
    p_ativo IN VARCHAR2,
    p_ok    OUT VARCHAR2,
    p_err   OUT VARCHAR2
  ) IS
  BEGIN
    UPDATE megag_task_lists
       SET ativo = NVL(p_ativo, 'S'),
           atualizado_em = SYSDATE
     WHERE id = p_id;

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20002, 'List nao encontrada.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_tasks_create(
    p_list_id      IN NUMBER,
    p_titulo       IN VARCHAR2,
    p_descricao    IN CLOB,
    p_status       IN VARCHAR2,
    p_prioridade   IN VARCHAR2,
    p_tags         IN VARCHAR2,
    p_responsavel  IN VARCHAR2,
    p_data_entrega IN VARCHAR2,
    p_criado_por   IN VARCHAR2,
    p_id           OUT NUMBER,
    p_ok           OUT VARCHAR2,
    p_err          OUT VARCHAR2
  ) IS
  BEGIN
    INSERT INTO megag_task_tasks (
      id, list_id, titulo, descricao, status, prioridade, tags, responsavel, data_entrega, criado_por, criado_em
    ) VALUES (
      seq_megag_task_tasks.NEXTVAL,
      p_list_id,
      TRIM(p_titulo),
      p_descricao,
      NVL(TRIM(p_status), 'TODO'),
      NVL(TRIM(p_prioridade), 'MED'),
      p_tags,
      p_responsavel,
      p_data_entrega,
      p_criado_por,
      SYSDATE
    )
    RETURNING id INTO p_id;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_tasks_move(
    p_task_id IN NUMBER,
    p_status  IN VARCHAR2,
    p_user    IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  ) IS
  BEGIN
    UPDATE megag_task_tasks
       SET status = TRIM(p_status),
           atualizado_por = p_user,
           atualizado_em = SYSDATE
     WHERE id = p_task_id;

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20003, 'Task nao encontrada para mover.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_tasks_update(
    p_task_id      IN NUMBER,
    p_titulo       IN VARCHAR2,
    p_descricao    IN CLOB,
    p_prioridade   IN VARCHAR2,
    p_tags         IN VARCHAR2,
    p_responsavel  IN VARCHAR2,
    p_data_entrega IN VARCHAR2,
    p_user         IN VARCHAR2,
    p_ok           OUT VARCHAR2,
    p_err          OUT VARCHAR2
  ) IS
  BEGIN
    UPDATE megag_task_tasks
       SET titulo = TRIM(p_titulo),
           descricao = p_descricao,
           prioridade = NVL(TRIM(p_prioridade), 'MED'),
           tags = p_tags,
           responsavel = p_responsavel,
           data_entrega = p_data_entrega,
           atualizado_por = p_user,
           atualizado_em = SYSDATE
     WHERE id = p_task_id;

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20004, 'Task nao encontrada para atualizar.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_tasks_delete(
    p_task_id IN NUMBER,
    p_user    IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  ) IS
  BEGIN
    DELETE FROM megag_task_comments WHERE task_id = p_task_id;
    DELETE FROM megag_task_files WHERE task_id = p_task_id;
    DELETE FROM megag_task_notificacoes WHERE task_id = p_task_id;
    DELETE FROM megag_task_tasks WHERE id = p_task_id;

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20005, 'Task nao encontrada para excluir.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_comments_list(
    p_task_id IN NUMBER,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2,
    p_rc      OUT SYS_REFCURSOR
  ) IS
  BEGIN
    OPEN p_rc FOR
      SELECT id,
             task_id,
             comentario,
             criado_por,
             criado_em
        FROM megag_task_comments
       WHERE task_id = p_task_id
       ORDER BY criado_em DESC, id DESC;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_comments_create(
    p_task_id    IN NUMBER,
    p_comentario IN CLOB,
    p_criado_por IN VARCHAR2,
    p_id         OUT NUMBER,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  ) IS
  BEGIN
    INSERT INTO megag_task_comments (
      id, task_id, comentario, criado_por, criado_em
    ) VALUES (
      seq_megag_task_comments.NEXTVAL, p_task_id, p_comentario, p_criado_por, SYSDATE
    )
    RETURNING id INTO p_id;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_comments_delete(
    p_comment_id IN NUMBER,
    p_user       IN VARCHAR2,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  ) IS
  BEGIN
    DELETE FROM megag_task_comments
     WHERE id = p_comment_id
       AND UPPER(criado_por) = UPPER(p_user);

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20006, 'Comentario nao encontrado ou sem permissao para excluir.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_files_delete(
    p_file_id IN NUMBER,
    p_user    IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  ) IS
  BEGIN
    DELETE FROM megag_task_files
     WHERE id = p_file_id
       AND UPPER(criado_por) = UPPER(p_user);

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20007, 'Arquivo nao encontrado ou sem permissao para excluir.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_notif_list(
    p_usuario IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2,
    p_rc      OUT SYS_REFCURSOR
  ) IS
  BEGIN
    OPEN p_rc FOR
      SELECT id,
             usuario,
             tipo,
             titulo,
             mensagem,
             task_id,
             lida,
             criado_em,
             lida_em
        FROM megag_task_notificacoes
       WHERE usuario = p_usuario
       ORDER BY lida ASC, criado_em DESC, id DESC;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_notif_create(
    p_usuario  IN VARCHAR2,
    p_tipo     IN VARCHAR2,
    p_titulo   IN VARCHAR2,
    p_mensagem IN CLOB,
    p_task_id  IN NUMBER,
    p_id       OUT NUMBER,
    p_ok       OUT VARCHAR2,
    p_err      OUT VARCHAR2
  ) IS
  BEGIN
    INSERT INTO megag_task_notificacoes (
      id, usuario, tipo, titulo, mensagem, task_id, lida, criado_em
    ) VALUES (
      seq_megag_task_notificacoes.NEXTVAL,
      p_usuario,
      p_tipo,
      p_titulo,
      p_mensagem,
      p_task_id,
      'N',
      SYSDATE
    )
    RETURNING id INTO p_id;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_notif_mark_read(
    p_id  IN NUMBER,
    p_ok  OUT VARCHAR2,
    p_err OUT VARCHAR2
  ) IS
  BEGIN
    UPDATE megag_task_notificacoes
       SET lida = 'S',
           lida_em = SYSDATE
     WHERE id = p_id;

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20008, 'Notificacao nao encontrada.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_notif_mark_all_read(
    p_usuario IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  ) IS
  BEGIN
    UPDATE megag_task_notificacoes
       SET lida = 'S',
           lida_em = SYSDATE
     WHERE usuario = p_usuario
       AND NVL(lida, 'N') = 'N';

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

  PROCEDURE proc_notif_delete(
    p_id   IN NUMBER,
    p_user IN VARCHAR2,
    p_ok   OUT VARCHAR2,
    p_err  OUT VARCHAR2
  ) IS
  BEGIN
    DELETE FROM megag_task_notificacoes
     WHERE id = p_id
       AND UPPER(usuario) = UPPER(p_user);

    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20009, 'Notificacao nao encontrada ou sem permissao para excluir.');
    END IF;

    set_success(p_ok, p_err);
  EXCEPTION
    WHEN OTHERS THEN
      set_error(SQLERRM, p_ok, p_err);
  END;

END MEGAG_PKG_TASK;
