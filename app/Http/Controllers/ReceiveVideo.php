<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    Http,
    Auth,
    DB,
    Log,
    File,
    Cache,
    Validator,
    Storage
};


use FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;

class ReceiveVideo extends Controller
{
    public function handle(Request $request)
{
    // Verificação do token
    if (!$request->bearerToken() || $request->bearerToken() !== env('HEADER_ACCESS')) {
        Log::warning("Token de autenticação ausente.");
        return response()->json(['error' => 'Token de autenticação ausente'], 401);
    }



    if ($request->hasFile('video')) {
        $video = $request->file('video');
        $nome = $request->input('nome');
        $path = storage_path("app/public/recebidos");

        $userName = optional(json_decode($request->input('user'), true))['name'] ?? 'Desconhecido';
        Log::info("Requisição de {$userName} aceita");


        if (!is_dir($path)) {mkdir($path, 0777, true);}

        try {
            $startTime = microtime(true); // Início da contagem de tempo

            $ext = strtolower($video->getClientOriginalExtension());
            $baseName = pathinfo($nome, PATHINFO_FILENAME);
            $outputName = $baseName . '.mp4'; 
        
            $video->move($path, $nome); 
            $destinationPath = $path . '/' . $nome;
            $compressedPath = storage_path('app/public/recebidos/minified_' . $outputName);
            $videoName = 'minified_' . $outputName;
            ini_set('memory_limit', '-1');

            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
                'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
                'timeout' => 3600,
            ]);
        
            $videoInstance = $ffmpeg->open($destinationPath);
        
            $format = new \FFMpeg\Format\Video\X264('aac', 'libx264');
            $format->setKiloBitrate(200);
            $format->setAdditionalParameters(['-preset', 'veryslow', '-crf', '35']); 
        
            $videoInstance->save($format, $compressedPath);
        
            if (file_exists($compressedPath)) {
                unlink($destinationPath); 
                $mimeType = \Illuminate\Support\Facades\File::mimeType($compressedPath);
                $storagePath = 'videos/' . $videoName;
        
                $fileStream = fopen($compressedPath, 'r');
        
                Storage::disk('wasabi')->put($storagePath, $fileStream, [
                    'visibility' => 'public',
                    'CacheControl' => 'public, max-age=31536000, immutable',
                    'ContentType' => $mimeType,
                ]);
        
                fclose($fileStream);
        
                if (file_exists($compressedPath)) {
                    unlink($compressedPath); //
                }
        
                $url = "https://wasabi-deez.b-cdn.net/" . $storagePath;
                $elapsed = round(microtime(true) - $startTime, 2); // Tempo gasto em segundos (2 casas)

                $webhookUrl = 'https://discord.com/api/webhooks/1369055457443971233/VAMdEK4UjNfWnrKpyCShvwdOamOm5qahrCldbgkU0eWjbYW3-OZVOQdx_dSAwsckW2Ll';
                $userName = optional(json_decode($request->input('user'), true))['name'] ?? 'Desconhecido';
                Http::post($webhookUrl, ['content' => "🟩 Compressão e envio de **[{$userName}]** execudado com sucesso em **". $elapsed . "** "]);

                return response()->json(['success' => true,'videoPath' => $url]);
            }
        } catch (\Exception $e) {
            if (isset($destinationPath) && file_exists($destinationPath)) {unlink($destinationPath); }
            if (isset($compressedPath) && file_exists($compressedPath)) {unlink($compressedPath); }


            $webhookUrl = 'https://discord.com/api/webhooks/1369055002110197810/8EJkmClwRt6uUQJ4n-t21bnQzvhszwMjLBiv57lHv7XW9V1UzTCOj9vVnr-NHm3xp7wu';
            $userName = optional(json_decode($request->input('user'), true))['name'] ?? 'Desconhecido';
            Http::post($webhookUrl, ['content' => "🟥 (Antonov II) Erro ao processar vídeo de **[{$userName}]** \n\nErro: ```{$e->getMessage()}```"]);


            Log::error("Erro ao mover o vídeo.", ['erro' => $e->getMessage()]);
            return response()->json(['error' => 'Falha ao salvar o vídeo'], 500);
        }
        
    }


        Log::warning("Requisição recebida sem vídeo.");
        return response()->json(['error' => 'Nenhum vídeo enviado'], 400);
    }
}