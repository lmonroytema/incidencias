<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmployeeLookupController extends Controller
{
    public function lookup(Request $request)
    {
        $request->validate([
            'dni_type' => 'required',
            'dni_number' => 'required|string',
        ]);

        // La API externa espera el código numérico (1=DNI, 2=CE)
        $dniType = (string) $request->query('dni_type');
        $dniNumber = $request->query('dni_number');

        $base = config('services.employee_api.base_url');

        try {
            $response = Http::withOptions(['verify' => config('services.employee_api.verify_ssl')])
                ->timeout(10)
                ->get($base, [ 'dni_type' => $dniType, 'dni_number' => $dniNumber ]);
            if (!$response->successful()) {
                return response()->json([
                    'message' => 'No se pudo consultar el empleado',
                    'status' => $response->status(),
                ], 502);
            }

            $data = $response->json();
            // La respuesta puede venir anidada en "employee"
            $employee = data_get($data, 'employee');
            if (!$employee && isset($data['full_name'])) {
                // Fallback: algunos endpoints retornan plano
                $employee = $data;
            }

            if (!$employee || !isset($employee['full_name'])) {
                return response()->json([
                    'message' => 'Empleado no encontrado o respuesta inválida',
                ], 404);
            }

            return response()->json([
                'full_name' => $employee['full_name'] ?? null,
                // Área debe ser el nombre dentro de employee.area.name
                'area_name' => data_get($employee, 'area.name'),
                // Cargo (posición) separado
                'cargo' => $employee['position'] ?? null,
                'corporate_email' => $employee['corporate_email'] ?? null,
                'raw' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error consultando API externa',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
