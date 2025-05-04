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
    if (!$request->bearerToken() || $request->bearerToken() !== 'Jib8RwDEIzwS87Emj') {
        Log::warning("Token de autenticação ausente.");
        return response()->json(['error' => 'Token de autenticação ausente'], 401);
    }

    /*

    Log::info('Recebida uma nova solicitação para upload de vídeo.', [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'inputs' => $request->all(),
    ]);
    
    */

    if ($request->hasFile('video')) {
        $video = $request->file('video');
        $nome = $request->input('nome');
        $path = storage_path("app/public/recebidos");


        if (!is_dir($path)) {mkdir($path, 0777, true);}

        try {

            $video->move($path, $nome);
            $destinationPath = $path . '/' . $nome;
            $compressedPath = storage_path('app/public/recebidos/minified_' . $nome);
        
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
                'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
                'timeout' => 3600,
                'ffmpeg.threads' => 12,
            ]);
        
            $videoInstance = $ffmpeg->open($destinationPath);
        
            $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');
            $format->setKiloBitrate(200);
            $format->setAdditionalParameters(['-preset', 'veryslow', '-crf', '35']); 
        
            $videoInstance->save($format, $compressedPath);
        
            if (file_exists($compressedPath)) {
                unlink($destinationPath);
                $videoName = 'minified_' . $nome;
                $mimeType = \Illuminate\Support\Facades\File::mimeType($compressedPath);
                $storagePath = 'videos/' . $videoName;
        
                // Abre stream do vídeo comprimido
                $fileStream = fopen($compressedPath, 'r');
        
                Storage::disk('wasabi')->put($storagePath, $fileStream, [
                    'visibility' => 'public',
                    'CacheControl' => 'public, max-age=31536000, immutable',
                    'ContentType' => $mimeType,
                ]);
        
                fclose($fileStream);
        
                if (file_exists($compressedPath)) {
                    unlink($compressedPath);
                }

                $url = "https://wasabi-deez.b-cdn.net/" . $storagePath;
        
                return response()->json([
                    'success' => true,
                    'videoPath' => $url
                ]);
            }
        
        }  catch (\Exception $e) {
            
                Log::error("Erro ao mover o vídeo.", ['erro' => $e->getMessage()]);
                return response()->json(['error' => 'Falha ao salvar o vídeo'], 500);
            }
        }

        Log::warning("Requisição recebida sem vídeo.");
        return response()->json(['error' => 'Nenhum vídeo enviado'], 400);
    }
}