<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function downloadFile($url, $savePath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $fileContent = curl_exec($ch);
    if ($fileContent === false) {
        throw new Exception("Не удалось скачать файл: " . curl_error($ch));
    }
    curl_close($ch);
    file_put_contents($savePath, $fileContent);
}

function getRowFromXlsx($filePath, $name, $birthDate) {
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    // Формат даты: ГГГГ-ММ-ДД
    $formattedBirthDate = date('Y-m-d', strtotime($birthDate));

    // Получаем заголовки
    $headerRow = $sheet->getRowIterator()->current();
    $cellIterator = $headerRow->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);

    $headers = [];
    foreach ($cellIterator as $cell) {
        $headers[] = $cell->getValue();
    }

    // Находим индексы fio и dateOfBirth и работаем дальше с ними :)
    $fioIndex = array_search('fio', $headers);
    $dateOfBirthIndex = array_search('dateOfBirth', $headers);

    if ($fioIndex === false || $dateOfBirthIndex === false) {
        throw new Exception('Не найдены необходимые колонки в файле.');
    }
    foreach ($sheet->getRowIterator(2) as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getValue();
        }
        if (isset($rowData[$fioIndex]) && isset($rowData[$dateOfBirthIndex])) {
            if ($rowData[$fioIndex] === $name && $rowData[$dateOfBirthIndex] === $formattedBirthDate) {
                return $rowData;
            }
        }
    }

    return null;
}

$url = 'https://xn--b1aew.xn--p1ai/bannedfans/export/';
$filePath = 'banned_fans.xlsx';
downloadFile($url, $filePath);
//Пример правильного использования
$fullName = 'Тюрин Максим Всеволодович';
$birthDate = '1987-12-31';
if(getRowFromXlsx($filePath, $fullName, $birthDate)){
print_r(getRowFromXlsx($filePath, $fullName, $birthDate));}
else{
    echo "Совпадений не найдено";
}