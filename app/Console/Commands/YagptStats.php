<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class YagptStats extends Command
{
    protected $signature = 'yagpt:stats';

    protected $description = 'Show YandexGPT usage statistics from local logs';

    public function handle(): int
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            $this->warn('Лог-файл не найден.');

            return 1;
        }

        $lines = file($logFile);
        $requests = [];
        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $errorCount = 0;

        foreach ($lines as $line) {
            if (str_contains($line, 'YandexGPT API error')) {
                $errorCount++;
            }
            if (str_contains($line, 'YandexGPT request')) {
                $json = json_decode(trim(substr($line, strpos($line, '{'))), true);
                if ($json) {
                    $requests[] = $json;
                    $totalInputTokens += $json['input_tokens'] ?? 0;
                    $totalOutputTokens += $json['output_tokens'] ?? 0;
                }
            }
        }

        $this->info('=== Статистика YandexGPT ===');
        $this->newLine();
        $this->line('Всего запросов: <info>'.count($requests).'</info>');
        $this->line("Ошибок: <error>$errorCount</error>");
        $this->line("Входных токенов: <info>$totalInputTokens</info>");
        $this->line("Выходных токенов: <info>$totalOutputTokens</info>");

        $totalTokens = $totalInputTokens + $totalOutputTokens;
        $cost = round($totalTokens / 1000 * 0.2, 2);
        $this->line("Примерная стоимость: <info>$cost ₽</info>");
        $this->line('(тариф YandexGPT Lite: ~0.2 ₽/1000 токенов)');
        $this->newLine();

        if (count($requests) > 0) {
            $this->line('=== Последние запросы ===');
            foreach (array_slice($requests, -5) as $i => $r) {
                $this->line("  #{$i}: {$r['input_tokens']} → {$r['output_tokens']} токенов");
            }
        }

        return 0;
    }
}
