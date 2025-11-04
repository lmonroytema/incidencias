<?php /* Simple Login page without Blade */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Incidencias | Login</title>
  <link rel="stylesheet" href="/ui/styles.css?v=theme1">
</head>
<body>
  <div class="container">
    <nav class="nav">
      <a href="/ui/report.php" title="Reportar incidencia">
        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h8a2 2 0 0 1 2 2v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 4v11h12V9h-2v2H9V7H7Zm4 0v2h4V7h-4Z"/></svg>
        <span style="margin-left:6px">Reportar incidencia</span>
      </a>
      <a href="/ui/dashboard.php" title="Panel de consultor">
        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 3h8v8H3V3Zm10 0h8v6h-8V3ZM3 13h6v8H3v-8Zm8 6h10v2H11v-2Z"/></svg>
        <span style="margin-left:6px">Panel de consultor</span>
      </a>
      <span class="muted">/ Login</span>
    </nav>

    <section class="card">
      <h2 class="title">Acceder como consultor</h2>
      <form id="loginForm">
        <div class="row">
          <div>
            <label for="email">Correo</label>
            <input type="email" id="email" required placeholder="consultor@temalitoclean.com">
          </div>
          <div>
            <label for="password">Contraseña</label>
            <div class="password-field">
              <input type="password" id="password" required placeholder="Tema2025@Migration">
              <button type="button" id="togglePassword" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false">👁</button>
            </div>
          </div>
        </div>
        <div class="footer flex">
          <button class="btn" type="submit">
            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1a5 5 0 0 1 5 5v3h-2V6a3 3 0 1 0-6 0v3H7V6a5 5 0 0 1 5-5Zm-7 10h14v10H5V11Zm4 3v4h2v-4H9Z"/></svg>
            <span style="margin-left:6px">Iniciar sesión</span>
          </button>
          <a class="btn secondary" href="/ui/report.php">
            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h8a2 2 0 0 1 2 2v2h2v2h-2v2H9V7H7V5a2 2 0 0 1 2-2Z"/></svg>
            <span style="margin-left:6px">Ir a Reporte</span>
          </a>
        </div>
      </form>
    </section>
  </div>

  <script type="module" src="/ui/login.js"></script>
</body>
</html>