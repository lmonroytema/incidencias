// Simple UI config for Incidencias
// Detect app root prefix dynamically (supports subfolders like /incidencias)
const PREFIX = (()=>{
  try {
    const p = new URL(import.meta.url).pathname;
    const parts = p.split('/ui/');
    return parts[0] || '';
  } catch {
    // Fallback: derive from location
    const loc = (typeof window !== 'undefined' ? window.location.pathname : '') || '';
    const parts = loc.split('/ui/');
    return parts[0] || '';
  }
})();
export const BASE_API = PREFIX + '/api'; // relative to Laravel public root

export const tokenKey = 'apiToken';
export const consultantKey = 'consultantInfo';

export function getToken(){
  try { return localStorage.getItem(tokenKey) || ''; } catch { return ''; }
}
export function setToken(t){
  try { localStorage.setItem(tokenKey, t || ''); } catch {}
}
export function setConsultantInfo(info){
  try { localStorage.setItem(consultantKey, JSON.stringify(info||{})); } catch {}
}
export function getConsultantInfo(){
  try { return JSON.parse(localStorage.getItem(consultantKey)||'{}'); } catch { return {}; }
}

export async function apiFetch(path, opts={}){
  const headers = {
    'Accept': 'application/json',
    ...(opts.headers||{})
  };
  const t = getToken();
  if (t) {
    headers['X-API-TOKEN'] = t;
    // En algunos hostings/proxies se pierden headers personalizados.
    // Añadimos también Authorization: Bearer para mayor compatibilidad.
    headers['Authorization'] = `Bearer ${t}`;
  }
  const res = await fetch(BASE_API + path, { ...opts, headers });
  const ct = res.headers.get('content-type') || '';
  const isJson = ct.includes('application/json');
  const data = isJson ? await res.json() : await res.text();
  if (!res.ok) throw { status: res.status, data };
  return data;
}

export function showToast(msg){
  const t = document.createElement('div');
  t.className = 'toast';
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(()=>{ t.remove(); }, 3000);
}