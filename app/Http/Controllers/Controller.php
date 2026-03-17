<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "YukiMart API",
    description: "YukiMart E-Commerce API Documentation",
    contact: new OA\Contact(name: "YukiMart Team", email: "admin@yukimart.com")
)]
#[OA\Server(url: "http://localhost:8000", description: "Local Development")]
#[OA\Server(url: "https://api.yukimart.com", description: "Production")]
abstract class Controller
{
    //
}
