<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Consultant;

class ConsultantAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $consultant = Consultant::where('email', $request->input('email'))->first();
        if (!$consultant || !Hash::check($request->input('password'), $consultant->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $consultant->api_token = Str::random(60);
        $consultant->save();

        return response()->json([
            'token' => $consultant->api_token,
            'consultant' => [
                'id' => $consultant->id,
                'name' => $consultant->name,
                'email' => $consultant->email,
                'area_name' => $consultant->area_name,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $consultant = $request->attributes->get('consultant');
        if (!$consultant) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $consultant->api_token = null;
        $consultant->save();

        return response()->json(['message' => 'Sesión cerrada']);
    }
}
