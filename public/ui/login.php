<?php /* Simple Login page without Blade */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Incidencias | Login</title>
  <link rel="stylesheet" href="/ui/styles.css">
</head>
<body>
  <div class="container">
    <nav class="nav">
      <a href="/ui/report.php">Reportar incidencia</a>
      <a href="/ui/dashboard.php">Panel de consultor</a>
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
          <button class="btn" type="submit">Iniciar sesión</button>
          <a class="btn secondary" href="/ui/report.php">Ir a Reporte</a>
        </div>
      </form>
    </section>
  </div>

  <script type="module" src="/ui/login.js"></script>
</body>
</html>