<?php

namespace Controllers;

use Models\Poll;
use Models\Order;
use Components\GenericResponse;
use Enum\OrderStatus;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PollController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $polls = Poll::all([
                'id',
                'table_value',
                'restaurant_value',
                'chef_value',
                'waiter_value',
                'comment',
                'created_at',
                'updated_at'
            ]);

            $response->getBody()->write(GenericResponse::obtain(true, "", $polls));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener las encuestas existentes.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $id_order = $args['id_order'] ?? '';
            $table_value = $request->getParsedBody()['table_value'] ?? null;
            $restaurant_value = $request->getParsedBody()['restaurant_value'] ?? null;
            $chef_value = $request->getParsedBody()['chef_value'] ?? null;
            $waiter_value = $request->getParsedBody()['waiter_value'] ?? null;
            $comment = $request->getParsedBody()['comment'] ?? '';

            $order = Order::where('id', $id_order)->first();

            /* Check order status, it should be enabled only for closed orders. */
            if ($order == null) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una encuesta, la orden no existe."));
                $response->withStatus(400);
            } else if ($order->status != OrderStatus::CLOSED && $order->status != OrderStatus::VOTED) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una encuesta, la orden aún no está cerrada"));
                $response->withStatus(400);
            } else if (empty($table_value)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una encuesta, el parametro 'table_value' no puede estar vacío.."));
                $response->withStatus(400);
            } else if (empty($restaurant_value)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una encuesta, el parametro 'restaurant_value' no puede estar vacío."));
                $response->withStatus(400);
            } else if (empty($chef_value)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una encuesta, el parametro 'chef_value' no puede estar vacío."));
                $response->withStatus(400);
            } else if (empty($waiter_value)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una encuesta, el parametro 'waiter_value' no puede estar vacío."));
                $response->withStatus(400);
            } else if ($order->status == OrderStatus::VOTED) {
                $response->getBody()->write(GenericResponse::obtain(true, "La encuensta ya fue realizada para esta orden."));
                $response->withStatus(400);
            } else {
                $poll = new Poll;
                $poll->id = 0;
                $poll->table_value = $table_value;
                $poll->restaurant_value = $restaurant_value;
                $poll->chef_value = $chef_value;
                $poll->waiter_value = $waiter_value;
                $poll->comment = $comment;
                $poll->save();

                $order->status = OrderStatus::VOTED;
                $order->save();
                $response->getBody()->write(GenericResponse::obtain(true, "Encuesta agregada correctamente.", $poll));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear una nueva encuesta.", null));
        }

        return $response;
    }
}
