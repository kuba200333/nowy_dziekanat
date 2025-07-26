<?php
// Plik: generator.php

// --- Konfiguracja ---
$nazwa_pliku_csv = 'zajecia.csv';
$rok_akademicki_id_docelowy = 1; // ID roku, do którego przypisujemy konfiguracje
// --------------------

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Generator Skryptu SQL na podstawie pliku CSV</h1>";

if (!file_exists($nazwa_pliku_csv)) {
    die("<p style='color:red;'>BŁĄD: Nie znaleziono pliku <strong>{$nazwa_pliku_csv}</strong> w tym samym folderze co skrypt.</p>");
}

$sql_output = "-- Wygenerowano " . date('Y-m-d H:i:s') . "\n";
$sql_output .= "-- Skrypt dodaje zajęcia z pliku {$nazwa_pliku_csv}\n";
$sql_output .= "-- Rok akademicki ID: {$rok_akademicki_id_docelowy}\n\n";

$unikalne_wpisy = [];

if (($handle = fopen($nazwa_pliku_csv, "r")) !== FALSE) {
    // Pomiń nagłówek
    fgetcsv($handle, 1000, ";");

    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($data) < 4) continue;

        $prowadzacy_raw = trim($data[0]);
        $forma_raw = trim($data[1]);
        $grupa_raw = trim($data[2]);
        $przedmiot_raw = trim($data[3]);
        
        // Przetwarzanie danych
        $prowadzacy_czesci = explode(' ', $prowadzacy_raw, 2);
        $nazwisko = $prowadzacy_czesci[0];
        $imie = $prowadzacy_czesci[1] ?? '';

        $forma_poprawna = ucfirst(strtolower($forma_raw));

        // Tworzenie unikalnego klucza, aby uniknąć duplikatów z pliku CSV
        $klucz = md5($nazwisko . $imie . $forma_poprawna . $grupa_raw . $przedmiot_raw);
        
        if (!isset($unikalne_wpisy[$klucz])) {
            $unikalne_wpisy[$klucz] = [
                'nazwisko' => $nazwisko,
                'imie' => $imie,
                'forma' => $forma_poprawna,
                'grupa' => $grupa_raw,
                'przedmiot' => $przedmiot_raw
            ];
        }
    }
    fclose($handle);
}

// Generowanie zapytań SQL
foreach($unikalne_wpisy as $wpis) {
    $sql_output .= "\n-- Dodaj zajęcia dla: {$wpis['nazwisko']} {$wpis['imie']}, Grupa: {$wpis['grupa']}, Przedmiot: {$wpis['przedmiot']}\n";
    $sql_output .= "INSERT INTO Zajecia (konfiguracja_id, prowadzacy_id, grupa_id, forma_zajec)\n";
    $sql_output .= "SELECT\n";
    $sql_output .= "    (SELECT k.konfiguracja_id FROM KonfiguracjaPrzedmiotu k JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id WHERE p.nazwa_przedmiotu = '" . addslashes($wpis['przedmiot']) . "' AND k.rok_akademicki_id = {$rok_akademicki_id_docelowy} LIMIT 1),\n";
    $sql_output .= "    (SELECT prowadzacy_id FROM Prowadzacy WHERE imie = '" . addslashes($wpis['imie']) . "' AND nazwisko = '" . addslashes($wpis['nazwisko']) . "' LIMIT 1),\n";
    $sql_output .= "    (SELECT grupa_id FROM GrupyZajeciowe WHERE nazwa_grupy = '" . addslashes($wpis['grupa']) . "' LIMIT 1),\n";
    $sql_output .= "    '" . addslashes($wpis['forma']) . "'\n";
    $sql_output .= "WHERE NOT EXISTS (\n";
    $sql_output .= "    SELECT 1 FROM Zajecia z\n";
    $sql_output .= "    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id\n";
    $sql_output .= "    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id\n";
    $sql_output .= "    JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id\n";
    $sql_output .= "    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id\n";
    $sql_output .= "    WHERE p.nazwa_przedmiotu = '" . addslashes($wpis['przedmiot']) . "'\n";
    $sql_output .= "      AND g.nazwa_grupy = '" . addslashes($wpis['grupa']) . "'\n";
    $sql_output .= "      AND pr.imie = '" . addslashes($wpis['imie']) . "' AND pr.nazwisko = '" . addslashes($wpis['nazwisko']) . "'\n";
    $sql_output .= "      AND z.forma_zajec = '" . addslashes($wpis['forma']) . "'\n";
    $sql_output .= ");\n";
}

echo "<h2>Gotowy Skrypt SQL</h2>";
echo "<p>Skopiuj całą poniższą zawartość i wklej do zakładki SQL w phpMyAdmin, aby dodać brakujące zajęcia.</p>";
echo "<textarea rows='20' style='width:100%; font-family: monospace;'>" . htmlspecialchars($sql_output) . "</textarea>";

?>