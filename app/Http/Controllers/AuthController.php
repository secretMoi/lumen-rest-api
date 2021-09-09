<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthController extends Controller
{
    public function login(Request $request){
        $email = $request->email;
        $password = $request->password;

        if(empty($email) OR empty($password)){
            return response()->json(['status' => 'error', 'message' => 'Email or password empty']);
        }

        $client = new Client();

        try {
            return $client->post(config('service.passport.login_endpoint'), [
                "form_params" => [
                    "client_secret" => config('service.passport.client_secret'),
                    "grant_type" => "password",
                    "client_id" => config('service.passport.client_id'),
                    "username" => $request->email,
                    "password" => $request->password
                ]
            ]);
        }
        catch (BadRequestException $exception){
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
                ]);
        }
    }

    public function register(Request $request){
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;

        // check si champs vide
        if(empty($email) OR empty($password) OR empty($name)){
            return response()->json(['status' => 'error', 'message' => 'Veuillez remplirtous les champs']);
        }

        // check si email valide
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return response()->json(['status' => 'error', 'message' => 'Email invalide']);
        }

        // check longueur du password
        if(strlen($password) < 6){
            return response()->json(['status' => 'error', 'message' => 'Mot de passe inférieur à 6 caractères']);
        }

        if(User::where('email', '=', $email)->exists()){
            return response()->json(['status' => 'error', 'message' => 'Email déjà utilisée']);
        }

        try {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = app('hash')->make($password);

            if($user->save()){
                return $this->login($request);
            }
        }
        catch (\Exception $exception){
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    public function logout(Request $request){
        try {
            auth()->user()->tokens()->each(function ($token){
                $token->delete();
            });

            return response()->json(['status' => 'success', 'message' => 'Déconnexion réussie']);
        }
        catch (\Exception $exception){
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}
