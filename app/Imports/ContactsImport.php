<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ContactsImport implements ToCollection
{
    public function collection(Collection $rows)
    {


        $contacts = $rows->map(function ($row) {
            return $this->createContact($row); // Создание клиента на основе каждой строки
        });

        return $contacts; // Теперь возвращает измененный массив
    }



    private function createContact($row)
    {
        // Предполагая, что данные контактов также содержатся в строке
        return [
            'clientId' => trim($row[0]),
            'name' => $row[1], // Пример, адаптируйте к вашей структуре
            'phone' => $row[2]  // Пример, адаптируйте к вашей структуре
        ];
    }
}
