<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;

class ClientsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Просто возвращаем данные вместо создания модели
        return [
            'id' => $row[0],
            'name' => $row[1],
            'concurent' => $row[1],
            'gorod' => $row[1],
            'indeks' => $row[1],
            'region' => $row[1],
            'gorod' => $row[1],
            'adress' => $row[1],
            'house' => $row[1],
            'corpus' => $row[1],
            'flat' => $row[1],
            'inn' => $row[1],
            'assigned' => $row[1],
            'perspect' => $row[1],
            'category' => $row[1],

            'prognoz' => $row[1],
            'statusk' => $row[1],
            'commaent' => $row[1],
            'commaent' => $row[1],



            'contacts' => [],
            'events' => []
        ];
    }
}
