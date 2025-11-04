import { apiFetch, showToast } from '/ui/config.js';

const categories = [
  'Conectividad y autenticación Office 365',
  'Sincronización OneDrive/SharePoint',
  'Problemas con Outlook (envío/recepción)',
  'Microsoft Teams',
  'Configuración de perfil/licencias',
  'Otros',
];
const urgencies = ['Crítico', 'Alto', 'Medio', 'Bajo'];
const windowsVersions = [
  'Windows 11 Pro',
  'Windows 11 Enterprise',
  'Windows 10 Pro',
  'Windows 10 Enterprise',
  'Windows 10 LTSC',
  'Windows Server 2019',
  'Windows Server 2022',
  'Otro',
];
const officeVersions = [
  'Microsoft 365 Apps',
  'Office 2021',
  'Office 2019',
  'Office 2016',
  'Office 2013',
  'Otro',
];

const el = (id) => document.getElementById(id);

// Tabs: Colaborador / Incidencia
const tabCol = document.getElementById('tabColaborador');
const tabInc = document.getElementById('tabIncidencia');
const paneCol = document.getElementById('paneColaborador');
const paneInc = document.getElementById('paneIncidencia');
let collaboratorValidated = false;
let editingIncidentId = null;
let equipmentLocked = false;
// Listado de incidencias del colaborador
const listWrapper = document.getElementById('incidentsListWrapper');
const listHint = document.getElementById('listHint');
const listTbody = document.querySelector('#collabIncidentsTable tbody');
function activateTab(which){
  if(which === 'col'){
    tabCol?.classList.add('active'); tabInc?.classList.remove('active');
    paneCol?.classList.remove('hidden'); paneInc?.classList.add('hidden');
  }else{
    tabInc?.classList.add('active'); tabCol?.classList.remove('active');
    paneInc?.classList.remove('hidden'); paneCol?.classList.add('hidden');
  }
}
tabCol?.addEventListener('click', ()=>activateTab('col'));
tabInc?.addEventListener('click', ()=>{ if(tabInc.disabled) return; activateTab('inc'); });

function toggleEquipmentLock(locked){
  equipmentLocked = !!locked;
  ['hostname','os','office_version'].forEach(id => {
    const node = el(id);
    if(node) node.disabled = equipmentLocked;
  });
}

function prefillIncidentForm(inc){
  try{
    if(inc?.category) el('category').value = inc.category;
    if(inc?.urgency) el('urgency').value = inc.urgency;
    el('started_at').value = inc?.started_at ? String(inc.started_at).slice(0,10) : '';
    el('hostname').value = inc?.hostname ? normalizeHost(String(inc.hostname)) : '';
    if(inc?.os) el('os').value = inc.os;
    if(inc?.office_version) el('office_version').value = inc.office_version;
    el('description').value = inc?.description || '';

    // Banner de adjuntos: mostrar conteo y ojo si hay imagen
    const info = el('currentAttachmentText');
    const eyeBtn = el('btnViewCurrentImage');
    const atts = Array.isArray(inc?.attachments) ? inc.attachments : [];
    const imgAtt = atts.find(a => String(a?.mime||'').startsWith('image/')) || null;
    const has = atts.length > 0;
    if(info){ info.textContent = has ? `Adjuntos: ${atts.length}` : 'Sin adjuntos'; }
    if(eyeBtn){
      const imgUrl = imgAtt ? `/api/attachments/${imgAtt.id}` : '';
      eyeBtn.style.display = imgUrl ? 'inline-flex' : 'none';
      eyeBtn.ondblclick = imgUrl ? (()=> showImageModal(imgUrl, `Incidencia #${inc.id}`)) : null;
      eyeBtn.title = 'Doble clic para ver imagen';
    }

    // Indicador inline junto al campo Adjuntos
    const inlineStatus = el('inlineImageStatus');
    const inlineEye = el('btnInlineViewImage');
    const inlineUrl = imgAtt ? `/api/attachments/${imgAtt.id}` : '';
    if(inlineStatus) inlineStatus.textContent = inlineUrl ? '' : 'No hay imagen';
    if(inlineEye){
      inlineEye.style.display = inlineUrl ? 'inline-flex' : 'none';
      inlineEye.ondblclick = inlineUrl ? (()=> showImageModal(inlineUrl, `Incidencia #${inc.id}`)) : null;
      inlineEye.title = 'Doble clic para ver imagen';
    }
  }catch{ /* ignore */ }
}

function resetIncidentForm(){
  el('started_at').value = '';
  el('description').value = '';
  el('attachments').value = '';
  // Limpiar banner de adjuntos
  const info = el('currentAttachmentText');
  const eyeBtn = el('btnViewCurrentImage');
  if(info) info.textContent = '';
  if(eyeBtn) { eyeBtn.style.display = 'none'; eyeBtn.ondblclick = null; }
  // Limpiar indicador inline
  const inlineStatus = el('inlineImageStatus');
  const inlineEye = el('btnInlineViewImage');
  if(inlineStatus) inlineStatus.textContent = 'No hay imagen';
  if(inlineEye) { inlineEye.style.display = 'none'; inlineEye.ondblclick = null; }
  if(!equipmentLocked){
    el('hostname').value = '';
    const osSel = el('os'); if(osSel) osSel.selectedIndex = 0;
    const offSel = el('office_version'); if(offSel) offSel.selectedIndex = 0;
  }
}

function resetAll(){
  // Reset colaborador
  el('dni_type').value = '1';
  el('dni_number').value = '';
  el('full_name').value = '';
  el('area_name').value = '';
  el('corporate_email').value = '';
  const banner = document.getElementById('collabFullNameHeader'); if(banner) banner.textContent = '';
  collaboratorValidated = false;
  if(tabInc){ tabInc.disabled = true; }
  activateTab('col');
  // Reset incidencia
  el('category').selectedIndex = 0;
  el('urgency').selectedIndex = 0;
  el('os').selectedIndex = 0;
  el('office_version').selectedIndex = 0;
  resetIncidentForm();
  editingIncidentId = null;
}

function showSuccessModal({ updated }){
  return new Promise((resolve)=>{
    const modal = el('successModal');
    const title = el('successTitle');
    const text = el('successText');
    const btnOk = el('btnSuccessOk');
    if(title) title.textContent = updated ? 'Actualización exitosa' : 'Registro exitoso';
    if(text) text.textContent = 'Se grabó satisfactoriamente.';
    modal?.classList.remove('hidden');
    const onOk = ()=>{ cleanup(); resolve('ok'); };
    function cleanup(){
      modal?.classList.add('hidden');
      btnOk?.removeEventListener('click', onOk);
    }
    btnOk?.addEventListener('click', onOk);
  });
}

function dniTypeText(code){ return code === '1' ? 'DNI' : code === '2' ? 'CE' : code; }
function formatDate(dt){
  try{ return String(dt).slice(0,19).replace('T',' '); }catch{ return ''; }
}
function formatDateOnly(dt){
  try{
    const s = String(dt||'');
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if(m) return `${m[3]}/${m[2]}/${m[1]}`;
    const d = new Date(s);
    if(!isNaN(d.getTime())){
      const dd = String(d.getDate()).padStart(2,'0');
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const yy = String(d.getFullYear());
      return `${dd}/${mm}/${yy}`;
    }
    return s.slice(0,10);
  }catch{ return ''; }
}
function renderIncidentList(rows){
  if(!listTbody) return;
  listTbody.innerHTML = '';
  const has = Array.isArray(rows) && rows.length > 0;
  if(listWrapper){ listWrapper.classList.toggle('hidden', !has); }
  if(listHint){ listHint.textContent = has ? '' : 'No hay incidencias registradas para este colaborador.'; }
  rows.forEach(inc => {
    const tr = document.createElement('tr');
    const imgAtt = (Array.isArray(inc.attachments)? inc.attachments : []).find(a => String(a?.mime||'').startsWith('image/')) || null;
    const imgUrl = imgAtt ? `/api/attachments/${imgAtt.id}` : '';
    const imgCell = imgUrl
      ? `<button class="btn secondary view-image" data-img-url="${imgUrl}" title="Doble clic para ver imagen" aria-label="Ver imagen">
           <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="currentColor"/></svg>
         </button>`
      : '<span class="muted">sin Imagen</span>';
    tr.innerHTML = `
      <td>${formatDateOnly(inc.started_at || inc.created_at)}</td>
      <td>${inc.category||''}</td>
      <td>${inc.urgency||''}</td>
      <td>${inc.status||'Pendiente'}</td>
      <td>${imgCell}</td>
      <td class="flex">
        <button class="btn secondary" data-action="edit" data-id="${inc.id}" title="Editar incidencia" aria-label="Editar incidencia">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm14.71-9.46a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z" fill="currentColor"/></svg>
          <span style="margin-left:6px">Editar</span>
        </button>
        <button class="btn danger" data-action="delete" data-id="${inc.id}" title="Eliminar incidencia" aria-label="Eliminar incidencia">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 3h6a1 1 0 0 1 1 1v1h4a1 1 0 1 1 0 2h-1v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7H4a1 1 0 1 1 0-2h4V4a1 1 0 0 1 1-1Zm1 3V4h4v2h-4Zm-2 4a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0v-7Zm4 0a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0v-7Zm4 0a1 1 0 1 1 2 0v7a1 1 0 1 1-2 0v-7Z" fill="currentColor"/></svg>
        </button>
      </td>
    `;
    listTbody.appendChild(tr);

    // Vincular doble clic en el ícono de ojo para abrir modal en grande
    const eyeBtn = tr.querySelector('button.view-image');
    if(eyeBtn && imgUrl){
      eyeBtn.addEventListener('dblclick', ()=> showImageModal(imgUrl, `Incidencia #${inc.id}`));
    }
  });
  listTbody.querySelectorAll('button[data-action="edit"]').forEach(btn => {
    btn.addEventListener('click', ()=>{
      const id = Number(btn.getAttribute('data-id'));
      const fallback = rows.find(r => r.id === id);
      // Para asegurar que cargamos adjuntos actuales, consultamos el detalle a la API
      apiFetch(`/incidencias/${id}`)
        .then(inc => {
          const full = inc || fallback || {};
          prefillIncidentForm(full);
          editingIncidentId = Number(full.id || id);
          activateTab('inc');
        })
        .catch(()=>{
          // Si falla el detalle, usamos el dato del listado
          if(fallback){
            prefillIncidentForm(fallback);
            editingIncidentId = fallback.id;
            activateTab('inc');
          }
        });
    });
  });

  // Acción: borrar incidencia
  listTbody.querySelectorAll('button[data-action="delete"]').forEach(btn => {
    btn.addEventListener('click', async ()=>{
      const id = Number(btn.getAttribute('data-id'));
      const dni_type = el('dni_type').value;
      const dni_number = el('dni_number').value.trim();
      if(!dni_number){ showToast('Ingrese número de documento'); return; }
      if(!confirm(`¿Desea borrar la incidencia #${id}? Esta acción no se puede deshacer.`)) return;
      try{
        await apiFetch(`/incidencias/${id}`, {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ dni_type, dni_number })
        });
        showToast('Incidencia eliminada');
        // Refrescar listado
        await loadIncidentList(dni_type, dni_number);
      }catch(err){
        const msg = err?.data?.message || 'No se pudo eliminar la incidencia';
        showToast(msg);
      }
    });
  });
}

// Mostrar/ocultar modal de imagen
function showImageModal(url, altTxt){
  const modal = el('imageModal');
  const img = el('previewImage');
  const btnClose = el('btnImageClose');
  if(!modal || !img) return;
  img.src = url;
  img.alt = altTxt || 'Adjunto';
  modal.classList.remove('hidden');
  const onClose = ()=>{
    modal.classList.add('hidden');
    img.src = '';
    btnClose?.removeEventListener('click', onClose);
    document.removeEventListener('keydown', onEsc);
    modal.removeEventListener('click', onBackdrop);
  };
  const onEsc = (e)=>{ if(e.key === 'Escape') onClose(); };
  const onBackdrop = (e)=>{ if(e.target === modal) onClose(); };
  btnClose?.addEventListener('click', onClose);
  document.addEventListener('keydown', onEsc);
  modal.addEventListener('click', onBackdrop);
}
async function loadIncidentList(dni_type, dni_number){
  try{
    const dniTxt = dniTypeText(dni_type);
    const res = await apiFetch(`/incidencias?dni_type=${encodeURIComponent(dniTxt)}&dni_number=${encodeURIComponent(dni_number)}`);
    const rows = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    renderIncidentList(rows);
    if(Array.isArray(rows) && rows.length > 0){
      toggleEquipmentLock(true);
      const base = rows[0];
      if(base){
        if(base?.hostname) el('hostname').value = normalizeHost(String(base.hostname));
        if(base?.os) el('os').value = base.os;
        if(base?.office_version) el('office_version').value = base.office_version;
      }
    } else {
      toggleEquipmentLock(false);
    }
  }catch(err){
    renderIncidentList([]);
    toggleEquipmentLock(false);
  }
}

function fillSelect(id, options){
  const s = el(id); s.innerHTML = '';
  options.forEach(o => {
    const opt = document.createElement('option');
    opt.value = o; opt.textContent = o; s.appendChild(opt);
  });
}

fillSelect('category', categories);
fillSelect('urgency', urgencies);
fillSelect('os', windowsVersions);
fillSelect('office_version', officeVersions);

// Máscara y validación de Hostname
const hostInput = el('hostname');
function normalizeHost(v){
  return v.toUpperCase().replace(/\s+/g,'');
}
function isValidHost(v){
  return /^(TL-LAP-\d{3}|PCR-LAP\d{5})$/.test(v);
}
hostInput?.addEventListener('input', ()=>{
  const v = normalizeHost(hostInput.value);
  hostInput.value = v;
});

document.getElementById('btnLookup')?.addEventListener('click', async () => {
  const dni_type = el('dni_type').value;
  const dni_number = el('dni_number').value.trim();
  if(!dni_number){ showToast('Ingrese número de documento'); return; }
  try{
    const data = await apiFetch(`/employees/lookup?dni_type=${encodeURIComponent(dni_type)}&dni_number=${encodeURIComponent(dni_number)}`);
    el('full_name').value = data.full_name || '';
    el('area_name').value = data.area_name || '';
    el('corporate_email').value = data.corporate_email || '';
    const banner = document.getElementById('collabFullNameHeader'); if(banner) banner.textContent = data.full_name || '';
    collaboratorValidated = true;
    if(tabInc) { tabInc.disabled = false; tabInc.classList.remove('disabled'); }
    showToast('Empleado encontrado');
    // Cargar el listado de incidencias del colaborador
    await loadIncidentList(dni_type, dni_number);
    activateTab('inc');
  }catch(err){
    const msg = err?.data?.message || 'No se encontró el empleado';
    showToast(msg);
  }
});

document.getElementById('btnSubmit')?.addEventListener('click', async () => {
  // Exigir colaborador validado desde API
  const fullName = el('full_name').value.trim();
  const areaName = el('area_name').value.trim();
  const corpEmail = el('corporate_email').value.trim();
  if(!fullName || !areaName || !corpEmail){
    showToast('Valide el colaborador para traer nombre, área y correo');
    activateTab('col');
    return;
  }
  const fd = new FormData();
  fd.append('dni_type', el('dni_type').value);
  fd.append('dni_number', el('dni_number').value.trim());
  fd.append('full_name', fullName);
  fd.append('area_name', areaName);
  fd.append('corporate_email', corpEmail);
  fd.append('category', el('category').value);
  fd.append('description', el('description').value.trim());
  fd.append('urgency', el('urgency').value);
  const hostname = normalizeHost(el('hostname').value.trim());
  if(hostname && !isValidHost(hostname)){
    showToast('Formato de Hostname inválido. Use TL-LAP-123 o PCR-LAP12345');
    return;
  }
  fd.append('hostname', hostname);
  fd.append('os', el('os').value.trim());
  fd.append('office_version', el('office_version').value.trim());
  const started = el('started_at').value;
  if(started) fd.append('started_at', started);
  const files = el('attachments').files;
  for(let i=0;i<files.length;i++){ fd.append('attachments[]', files[i]); }

  const submit = async (extra = {}) => {
    const body = fd;
    Object.entries(extra).forEach(([k,v]) => body.append(k, v));
    return await apiFetch('/incidencias', { method: 'POST', body });
  };

  try{
    const incident = editingIncidentId ? await submit({ edit_existing_id: String(editingIncidentId) }) : await submit();
    await showSuccessModal({ updated: !!editingIncidentId });
    editingIncidentId = null;
    // Mantener colaborador validado; limpiar formulario y refrescar listado
    resetIncidentForm();
    await loadIncidentList(el('dni_type').value, el('dni_number').value.trim());
    activateTab('inc');
  }catch(err){
    const vErrors = err?.data?.errors;
    if(vErrors && typeof vErrors === 'object'){
      const flat = Object.entries(vErrors).map(([k,v])=>{
        const text = Array.isArray(v) ? v.join(', ') : String(v||'');
        return `${k}: ${text}`;
      }).join(' | ');
      showToast(flat || 'Errores de validación');
    } else {
      const msg = err?.data?.message || 'Error al registrar incidencia';
      showToast(msg);
    }
  }
});

// Nueva incidencia: limpiar formulario y salir de modo edición
document.getElementById('btnNewIncident')?.addEventListener('click', ()=>{
  editingIncidentId = null;
  resetIncidentForm();
  activateTab('inc');
});