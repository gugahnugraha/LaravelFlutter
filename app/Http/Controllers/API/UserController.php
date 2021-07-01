<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\API\ResponseFormatter;

class UserController extends Controller
{
    public function register(Request $request) {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', ,'email','max:255', 'unique:users'],
                'phone' => ['nullable', 'string', 'max:255'],
                'password' => ['required', 'string', new password]
            ]); //request validate

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password)
            ]); //user creation

            $user = User::where('email', $request->email)->first('authToken')->plainTextToken;

            $tokenResult = $user->createToken();
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered'); //Sukses
        } catch (Exception $error) {
            return ResponseFormatter::error([
               'message' => 'Something went wrong',
               'error' => $error
            ], 'Authentication Failed', 500); //gagal
        }
    }

    public function login(Request $request) {
        try{
            $request->validate([
                'email' => ['email|required'],
                'password' => ['required']
            ]);
            $credential = request(['email','password']);
            if (!Auth::attempt($credential)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'], 'Authentication Failed', 500);
            }
            $user = User::where('email', $request->email)->first;
            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credential'); //Respon Gagal Login
            }
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token'  => $tokenResult,
                'token_type'    => 'Bearer',
                'user'          => $user
            ], 'Authenticated'); // Respon Sukses Login
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
             ], 'Authentication Failed', 500); //gagal
        }
    }

    public function fetch(Request $request) {
        return ResponseFormatter::success($request->user(), 'Data profil user berhasil diambil');
    }
    
    public function updateProfile(Request $request) {
        $data = $request->all();
        $user = Auth::user();
        $user->update($data);
        return ResponseFormatter::success($user, 'Profile Updated');
    }

    public function logout(Request $request) {
        $token = $request->user()->currentAccessUser->detele();
        return ResponseFormatter::success($token, 'Token Revoked');
    }
}
