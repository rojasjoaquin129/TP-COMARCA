<?php

namespace Controllers;

use Models\Order;
use Models\Table;
use Models\Customer;
use Components\Token;
use Enum\OrderStatus;
use Components\GenericResponse;
use Enum\TableStatus;
use Middlewares\Authentication\UserRole;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $items = Order::all();
            $response->getBody()->write(GenericResponse::obtain(true, "", $items));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los items.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getExpectedTime(Request $request, Response $response, $args)
    {
        try {
            $table_code = $request->getParsedBody()['table_code'] ?? '';
            $client_code = $request->getParsedBody()['client_code'] ?? '';

            /* Order by table code. */
            $data = Order::select('orders.id', 'orders.status', 'orders.id_table', 'orders.expectedTime', 'tables.guid', 'orders.created_at')
                ->join('tables', 'orders.id_table', '=', 'tables.id')
                ->where('orders.guid', '=', $client_code)
                ->where('tables.guid', '=', $table_code)
                ->first();

            if (!$data) {
                $response->getBody()->write(GenericResponse::obtain(false, "La relación entre los códigos no es válida.", null));
                $response->withStatus(400);
            } else {
                /* Expected DateTime */
                $expected_at = new \DateTime($data->created_at);
                $expected_at->modify("+{$data->expectedTime} minutes");

                /* Current DateTime */
                $now = date_create('now');

                $diff = $now->diff($expected_at);
                $intervalInSeconds = (new \DateTime())->setTimeStamp(0)->add($diff)->getTimeStamp();
                $intervalInMinutes = intval($intervalInSeconds / 60);

                $mensaje = "Su pedido debería estar listo en {$intervalInMinutes} minutos.";
                if ($intervalInMinutes <= 0) {
                    $intervalInMinutes = $intervalInMinutes * -1; // Avoid negative.
                    $mensaje = "Su pedido ya debería estar listo hace {$intervalInMinutes} minutos.";
                }

                $response->getBody()->write(GenericResponse::obtain(true, $mensaje, $intervalInMinutes));
            }
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
                $response->getBody()->write(GenericResponse::obtain(false, "Error al borrar una orden, el campo id es obligatorio.", null));
                $response->withStatus(400);
            } else {
                $item = Order::where('id', $id)->first();

                if ($item) {
                    $item->delete();
                    $response->getBody()->write(GenericResponse::obtain(true, "Orden borrada correctamente.", null));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "El item especificado es inexsistente.", null));
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de borrar la órden.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function updateOne(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'] ?? '';

            if (empty($id)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Error al modificar la orden, el campo id es obligatorio.", null));
                $response->withStatus(400);
            } else {

                $id_customer = $request->getParsedBody()['id_customer'] ?? null;
                $id_table = $request->getParsedBody()['id_table'] ?? null;
                $status = $request->getParsedBody()['status'] ?? null;
                $expectedTime = $request->getParsedBody()['expectedTime'] ?? null;

                $item = Order::where('id', $id)->first();

                if (!$item) {
                    $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de modificar un item, el item indicado no existe.'", $id));
                } else {
                    if (!empty($id_customer)) {
                        $item->id_customer = $id_customer;
                    }

                    if (!empty($id_table)) {
                        $this->id_table = $id_table;
                    }

                    if (!empty($status)) {
                        $this->status = $status;
                    }

                    if (!empty($expectedTime)) {
                        $this->expectedTime = $expectedTime;
                    }

                    $item->save();
                    $item->hash = null;
                    $response->getBody()->write(GenericResponse::obtain(true, "", $item));
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
            $item = Order::where('id', $id)->first();
            $response->getBody()->write(GenericResponse::obtain(true, "", $item));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener la orden", null));
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $customer_name = $request->getParsedBody()['customer_name'] ?? '';
            $id_table = $request->getParsedBody()['id_table'] ?? '';

            $token = $request->getHeaders()['token'] ?? "";
            $id_waiter = Token::getId($token);

            $base64 = "";
            $photoTmpPath = $_FILES['picture']['tmp_name'] ?? null;
            if ($photoTmpPath) {
                $data = file_get_contents($photoTmpPath);
                $base64 = base64_encode($data);
            }

            if (empty($id_waiter)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una orden, no se conoce el id del usuario. Verifique sus sesión.", $id_waiter));
                $response->withStatus(400);
            } else if (empty($id_table)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una orden, debe especificar el id de la mesa.", null));
                $response->withStatus(400);
            } else if (empty($customer_name)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una orden, debe especificar el id de cliente.", $customer_name));
                $response->withStatus(400);
            } else if (!Table::where('id', $id_table)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una orden, la mesa no existe.", $customer_name));
                $response->withStatus(400);
            } else if (Table::where('id', $id_table)->first()->state != TableStatus::OPEN) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear una orden, la mesa no se encuentra en estado ABIERTA.", $customer_name));
                $response->withStatus(400);
            } else {
                $order = new Order;
                $order->id = 0;
                $order->guid = $this->gen_uuid();
                $order->status = OrderStatus::OPEN;
                $order->id_table = $id_table;
                $order->id_waiter = $id_waiter;
                $order->customer_name = $customer_name;

                if (!empty($base64)) {
                    $order->picture = $base64;
                }

                $order->save();

                $response->getBody()->write(GenericResponse::obtain(true, "Orden agregado correctamente.", $order));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear una nueva orden", null));
        }

        return $response;
    }

    function gen_uuid($len = 5)
    {
        $hex = md5("rampiAprobame" . uniqid("", true));
        $pack = pack('H*', $hex);
        $tmp =  base64_encode($pack);
        $uid = preg_replace("#(*UTF8)[^A-Za-z0-9]#", "", $tmp);
        $len = max(4, min(128, $len));

        while (strlen($uid) < $len)
            $uid .= $this->gen_uuid(22);

        return strtoupper(substr($uid, 0, $len));
    }
}
