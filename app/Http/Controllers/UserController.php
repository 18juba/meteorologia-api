<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Validations\UserValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($validate_errors = UserValidation::store($request))
        {
            return $validate_errors;
        }

        $user = User::create([
            'nome'  => $request->nome,
            'senha' => Hash::make($request->senha),
            'email' => $request->email
        ]);

        return response()->json([
            'status' => [
                'code'      => 201,
                'message'   => 'Conta criada com sucesso'
            ],
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
