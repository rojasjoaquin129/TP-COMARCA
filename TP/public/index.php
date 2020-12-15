<?php

use Enum\UserRole;
use Config\Database;
use Slim\Factory\AppFactory;
use Middlewares\JsonMiddleware;
use Slim\Routing\RouteCollectorProxy;
use Middlewares\Authentication\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Controllers\OrderController;
use Controllers\OrderItemController;
use Controllers\LoginController;
use Controllers\TableController;
use Controllers\UserController;
use Controllers\ReportController;
use Controllers\PollController;
use Controllers\ProductController;
use Middlewares\RegisterActionMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$conn = new Database();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Servicio funcando correctamente.");
    return $response;
});

$app->post('/file', function (Request $request, Response $response, $args) {

    $name = explode(".", $_FILES['fichero_usuario']["name"]);
    $ext = end($name);
    $codigoMesa = "ABTDFS";
    $fichero_subido = __DIR__ . "\\..\\screens\\" . "screenMesa-" . $codigoMesa . "." . $ext;
    if (move_uploaded_file($_FILES['fichero_usuario']['tmp_name'], $fichero_subido)) {
    }
    var_dump($fichero_subido);
    return $response;
});

/* -- Users -- */
$app->group('/users', function (RouteCollectorProxy $group) {
    $group->get('[/]', UserController::class . ":getAll"); 
    $group->post('[/]', UserController::class . ":addOne");
    

    
    $group->put('/ban/{id}', UserController::class . ":banUser");

    
    $group->put('/unban/{id}', UserController::class . ":unbanUser");
});

// /* -- Products -- */
$app->group('/products', function (RouteCollectorProxy $group) {
    $group->get('[/]', ProductController::class . ":getAll");
    $group->post('[/]', ProductController::class . ":addOne");
    $group->get('/{id}', ProductController::class . ":getOne");
    $group->put('/{id}', ProductController::class . ":updateOne");
    $group->delete('/{id}', ProductController::class . ":deleteOne");
});

/* -- Orders -- */
$app->group('/orders', function (RouteCollectorProxy $group) {

    // Each person just could see his own order.
    $group->get('[/]', OrderController::class . ":getAll")->add(new AuthMiddleware([
        UserRole::MOZO,
        UserRole::COCINERO,
        UserRole::ADMIN,
        UserRole::CERVECERO,
        UserRole::BARTENDER
    ]));

    // Only for waiters.
    $group->post('[/]', OrderController::class . ":addOne")->add(new AuthMiddleware([UserRole::MOZO, UserRole::ADMIN]));
    $group->get('/{id}', OrderController::class . ":getExpectedTime"); // For clients.
    $group->put('/{id}', OrderController::class . ":updateOne")->add(new AuthMiddleware([UserRole::ADMIN]));
    $group->delete('/{id}', OrderController::class . ":deleteOne")->add(new AuthMiddleware([UserRole::ADMIN]));
});

/* -- Orders Items  -- */
$app->group('/items', function (RouteCollectorProxy $group) {
    $group->get('[/]', OrderItemController::class . ":getPending");
    $group->post('[/]', OrderItemController::class . ":addOne")->add(new AuthMiddleware([UserRole::MOZO]));

    $group->put('/finish/{id}', OrderItemController::class . ":finishOrderItem")->add(new AuthMiddleware([UserRole::BARTENDER, UserRole::CERVECERO, UserRole::COCINERO, UserRole::ADMIN]));
    $group->put('/start/{id}', OrderItemController::class . ":startOrderItem")->add(new AuthMiddleware([UserRole::BARTENDER, UserRole::CERVECERO, UserRole::COCINERO, UserRole::ADMIN]));
    $group->put('/cancel/{id}', OrderItemController::class . ":cancelOrderItem")->add(new AuthMiddleware([UserRole::BARTENDER, UserRole::CERVECERO, UserRole::COCINERO, UserRole::ADMIN]));
});

/* -- Tables  -- */
$app->group('/tables', function (RouteCollectorProxy $group) {
    $group->get('[/]', TableController::class . ":getAll")->add(new AuthMiddleware([UserRole::ADMIN]));
    $group->post('[/]', TableController::class . ":addOne")->add(new AuthMiddleware([UserRole::ADMIN]));

    /* Sets table status to closed. (It must to be opened) */
    $group->put('/close/{id}', TableController::class . ":closeTable")->add(new AuthMiddleware([UserRole::ADMIN]));

    /* Sets table status to open (It must to be closed) */
    $group->put('/open/{id}', TableController::class . ":openTable")->add(new AuthMiddleware([UserRole::ADMIN]));

    /* Sets table status to client paying */
    $group->put('/paying/{id}', TableController::class . ":clientPayingTable")->add(new AuthMiddleware([UserRole::MOZO, UserRole::ADMIN]));
});

/* -- Polls  -- */
$app->group('/polls', function (RouteCollectorProxy $group) {
    $group->get('[/]', PollController::class . ":getAll")->add(new AuthMiddleware([UserRole::ADMIN]));
    $group->post('/{id_order}', PollController::class . ":addOne");
});

/* -- Reports  -- */
$app->group('/reports', function (RouteCollectorProxy $group) {
    /* Order items */
    $group->get('/items/most', ReportController::class . ":getMostSoldOrderItems")->add(new AuthMiddleware([UserRole::ADMIN])); // El más vendido.
    $group->get('/items/less', ReportController::class . ":getLessSoldOrderItems")->add(new AuthMiddleware([UserRole::ADMIN])); // El menos vendido.
    $group->get('/items/cancelled', ReportController::class . ":getCancelledOrderItems")->add(new AuthMiddleware([UserRole::ADMIN])); // Items cancelados.
    // $group->get('[/]', ReportController::class . ":getOutOfTimeOrderItems")->add(new AuthMiddleware([UserRole::ADMIN])); // El más vendido.

    /* Tables */
    $group->get('/tables/most', ReportController::class . ":getMostUsedTables")->add(new AuthMiddleware([UserRole::ADMIN]));
    $group->get('/tables/less', ReportController::class . ":getLessUsedTables")->add(new AuthMiddleware([UserRole::ADMIN]));
});

/* Authentication */
$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('[/]', LoginController::class . ":login");
});

$app->add(new JsonMiddleware());
$app->addBodyParsingMiddleware();
$app->add(new RegisterActionMiddleware());
$app->addErrorMiddleware(false, true, true);

$app->run();
