CREATE OR REPLACE PACKAGE MEGAG_PKG_TASK IS

  PROCEDURE proc_spaces_create(
    p_nome       IN VARCHAR2,
    p_criado_por IN VARCHAR2,
    p_id         OUT NUMBER,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  );

  PROCEDURE proc_spaces_set_ativo(
    p_id    IN NUMBER,
    p_ativo IN VARCHAR2,
    p_ok    OUT VARCHAR2,
    p_err   OUT VARCHAR2
  );

  PROCEDURE proc_lists_create(
    p_space_id   IN NUMBER,
    p_nome       IN VARCHAR2,
    p_ordem      IN NUMBER,
    p_criado_por IN VARCHAR2,
    p_id         OUT NUMBER,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  );

  PROCEDURE proc_lists_set_ativo(
    p_id    IN NUMBER,
    p_ativo IN VARCHAR2,
    p_ok    OUT VARCHAR2,
    p_err   OUT VARCHAR2
  );

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
  );

  PROCEDURE proc_tasks_move(
    p_task_id IN NUMBER,
    p_status  IN VARCHAR2,
    p_user    IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  );

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
  );

  PROCEDURE proc_tasks_delete(
    p_task_id IN NUMBER,
    p_user    IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  );

  PROCEDURE proc_comments_list(
    p_task_id IN NUMBER,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2,
    p_rc      OUT SYS_REFCURSOR
  );

  PROCEDURE proc_comments_create(
    p_task_id    IN NUMBER,
    p_comentario IN CLOB,
    p_criado_por IN VARCHAR2,
    p_id         OUT NUMBER,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  );

  PROCEDURE proc_comments_delete(
    p_comment_id IN NUMBER,
    p_user       IN VARCHAR2,
    p_ok         OUT VARCHAR2,
    p_err        OUT VARCHAR2
  );

  PROCEDURE proc_files_delete(
    p_file_id IN NUMBER,
    p_user    IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  );

  PROCEDURE proc_notif_list(
    p_usuario IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2,
    p_rc      OUT SYS_REFCURSOR
  );

  PROCEDURE proc_notif_create(
    p_usuario  IN VARCHAR2,
    p_tipo     IN VARCHAR2,
    p_titulo   IN VARCHAR2,
    p_mensagem IN CLOB,
    p_task_id  IN NUMBER,
    p_id       OUT NUMBER,
    p_ok       OUT VARCHAR2,
    p_err      OUT VARCHAR2
  );

  PROCEDURE proc_notif_mark_read(
    p_id  IN NUMBER,
    p_ok  OUT VARCHAR2,
    p_err OUT VARCHAR2
  );

  PROCEDURE proc_notif_mark_all_read(
    p_usuario IN VARCHAR2,
    p_ok      OUT VARCHAR2,
    p_err     OUT VARCHAR2
  );

  PROCEDURE proc_notif_delete(
    p_id   IN NUMBER,
    p_user IN VARCHAR2,
    p_ok   OUT VARCHAR2,
    p_err  OUT VARCHAR2
  );

END MEGAG_PKG_TASK;
