<?php /* Report Incident page without Blade */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Incidencias | Reportar</title>
  <link rel="stylesheet" href="/ui/styles.css?v=delbtn">
</head>
<body>
  <div class="container">
    <nav class="nav">
      <a href="/ui/report.php">Reportar incidencia</a>
      <a href="/ui/login.php">Login consultor</a>
      <a href="/ui/dashboard.php">Panel de consultor</a>
      <span class="muted">/ Reportar</span>
    </nav>
    <!-- Sección informativa sobre la finalidad del registro -->
    <section class="card" aria-labelledby="infoTitle">
      <h2 id="infoTitle" class="title">Finalidad del registro</h2>
      <p class="muted">
        Este formulario permite registrar las incidencias actuales que presentan los usuarios de Tema Litoclean
        después de la migración de Tenant. El objetivo es reunir información precisa para dar soporte y remediación
        en las aplicaciones relacionadas a Microsoft 365 y en la ofimática de los equipos de los colaboradores.
      </p>
      <div class="flex" style="flex-wrap:wrap; gap:8px">
        <span class="badge app-teams">Teams</span>
        <span class="badge app-onedrive">OneDrive</span>
        <span class="badge app-sharepoint">SharePoint</span>
        <span class="badge app-outlook">Outlook</span>
        <span class="badge app-office">Office</span>
        <span class="badge app-pc">PCs/Ofimática</span>
      </div>
    </section>

    <div class="tabs">
      <button id="tabColaborador" class="tab active" type="button">Colaborador</button>
      <button id="tabIncidencia" class="tab" type="button" disabled>Detalle de la incidencia</button>
    </div>

    <section id="paneColaborador" class="card pane">
      <h2 class="title">Datos del colaborador</h2>
      <div class="row">
        <div>
          <label for="dni_type">Tipo de documento</label>
          <select id="dni_type">
            <option value="1">DNI</option>
            <option value="2">CE</option>
          </select>
        </div>
        <div>
          <label for="dni_number">Número</label>
          <div class="input-inline">
            <input type="text" id="dni_number" placeholder="Ej. 12345678" />
            <button class="btn secondary" id="btnLookup">Buscar empleado</button>
          </div>
        </div>
        <div>
          <label for="full_name">Nombre completo</label>
          <input type="text" id="full_name" class="readonly" placeholder="Se cargará desde el sistema" readonly disabled />
        </div>
        <div>
          <label for="area_name">Área</label>
          <input type="text" id="area_name" class="readonly" placeholder="Se cargará desde el sistema" readonly disabled />
        </div>
        <div>
          <label for="corporate_email">Correo corporativo</label>
          <input type="email" id="corporate_email" class="readonly" placeholder="Se cargará desde el sistema" readonly disabled />
        </div>
      </div>
    </section>

    <section id="paneIncidencia" class="card pane hidden">
      <h2 class="title">Colaborador: <span id="collabFullNameHeader"></span></h2>
      <!-- Banner de adjuntos de la incidencia seleccionada -->
      <div id="currentAttachmentRow" class="flex" style="margin-bottom:10px">
        <span id="currentAttachmentText" class="muted"></span>
        <button class="btn secondary" id="btnViewCurrentImage" type="button" title="Doble clic para ver imagen" style="display:none">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="currentColor"/></svg>
        </button>
      </div>
      <h2 class="title">Datos en Equipo</h2>
      <div class="row-3">
        <div>
          <label for="hostname">Hostname/Equipo (TL-LAP-123 o PCR-LAP12345)</label>
          <input type="text" id="hostname" placeholder="TL-LAP-123 / PCR-LAP12345" title="Formatos permitidos: TL-LAP-### (tres dígitos) o PCR-LAP##### (cinco dígitos)" />
        </div>
        <div>
          <label for="os">Sistema operativo</label>
          <select id="os"></select>
        </div>
        <div>
          <label for="office_version">Versión de Office</label>
          <select id="office_version"></select>
        </div>
      </div>

      <h2 class="title">Detalles de la incidencia</h2>
      <div class="row-3">
        <div>
          <label for="category">Categoría</label>
          <select id="category"></select>
        </div>
        <div>
          <label for="urgency">Urgencia</label>
          <select id="urgency"></select>
        </div>
        <div>
          <label for="started_at">Fecha de inicio</label>
          <input type="date" id="started_at" />
        </div>
      </div>
      <div>
        <label for="description">Descripción</label>
        <textarea id="description" placeholder="Describe el problema"></textarea>
      </div>
      <div>
        <label for="attachments">Adjuntos (opcional)</label>
        <div class="input-inline">
          <input type="file" id="attachments" name="attachments[]" multiple class="visually-hidden-file" />
          <label for="attachments" class="btn secondary" id="btnAttachLabel">Adjuntar imagen</label>
          <button class="btn secondary" id="btnInlineViewImage" type="button" title="Doble clic para ver imagen" style="display:none">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="currentColor"/></svg>
            <span style="margin-left:6px">Ver</span>
          </button>
          <span id="inlineImageStatus" class="muted">No hay imagen</span>
        </div>
      </div>
      <div class="footer">
        <button class="btn" id="btnSubmit">Enviar incidencia</button>
        <button class="btn secondary" id="btnNewIncident" type="button">Nueva incidencia</button>
      </div>
      <hr>
      <h3 class="title">Incidencias del colaborador</h3>
      <div id="listHint" class="muted">Valide al colaborador para mostrar sus incidencias.</div>
      <div id="incidentsListWrapper" style="overflow:auto; max-height:40vh;" class="hidden">
        <table class="table" id="collabIncidentsTable">
          <thead>
            <tr>
              <th>Fecha de inicio</th>
              <th>Categoría</th>
              <th>Urgencia</th>
              <th>Estado</th>
              <th>Imagen Ver</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>
  </div>

  <!-- Modal de edición de incidencia existente -->
  <div id="editModal" class="modal hidden" role="dialog" aria-modal="true">
    <div class="modal-dialog">
      <h3 class="modal-title">Incidencia existente</h3>
      <p class="modal-text">
        Ya existe incidencia reportada por el colaborador <strong id="modalName"></strong>
        (ID #<span id="modalIncidentId"></span>). ¿Desea Editarla?
      </p>
      <div class="modal-actions">
        <button class="btn secondary" id="btnModalNo" type="button">No</button>
        <button class="btn" id="btnModalYes" type="button">Sí</button>
      </div>
    </div>
  </div>

  <!-- Modal de éxito (registrada/actualizada) -->
  <div id="successModal" class="modal hidden" role="dialog" aria-modal="true">
    <div class="modal-dialog">
      <h3 class="modal-title" id="successTitle">Operación exitosa</h3>
      <p class="modal-text" id="successText">Se grabó satisfactoriamente.</p>
      <div class="modal-actions">
        <button class="btn" id="btnSuccessOk" type="button">Aceptar</button>
      </div>
    </div>
  </div>

  <!-- Modal de vista de imagen -->
  <div id="imageModal" class="modal hidden" role="dialog" aria-modal="true">
    <div class="modal-dialog">
      <h3 class="modal-title">Vista de la imagen</h3>
      <div class="modal-text" style="text-align:center">
        <img id="previewImage" src="" alt="Adjunto" style="max-width:95vw; max-height:80vh; border-radius:6px; box-shadow:0 2px 10px rgba(0,0,0,.3);" />
      </div>
      <div class="modal-actions">
        <button class="btn" id="btnImageClose" type="button">Cerrar</button>
      </div>
    </div>
  </div>

  <script type="module" src="/ui/report.js?v=delbtn"></script>
</body>
</html>