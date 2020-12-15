<?php

namespace Controllers;

use Models\Customer;
use Components\GenericResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/* 
        -> No se termino de testear, porque quizás no se use. 
*/
class CustomerController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $customer = Customer::all([
                'id',
                'fullname',
                'created_at',
                'updated_at'
            ]);

            $response->getBody()->write(GenericResponse::obtain(true, "", $customer));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los clientes", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function deleteOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al borrar un cliente, el campo id es obligatorio.", null));
                $response->withStatus(401);
            } else {
                $customer = Customer::where('id', $id)->first();
                $customer->delete();
                $response->getBody()->write(GenericResponse::obtain(true, "Cliente borrado correctamente.", null));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los clientes", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function updateOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al modificar un cliente, el campo id es obligatorio.", null));
                $response->withStatus(401);
            } else {

                $fullname = $request->getParsedBody()['fullname'] ?? null;

                if (empty($fullname)) {
                    $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de modificar un cliente, debe especificar el campo 'fullname'", $fullname));
                } else {

                    $customer = Customer::where('id', $id)->first();

                    if (!$customer) {
                        $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de modificar un cliente, el cliente indicado no existe'", $id));
                    } else {
                        $customer->fullname = $fullname;
                        $customer->save();
                        $customer->hash = null;
                    }

                    $response->getBody()->write(GenericResponse::obtain(true, "", $customer));
                }

                return $response;
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
            $customer = Customer::where('id', $args['id'])->first([
                'id',
                'fullname',
                'created_at',
                'updated_at'
            ]);

            $response->getBody()->write(GenericResponse::obtain(true, "", $customer));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener el cliente.", null));
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $fullname = $request->getParsedBody()['fullname'] ?? '';

            if (empty($fullname)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un cliente, los datos ingresados son inválidos."));
                $response->withStatus(401);
            } else {
                $customer = new Customer;
                $customer->id = 0;
                $customer->fullname = $fullname;

                $customer->save();
                $response->getBody()->write(GenericResponse::obtain(true, "Cliente agregado correctamente.", $customer));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear un nuevo cliente.", null));
        }

        return $response;
    }
}
