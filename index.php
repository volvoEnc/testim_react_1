<?php

require_once 'vendor/autoload.php';
require_once 'http_codes.php';


function getHtmlContent($filename) {
    return file_get_contents($filename);
}

$users = [];

$requestHandler = function (\Psr\Http\Message\ServerRequestInterface $request) use (&$users) {
    $requestPath = $request->getUri()->getPath();
    $requestMethod = $request->getMethod();
    $ip = $request->getServerParams()["REMOTE_ADDR"];

    if ($requestPath == '/info') {
        return new \React\Http\Message\Response(
            200,
            ['Content-Type' => 'text/html; charset: windows-1251'],
            getHtmlContent('rules.html')
        );
    }

    if ($users[$ip] == 5) {
        $users[$ip] = 35;
        echo "User {$ip} is banned!\n";
    }

    if ($users[$ip] > 5 && $users[$ip] <= 35) {
        return new \React\Http\Message\Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['hello' => 'Упс, я случайно тебя заблокировал по IP на ' . ((int)$users[$ip] - 6) . ' сек.'], JSON_UNESCAPED_UNICODE)
        );
    } else {
        $users[$ip] += 1;
    }


    if ($requestMethod != 'GET') {
        return new \React\Http\Message\Response(
            405
        );
    }
    if ($requestPath != '/random') {
        return new \React\Http\Message\Response(
            404
        );
    }

    $code = isset($request->getQueryParams()['code']) ? $request->getQueryParams()['code'] : null;
    $allowCodes = getAllCodes();

    if (!empty($code)) {
        if (!array_key_exists($code, $allowCodes)) {
            return new \React\Http\Message\Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['hello' => 'Тебе в дурку пора с такими кодами'], JSON_UNESCAPED_UNICODE)
            );
        }
        return new \React\Http\Message\Response(
            (int)$code
        );
    }

    return new \React\Http\Message\Response(
        (int)array_rand($allowCodes)
    );
};

$loop = React\EventLoop\Factory::create();
$loop->addPeriodicTimer(1, function () use (&$users) {
    foreach ($users as $ip => &$user) {
        if ($user > 6 && $user <= 35) {
            $user--;
        }
        if ($user == 6) {
            $user = 0;
            echo "User {$ip} un banned!\n";
        }
    }
});



$server = new \React\Http\Server($loop, $requestHandler);
$socket = new \React\Socket\Server(7030, $loop);
$server->listen($socket);


echo 'Server is started. Listing: localhost:7030' . PHP_EOL;
$loop->run();