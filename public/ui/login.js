import { apiFetch, setToken, setConsultantInfo, showToast } from '/ui/config.js';

// Toggle de visibilidad de contraseña
const passwordInput = document.getElementById('password');
const togglePasswordBtn = document.getElementById('togglePassword');
togglePasswordBtn?.addEventListener('click', () => {
  const isHidden = passwordInput.type === 'password';
  passwordInput.type = isHidden ? 'text' : 'password';
  togglePasswordBtn.setAttribute('aria-pressed', String(isHidden));
  togglePasswordBtn.title = isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña';
  togglePasswordBtn.textContent = isHidden ? '🙈' : '👁';
});

const form = document.getElementById('loginForm');
form?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  try {
    const data = await apiFetch('/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    setToken(data.token);
    setConsultantInfo(data.consultant);
    showToast('Sesión iniciada');
    window.location.href = '/ui/dashboard.php';
  } catch (err) {
    const msg = err?.data?.message || 'Error de autenticación';
    showToast(msg);
  }
});