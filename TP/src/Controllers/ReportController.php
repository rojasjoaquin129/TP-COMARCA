<?php

namespace Controllers;

use Models\OrderItem;
use Components\GenericResponse;
use Enum\OrderItemStatus;
use Models\Order;
use Models\Table;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReportController
{
    /* Orders items start*/

    public function getMostSoldOrderItems(Request $request, Response $response, $args)
    {
        try {
            $activeusers = OrderItem::selectRaw('orders_items.id_product, count(*) as cantidad')
                ->groupBy('orders_items.id_product')
                ->orderBy('cantidad', 'DESC')
                ->first();

            $response->getBody()->write(GenericResponse::obtain(true, "Informe de mas vendidos generado perfectamente.", $activeusers));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de generar el informe.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getLessSoldOrderItems(Request $request, Response $response, $args)
    {
        try {
            $activeusers = OrderItem::selectRaw('orders_items.id_product, count(*) as cantidad')
                ->groupBy('orders_items.id_product')
                ->orderBy('cantidad', 'ASC')
                ->first();

            $response->getBody()->write(GenericResponse::obtain(true, "Informe de menos vendidos generado perfectamente.", $activeusers));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de generar el informe.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getCancelledOrderItems(Request $request, Response $response, $args)
    {
        try {
            $ordersItems = OrderItem::where('item_status', 3)
                ->get();

            $response->getBody()->write(GenericResponse::obtain(true, "", $ordersItems));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los clientes", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getOutOfTimeOrderItems(Request $request, Response $response, $args)
    {
        // try {
        //     $customer = Customer::all([
        //         'id',
        //         'fullname',
        //         'created_at',
        //         'updated_at'
        //     ]);

        //     $response->getBody()->write(GenericResponse::obtain(true, "", $customer));
        // } catch (\Exception $e) {
        //     $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los clientes", null));
        //     $response->withStatus(500);
        // }

        // return $response;
    }


    /* Orders items end */

    /* Tables start */
    public function getMostUsedTables(Request $request, Response $response, $args)
    {
        try {
            $activeusers = Order::selectRaw('orders.id_table, count(*) as cantidad')
                ->groupBy('orders.id_table')
                ->orderBy('cantidad', 'DESC')
                ->first();

            $response->getBody()->write(GenericResponse::obtain(true, "Informe de mesas más usadas generado perfectamente.", $activeusers));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de generar el informe.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getLessUsedTables(Request $request, Response $response, $args)
    {
        try {
            $activeusers = Order::selectRaw('orders.id_table, count(*) as cantidad')
            ->groupBy('orders.id_table')
            ->orderBy('cantidad', 'ASC')
            ->first();

            $response->getBody()->write(GenericResponse::obtain(true, "Informe de mesas menos usadas generado perfectamente.", $activeusers));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de generar el informe.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    /* Tables end */
}
