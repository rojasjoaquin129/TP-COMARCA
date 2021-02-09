<?php

namespace Controllers;

use Models\Product;
use Enum\UserRole;
use Components\GenericResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $items = Product::all([
                'id',
                'description',
                'area',
                'created_at',
                'updated_at'
            ]);

            $response->getBody()->write(GenericResponse::obtain(true, "", $items));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los items.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function deleteOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al borrar un item, el campo id es obligatorio.", null));
                $response->withStatus(400);
            } else {
                $item = Product::where('id', $id)->first();

                if ($item) {
                    $item->delete();
                    $response->getBody()->write(GenericResponse::obtain(true, "Item borrado correctamente.", null));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "Item especificado es inexistente.", null));
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los items", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function updateOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al modificar un item, el campo id es obligatorio.", null));
                $response->withStatus(400);
            } else {

                $description = $request->getParsedBody()['description'] ?? null;
                $area = $request->getParsedBody()['area'] ?? null;

                // On empty description.
                if (!empty($area) && !UserRole::IsValidArea($area)) {
                    $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de modificar un item, el 'area' especificada no existe.", $description));
                } else {

                    $item = Product::where('id', $id)->first();

                    // Client doesn't exist.
                    if (!$item) {
                        $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de modificar un item, el item indicado no existe.'", $id));
                    } else {

                        if (!empty($description))
                            $item->description = $description;

                        if (!empty($area))
                            $item->area = $area;

                        $item->save();
                        $item->hash = null;
                        $response->getBody()->write(GenericResponse::obtain(true, "", $item));
                    }
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los clientes", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'];

            $item = Product::where('id', $id)->first();

            if (!empty($item))
                $response->getBody()->write(GenericResponse::obtain(true, "", $item));
            else
                $response->getBody()->write(GenericResponse::obtain(false, "El item especificado no existe.", $item));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener el item", null));
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $description = $request->getParsedBody()['description'] ?? '';
            $area = $request->getParsedBody()['area'] ?? '';
            $cost = $request->getParsedBody()['cost'] ?? '';

            if (!UserRole::IsValidArea($area)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un item, area invalida.", $area));
                $response->withStatus(400);
            } else if (empty($description)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un item, los datos ingresados son inválidos."));
                $response->withStatus(400);
            } else {
                $item = new Product;
                $item->id = 0;
                $item->description = $description;
                $item->area = $area;
                $item->cost = $cost;
                $item->save();
                $response->getBody()->write(GenericResponse::obtain(true, "Item agregado correctamente.", $item));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear un nuevo item", null));
        }

        return $response;
    }
}
