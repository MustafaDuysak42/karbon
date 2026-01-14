<?php

declare(strict_types=1);

class CBAMEngine
{
    public function calculate(array $processes, array $emissions): array
    {
        $direct = 0.0;
        $indirect = 0.0;
        $precursor = 0.0;

        foreach ($emissions as $emission) {
            $amount = (float)$emission['amount'];
            $ef = (float)$emission['ef_factor'];
            if ($emission['type'] === 'direct') {
                $direct += $amount * $ef;
                continue;
            }
            $indirect += $amount * $ef;
        }

        $totalProduction = array_reduce($processes, function ($carry, $item) {
            return $carry + (float)$item['production_amount'];
        }, 0.0);

        $see = 0.0;
        if ($totalProduction > 0) {
            $see = ($direct + $indirect + $precursor) / $totalProduction;
        }

        return [
            'direct' => $direct,
            'indirect' => $indirect,
            'precursor' => $precursor,
            'total_production' => $totalProduction,
            'see' => $see,
        ];
    }
}
