<?php

namespace Controllers;

use Models\Table;
use Enum\TableStatus;
use Components\GenericResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TableController
{
    public function getAll(Request $request, Response $response, $args)
    {
        try {
            $tables = Table::all();
            $response->getBody()->write(GenericResponse::obtain(true, "", $tables));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de obtener las mesas.", null));
            $response->withStatus(500);
        }

        return $response;
    }

    // Abrir mesa solo socio
    public function openTable(Request $request, Response $response, $args)
    {
        try {
            $id_table = $args['id'] ?? '';

            if (empty($id_table)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Debe especificar el id de la mesa..", $id_table));
                $response->withStatus(400);
                // 
            } else if (!Table::where('id', $id_table)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(false, "La mesa no existe..", $id_table));
                $response->withStatus(400);
            } else {
                $table = Table::where('id', $id_table)->first();
                $table->state = TableStatus::OPEN;
                $table->save();
                $response->getBody()->write(GenericResponse::obtain(true, "Mesa establecida en estado ABIERTA correctamente.", $table));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear una nueva mesa.", null));
        }

        return $response;
    }

    // Cerrar mesa solo socio
   
    public function closeTable(Request $request, Response $response, $args)
    {
        try {
            $id_table = $args['id'] ?? '';

            if (empty($id_table)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Debe especificar el id de la mesa..", $id_table));
                $response->withStatus(400);
                // 
            } else if (!Table::where('id', $id_table)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(false, "La mesa no existe..", $id_table));
                $response->withStatus(400);
            } else if (Table::where('id', $id_table)->first()->state != TableStatus::OPEN) {
                $response->getBody()->write(GenericResponse::obtain(false, "La mesa debe estar libre para ser cerrada..", Table::where('id', $id_table)->first()->state));
                $response->withStatus(400);
            } else {
                $table = Table::where('id', $id_table)->first();
                $table->state = TableStatus::CLOSED;
                $table->save();
                $response->getBody()->write(GenericResponse::obtain(true, "Mesa establecida en estado de cierre correctamente.", $table));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de cerrar una nueva mesa.", null));
        }

        return $response;
    }

    // Con socio pagando, mozo y socio
    
    public function clientPayingTable(Request $request, Response $response, $args)
    {
        try {
            $id_table = $args['id'] ?? '';

            if (empty($id_table)) {
                $response->getBody()->write(GenericResponse::obtain(false, "Debe especificar el id de la mesa..", $id_table));
                $response->withStatus(400);
                // 
            } else if (!Table::where('id', $id_table)->exists()) {
                $response->getBody()->write(GenericResponse::obtain(false, "La mesa no existe..", $id_table));
                $response->withStatus(400);
            } else if (Table::where('id', $id_table)->first()->state != TableStatus::EATING) {
                $response->getBody()->write(GenericResponse::obtain(false, "Deben haber clientes comiendo para pasar a una instancia de cobro..", Table::where('id', $id_table)->first()->state));
                $response->withStatus(400);
            } else {
                $table = Table::where('id', $id_table)->first();
                $table->state = TableStatus::PAYING;
                $table->save();
                $response->getBody()->write(GenericResponse::obtain(true, "Mesa establecida en estado de pago correctamente.", $table));
            }
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de pasar una messa a estado PAGANDO.", null));
        }

        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        try {
            $table = new Table;
            $table->id = 0;
            $table->state = TableStatus::OPEN;
            $table->guid = $this->gen_uuid();
            $table->save();
            $response->getBody()->write(GenericResponse::obtain(true, "Mesa creada correctamente.", $table));
        } catch (\Exception $e) {
            $response->getBody()->write(GenericResponse::obtain(false, "Error a la hora de crear una nueva mesa.", null));
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
