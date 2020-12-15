<?php

namespace Controllers;

use Enum\UserRole;
use Enum\OrderItemStatus;
use Enum\TableStatus;
use Models\Order;
use Models\Product;
use Models\OrderItem;
use Models\Table;
use Components\Token;
use Components\GenericResponse;
use Config\Database;
use Enum\OrderStatus;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderItemController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $token = $request->getHeaders()['token'] ?? "";
            $currentUserArea = Token::getRole($token);

            $data = OrderItem::select('orders_items.id', 'products.area', 'products.description', 'orders_items.item_status')
                ->join('products', 'orders_items.id', '=', 'products.id')
                ->get();

            $response->getBody()->write(GenericResponse::obtain(true, "", $data));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los items del pedido.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function getPending(Request $request, Response $response, $args)
    {
        try {
            $token = $request->getHeaders()['token'] ?? "";
            $currentUserArea = Token::getRole($token);

            $data = OrderItem::select('orders_items.id', 'products.area', 'products.description', 'orders_items.item_status')
                ->join('products', 'orders_items.id', '=', 'products.id')
                ->where('orders_items.item_status', '=', 0)
                ->where('products.area', '=', $currentUserArea)
                ->get();

            $response->getBody()->write(GenericResponse::obtain(true, "", $data));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener los items del pedido.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function cancelOrderItem(Request $request, Response $response, $args)
    {
        try {
            $id_orderItem = $args['id'] ?? "";
            $orderItem = OrderItem::where('id', $id_orderItem)->first();

            if (empty($id_orderItem)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, debe especificar el id del item.", $id_orderItem));
                $response->withStatus(400);
            } else if (!$orderItem) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, el item no existe.", $id_orderItem));
                $response->withStatus(400);
            } else if ($orderItem->item_status == OrderItemStatus::READY) {
                $response->getBody()->write(GenericResponse::obtain(true, "No es posible cancelar un item que ya se realizó.", $id_orderItem));
                $response->withStatus(400);
            } else if ($orderItem->item_status == OrderItemStatus::CANCELLED) {
                $response->getBody()->write(GenericResponse::obtain(true, "No es posible cancelar un item que ya se canceló.", $id_orderItem));
                $response->withStatus(400);
            } else {
                // Set it to CANCELLED.
                $orderItem->item_status = OrderItemStatus::CANCELLED;
                $orderItem->save();

                $order = Order::where('id', $orderItem->id_order)->first();
                $table = Table::where('id', $order->id_table)->first();

                if ($table != null) {

                    // Check and set order status.
                    $orderItems = OrderItem::where('id_order', $orderItem->id_order)
                        ->where('item_status', '<>', OrderItemStatus::READY)
                        ->where('item_status', '<>', OrderItemStatus::CANCELLED)
                        ->get();

                    if (count($orderItems) == 0) {
                        $order->status = OrderStatus::CLOSED;
                        $order->save();

                        $table->state = TableStatus::EATING;
                        $table->save();
                    }

                    $response->getBody()->write(GenericResponse::obtain(true, "Item cancelado correctamente.", $orderItem));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, la mesa de la orden no existe.", $id_orderItem));
                    $response->withStatus(400);
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de finalizar la preparacion del item.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function startOrderItem(Request $request, Response $response, $args)
    {
        try {
            $token = $request->getHeaders()['token'] ?? "";
            $currentUserArea = Token::getRole($token);
            $expected_minutes = $request->getParsedBody()['expected_minutes'] ?? '';

            $id_orderItem = $args['id'] ?? "";
            $orderItem = OrderItem::where('id', $id_orderItem)->first();

            if (empty($expected_minutes)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, debe especificar el tiempo de espera.", $id_orderItem));
                $response->withStatus(400);
            } else if (empty($id_orderItem)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, debe especificar el id del item.", $id_orderItem));
                $response->withStatus(400);
            } else if (!$orderItem) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, el item no existe.", $id_orderItem));
                $response->withStatus(400);
            } else if (Product::where('id', $orderItem->id_product)->first()->area != $currentUserArea) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, el usuario no pertenece al area.", $id_orderItem));
                $response->withStatus(400);
            } else if ($orderItem->item_status != OrderItemStatus::WAITING) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, el item no se encuentra disponible para comenzar..", $id_orderItem));
                $response->withStatus(400);
            } else {
                // Set it to ready.
                $orderItem->item_status = OrderItemStatus::WORKING;
                $orderItem->save();

                // Check and set order status.
                $orderItems = OrderItem::where('id_order', $orderItem->id_order)
                    ->where('item_status', '<>', OrderItemStatus::READY)
                    ->where('item_status', '<>', OrderItemStatus::CANCELLED)
                    ->get();

                // Exists non ready order items?
                if (count($orderItems) != 0) {
                    $order = Order::where('id', $orderItem->id_order)->first();

                    $table = Table::where('id', $order->id_table)->first();

                    if ($table == null) {
                        $response->getBody()->write(GenericResponse::obtain(true, "Error al comenzar un item, la mesa de la orden no existe.", $id_orderItem));
                        $response->withStatus(400);
                    } else {

                        /* Set the max */
                        if ($order->expectedTime < $expected_minutes) {
                            $order->expectedTime = $expected_minutes;
                        }

                        $table->state = TableStatus::WAITING; // Con gente esperando.
                        $table->save();

                        $order->status = OrderStatus::PREPARING;
                        $order->save();

                        $response->getBody()->write(GenericResponse::obtain(true, "", $orderItem));
                    }
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de finalizar la preparacion del item.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function finishOrderItem(Request $request, Response $response, $args)
    {
        try {
            $token = $request->getHeaders()['token'] ?? "";
            $currentUserArea = Token::getRole($token);

            $id_orderItem = $args['id'] ?? "";
            $orderItem = OrderItem::where('id', $id_orderItem)->first();

            if (empty($id_orderItem)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, debe especificar el id del item.", $id_orderItem));
                $response->withStatus(400);
            } else if (!$orderItem) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, el item no existe.", $id_orderItem));
                $response->withStatus(400);
            } else if ($orderItem->area != $currentUserArea && $currentUserArea != UserRole::ADMIN) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, el usuario no pertenece al area.", $id_orderItem));
                $response->withStatus(400);
            } else if ($orderItem->item_status != OrderItemStatus::WORKING) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, el item no se encuentra en preparación.", $id_orderItem));
                $response->withStatus(400);
            } else {
                // Set it to ready.
                $orderItem->item_status = OrderItemStatus::READY;
                $orderItem->save();

                $order = Order::where('id', $orderItem->id_order)->first();
                $table = Table::where('id', $order->id_table)->first();

                if ($table != null) {
                    // Check and set order status.
                    $orderItems = OrderItem::where('id_order', $orderItem->id_order)->where('item_status', '<>', OrderItemStatus::READY)->get();

                    if (count($orderItems) == 0) {
                        $order->status = OrderStatus::CLOSED;
                        $order->save();

                        $table->state = TableStatus::EATING; // Cuando se cierra la orden, la mesa pasa a comiendo automáticamente.
                        $table->save();
                    }

                    $response->getBody()->write(GenericResponse::obtain(true, "Item finalizado correctamente.", $orderItem));
                } else {
                    $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, la mesa de la orden no existe.", $id_orderItem));
                    $response->withStatus(400);
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de finalizar la preparacion del item.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $token = $request->getHeaders()['token'] ?? "";

            $user_id = Token::getId($token);
            $user_role = Token::getRole($token);

            $id_order = $request->getParsedBody()['id_order'] ?? '';
            $id_product = $request->getParsedBody()['id_product'] ?? '';

            if (empty($id_order)) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un item, debe especificar el id de la orden.", $id_order));
                $response->withStatus(400);
            } else if (!Order::where('id', $id_order)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un item, la orden no existe.", $id_order));
                $response->withStatus(400);
            } else if (Order::where('id', $id_order)->first()->id_waiter != $user_id && $user_role != UserRole::ADMIN) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un item, la orden no pertenece al usuario en cuestión.", $id_order));
                $response->withStatus(400);
            } else if (!Product::where('id', $id_product)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(true, "Error al crear un item, el producto no existe.", $id_order));
                $response->withStatus(400);
            } else {
                $item = new OrderItem();
                $item->id = 0;
                $item->item_status = OrderItemStatus::WAITING;
                $item->id_order = $id_order;
                $item->id_product = $id_product;

                $order = Order::where('id', $id_order)->first();
                $table = Table::where('id', $order->id_table)->first();

                if ($table == null) {
                    $response->getBody()->write(GenericResponse::obtain(true, "Error al finalizar un item, la mesa de la orden no existe.", $order->id_table));
                    $response->withStatus(400);
                } else {
                    if ($table->state != TableStatus::WAITING) {
                        $table->state = TableStatus::WAITING; // Hay un pedido? Hay gente esperando.
                        $table->save();
                    }

                    $item->save();
                    $response->getBody()->write(GenericResponse::obtain(true, "Item agregado correctamente.", $item));
                }
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear un nuevo item", null));
        }

        return $response;
    }
}
