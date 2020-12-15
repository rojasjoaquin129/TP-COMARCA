<?php

namespace Controllers;

use Models\User;
use Components\PassManager;
use Components\GenericResponse;
use Enum\UserRole;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $users = User::all();
            $response->getBody()->write(GenericResponse::obtain(true, "", $users));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los usuarios", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function banUser(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al borrar un usuario, el campo id es obligatorio.", null));
                $response->withStatus(401);
            } else {
                $user = User::where('id', $id)->first();

                if ($user) {
                    $user->enabled = 0;
                    $user->save();
                    $response->getBody()->write(GenericResponse::obtain(true, "Usuario borrado correctamente.", null));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "El usuario especificado es inexistente.", null));
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los usuarios", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function unbanUser(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al borrar un usuario, el campo id es obligatorio.", null));
                $response->withStatus(401);
            } else {
                $user = User::where('id', $id)->first();

                if ($user) {
                    $user->enabled = 1;
                    $user->save();
                    $response->getBody()->write(GenericResponse::obtain(true, "Usuario borrado correctamente.", null));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "El usuario especificado es inexistente.", null));
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los usuarios", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function deleteOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al borrar un usuario, el campo id es obligatorio.", null));
                $response->withStatus(401);
            } else {
                $user = User::where('id', $id)->first();

                if ($user) {
                    $user->delete();
                    $response->getBody()->write(GenericResponse::obtain(true, "Usuario borrado correctamente.", null));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "El usuario especificado es inexistente.", null));
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los usuarios", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getOne(Request $request, Response $response, $args)
    {
        try {
            $user = User::where('id', $args['id'])->first();
            $response->getBody()->write(GenericResponse::obtain(true, "", $user));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener el usuario", null));
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $name = $request->getParsedBody()['name'] ?? "";
            $email = $request->getParsedBody()['email']  ?? "";
            $password = $request->getParsedBody()['password']  ?? "";
            $area = $request->getParsedBody()['area']  ?? "";

            if (empty($name)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, debe especificar el nombre."));
                $response->withStatus(400);
            } else if (empty($email)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, debe especificar el email."));
                $response->withStatus(400);
            } else if (empty($password)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, debe especificar el password."));
                $response->withStatus(400);
            } else if (empty($area)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, debe especificar el area."));
                $response->withStatus(400);
            } else if (User::where('name', '=', $name)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, el usuario ya existe. (nombre)", $name));
                $response->withStatus(400);
            } else if (User::where('email', '=', $email)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, el usuario ya existe. (email)", $email));
                $response->withStatus(400);
            } else if (!UserRole::IsValidArea($area)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un usuario, area invalida.", $area));
                $response->withStatus(400);
            } else {
                $user = new User;
                $user->id = 0;
                $user->email = $email;
                $user->name = $name;
                $user->hash = PassManager::Hash($password);
                $user->area = UserRole::GetVal($area);
                $user->enabled = 1;
                $user->save();
                $user->hash = null;
                $response->getBody()->write(GenericResponse::obtain(true, "Usuario agregado correctamente.", $user));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear un nuevo usuario", null));
        }

        return $response;
    }
}
