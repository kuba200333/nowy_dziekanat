<?php
// Plik: generator_planu.php (Wersja z weryfikacją istniejących terminów)

// --- KONFIGURACJA ---
set_time_limit(0);
$nazwa_pliku_csv = 'zajecia.csv'; // Upewnij się, że nazwa pliku jest poprawna
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dziekanat";
// --------------------

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "<h1>Generator Skryptu SQL dla Planu Zajęć</h1>";

if (!file_exists($nazwa_pliku_csv)) {
    die("<p style='color:red;'>BŁĄD: Nie znaleziono pliku <strong>{$nazwa_pliku_csv}</strong>.</p>");
}

$sql_output = "-- Wygenerowano " . date('Y-m-d H:i:s') . "\n";
$sql_output .= "-- Skrypt dodaje tylko brakujące terminy zajęć z pliku {$nazwa_pliku_csv}\n\n";
$log_bledow = [];
$licznik_nowych = 0;
$licznik_pominietych = 0;

// Przygotowujemy zapytania, aby używać ich w pętli
$stmt_sala = $conn->prepare("SELECT sala_id FROM SaleZajeciowe WHERE budynek = ? AND numer_sali = ? LIMIT 1");
$stmt_prowadzacy = $conn->prepare("SELECT prowadzacy_id FROM Prowadzacy WHERE imie = ? AND nazwisko = ? LIMIT 1");
$stmt_zajecia = $conn->prepare("
    SELECT z.zajecia_id 
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    WHERE p.nazwa_przedmiotu = ? AND g.nazwa_grupy = ? AND z.forma_zajec = ?
    LIMIT 1
");
// NOWE ZAPYTANIE: Sprawdza, czy termin już istnieje
$stmt_check_termin = $conn->prepare("SELECT termin_id FROM TerminyZajec WHERE zajecia_id = ? AND data_zajec = ? AND godzina_rozpoczecia = ?");


if (($handle = fopen($nazwa_pliku_csv, "r")) !== FALSE) {
    fgetcsv($handle, 1000, ";"); // Pomiń nagłówek

    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Mapowanie kolumn z CSV
        $start_str = $row[0];
        $end_str = $row[1];
        $prowadzacy_str = trim($row[2]);
        $forma_str = trim($row[3]);
        $grupa_str = trim($row[4]);
        $sala_str = trim($row[5]);
        $przedmiot_str = trim($row[6]);

        // Przetwarzanie danych
        $start_dt = new DateTime($start_str);
        $end_dt = new DateTime($end_str);
        $data_zajec = $start_dt->format('Y-m-d');
        $godzina_start = $start_dt->format('H:i:s');
        $godzina_end = $end_dt->format('H:i:s');
        $forma_zajec = ucfirst(strtolower($forma_str));

        $prowadzacy_czesci = explode(' ', $prowadzacy_str, 2);
        $imie_prow = $prowadzacy_czesci[1] ?? '';
        $nazwisko_prow = $prowadzacy_czesci[0] ?? '';
        
        $budynek = '';
        $numer_sali = '';
        if (preg_match('/(WI\sWI[12])-\s*(.*)/', $sala_str, $matches)) {
            $budynek = trim($matches[1]);
            $numer_sali = trim($matches[2]);
        } elseif (preg_match('/(IiJM BMW)\s*(.*)/', $sala_str, $matches)) {
            $budynek = trim($matches[1]);
            $numer_sali = trim($matches[2]);
        }

        // Wyszukiwanie ID w bazie
        $sala_id = null;
        $stmt_sala->bind_param("ss", $budynek, $numer_sali);
        $stmt_sala->execute();
        $res_sala = $stmt_sala->get_result();
        if($res_sala->num_rows > 0) $sala_id = $res_sala->fetch_assoc()['sala_id'];

        $prowadzacy_id = null;
        $stmt_prowadzacy->bind_param("ss", $imie_prow, $nazwisko_prow);
        $stmt_prowadzacy->execute();
        $res_prow = $stmt_prowadzacy->get_result();
        if($res_prow->num_rows > 0) $prowadzacy_id = $res_prow->fetch_assoc()['prowadzacy_id'];

        $zajecia_id = null;
        $stmt_zajecia->bind_param("sss", $przedmiot_str, $grupa_str, $forma_zajec);
        $stmt_zajecia->execute();
        $res_zajecia = $stmt_zajecia->get_result();
        if($res_zajecia->num_rows > 0) $zajecia_id = $res_zajecia->fetch_assoc()['zajecia_id'];
        
        // Sprawdzenie, czy wszystko znaleziono
        if ($sala_id && $prowadzacy_id && $zajecia_id) {
            // SPRAWDZANIE, CZY TERMIN JUŻ ISTNIEJE
            $stmt_check_termin->bind_param("iss", $zajecia_id, $data_zajec, $godzina_start);
            $stmt_check_termin->execute();
            $check_result = $stmt_check_termin->get_result();

            if ($check_result->num_rows === 0) {
                // Jeśli nie istnieje, generujemy zapytanie
                $sql_output .= "INSERT INTO TerminyZajec (zajecia_id, prowadzacy_id, sala_id, data_zajec, godzina_rozpoczecia, godzina_zakonczenia, status) VALUES ('$zajecia_id', '$prowadzacy_id', '$sala_id', '$data_zajec', '$godzina_start', '$godzina_end', 'planowe');\n";
                $licznik_nowych++;
            } else {
                $licznik_pominietych++;
            }
        } else {
            $bledy_wiersza = [];
            if (!$sala_id) $bledy_wiersza[] = "nie znaleziono sali '$sala_str'";
            if (!$prowadzacy_id) $bledy_wiersza[] = "nie znaleziono prowadzącego '$prowadzacy_str'";
            if (!$zajecia_id) $bledy_wiersza[] = "nie znaleziono zajęć dla przedmiotu '$przedmiot_str' w grupie '$grupa_str'";
            $log_bledow[] = "Pominięto wiersz: [" . implode('; ', $row) . "] -> Powód: " . implode(', ', $bledy_wiersza) . ".";
        }
    }
    fclose($handle);
}

echo "<h2>Zakończono generowanie.</h2>";
echo "<p style='color:green;'>Liczba nowych terminów do dodania: <strong>$licznik_nowych</strong></p>";
echo "<p style='color:orange;'>Liczba terminów pominiętych (już istnieją w bazie): <strong>$licznik_pominietych</strong></p>";

if (!empty($log_bledow)) {
    echo "<h3>Szczegółowy log pominiętych wierszy:</h3>";
    echo "<textarea rows='10' style='width:100%; font-family: monospace;'>" . implode("\n", $log_bledow) . "</textarea>";
}

echo "<h2>Gotowy Skrypt SQL</h2>";
echo "<p>Skopiuj całą poniższą zawartość i wklej do zakładki SQL w phpMyAdmin, aby dodać tylko brakujące terminy zajęć.</p>";
echo "<textarea rows='20' style='width:100%; font-family: monospace;'>" . htmlspecialchars($sql_output) . "</textarea>";

$conn->close();
?>