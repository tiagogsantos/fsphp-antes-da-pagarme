<?php
ob_start();

require __DIR__. "/vendor/autoload.php";

/**
 * BOOTSTRAP
 */

use Source\Core\Session;
use CoffeeCode\Router\Router;

$session = new Session();
$route = new Router(url(), ":");


/*
 * WEB ROUTES - Aqui digo aonde será a raiz do meu site
 */
$route->namespace("Source\App");
$route->get("/", "Web:Home");
$route->get("/sobre", "Web:about");

/*
 * Blog
 */
$route->group("/blog");
$route->get("/", "Web:blog");
$route->get("/p/{page}", "Web:blog");
$route->get("/{uri}", "Web:blogPost");
$route->post("/buscar", "Web:blogSearch");
$route->get("/buscar/{terms}/{page}", "Web:blogSearch");
$route->get("/em/{category}", "Web:blogCategory");
$route->get("/em/{category}/{page}", "Web:blogCategory");


/*
 * Author
 */
$route->get("/blog/a/{author}", "Web:blogAuthor");
$route->get("/blog/a/{author}/{page}", "Web:blogAuthor");


/*
 * Paginas de Entrar, Recuperar e Cadastrar
 */

// auth
$route->group(null);
$route->get("/entrar", "Web:login");
$route->post("/entrar", "Web:login");

$route->get("/cadastrar", "Web:register");
$route->post("/cadastrar", "Web:register");

$route->get("/recuperar", "Web:forget");
$route->post("/recuperar", "Web:forget");

$route->get("/recuperar/{code}", "Web:reset");
$route->post("/recuperar/resetar", "Web:reset");

// Optin
$route->get("/confirma", "Web:confirm");
$route->get("/obrigado/{email}", "Web:success");

/**
 * APP
 */
$route->group("/app");
$route->get("/", "App:home");
$route->get("/receber", "App:income");
$route->get("/receber/{status}/{category}/{date}", "App:income");
$route->get("/pagar", "App:expense");
$route->get("/pagar/{status}/{category}/{date}", "App:expense");
$route->get("/fixas", "App:fixed");
$route->get("/carteiras", "App:wallets");
$route->get("/fatura/{invoice}", "App:invoice");

$route->post("/dash", "App:dash");
$route->post("/launch", "App:launch");
$route->post("/invoice/{invoice}", "App:invoice");
$route->post("/remove/{invoice}", "App:remove");
$route->post("/", "App:support");
$route->post("/onpaid", "App:onpaid");
$route->post("/filter", "App:filter");
$route->post("/profile", "App:profile");
$route->post("/wallets/{wallet}", "App:wallets");

$route->get("/perfil", "App:profile");
$route->get("/sair", "App:logout");

// services ou serviços

$route->get("/termos", "Web:terms");

/*
 * ERROR ROUTES - Desta forma eu digo que deu erro e qual será o código de erro
 */
$route->namespace("Source\App")->group("/ops");
$route->get("/{errcode}", "Web:error");

/*
 * ROUTE - Poder executar a rota que está sendo acessada
 */
$route->dispatch();

/*
 * ERROR REDIRECT
 * o if abaixo quer dizer quer dizer que nao conseguiu despachar
 */

if ($route->error()) {
    $route->redirect("/ops/{$route->error()}");
}

ob_end_flush();