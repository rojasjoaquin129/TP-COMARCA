<?php

namespace Controllers;

use Models\User;
use Models\Login;
use Components\PassManager;
use Components\Token;
use Components\GenericResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController
{
    public static function login(Request $request, Response $response, $args)
    {
        try {
            $email = $request->getParsedBody()['email'] ?? "";
            $pass =  $request->getParsedBody()['password'] ?? "";

            if (!empty($email) && !empty($pass)) {
                /* Crypto */
                $pass = PassManager::Hash($pass);

                /* Look up for credentials */
                $retrievedUser = User::where('email', $email)->where('hash', $pass)->first();
                
                if($retrievedUser == null)
                {
                    $response->getBody()->write(GenericResponse::obtain(false, 'Contraseña inválida.'));
                }
                else if ($retrievedUser->enabled != 1) {
                    $response->getBody()->write(GenericResponse::obtain(false, 'Usuario suspendido, entre en contacto con el admin.'));
                } else {
                    if ($retrievedUser != null) {
                        $token = Token::getToken($retrievedUser->id, $email, $retrievedUser->area);

                        $login = new Login;
                        $login->id_user = $retrievedUser->id;
                        $login->save();

                        $response->getBody()->write(GenericResponse::obtain(true, 'Bienvenidx ' . $email, $token));
                    } else {
                        $response->getBody()->write(GenericResponse::obtain(false, 'Credenciales invalidas.'));
                    }
                }
            } else {
                $response->getBody()->write(GenericResponse::obtain(false, 'Debe especificar el campo email y password.'));
                $response->withStatus(401);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de realizar la autenticacion.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $response->getBody()->write(GenericResponse::obtain(true, '', Login::all()));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los inicios de sesión.", null));
        }

        return $response;
    }

    public function getRole(Request $request, Response $response, $args)
    {
        try {
            $token = $request->getHeaders()['token'] ?? "";
            $role = Token::getRole($token);
            $response->getBody()->write(GenericResponse::obtain(true, '', $role));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener el rol del usuario.", null));
        }

        return $response;
    }

    public static function validateToken(Request $request, Response $response, $args)
    {
        $token = $request->getHeaders()['token'] ?? "";

        if (!empty($token)) {
            $isDecoded = Token::validateToken($token);
            $response->getBody()->write(GenericResponse::obtain($isDecoded, $isDecoded ? 'Token valido.' : 'Token ivalido', $token));
        } else {
            $response->getBody()->write(GenericResponse::obtain(false, 'Invalid credentials'));
        }

        return $response;
    }
}
