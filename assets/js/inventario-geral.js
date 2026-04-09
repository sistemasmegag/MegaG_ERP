document.addEventListener('DOMContentLoaded', () => {
  const API = 'api/inventario_ti.php';
  const LOGO = 'assets/images/logo.png';
  const state = { domains: {}, requestDomains: {}, report: null, term: null };
  let costCenterTom = null;
  let filialTom = null;
  let responsibleTom = null;
  let requestModalReadOnly = false;

  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
  const fmtDate = (value) => value && String(value).includes('-') ? String(value).split('-').reverse().join('/') : (value || '—');
  const fmtMoney = (value) => Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const cpf = (value) => String(value || '').replace(/\D+/g, '').slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  const badge = (status, label) => `<span class="inventory-badge status-${esc(status || '')}">${esc(label || status || 'Sem status')}</span>`;
  const getValue = (id) => document.getElementById(id).value.trim();
  const setValue = (id, value) => { document.getElementById(id).value = value ?? ''; };
  const today = () => new Date().toISOString().slice(0, 10);

  function setSelectValue(id, value) {
    const normalized = value ?? '';
    const element = document.getElementById(id);
    if (element) element.value = normalized;
    if (id === 'centroCustoSolicitacao' && costCenterTom) {
      costCenterTom.setValue(normalized, true);
    } else if (id === 'filialSolicitacao' && filialTom) {
      filialTom.setValue(normalized, true);
    } else if (id === 'responsavelDestino' && responsibleTom) {
      responsibleTom.setValue(normalized, true);
    }
  }

  function setRequestReadOnly(readOnly) {
    requestModalReadOnly = readOnly;
    const form = document.getElementById('formSolicitacao');
    if (!form) return;
    const fields = form.querySelectorAll('input, select, textarea, button');
    fields.forEach((field) => {
      if (field.id === 'solicitacaoId') return;
      if (field.id === 'btnLimparAssSolicitante' || field.id === 'btnLimparAssAlmox') {
        field.disabled = readOnly;
        return;
      }
      field.disabled = readOnly;
    });
    if (costCenterTom) costCenterTom.lock();
    if (filialTom) filialTom.lock();
    if (responsibleTom) responsibleTom.lock();
    if (!readOnly) {
      if (costCenterTom) costCenterTom.unlock();
      if (filialTom) filialTom.unlock();
      if (responsibleTom) responsibleTom.unlock();
    }
    const saveBtn = document.getElementById('btnSalvarSolicitacao');
    if (saveBtn) saveBtn.style.display = readOnly ? 'none' : '';
  }

  const modal = {
    equip: new bootstrap.Modal(document.getElementById('modalEquipamento')),
    hist: new bootstrap.Modal(document.getElementById('modalHistorico')),
    term: new bootstrap.Modal(document.getElementById('modalTermo')),
    rep: new bootstrap.Modal(document.getElementById('modalRelatorios')),
    req: new bootstrap.Modal(document.getElementById('modalSolicitacao')),
  };

  function toast(message, type = 'info') {
    const map = { danger: 'danger', warning: 'warning', success: 'success', info: 'primary' };
    document.getElementById('inventoryAlert').innerHTML = `<div class="alert alert-${map[type] || 'primary'} alert-dismissible fade show">${esc(message)}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
  }

  async function apiGet(action, params = {}) {
    const response = await fetch(`${API}?${new URLSearchParams({ action, ...params })}`);
    const json = await response.json();
    if (!response.ok || !json.sucesso) throw new Error(json.erro || 'Falha na operacao.');
    return json.dados;
  }

  async function apiPost(action, payload) {
    const response = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, ...payload }),
    });
    const json = await response.json();
    if (!response.ok || !json.sucesso) throw new Error(json.erro || 'Falha na operacao.');
    return json.dados;
  }

  function populateSelect(id, items = [], placeholder = 'Selecione', includePlaceholder = true) {
    const el = document.getElementById(id);
    el.innerHTML = `${includePlaceholder ? `<option value="">${esc(placeholder)}</option>` : ''}${items.map((item) => `<option value="${esc(item.valor)}">${esc(item.rotulo)}</option>`).join('')}`;
  }

  function initCostCenterSuggest() {
    const select = document.getElementById('centroCustoSolicitacao');
    if (!select || typeof TomSelect === 'undefined') return;
    if (costCenterTom) {
      costCenterTom.destroy();
      costCenterTom = null;
    }
    costCenterTom = new TomSelect(select, {
      create: false,
      maxOptions: 300,
      allowEmptyOption: true,
      placeholder: 'Digite para buscar o centro de custo...',
      searchField: ['text'],
      sortField: [{ field: 'text', direction: 'asc' }],
    });
  }

  function initFilialSuggest() {
    const select = document.getElementById('filialSolicitacao');
    if (!select || typeof TomSelect === 'undefined') return;
    if (filialTom) {
      filialTom.destroy();
      filialTom = null;
    }
    filialTom = new TomSelect(select, {
      create: false,
      maxOptions: 20,
      allowEmptyOption: true,
      placeholder: 'Digite para buscar a filial...',
      searchField: ['text'],
      sortField: [{ field: 'text', direction: 'asc' }],
    });
  }

  function initResponsibleSuggest() {
    const select = document.getElementById('responsavelDestino');
    if (!select || typeof TomSelect === 'undefined') return;
    if (responsibleTom) {
      responsibleTom.destroy();
      responsibleTom = null;
    }
    responsibleTom = new TomSelect(select, {
      create: false,
      maxOptions: 50,
      allowEmptyOption: true,
      placeholder: 'Selecione o responsavel da proxima etapa...',
      searchField: ['text'],
      sortField: [{ field: 'text', direction: 'asc' }],
    });
  }

  async function loadResponsibleOptions() {
    const costCenter = getValue('centroCustoSolicitacao');
    const select = document.getElementById('responsavelDestino');
    if (!select) return;
    if (responsibleTom) {
      responsibleTom.destroy();
      responsibleTom = null;
    }
    populateSelect('responsavelDestino', [], 'Selecione o responsavel da proxima etapa');
    if (!costCenter) {
      initResponsibleSuggest();
      return;
    }
    try {
      const rows = await apiGet('request_responsibles', { centro_custo: costCenter });
      populateSelect('responsavelDestino', rows || [], (rows || []).length ? 'Selecione o responsavel da proxima etapa' : 'Nenhum aprovador vinculado para este centro de custo');
      initResponsibleSuggest();
      if ((rows || []).length === 1) {
        setValue('responsavelDestino', rows[0].valor || '');
        if (responsibleTom) {
          responsibleTom.setValue(rows[0].valor || '', true);
        }
      }
    } catch (error) {
      toast(error.message, 'warning');
    }
  }

  function drawPad(id, onChange) {
    const canvas = document.getElementById(id);
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let ink = false;
    let last = '';

    const resize = () => {
      const bounds = canvas.getBoundingClientRect();
      if (!bounds.width || !bounds.height) return;
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      canvas.width = bounds.width * ratio;
      canvas.height = bounds.height * ratio;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.scale(ratio, ratio);
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.strokeStyle = '#111827';
      if (last) {
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0, bounds.width, bounds.height);
        img.src = last;
      }
    };

    const point = (event) => {
      const rect = canvas.getBoundingClientRect();
      const src = event.touches ? event.touches[0] : event;
      return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    };

    const start = (event) => {
      resize();
      drawing = true;
      const p = point(event);
      ctx.beginPath();
      ctx.moveTo(p.x, p.y);
      event.preventDefault();
    };

    const move = (event) => {
      if (!drawing) return;
      const p = point(event);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      ink = true;
      last = canvas.toDataURL('image/png');
      if (onChange) onChange();
      event.preventDefault();
    };

    const end = () => {
      if (!drawing) return;
      drawing = false;
      ctx.closePath();
      if (ink) last = canvas.toDataURL('image/png');
      if (onChange) onChange();
    };

    ['mousedown', 'touchstart'].forEach((ev) => canvas.addEventListener(ev, start, { passive: false }));
    ['mousemove', 'touchmove'].forEach((ev) => canvas.addEventListener(ev, move, { passive: false }));
    window.addEventListener('mouseup', end);
    window.addEventListener('touchend', end, { passive: false });
    window.addEventListener('resize', resize);

    return {
      resize,
      clear() { ctx.clearRect(0, 0, canvas.width, canvas.height); drawing = false; ink = false; last = ''; if (onChange) onChange(); },
      isEmpty() { return !ink; },
      toDataURL() { return last || canvas.toDataURL('image/png'); },
      load(url) {
        if (!url) return;
        last = url;
        ink = true;
        resize();
        const bounds = canvas.getBoundingClientRect();
        const img = new Image();
        img.onload = () => { ctx.clearRect(0, 0, canvas.width, canvas.height); ctx.drawImage(img, 0, 0, bounds.width, bounds.height); if (onChange) onChange(); };
        img.src = url;
      },
    };
  }

  const pads = {
    col: drawPad('assinaturaColaboradorCanvas', renderTerm),
    ti: drawPad('assinaturaTiCanvas', renderTerm),
    sol: drawPad('assinaturaSolicitanteCanvas'),
    alm: drawPad('assinaturaAlmoxCanvas'),
  };

  function parseItens(raw) {
    return String(raw || '').split('\n').map((line) => line.trim()).filter(Boolean).map((line) => {
      const [serie = '', descricao = '', valor = '', quantidade = '1'] = line.split(' - ');
      return { serie, descricao, valor, quantidade };
    });
  }

  function renderTerm() {
    const equip = state.term?.equipamento || {};
    const nome = getValue('termoNomeColaborador');
    const doc = cpf(getValue('termoCpfColaborador'));
    const setor = getValue('termoSetorColaborador');
    const cidade = getValue('termoCidadeEmissao') || 'Sao Paulo';
    const data = getValue('termoData');
    const responsavel = getValue('termoResponsavelTi');
    const base = [equip.NUMERO_SERIE, equip.COD_PATRIMONIO].filter(Boolean).join(' | ');
    const itens = parseItens(getValue('termoItensEntregues') || `${base} - ${equip.NOME_EQUIPAMENTO || ''} - ${equip.VALOR_AQUISICAO || ''} - 1`);

    document.getElementById('termoPreview').innerHTML = `
      <div style="display:grid;grid-template-columns:110px 1fr 110px;gap:1rem">
        <div><img src="${LOGO}" style="max-width:90px;max-height:90px"></div>
        <div style="text-align:center"><h3 style="margin:0;font-size:28px">TERMO DE RESPONSABILIDADE</h3><h4 style="margin:.4rem 0 0;font-size:22px">GUARDA E USO DO ITEM</h4></div>
        <div></div>
      </div>
      <p style="margin-top:1.25rem;font-size:16px;line-height:1.75">Eu, <strong>${esc(nome)}</strong>, CPF <strong>${esc(doc)}</strong>, setor <strong>${esc(setor)}</strong>, recebi o(s) item(ns) abaixo para uso profissional na MEGA G.</p>
      <table class="term-table"><thead><tr><th>Serie / Patrimonio</th><th>Item</th><th>Valor</th><th>Qtd.</th></tr></thead><tbody>${itens.map((item) => `<tr><td>${esc(item.serie || base)}</td><td>${esc(item.descricao || equip.NOME_EQUIPAMENTO || '')}</td><td>${esc(item.valor || '')}</td><td>${esc(item.quantidade || '1')}</td></tr>`).join('')}</tbody></table>
      <div style="display:flex;justify-content:flex-end;margin-top:1rem">${esc(cidade)}, ${esc(fmtDate(data))}</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:1.75rem">
        <div>${pads.ti.isEmpty() ? '<div style="height:70px"></div>' : `<img style="max-height:70px" src="${pads.ti.toDataURL()}">`}<div style="border-top:1px solid #111827;padding-top:.35rem">${esc(responsavel)}</div></div>
        <div>${pads.col.isEmpty() ? '<div style="height:70px"></div>' : `<img style="max-height:70px" src="${pads.col.toDataURL()}">`}<div style="border-top:1px solid #111827;padding-top:.35rem">${esc(nome)}</div></div>
      </div>`;
  }

  function syncReportVisibility() {
    document.getElementById('reportSignedOnly').closest('.inventory-field').style.display = getValue('reportType') === 'TERMOS_ASSINADOS' ? '' : 'none';
  }

  function syncAlmoxDeliveryFields() {
    if (requestModalReadOnly) {
      const field = document.getElementById('responsavelAlmox');
      const clearBtn = document.getElementById('btnLimparAssAlmox');
      const padWrap = document.getElementById('assinaturaAlmoxCanvas').closest('.signature-pad');
      field.disabled = true;
      clearBtn.disabled = true;
      padWrap.style.opacity = '.85';
      padWrap.style.pointerEvents = 'none';
      return;
    }
    const delivered = getValue('statusSolicitacao') === 'ATENDIDO';
    const field = document.getElementById('responsavelAlmox');
    const clearBtn = document.getElementById('btnLimparAssAlmox');
    const padWrap = document.getElementById('assinaturaAlmoxCanvas').closest('.signature-pad');
    field.disabled = !delivered;
    clearBtn.disabled = !delivered;
    padWrap.style.opacity = delivered ? '1' : '.55';
    padWrap.style.pointerEvents = delivered ? 'auto' : 'none';
    if (!delivered) {
      field.value = '';
      pads.alm.clear();
    }
  }

  async function loadDomains() {
    state.domains = await apiGet('domains');
    state.requestDomains = await apiGet('request_form_domains');

    populateSelect('filtroStatus', state.domains.status, 'Todos os status');
    populateSelect('filtroTipo', state.domains.tipos, 'Todas as categorias');
    populateSelect('tipoEquipamento', state.domains.tipos, '', false);
    populateSelect('statusEquipamento', state.domains.status, '', false);
    populateSelect('condicaoEquipamento', state.domains.condicoes, '', false);
    populateSelect('reportType', state.domains.report_types, '', false);
    populateSelect('reportStatus', state.domains.status, 'Todos os status');
    populateSelect('reportTipo', state.domains.tipos, 'Todas as categorias');
    populateSelect('statusSolicitacao', state.domains.request_status, '', false);
    populateSelect('prioridadeSolicitacao', state.domains.request_priorities, '', false);
    populateSelect('centroCustoSolicitacao', state.requestDomains.cost_centers || [], 'Selecione o centro de custo');
    populateSelect('filialSolicitacao', state.requestDomains.filiais || [], 'Selecione a filial');
    populateSelect('responsavelDestino', [], 'Selecione o responsavel da proxima etapa');
    initCostCenterSuggest();
    initFilialSuggest();
    initResponsibleSuggest();

    setValue('reportType', state.domains.report_types?.[0]?.valor || 'EM_USO');
    setValue('prioridadeSolicitacao', 'MEDIA');
    setValue('statusSolicitacao', 'SOLICITADO');
    syncReportVisibility();
    syncAlmoxDeliveryFields();
  }

  async function loadDashboard() {
    const data = await apiGet('dashboard');
    [['kpiTotal', data.total], ['kpiUso', data.em_uso], ['kpiEstoque', data.em_estoque], ['kpiManutencao', data.manutencao], ['kpiGarantia', data.garantia_proxima]].forEach(([id, value]) => {
      document.getElementById(id).textContent = Number(value || 0).toLocaleString('pt-BR');
    });
  }

  async function loadTable() {
    const rows = await apiGet('list', { q: getValue('filtroBusca'), status: getValue('filtroStatus'), tipo: getValue('filtroTipo'), responsavel: getValue('filtroResponsavel') });
    document.getElementById('inventarioTbody').innerHTML = rows?.length ? rows.map((row) => `
      <tr>
        <td class="ps-3 fw-bold">${esc(row.COD_PATRIMONIO || '—')}</td>
        <td><div class="fw-semibold">${esc(row.NOME_EQUIPAMENTO || '—')}</div><div class="text-muted small">${esc([row.MARCA, row.MODELO, row.NUMERO_SERIE].filter(Boolean).join(' • ') || 'Sem complemento')}</div></td>
        <td>${esc(row.TIPO_LABEL || row.TIPO || '—')}</td>
        <td>${badge(row.STATUS, row.STATUS_LABEL)}</td>
        <td><div>${esc(row.NOME_USUARIO || 'Disponivel')}</div><div class="text-muted small">${esc(row.LOGIN_USUARIO || row.DEPARTAMENTO || 'Sem vinculo')}</div></td>
        <td>${esc(row.LOCALIZACAO || '—')}</td>
        <td>${esc(fmtDate(row.GARANTIA_ATE))}<div class="text-muted small">${esc(row.ALERTA_GARANTIA_LABEL || 'Sem alerta')}</div></td>
        <td>${esc(row.ALTERADO_EM_FMT || '—')}</td>
        <td class="text-end pe-3"><div class="d-inline-flex gap-2 flex-wrap justify-content-end"><button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="openEditModal(${row.ID_EQUIPAMENTO})">Editar</button><button class="btn btn-sm btn-outline-primary rounded-pill" onclick="openTermModal(${row.ID_EQUIPAMENTO})">Termo</button><button class="btn btn-sm btn-outline-primary rounded-pill" onclick="openHistoryModal(${row.ID_EQUIPAMENTO}, '${esc(row.COD_PATRIMONIO || '')}')">Historico</button></div></td>
      </tr>`).join('') : '<tr><td colspan="9" class="inventory-empty">Nenhum item encontrado com os filtros atuais.</td></tr>';
  }

  async function loadRequests() {
    const rows = await apiGet('list_requests');
    document.getElementById('solicitacoesTbody').innerHTML = rows?.length ? rows.map((row) => `
      <tr>
        <td class="ps-3 fw-bold">${esc(row.PROTOCOLO || '—')}</td>
        <td><div>${esc(row.SOLICITANTE_NOME || '—')}</div><div class="text-muted small">${esc(row.CENTRO_CUSTO || row.SETOR_SOLICITANTE || '')}</div></td>
        <td><div class="fw-semibold">${esc(row.ITEM_SOLICITADO || '—')}</div><div class="text-muted small">${esc(row.FILIAL || row.LOCAL_ENTREGA || '')}</div></td>
        <td><div>${esc(row.RESPONSAVEL_DESTINO || '—')}</div><div class="text-muted small">${esc(row.RESPONSAVEL_ALMOX || '')}</div></td>
        <td>${esc(row.QUANTIDADE || '—')}</td>
        <td>${badge(row.STATUS, row.STATUS_LABEL)}</td>
        <td>${esc(row.DATA_NECESSIDADE_FMT || '—')}</td>
        <td>${esc(row.ASSINATURAS_LABEL || '—')}</td>
        <td>${esc(row.CRIADO_EM_FMT || '—')}</td>
        <td class="text-end pe-3"><button class="btn btn-sm btn-outline-success rounded-pill" onclick="openRequestModal(${row.ID_SOLICITACAO})">Abrir</button></td>
      </tr>`).join('') : '<tr><td colspan="10" class="inventory-empty">Nenhuma solicitacao cadastrada.</td></tr>';
  }

  function equipamentoPayload() {
    return {
      id_equipamento: getValue('equipamentoId'),
      cod_patrimonio: getValue('patrimonio'),
      nome_equipamento: getValue('nomeEquipamento'),
      tipo: getValue('tipoEquipamento'),
      status: getValue('statusEquipamento'),
      marca: getValue('marcaEquipamento'),
      modelo: getValue('modeloEquipamento'),
      numero_serie: getValue('numeroSerie'),
      condicao: getValue('condicaoEquipamento'),
      login_usuario: getValue('loginUsuario'),
      nome_usuario: getValue('nomeUsuario'),
      cpf_usuario: getValue('cpfUsuario'),
      departamento: getValue('departamento'),
      localizacao: getValue('localizacao'),
      data_aquisicao: getValue('dataAquisicao'),
      garantia_ate: getValue('garantiaAte'),
      valor_aquisicao: getValue('valorAquisicao'),
      ip_equipamento: getValue('ipEquipamento'),
      fornecedor: getValue('fornecedorEquipamento'),
      nota_fiscal: getValue('notaFiscal'),
      itens_entregues: getValue('itensEntregues'),
      observacao: getValue('observacaoEquipamento'),
    };
  }

  async function saveEquip(openTerm = false) {
    try {
      const saved = await apiPost('save', equipamentoPayload());
      modal.equip.hide();
      await Promise.all([loadDashboard(), loadTable()]);
      toast('Item salvo com sucesso.', 'success');
      if (openTerm) openTermModal(saved.ID_EQUIPAMENTO);
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  async function openEditModal(id) {
    try {
      const item = await apiGet('get', { id });
      document.getElementById('formEquipamento').reset();
      Object.entries({
        equipamentoId: item.ID_EQUIPAMENTO,
        patrimonio: item.COD_PATRIMONIO,
        nomeEquipamento: item.NOME_EQUIPAMENTO,
        tipoEquipamento: item.TIPO,
        statusEquipamento: item.STATUS,
        marcaEquipamento: item.MARCA,
        modeloEquipamento: item.MODELO,
        numeroSerie: item.NUMERO_SERIE,
        condicaoEquipamento: item.CONDICAO,
        loginUsuario: item.LOGIN_USUARIO,
        nomeUsuario: item.NOME_USUARIO,
        cpfUsuario: cpf(item.CPF_USUARIO),
        departamento: item.DEPARTAMENTO,
        localizacao: item.LOCALIZACAO,
        dataAquisicao: item.DATA_AQUISICAO,
        garantiaAte: item.GARANTIA_ATE,
        valorAquisicao: item.VALOR_AQUISICAO,
        ipEquipamento: item.IP_EQUIPAMENTO,
        fornecedorEquipamento: item.FORNECEDOR,
        notaFiscal: item.NOTA_FISCAL,
        itensEntregues: item.ITENS_ENTREGUES,
        observacaoEquipamento: item.OBSERVACAO,
      }).forEach(([key, value]) => setValue(key, value));
      document.getElementById('modalEquipamentoTitulo').textContent = 'Editar item';
      document.getElementById('modalEquipamentoSubtitulo').textContent = `Patrimonio ${item.COD_PATRIMONIO || ''} • ${item.NOME_EQUIPAMENTO || ''}`;
      modal.equip.show();
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  async function openHistoryModal(id, patrimonio) {
    document.getElementById('historicoEquipamentoTitulo').textContent = patrimonio ? `Patrimonio ${patrimonio}` : 'Historico do item';
    document.getElementById('historicoLista').innerHTML = 'Carregando historico...';
    modal.hist.show();
    try {
      const rows = await apiGet('history', { id });
      document.getElementById('historicoLista').innerHTML = rows?.length ? rows.map((row) => `<div class="mb-3 p-3 border rounded-4"><strong>${esc(row.TIPO_MOVIMENTACAO_LABEL || row.TIPO_MOVIMENTACAO || 'Movimentacao')}</strong><div class="text-muted small">${esc(row.CRIADO_EM_FMT || 'Sem data')} • ${esc(row.CRIADO_POR || 'Sistema')}</div><div class="mt-2">${esc(row.DESCRICAO_RESUMO || '')}</div>${row.OBSERVACAO ? `<div class="text-muted small mt-2">${esc(row.OBSERVACAO)}</div>` : ''}</div>`).join('') : '<div class="inventory-empty">Nenhuma movimentacao registrada ate agora.</div>';
    } catch (error) {
      document.getElementById('historicoLista').innerHTML = `<div class="inventory-empty text-danger">${esc(error.message)}</div>`;
    }
  }

  async function openTermModal(id) {
    try {
      state.term = await apiGet('get_term', { id });
      const equip = state.term.equipamento || {};
      const term = state.term.termo || {};
      Object.entries({
        termoEquipamentoId: equip.ID_EQUIPAMENTO || id,
        termoNomeColaborador: term.NOME_COLABORADOR || equip.NOME_USUARIO,
        termoCpfColaborador: cpf(term.CPF_COLABORADOR || equip.CPF_USUARIO),
        termoSetorColaborador: term.SETOR_COLABORADOR || equip.DEPARTAMENTO,
        termoCidadeEmissao: term.CIDADE_EMISSAO || 'Sao Paulo',
        termoData: term.DATA_TERMO || today(),
        termoResponsavelTi: term.RESPONSAVEL_TI || (window.MG_USER || ''),
        termoItensEntregues: equip.ITENS_ENTREGUES || `${[equip.NUMERO_SERIE, equip.COD_PATRIMONIO].filter(Boolean).join(' | ')} - ${equip.NOME_EQUIPAMENTO || ''} - ${equip.VALOR_AQUISICAO || ''} - 1`,
      }).forEach(([key, value]) => setValue(key, value));
      document.getElementById('termoSubtitulo').textContent = `Patrimonio ${equip.COD_PATRIMONIO || ''} • ${equip.NOME_EQUIPAMENTO || ''}`;
      pads.col.clear();
      pads.ti.clear();
      if (term.ASSINATURA_COLABORADOR) pads.col.load(term.ASSINATURA_COLABORADOR);
      if (term.ASSINATURA_TI) pads.ti.load(term.ASSINATURA_TI);
      modal.term.show();
      renderTerm();
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  async function saveTerm() {
    try {
      await apiPost('save_term', {
        id_equipamento: getValue('termoEquipamentoId'),
        nome_colaborador: getValue('termoNomeColaborador'),
        cpf_colaborador: getValue('termoCpfColaborador'),
        setor_colaborador: getValue('termoSetorColaborador'),
        cidade_emissao: getValue('termoCidadeEmissao'),
        data_termo: getValue('termoData'),
        responsavel_ti: getValue('termoResponsavelTi'),
        assinatura_colaborador: pads.col.isEmpty() ? '' : pads.col.toDataURL(),
        assinatura_ti: pads.ti.isEmpty() ? '' : pads.ti.toDataURL(),
        termo_html: document.getElementById('termoPreview').innerHTML,
      });
      toast('Termo assinado salvo com sucesso.', 'success');
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  function printTerm() {
    const popup = window.open('', '_blank', 'width=980,height=900');
    if (!popup) return;
    popup.document.write(`<html><head><title>Termo de Responsabilidade</title><style>body{font-family:Arial,sans-serif;margin:0;padding:12mm}.term-table{width:100%;border-collapse:collapse}.term-table th,.term-table td{border:1px solid #b6c2cf;padding:6px;font-size:11px}</style></head><body>${document.getElementById('termoPreview').innerHTML}</body></html>`);
    popup.document.close();
    popup.focus();
    popup.print();
  }

  async function generateReport() {
    try {
      const report = await apiGet('report', {
        report_type: getValue('reportType'),
        signed_only: getValue('reportSignedOnly'),
        status: getValue('reportStatus'),
        tipo: getValue('reportTipo'),
        responsavel: getValue('reportResponsavel'),
        localizacao: getValue('reportLocalizacao'),
        date_from: getValue('reportDateFrom'),
        date_to: getValue('reportDateTo'),
      });
      state.report = report;
      document.getElementById('reportCard').style.display = '';
      document.getElementById('reportTitle').textContent = report.meta?.title || 'Relatorio';
      document.getElementById('reportSubtitle').textContent = report.meta?.subtitle || '';
      document.getElementById('reportGeneratedAt').textContent = report.meta?.generated_at || '-';
      document.getElementById('reportThead').innerHTML = (report.columns || []).map((column, index) => `<th class="${index === 0 ? 'ps-3' : ''}">${esc(column.label || column.key || 'Coluna')}</th>`).join('');
      document.getElementById('reportTbody').innerHTML = (report.rows || []).length ? report.rows.map((row) => `<tr>${(report.columns || []).map((column, index) => `<td class="${index === 0 ? 'ps-3' : ''}">${esc(column.format === 'currency' ? fmtMoney(row[column.key]) : (row[column.key] ?? '—'))}</td>`).join('')}</tr>`).join('') : '<tr><td class="inventory-empty">Nenhum dado encontrado.</td></tr>';
      modal.rep.hide();
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  function exportCsv() {
    if (!state.report) {
      toast('Gere um relatorio antes de exportar.', 'warning');
      return;
    }
    const cols = state.report.columns || [];
    const rows = state.report.rows || [];
    const csv = ['\uFEFF' + cols.map((col) => `"${String(col.label || col.key || '').replace(/"/g, '""')}"`).join(';'), ...rows.map((row) => cols.map((col) => `"${String(col.format === 'currency' ? fmtMoney(row[col.key]) : (row[col.key] ?? '')).replace(/"/g, '""')}"`).join(';'))].join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    link.download = `${state.report.meta?.filename || 'relatorio'}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  }

  function resetRequestForm() {
    document.getElementById('formSolicitacao').reset();
    setRequestReadOnly(false);
    setValue('solicitacaoId', '');
    setValue('solicitanteLogin', window.MG_USER || '');
    setValue('dataNecessidade', today());
    setValue('prioridadeSolicitacao', 'MEDIA');
    setValue('statusSolicitacao', 'SOLICITADO');
    pads.sol.clear();
    pads.alm.clear();
    populateSelect('responsavelDestino', [], 'Selecione o responsavel da proxima etapa');
    initResponsibleSuggest();
    syncAlmoxDeliveryFields();
  }

  function requestPayload() {
    const itens = getValue('itensSolicitados');
    const firstItem = itens.split(';').map((item) => item.trim()).filter(Boolean)[0] || '';
    return {
      id_solicitacao: getValue('solicitacaoId'),
      solicitante_nome: getValue('solicitanteNome'),
      solicitante_login: getValue('solicitanteLogin'),
      solicitante_cpf: getValue('solicitanteCpf'),
      setor_solicitante: getValue('solicitanteSetor'),
      centro_custo: getValue('centroCustoSolicitacao'),
      filial: getValue('filialSolicitacao'),
      item_solicitado: firstItem,
      itens_solicitados: itens,
      descricao_item: '',
      quantidade: 1,
      unidade_medida: '',
      local_entrega: getValue('localEntrega'),
      data_necessidade: getValue('dataNecessidade'),
      prioridade: getValue('prioridadeSolicitacao'),
      status: getValue('statusSolicitacao'),
      justificativa: getValue('justificativaSolicitacao'),
      observacao: getValue('observacaoSolicitacao'),
      responsavel_destino: getValue('responsavelDestino'),
      responsavel_almox: getValue('responsavelAlmox'),
      assinatura_solicitante: pads.sol.isEmpty() ? '' : pads.sol.toDataURL(),
      assinatura_almox: pads.alm.isEmpty() ? '' : pads.alm.toDataURL(),
    };
  }

  async function openRequestModal(id) {
    resetRequestForm();
    if (!id) {
      modal.req.show();
      return;
    }
    try {
      const row = await apiGet('get_request', { id });
      Object.entries({
        solicitacaoId: row.ID_SOLICITACAO,
        solicitanteNome: row.SOLICITANTE_NOME,
        solicitanteLogin: row.SOLICITANTE_LOGIN,
        solicitanteCpf: cpf(row.SOLICITANTE_CPF),
        solicitanteSetor: row.SETOR_SOLICITANTE,
        itensSolicitados: row.ITENS_SOLICITADOS,
        localEntrega: row.LOCAL_ENTREGA,
        dataNecessidade: row.DATA_NECESSIDADE,
        prioridadeSolicitacao: row.PRIORIDADE,
        statusSolicitacao: row.STATUS,
        justificativaSolicitacao: row.JUSTIFICATIVA,
        observacaoSolicitacao: row.OBSERVACAO,
        responsavelAlmox: row.RESPONSAVEL_ALMOX,
      }).forEach(([key, value]) => setValue(key, value));
      setSelectValue('centroCustoSolicitacao', row.SEQ_CENTRO_RESULTADO ? `${row.CENTRO_CUSTO}|${row.SEQ_CENTRO_RESULTADO}` : row.CENTRO_CUSTO);
      setSelectValue('filialSolicitacao', row.FILIAL);
      await loadResponsibleOptions();
      setSelectValue('responsavelDestino', row.RESPONSAVEL_DESTINO);
      setRequestReadOnly(true);
      document.getElementById('modalSolicitacaoTitulo').textContent = `Requisicao ${row.PROTOCOLO || ''}`;
      document.getElementById('modalSolicitacaoSubtitulo').textContent = `Criada em ${row.CRIADO_EM_FMT || '-'}${row.ALTERADO_EM_FMT ? ` • Atualizada em ${row.ALTERADO_EM_FMT}` : ''}`;
      const requestModalEl = document.getElementById('modalSolicitacao');
      requestModalEl.addEventListener('shown.bs.modal', function handleShown() {
        requestModalEl.removeEventListener('shown.bs.modal', handleShown);
        pads.sol.resize();
        pads.alm.resize();
        if (row.ASSINATURA_SOLICITANTE) pads.sol.load(row.ASSINATURA_SOLICITANTE);
        if (row.ASSINATURA_ALMOX && row.STATUS === 'ATENDIDO') pads.alm.load(row.ASSINATURA_ALMOX);
        syncAlmoxDeliveryFields();
      });
      modal.req.show();
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  async function saveRequest() {
    try {
      await apiPost('save_request', requestPayload());
      modal.req.hide();
      await loadRequests();
      toast('Solicitacao ao almoxarifado salva com sucesso.', 'success');
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  async function boot() {
    try {
      await loadDomains();
      await Promise.all([loadDashboard(), loadTable(), loadRequests()]);
    } catch (error) {
      toast(error.message, 'danger');
    }
  }

  document.getElementById('btnNovoEquipamento').addEventListener('click', () => {
    document.getElementById('formEquipamento').reset();
    setValue('equipamentoId', '');
    document.getElementById('modalEquipamentoTitulo').textContent = 'Novo item';
    document.getElementById('modalEquipamentoSubtitulo').textContent = 'Preencha os dados principais do ativo.';
    modal.equip.show();
  });
  document.getElementById('btnSalvarEquipamento').addEventListener('click', () => saveEquip(false));
  document.getElementById('btnSalvarGerarTermo').addEventListener('click', () => saveEquip(true));
  document.getElementById('btnAtualizarInventario').addEventListener('click', boot);
  document.getElementById('btnAbrirRelatorios').addEventListener('click', () => modal.rep.show());
  document.getElementById('btnReconfigurarRelatorio').addEventListener('click', () => modal.rep.show());
  document.getElementById('btnGerarRelatorio').addEventListener('click', generateReport);
  document.getElementById('btnExportarRelatorioCsv').addEventListener('click', exportCsv);
  document.getElementById('btnNovaSolicitacao').addEventListener('click', () => openRequestModal());
  document.getElementById('btnSalvarSolicitacao').addEventListener('click', saveRequest);
  document.getElementById('btnSalvarTermo').addEventListener('click', saveTerm);
  document.getElementById('btnImprimirTermo').addEventListener('click', printTerm);
  document.getElementById('btnLimparAssColab').addEventListener('click', () => pads.col.clear());
  document.getElementById('btnLimparAssTi').addEventListener('click', () => pads.ti.clear());
  document.getElementById('btnLimparAssSolicitante').addEventListener('click', () => pads.sol.clear());
  document.getElementById('btnLimparAssAlmox').addEventListener('click', () => pads.alm.clear());
  document.getElementById('cpfUsuario').addEventListener('input', (event) => { event.target.value = cpf(event.target.value); });
  document.getElementById('solicitanteCpf').addEventListener('input', (event) => { event.target.value = cpf(event.target.value); });
  document.getElementById('reportType').addEventListener('change', syncReportVisibility);
  document.getElementById('statusSolicitacao').addEventListener('change', syncAlmoxDeliveryFields);
  document.getElementById('centroCustoSolicitacao').addEventListener('change', loadResponsibleOptions);
  ['filtroStatus', 'filtroTipo'].forEach((id) => document.getElementById(id).addEventListener('change', loadTable));
  ['filtroBusca', 'filtroResponsavel'].forEach((id) => document.getElementById(id).addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); loadTable(); } }));
  ['termoNomeColaborador', 'termoCpfColaborador', 'termoSetorColaborador', 'termoCidadeEmissao', 'termoData', 'termoResponsavelTi', 'termoItensEntregues'].forEach((id) => document.getElementById(id).addEventListener('input', () => { if (id === 'termoCpfColaborador') document.getElementById(id).value = cpf(document.getElementById(id).value); renderTerm(); }));
  document.getElementById('modalTermo').addEventListener('shown.bs.modal', () => { pads.col.resize(); pads.ti.resize(); renderTerm(); });
  document.getElementById('modalSolicitacao').addEventListener('shown.bs.modal', () => { pads.sol.resize(); pads.alm.resize(); syncAlmoxDeliveryFields(); });

  window.openEditModal = openEditModal;
  window.openHistoryModal = openHistoryModal;
  window.openTermModal = openTermModal;
  window.openRequestModal = openRequestModal;

  boot();
});
