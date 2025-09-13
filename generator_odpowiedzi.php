<?php
// Plik: generator_odpowiedzi.php

// --- KONFIGURACJA ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dziekanat";
$nazwa_pliku_wejsciowego = 'user_groups_with_names.txt';
// --------------------

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "<h1>Generator Skryptu SQL - Zapisywanie Odpowiedzi Studentów</h1>";

if (!file_exists($nazwa_pliku_wejsciowego)) {
    die("<p style='color:red;'>BŁĄD: Nie znaleziono pliku <strong>{$nazwa_pliku_wejsciowego}</strong>.</p>");
}

// Mapa tłumacząca fragment nazwy grupy na nazwę przedmiotu
$mapa_grup_na_przedmioty = [
    '31_BO3_Tm_W' => 'Technika mikroprocesorowa',
    'BO8_Pd_W' => 'Produkcja dźwięku',
    'BO8_Gw_W' => 'Grafika webowa',
    '33_BO3_Ai1_W' => 'Aplikacje internetowe 1',
    'BO8_Gk_W' => 'Gry komputerowe',
    '31_BO3_Kb_W' => 'Komunikacja bezprzewodowa',
    '32_BO3_Priw_W' => 'Programowanie równoległe i współbieżne',
    '33_BO3_Ii_W' => 'Infrastruktura informatyczna',
    '32_BO3_Kc-m_W' => 'Komunikacja człowiek-maszyna'
];

// Wczytaj do pamięci wszystkie możliwe opcje wyboru
$opcje_w_bazie = [];
$res_opcje = $conn->query("SELECT ow.opcja_id, ow.wybor_id, p.nazwa_przedmiotu FROM OpcjeWyboru ow JOIN Przedmioty p ON ow.przedmiot_id = p.przedmiot_id");
while ($row = $res_opcje->fetch_assoc()) {
    $opcje_w_bazie[strtolower($row['nazwa_przedmiotu'])] = [
        'opcja_id' => $row['opcja_id'],
        'wybor_id' => $row['wybor_id']
    ];
}

$sql_output = "-- Wygenerowano " . date('Y-m-d H:i:s') . "\n";
$sql_output .= "-- Skrypt dodaje odpowiedzi studentów na podstawie przypisania do grup.\n\n";
$log_bledow = [];
$licznik_sukces = 0;

$linie = file($nazwa_pliku_wejsciowego, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($linie as $linia) {
    $czesci = explode(';', $linia);
    if (count($czesci) < 4) continue;

    $numer_albumu = trim($czesci[1]);
    $grupy_str = trim($czesci[3]);
    $grupy = array_map('trim', explode(',', $grupy_str));

    if (empty($numer_albumu)) continue;

    foreach ($grupy as $grupa) {
        // Sprawdź, czy grupa pasuje do naszej mapy
        if (isset($mapa_grup_na_przedmioty[$grupa])) {
            $nazwa_przedmiotu = $mapa_grup_na_przedmioty[$grupa];
            
            // Sprawdź, czy dla tego przedmiotu istnieje opcja wyboru
            if (isset($opcje_w_bazie[strtolower($nazwa_przedmiotu)])) {
                $opcja = $opcje_w_bazie[strtolower($nazwa_przedmiotu)];
                $wybor_id = $opcja['wybor_id'];
                $opcja_id = $opcja['opcja_id'];
                $data_odpowiedzi = date('Y-m-d H:i:s');
                
                $sql_output .= "INSERT IGNORE INTO OdpowiedziStudentow (wybor_id, numer_albumu, wybrana_opcja_id, data_odpowiedzi) VALUES ('$wybor_id', '$numer_albumu', '$opcja_id', '$data_odpowiedzi');\n";
                $licznik_sukces++;
            } else {
                $log_bledow[] = "Student $numer_albumu: znaleziono przedmiot '$nazwa_przedmiotu' z grupy '$grupa', ale nie ma go w tabeli OpcjeWyboru.";
            }
        }
    }
}

echo "<h2>Zakończono generowanie.</h2>";
echo "<p style='color:green;'>Pomyślnie wygenerowano unikalnych zapytaŃ INSERT: <strong>$licznik_sukces</strong></p>";
if (!empty($log_bledow)) {
    echo "<h3>Szczegółowy log błędów/ostrzeżeń:</h3>";
    echo "<textarea rows='5' style='width:100%; font-family: monospace;'>" . implode("\n", $log_bledow) . "</textarea>";
}
echo "<h2>Gotowy Skrypt SQL</h2>";
echo "<p>Skopiuj całą poniższą zawartość i wklej do zakładki SQL w phpMyAdmin.</p>";
echo "<textarea rows='20' style='width:100%; font-family: monospace;'>" . htmlspecialchars($sql_output) . "</textarea>";

$conn->close();
?>