<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    Http, Log, Storage, File
};
use FFMpeg;
use FFMpeg\Format\Video\X264;

class ReceiveVideo extends Controller
{
    public function handle(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['error' => 'Token de autenticação ausente'], 401);
        }

        if (!$request->hasFile('video')) {
            Log::warning("Requisição recebida sem vídeo.");
            return response()->json(['error' => 'Nenhum vídeo enviado'], 400);
        }

        $startTime = microtime(true);
        $userName = $this->getUserName($request);
        $video = $request->file('video');
        $nome = $request->input('nome');
        $ext = strtolower($video->getClientOriginalExtension());
        $baseName = pathinfo($nome, PATHINFO_FILENAME);
        $outputName = 'minified_' . $baseName . '.mp4';

        $localPath = storage_path("app/public/recebidos");
        $originalPath = $localPath . '/' . $nome;
        $compressedPath = $localPath . '/' . $outputName;
        $storageKey = 'videos/' . $outputName;

        File::ensureDirectoryExists($localPath);

        try {
            ini_set('memory_limit', '-1');
            Log::info("Requisição de {$userName} aceita");

            $video->move($localPath, $nome);

            $ffmpeg = FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
                'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
                'timeout' => 3600,
            ]);

            $videoInstance = $ffmpeg->open($originalPath);

            $format = new X264('aac', 'libx264');
            $format->setKiloBitrate(200);
            $format->setAdditionalParameters(['-preset', 'slow', '-crf', '28']);

            $videoInstance->save($format, $compressedPath);

            unlink($originalPath);

            $this->uploadToWasabi($compressedPath, $storageKey);

            unlink($compressedPath);

            $elapsed = round(microtime(true) - $startTime, 2);
            $url = "https://wasabi-deez.b-cdn.net/{$storageKey}";

            $this->sendWebhook(
                'https://discord.com/api/webhooks/1369055457443971233/VAMdEK4UjNfWnrKpyCShvwdOamOm5qahrCldbgkU0eWjbYW3-OZVOQdx_dSAwsckW2Ll',
                "🟩 Compressão e envio de **[{$userName}]** executado com sucesso em **{$elapsed}** segundos"
            );

            return response()->json(['success' => true, 'videoPath' => $url]);

        } catch (\Exception $e) {
            $this->cleanUp([$originalPath, $compressedPath]);

            $this->sendWebhook(
                'https://discord.com/api/webhooks/1369055002110197810/8EJkmClwRt6uUQJ4n-t21bnQzvhszwMjLBiv57lHv7XW9V1UzTCOj9vVnr-NHm3xp7wu',
                "🟥 (Antonov II) Erro ao processar vídeo de **[{$userName}]**\n\nErro: ```{$e->getMessage()}```"
            );

            Log::error("Erro ao processar vídeo.", ['erro' => $e->getMessage()]);
            return response()->json(['error' => 'Falha ao salvar o vídeo'], 500);
        }
    }

    private function isAuthorized(Request $request): bool
    {
        return $request->bearerToken() === env('HEADER_ACCESS');
    }

    private function getUserName(Request $request): string
    {
        return optional(json_decode($request->input('user'), true))['name'] ?? 'Desconhecido';
    }

    private function uploadToWasabi(string $filePath, string $key): void
    {
        $mimeType = File::mimeType($filePath);
        $stream = fopen($filePath, 'r');

        Storage::disk('wasabi')->put($key, $stream, [
            'visibility'    => 'public',
            'CacheControl'  => 'public, max-age=31536000, immutable',
            'ContentType'   => $mimeType,
        ]);

        fclose($stream);
    }

    private function sendWebhook(string $url, string $message): void
    {
        Http::post($url, ['content' => $message]);
    }

    private function cleanUp(array $paths): void
    {
        foreach ($paths as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
