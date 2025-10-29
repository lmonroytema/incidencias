<?php /* Consultant Dashboard without Blade */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Incidencias | Panel</title>
  <link rel="stylesheet" href="/ui/styles.css">
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
      <a href="/ui/report.php">Reportar incidencia</a>
      <a href="/ui/login.php">Login consultor</a>
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
        <button class="btn" id="btnBuscar">Buscar</button>
        <button class="btn secondary" id="btnLimpiar">Limpiar</button>
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
        <button class="btn secondary" id="prevPage">Anterior</button>
        <span id="pageInfo" class="muted"></span>
        <button class="btn secondary" id="nextPage">Siguiente</button>
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