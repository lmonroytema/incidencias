<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Incident;
use App\Models\Attachment;
use App\Models\Consultant;
use App\Services\IncidentExportService;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Incident::query()->with(['assignedTo:id,name,email', 'attachments:id,incident_id,filename,path,mime,size']);

        // Filtros
        if ($status = $request->query('status')) $query->where('status', $status);
        if ($category = $request->query('category')) $query->where('category', $category);
        if ($urgency = $request->query('urgency')) $query->where('urgency', $urgency);
        if ($area = $request->query('area_name')) $query->where('area_name', $area);
        if ($assigned = $request->query('assigned_to_id')) $query->where('assigned_to_id', $assigned);
        if ($app = $request->query('app')) $query->whereJsonContains('apps', $app);
        // Nuevos filtros para búsqueda por documento
        if ($dniType = $request->query('dni_type')) $query->where('dni_type', $dniType);
        if ($dniNumber = $request->query('dni_number')) $query->where('dni_number', $dniNumber);
        // Búsqueda parcial
        if ($nameLike = $request->query('full_name_like')) $query->where('full_name', 'LIKE', "%".$nameLike."%");
        if ($dniLike = $request->query('dni_number_like')) $query->where('dni_number', 'LIKE', "%".$dniLike."%");
        if ($dateFrom = $request->query('date_from')) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo = $request->query('date_to')) $query->whereDate('created_at', '<=', $dateTo);

        $incidents = $query->orderByDesc('created_at')->paginate(20);
        return response()->json($incidents);
    }

    public function show($id)
    {
        $incident = Incident::with(['assignedTo:id,name,email', 'attachments'])->findOrFail($id);
        return response()->json($incident);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni_type' => 'required',
            'dni_number' => 'required|string',
            'full_name' => 'required|string',
            'area_name' => 'nullable|string',
            'corporate_email' => 'required|email',
            'category' => 'required|string',
            'apps' => 'nullable|array',
            'description' => 'required|string',
            'urgency' => 'required|string|in:Crítico,Alto,Medio,Bajo',
            'hostname' => 'nullable|string',
            'os' => 'nullable|string',
            'office_version' => 'nullable|string',
            // El campo first_time se volvió opcional en el formulario
            'first_time' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:10240', // 10MB por archivo
            // Para edición desde Reportar por ID específico
            'edit_existing_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Permitir múltiples incidencias por colaborador.
        // Si se envía edit_existing_id, actualizamos esa incidencia específica.
        if ($editId = $request->input('edit_existing_id')) {
            $existing = Incident::find($editId);
            if (!$existing) {
                return response()->json(['message' => 'Incidencia no encontrada'], 404);
            }
            // Validar que corresponde al mismo colaborador (opcional, por seguridad)
            $dniTypeRawInput = (string) $request->input('dni_type');
            $dniTypeTextForCheck = match ($dniTypeRawInput) {
                '1' => 'DNI',
                '2' => 'CE',
                default => $dniTypeRawInput,
            };
            if (
                $existing->dni_type !== $dniTypeTextForCheck ||
                $existing->dni_number !== (string) $request->input('dni_number')
            ) {
                return response()->json(['message' => 'Los datos del colaborador no coinciden con la incidencia a editar'], 422);
            }
            $existing->category = $request->input('category', $existing->category);
            $existing->description = $request->input('description', $existing->description);
            $existing->urgency = $request->input('urgency', $existing->urgency);
            $existing->hostname = $request->input('hostname', $existing->hostname);
            $existing->os = $request->input('os', $existing->os);
            $existing->office_version = $request->input('office_version', $existing->office_version);
            $existing->started_at = $request->input('started_at', $existing->started_at);
            // Actualizar datos del empleado si corresponden
            $existing->full_name = $request->input('full_name', $existing->full_name);
            $existing->area_name = $request->input('area_name', $existing->area_name);
            $existing->corporate_email = $request->input('corporate_email', $existing->corporate_email);
            $existing->save();
            // Guardar nuevos adjuntos si se enviaron al editar.
            // Si entre los nuevos archivos hay imágenes, primero reemplazamos las imágenes existentes.
            $files = $request->file('attachments');
            if ($files) {
                if (!is_array($files)) { $files = [$files]; }

                // ¿Alguno de los nuevos adjuntos es imagen?
                $hasNewImage = false;
                foreach ($files as $f) {
                    if ($f && str_starts_with((string) $f->getClientMimeType(), 'image/')) { $hasNewImage = true; break; }
                }

                // Si se suben nuevas imágenes, eliminar imágenes anteriores del incidente (reemplazo)
                if ($hasNewImage) {
                    $prevImages = Attachment::where('incident_id', $existing->id)
                        ->where('mime', 'like', 'image/%')
                        ->get();
                    foreach ($prevImages as $att) {
                        if ($att->path && Storage::exists($att->path)) {
                            @Storage::delete($att->path);
                        }
                        $att->delete();
                    }
                }

                // Registrar nuevos adjuntos
                foreach ($files as $file) {
                    if (!$file) { continue; }
                    $path = $file->store('attachments/' . $existing->id);
                    Attachment::create([
                        'incident_id' => $existing->id,
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }
            return response()->json($existing->load('attachments'), 200);
        }

        // Map numeric dni_type codes to textual values before storing
        $dniTypeRaw = $request->input('dni_type');
        $dniType = match ((string)$dniTypeRaw) {
            '1' => 'DNI',
            '2' => 'CE',
            default => (string)$dniTypeRaw,
        };

        $incident = Incident::create([
            'dni_type' => $dniType,
            'dni_number' => $request->input('dni_number'),
            'full_name' => $request->input('full_name'),
            'area_name' => $request->input('area_name'),
            'corporate_email' => $request->input('corporate_email'),
            'category' => $request->input('category'),
            'apps' => $request->input('apps', []),
            'description' => $request->input('description'),
            'urgency' => $request->input('urgency'),
            'hostname' => $request->input('hostname'),
            'os' => $request->input('os'),
            'office_version' => $request->input('office_version'),
            'first_time' => $request->boolean('first_time'),
            'started_at' => $request->input('started_at'),
        ]);

        // Guardar adjuntos (soporta uno o múltiples archivos)
        $files = $request->file('attachments');
        if ($files) {
            if (!is_array($files)) { $files = [$files]; }
            foreach ($files as $file) {
                if (!$file) { continue; }
                $path = $file->store('attachments/' . $incident->id);
                Attachment::create([
                    'incident_id' => $incident->id,
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return response()->json($incident->load('attachments'), 201);
    }

    public function update(Request $request, $id)
    {
        $consultant = $request->attributes->get('consultant');
        if (!$consultant) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $incident = Incident::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:Pendiente,En revisión,Resuelto,Cerrado',
            'assigned_to_id' => 'nullable|exists:consultants,id',
            'consultant_notes' => 'nullable|string',
            'resolution_date' => 'nullable|date',
            'solution_applied' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incident->fill($validator->validated());
        // Si un consultor actualiza, puede autoasignarse
        if (!$request->has('assigned_to_id')) {
            $incident->assigned_to_id = $consultant->id;
        }
        $incident->save();

        return response()->json($incident->load('assignedTo', 'attachments'));
    }

    public function exportExcel(Request $request, IncidentExportService $exporter)
    {
        // Reutilizamos los filtros del index
        $query = Incident::query()->with(['assignedTo']);
        if ($status = $request->query('status')) $query->where('status', $status);
        if ($category = $request->query('category')) $query->where('category', $category);
        if ($urgency = $request->query('urgency')) $query->where('urgency', $urgency);
        if ($area = $request->query('area_name')) $query->where('area_name', $area);
        if ($assigned = $request->query('assigned_to_id')) $query->where('assigned_to_id', $assigned);
        if ($app = $request->query('app')) $query->whereJsonContains('apps', $app);
        if ($dateFrom = $request->query('date_from')) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo = $request->query('date_to')) $query->whereDate('created_at', '<=', $dateTo);
        // Búsqueda parcial también en export
        if ($nameLike = $request->query('full_name_like')) $query->where('full_name', 'LIKE', "%".$nameLike."%");
        if ($dniLike = $request->query('dni_number_like')) $query->where('dni_number', 'LIKE', "%".$dniLike."%");

        $incidents = $query->orderByDesc('created_at')->get();

        $filePath = $exporter->export($incidents, $request->query());
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Stream an attachment file by its ID. Useful for inline viewing of images.
     */
    public function viewAttachment($id)
    {
        $att = Attachment::findOrFail($id);
        if (!Storage::exists($att->path)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }
        $content = Storage::get($att->path);
        $mime = $att->mime ?: 'application/octet-stream';
        return response($content, 200)->header('Content-Type', $mime);
    }

    /**
     * Delete an incident. Intended for collaborator UI: requires dni_type and dni_number
     * to match the incident before allowing deletion. Also removes stored attachments.
     */
    public function destroy($id, Request $request)
    {
        $incident = Incident::with('attachments')->findOrFail($id);

        // Validate required collaborator identifiers
        $validator = Validator::make($request->all(), [
            'dni_type' => 'required',
            'dni_number' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Map numeric dni_type codes to textual values for comparison
        $dniTypeRaw = (string) $request->input('dni_type');
        $dniType = match ($dniTypeRaw) {
            '1' => 'DNI',
            '2' => 'CE',
            default => $dniTypeRaw,
        };
        $dniNumber = (string) $request->input('dni_number');

        if ($incident->dni_type !== $dniType || $incident->dni_number !== $dniNumber) {
            return response()->json(['message' => 'Los datos del colaborador no coinciden con la incidencia a eliminar'], 403);
        }

        // Delete attachments from storage and DB
        foreach ($incident->attachments as $att) {
            if ($att->path && Storage::exists($att->path)) {
                @Storage::delete($att->path);
            }
            $att->delete();
        }
        // Also remove the directory if present
        @Storage::deleteDirectory('attachments/' . $incident->id);

        $incident->delete();

        return response()->json(['message' => 'Incidencia eliminada'], 200);
    }
}
