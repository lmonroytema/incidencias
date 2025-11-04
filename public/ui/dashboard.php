<?php /* Consultant Dashboard without Blade */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Incidencias | Panel</title>
  <link rel="stylesheet" href="/ui/styles.css?v=theme1">
</head>
<body>
  <script>
    try {
      var t = localStorage.getItem('apiToken');
      if (!t) { window.location.href = '/consultor/login'; }
    } catch (e) {
      window.location.href = '/consultor/login';
    }
  </script>
  <div class="container">
    <nav class="nav">
      <a href="/ui/report.php" title="Reportar incidencia">
        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h8a2 2 0 0 1 2 2v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm0 4v11h12V9h-2v2H9V7H7Zm4 0v2h4V7h-4Z"/></svg>
        <span style="margin-left:6px">Reportar incidencia</span>
      </a>
      <a href="/ui/login.php" title="Login consultor">
        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15 3a3 3 0 1 1 0 6 3 3 0 0 1 0-6ZM4 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2H4Z"/></svg>
        <span style="margin-left:6px">Login consultor</span>
      </a>
      <span class="muted">/ Panel</span>
    </nav>

    <section class="card">
      <h2 class="title">Filtro</h2>
      <div class="row-3">
        <div>
          <label for="f_status">Estado</label>
          <select id="f_status">
            <option value="">Todos</option>
            <option>Pendiente</option>
            <option>En revisión</option>
            <option>Resuelto</option>
            <option>Cerrado</option>
          </select>
        </div>
        <div>
          <label for="f_category">Categoría</label>
          <select id="f_category">
            <option value="">Todas</option>
            <option>Conectividad y autenticación Office 365</option>
            <option>Sincronización OneDrive/SharePoint</option>
            <option>Problemas con Outlook (envío/recepción)</option>
            <option>Microsoft Teams</option>
            <option>Configuración de perfil/licencias</option>
            <option>Otros</option>
          </select>
        </div>
        <div>
          <label for="f_urgency">Urgencia</label>
          <select id="f_urgency">
            <option value="">Todas</option>
            <option>Crítico</option>
            <option>Alto</option>
            <option>Medio</option>
            <option>Bajo</option>
          </select>
        </div>
      </div>
      <div class="row-3">
        <div>
          <label for="f_dni">Documento</label>
          <input type="text" id="f_dni" placeholder="Ej. 12345678, 46144548" />
        </div>
        <div>
          <label for="f_name">Nombre</label>
          <input type="text" id="f_name" placeholder="Parte del nombre" />
        </div>
        <div></div>
      </div>
      <div class="footer">
        <button class="btn" id="btnBuscar">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 4a6 6 0 1 1 0 12 6 6 0 0 1 0-12Zm8.32 13.9-3.53-3.53 1.41-1.41 3.53 3.53-1.41 1.41Z"/></svg>
          <span style="margin-left:6px">Buscar</span>
        </button>
        <button class="btn secondary" id="btnLimpiar">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 6h18v2H3V6Zm2 4h14l-1 8H6l-1-8Zm5 10h2v2h-2v-2Z"/></svg>
          <span style="margin-left:6px">Limpiar</span>
        </button>
      </div>
    </section>

    <section class="card">
      <h2 class="title">Incidencias</h2>
      <div id="consultantInfo" class="muted"></div>
      <div style="overflow:auto; max-height:60vh;">
        <table class="table" id="incidentsTable">
          <thead>
            <tr>
              <th>Documento</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Urgencia</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="footer flex" id="pager">
        <button class="btn secondary" id="prevPage">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15 18 9 12l6-6v12Z"/></svg>
          <span style="margin-left:6px">Anterior</span>
        </button>
        <span id="pageInfo" class="muted"></span>
        <button class="btn secondary" id="nextPage">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 6 15 12 9 18V6Z"/></svg>
          <span style="margin-left:6px">Siguiente</span>
        </button>
      </div>
    </section>
  </div>

  <!-- Modal detalle de incidencia -->
  <div id="detailModal" class="modal hidden" role="dialog" aria-modal="true">
    <div class="modal-dialog">
      <h3 class="modal-title">Detalle de la incidencia</h3>
      <div class="modal-text" id="detailContent"></div>
      <div class="modal-actions">
        <button class="btn" id="btnDetailClose" type="button">Cerrar</button>
      </div>
    </div>
  </div>

  <script type="module" src="/ui/dashboard.js?v=delbtn"></script>
</body>
<html>