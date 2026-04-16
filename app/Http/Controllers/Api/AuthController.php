<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'num_cni' => 'required|string|unique:users',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:users',
            'date_naissance' => 'required|date',
            'telephone' => 'required|string|unique:users',
            'role_user' => 'nullable|string',
            'password' => 'required|string|min:8',
            'gare_id' => 'nullable|integer|exists:gares,id',
        ]);

        /** @var \App\Models\User $user */
        $user = User::create([
            'num_cni' => $request->num_cni,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'date_naissance' => $request->date_naissance,
            'telephone' => $request->telephone,
            'role_user' => $request->role_user ?? 'CLIENT',
            'password' => Hash::make($request->password),
            'gare_id' => $request->gare_id,
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        /** @var \App\Models\User $user */
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email ou mot de passe incorrect'], 401);
        }

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function getChauffeurs($id)
    {
        $chauffeurs = User::where('role_user', 'CHAUFFEUR')
            ->where('gare_id', $id)
            ->get();
        return response()->json($chauffeurs);
    }

    public function sendResetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Email envoye a ' . $request->email])
            : response()->json(['message' => 'Erreur'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Mot de passe reinitialise avec succes'])
            : response()->json(['message' => 'Erreur'], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Votre mot de passe actuel est incorrect.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Votre mot de passe a été modifié avec succès.'
        ]);
    }

    public function getUsers()
    {
        $users = User::all();
        return response()->json($users);
    }
}
