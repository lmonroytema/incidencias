import { apiFetch, getToken, getConsultantInfo, showToast, BASE_API } from './config.js';

const tbody = document.querySelector('#incidentsTable tbody');
const info = document.getElementById('consultantInfo');

function renderConsultant(){
  const t = getToken();
  const c = getConsultantInfo();
  info.textContent = t ? `Autenticado como ${c?.name||'Consultor'} (${c?.email||''})` : 'No autenticado';
}
renderConsultant();

function rowStatusSelect(current){
  const s = document.createElement('select');
  ['Pendiente','Evaluado','Atendido','En revisión','Resuelto','Cerrado'].forEach(v=>{
    const o=document.createElement('option'); o.value=v; o.textContent=v; if(v===current) o.selected=true; s.appendChild(o);
  });
  return s;
}

let page = 1;
let lastPage = 1;
let total = 0;

async function fetchIncidents(){
  const status = document.getElementById('f_status').value;
  const category = document.getElementById('f_category').value;
  const urgency = document.getElementById('f_urgency').value;
  const dniLike = document.getElementById('f_dni')?.value?.trim() || '';
  const nameLike = document.getElementById('f_name')?.value?.trim() || '';
  const qs = new URLSearchParams();
  if(status) qs.set('status', status);
  if(category) qs.set('category', category);
  if(urgency) qs.set('urgency', urgency);
  if(dniLike) qs.set('dni_number_like', dniLike);
  if(nameLike) qs.set('full_name_like', nameLike);
  qs.set('page', String(page));
  try{
    const res = await apiFetch(`/incidencias?${qs.toString()}`);
    const rows = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    page = Number(res?.current_page || page || 1);
    lastPage = Number(res?.last_page || 1);
    total = Number(res?.total || rows.length || 0);
    tbody.innerHTML = '';
    rows.forEach(inc => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${(inc.dni_type||'')+' '+(inc.dni_number||'')}</td>
        <td>${inc.full_name||''}</td>
        <td>${inc.category||''}</td>
        <td>${inc.urgency||''}</td>
        <td><span class="badge">${inc.status||'Pendiente'}</span></td>
        <td class="flex"></td>
      `;
      const actionTd = tr.querySelector('td:last-child');
      const viewBtn = document.createElement('button');
      viewBtn.className = 'btn secondary';
      viewBtn.title = 'Ver detalle';
      viewBtn.setAttribute('aria-label', 'Ver detalle');
      viewBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="currentColor"/></svg>';
      viewBtn.addEventListener('click', ()=> showIncidentDetailById(inc.id));
      actionTd.appendChild(viewBtn);

      const evalBtn = document.createElement('button');
      evalBtn.className = 'btn';
      evalBtn.title = 'Evaluar (cambiar estado y adjuntar)';
      evalBtn.setAttribute('aria-label', 'Evaluar incidencia');
      evalBtn.style.marginLeft = '8px';
      evalBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm18.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 2-1.66Z" fill="currentColor"/></svg>';
      evalBtn.addEventListener('click', ()=> showIncidentEvaluateById(inc.id));
      actionTd.appendChild(evalBtn);

      const delBtn = document.createElement('button');
      delBtn.className = 'btn danger';
      delBtn.title = 'Eliminar incidencia';
      delBtn.setAttribute('aria-label', 'Eliminar incidencia');
      delBtn.style.marginLeft = '8px';
      delBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 3h6a1 1 0 0 1 1 1v1h4a1 1 0 1 1 0 2h-1v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7H4a1 1 0 1 1 0-2h4V4a1 1 0 0 1 1-1Zm1 3V4h4v2h-4Zm-2 4a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0v-7Zm4 0a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0v-7Zm4 0a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0v-7Z" fill="currentColor"/></svg>';
      delBtn.addEventListener('click', async ()=>{
        if(!confirm(`¿Desea borrar la incidencia #${inc.id}? Esta acción no se puede deshacer.`)) return;
        try{
          await apiFetch(`/incidencias/${inc.id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dni_type: inc.dni_type, dni_number: inc.dni_number })
          });
          showToast('Incidencia eliminada');
          // Re-cargar manteniendo filtros/paginación actuales
          await fetchIncidents();
        }catch(err){
          const msg = err?.data?.message || 'No se pudo eliminar la incidencia';
          showToast(msg);
        }
      });
      actionTd.appendChild(delBtn);
      tbody.appendChild(tr);
    });
    // Render pager
    const infoEl = document.getElementById('pageInfo');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    if(infoEl) infoEl.textContent = `Página ${page} de ${lastPage} — Total ${total}`;
    if(prevBtn) prevBtn.disabled = page <= 1;
    if(nextBtn) nextBtn.disabled = page >= lastPage;
  }catch(err){
    const msg = err?.data?.message || 'Error obteniendo incidencias';
    showToast(msg);
  }
}

function showIncidentDetailById(id){
  apiFetch(`/incidencias/${id}`)
    .then(inc => {
      const modal = document.getElementById('detailModal');
      const content = document.getElementById('detailContent');
      const closeBtn = document.getElementById('btnDetailClose');
      const attBox = document.getElementById('detailAttachments');
      if(!content || !modal) return;
      const created = inc?.created_at ? new Date(inc.created_at).toLocaleString() : '';
      const apps = Array.isArray(inc?.apps) ? inc.apps.join(', ') : (inc?.apps || '');
      content.innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
          <div><strong>Documento:</strong> ${(inc?.dni_type||'')+' '+(inc?.dni_number||'')}</div>
          <div><strong>Nombre:</strong> ${inc?.full_name||''}</div>
          <div><strong>Categoría:</strong> ${inc?.category||''}</div>
          <div><strong>Urgencia:</strong> ${inc?.urgency||''}</div>
          <div><strong>Estado actual:</strong> ${inc?.status||'Pendiente'}</div>
          <div><strong>Fecha:</strong> ${created}</div>
          <div><strong>Área:</strong> ${inc?.area_name||''}</div>
          <div><strong>Correo:</strong> ${inc?.corporate_email||''}</div>
          <div><strong>Hostname:</strong> ${inc?.hostname||''}</div>
          <div><strong>SO:</strong> ${inc?.os||''}</div>
          <div><strong>Office:</strong> ${inc?.office_version||''}</div>
          <div style="grid-column:1/-1"><strong>Apps:</strong> ${apps}</div>
          <div style="grid-column:1/-1"><strong>Descripción:</strong><br>${(inc?.description||'').replace(/</g,'&lt;')}</div>
        </div>
      `;

      if (attBox) {
        const collabAtts = Array.isArray(inc?.attachments) ? inc.attachments : [];
        const consultantAtts = Array.isArray(inc?.consultant_attachments) ? inc.consultant_attachments : [];

        const renderList = (list, title) => {
          if (!list || list.length === 0) {
            return `<div class="muted">${title}: sin archivos</div>`;
          }
          const items = list.map(a => {
            const isImg = (a?.mime||'').startsWith('image/');
            const url = `${BASE_API}/attachments/${a.id}`;
            const label = a?.filename || 'archivo';
            const view = isImg
              ? `<a href="${url}" target="_blank" class="btn secondary" title="Ver imagen">Ver imagen</a>`
              : `<a href="${url}" target="_blank" class="btn secondary" title="Descargar">Descargar</a>`;
            return `<div class="flex" style="gap:8px; align-items:center;">
                      <span>${label}</span>
                      ${view}
                    </div>`;
          }).join('');
          return `<div><strong>${title}:</strong></div>${items}`;
        };

        attBox.innerHTML = [
          renderList(collabAtts, 'Adjuntos del colaborador'),
          '<hr style="margin:8px 0; opacity:0.25;">',
          renderList(consultantAtts, 'Evidencias del consultor')
        ].join('');
      }

      modal.classList.remove('hidden');
      const onClose = ()=>{ modal.classList.add('hidden'); closeBtn?.removeEventListener('click', onClose); };
      closeBtn?.addEventListener('click', onClose);
    })
    .catch(err => {
      const msg = err?.data?.message || 'Error obteniendo detalle';
      showToast(msg);
    });
}

// Modal de evaluación (cambio de estado + evidencia)
function showIncidentEvaluateById(id){
  apiFetch(`/incidencias/${id}`)
    .then(inc => {
      const modal = document.getElementById('evalModal');
      const statusSel = document.getElementById('evalStatus');
      const evidenceInput = document.getElementById('evalEvidence');
      const saveBtn = document.getElementById('btnEvalSave');
      const closeBtn = document.getElementById('btnEvalClose');
      if(!modal) return;

      if (statusSel) {
        statusSel.value = inc?.status || 'Pendiente';
        if (!['Pendiente','Evaluado','Atendido','En revisión','Resuelto','Cerrado'].includes(statusSel.value)) statusSel.value = 'Pendiente';
      }

      // Siempre iniciar sin archivo seleccionado
      if (evidenceInput) evidenceInput.value = '';

      const handleStatusChange = ()=>{ if (evidenceInput) evidenceInput.value = ''; };
      statusSel?.addEventListener('change', handleStatusChange);

      modal.classList.remove('hidden');
      const onClose = ()=>{ 
        modal.classList.add('hidden'); 
        closeBtn?.removeEventListener('click', onClose); 
        saveBtn?.removeEventListener('click', onSave); 
        statusSel?.removeEventListener('change', handleStatusChange);
        if (evidenceInput) evidenceInput.value = '';
      };
      closeBtn?.addEventListener('click', onClose);

      const onSave = async ()=>{
        try{
          const fd = new FormData();
          fd.set('status', statusSel?.value || 'Pendiente');
          const file = evidenceInput?.files?.[0];
          if (file) fd.append('attachments[]', file);
          fd.set('_method','PUT');
          await apiFetch(`/incidencias/${id}`, { method: 'POST', body: fd });
          showToast('Incidencia actualizada');
          modal.classList.add('hidden');
          closeBtn?.removeEventListener('click', onClose);
          saveBtn?.removeEventListener('click', onSave);
          statusSel?.removeEventListener('change', handleStatusChange);
          if (evidenceInput) evidenceInput.value = '';
          await fetchIncidents();
        }catch(err){
          const msg = err?.data?.message || 'No se pudo actualizar la incidencia';
          showToast(msg);
        }
      };
      saveBtn?.addEventListener('click', onSave);
    })
    .catch(err => {
      const msg = err?.data?.message || 'Error abriendo evaluación';
      showToast(msg);
    });
}

document.getElementById('btnBuscar')?.addEventListener('click', fetchIncidents);
document.getElementById('btnLimpiar')?.addEventListener('click', ()=>{
  document.getElementById('f_status').value='';
  document.getElementById('f_category').value='';
  document.getElementById('f_urgency').value='';
  if(document.getElementById('f_dni')) document.getElementById('f_dni').value='';
  if(document.getElementById('f_name')) document.getElementById('f_name').value='';
  page = 1; fetchIncidents();
});

document.getElementById('prevPage')?.addEventListener('click', ()=>{ if(page>1){ page--; fetchIncidents(); } });
document.getElementById('nextPage')?.addEventListener('click', ()=>{ if(page<lastPage){ page++; fetchIncidents(); } });

fetchIncidents();