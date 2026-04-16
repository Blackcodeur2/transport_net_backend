<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.openai.api_key');
$model = config('services.openai.model', 'llama-3.3-70b-versatile');

$c = new \App\Http\Controllers\Api\AssistantIaController();
$reflection = new \ReflectionClass($c);
$m = $reflection->getMethod('getToolsDefinition');
$m->setAccessible(true);
$tools = $m->invoke($c);

$response = \Illuminate\Support\Facades\Http::withoutVerifying()
    ->withToken($apiKey)
    ->post('https://api.groq.com/openai/v1/chat/completions', [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => 'hello']],
        'tools' => $tools,
        'tool_choice' => 'auto',
    ]);

echo $response->body();
