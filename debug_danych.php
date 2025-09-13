<?php
// Plik: generator_zajec_z_csv.php (Wersja Ostateczna - Pełna Synchronizacja)

// --- Konfiguracja ---
$nazwa_pliku_csv = 'zajecia.csv'; // Upewnij się, że nazwa pliku to 'grupy.csv'
$rok_akademicki_id_docelowy = 1;
$domyslny_semestr_dla_nowych_grup = 5; // Semestr dla nowo tworzonych grup
// --------------------

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Generator Skryptu SQL - Pełna Synchronizacja z CSV</h1>";

$conn = new mysqli("localhost", "root", "", "dziekanat");
if ($conn->connect_error) {
    die("<p style='color:red;'>BŁĄD: Nie udało się połączyć z bazą danych: " . $conn->connect_error . "</p>");
}
$conn->set_charset("utf8mb4");

if (!file_exists($nazwa_pliku_csv)) {
    die("<p style='color:red;'>BŁĄD: Nie znaleziono pliku <strong>{$nazwa_pliku_csv}</strong>.</p>");
}

// --- KROK 1: Wczytaj dane z CSV i zbierz unikalne wpisy ---
$wpisy_z_csv = [];
$unikalne_przedmioty_csv = [];
$unikalne_grupy_csv = [];
$unikalne_prowadzacy_csv = [];

if (($handle = fopen($nazwa_pliku_csv, "r")) !== FALSE) {
    fgetcsv($handle, 1000, ";"); // Pomiń nagłówek
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($data) < 4) continue;
        
        $prowadzacy_raw = trim($data[0]);
        $forma_raw = ucfirst(strtolower(trim($data[1])));
        $grupa_raw = trim($data[2]);
        $przedmiot_raw = trim($data[3]);

        $prowadzacy_czesci = explode(' ', $prowadzacy_raw, 2);
        $nazwisko = $prowadzacy_czesci[0];
        $imie = $prowadzacy_czesci[1] ?? '';
        
        $wpisy_z_csv[] = ['imie' => $imie, 'nazwisko' => $nazwisko, 'forma' => $forma_raw, 'grupa' => $grupa_raw, 'przedmiot' => $przedmiot_raw];
        
        $unikalne_przedmioty_csv[$przedmiot_raw] = true;
        $unikalne_grupy_csv[$grupa_raw] = true;
        $unikalne_prowadzacy_csv[trim($imie . ' ' . $nazwisko)] = ['imie' => $imie, 'nazwisko' => $nazwisko];
    }
    fclose($handle);
}
echo "<p>Przeanalizowano " . count($wpisy_z_csv) . " wierszy z pliku CSV.</p>";

// --- KROK 2: Porównaj z bazą i znajdź brakujące dane ---
function pobierzDaneZBazy($conn, $kolumna, $tabela) {
    $dane = [];
    $res = $conn->query("SELECT DISTINCT $kolumna FROM $tabela");
    while ($row = $res->fetch_assoc()) { $dane[strtolower($row[$kolumna])] = true; }
    return $dane;
}
$istniejace_przedmioty = pobierzDaneZBazy($conn, 'nazwa_przedmiotu', 'Przedmioty');
$istniejace_grupy = pobierzDaneZBazy($conn, 'nazwa_grupy', 'GrupyZajeciowe');
$istniejacy_prowadzacy_db = [];
$res_prow = $conn->query("SELECT imie, nazwisko FROM Prowadzacy");
while ($row = $res_prow->fetch_assoc()) { $istniejacy_prowadzacy_db[strtolower(trim($row['imie'] . ' ' . $row['nazwisko']))] = true; }

$brakujace_przedmioty = array_diff_key($unikalne_przedmioty_csv, $istniejace_przedmioty);
$brakujace_grupy = array_diff_key($unikalne_grupy_csv, $istniejace_grupy);
$brakujacy_prowadzacy = array_diff_key($unikalne_prowadzacy_csv, $istniejacy_prowadzacy_db);

// --- KROK 3: Generuj zapytania SQL ---
$sql_output = "-- Wygenerowano " . date('Y-m-d H:i:s') . "\n";
$sql_output .= "-- Skrypt uzupełnia brakujące dane i dodaje zajęcia z pliku {$nazwa_pliku_csv}\n\n";

// A. Zapytania dla brakujących danych podstawowych
if (!empty($brakujace_przedmioty)) {
    $sql_output .= "-- KROK A.1: Dodawanie brakujących PRZEDMIOTÓW\n";
    foreach (array_keys($brakujace_przedmioty) as $przedmiot) {
        $sql_output .= "INSERT INTO Przedmioty (nazwa_przedmiotu) VALUES ('" . addslashes($przedmiot) . "');\n";
    }
}
if (!empty($brakujace_grupy)) {
    $sql_output .= "\n-- KROK A.2: Dodawanie brakujących GRUP (dla semestru {$domyslny_semestr_dla_nowych_grup})\n";
    foreach (array_keys($brakujace_grupy) as $grupa) {
        $sql_output .= "INSERT INTO GrupyZajeciowe (nazwa_grupy, rok_akademicki_id, semestr) VALUES ('" . addslashes($grupa) . "', {$rok_akademicki_id_docelowy}, {$domyslny_semestr_dla_nowych_grup});\n";
    }
}
if (!empty($brakujacy_prowadzacy)) {
    $sql_output .= "\n-- KROK A.3: Dodawanie brakujących PROWADZĄCYCH\n";
    $domyslne_haslo = password_hash('haslo123', PASSWORD_DEFAULT);
    foreach ($brakujacy_prowadzacy as $prowadzacy) {
        $email = strtolower(str_replace(' ', '.', $prowadzacy['imie'].'.'.$prowadzacy['nazwisko'])) . "@uczelnia.edu.pl";
        $sql_output .= "INSERT INTO Prowadzacy (imie, nazwisko, email, haslo, is_admin, tytul_naukowy) VALUES ('" . addslashes($prowadzacy['imie']) . "', '" . addslashes($prowadzacy['nazwisko']) . "', '" . addslashes($email) . "', '{$domyslne_haslo}', 0, 'dr inż.');\n";
    }
}

// B. Zapytania dla brakujących konfiguracji
$sql_output .= "\n-- KROK B: Dodawanie brakujących KONFIGURACJI PRZEDMIOTÓW (ECTS)\n";
$sql_output .= "-- Uwaga: Domyślnie ustawiono 5 ECTS. Zmień w razie potrzeby.\n";
foreach (array_keys($unikalne_przedmioty_csv) as $przedmiot) {
    $sql_output .= "INSERT IGNORE INTO KonfiguracjaPrzedmiotu (przedmiot_id, rok_akademicki_id, punkty_ects) SELECT (SELECT przedmiot_id FROM Przedmioty WHERE nazwa_przedmiotu = '" . addslashes($przedmiot) . "'), {$rok_akademicki_id_docelowy}, 5;\n";
}

// C. Zapytania dla samych zajęć
$sql_output .= "\n-- KROK C: Dodawanie ZAJĘĆ (łączenie wszystkiego)\n";
foreach($wpisy_z_csv as $wpis) {
    $sql_output .= "INSERT INTO Zajecia (konfiguracja_id, prowadzacy_id, grupa_id, forma_zajec)\n";
    $sql_output .= "SELECT * FROM (SELECT\n";
    $sql_output .= "    (SELECT k.konfiguracja_id FROM KonfiguracjaPrzedmiotu k JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id WHERE p.nazwa_przedmiotu = '" . addslashes($wpis['przedmiot']) . "' AND k.rok_akademicki_id = {$rok_akademicki_id_docelowy} LIMIT 1),\n";
    $sql_output .= "    (SELECT prowadzacy_id FROM Prowadzacy WHERE imie = '" . addslashes($wpis['imie']) . "' AND nazwisko = '" . addslashes($wpis['nazwisko']) . "' LIMIT 1),\n";
    $sql_output .= "    (SELECT grupa_id FROM GrupyZajeciowe WHERE nazwa_grupy = '" . addslashes($wpis['grupa']) . "' LIMIT 1),\n";
    $sql_output .= "    '" . addslashes($wpis['forma']) . "'\n";
    $sql_output .= ") AS tmp\n";
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

echo "<h2>Zakończono generowanie.</h2>";
echo "<h2>Gotowy Skrypt SQL</h2>";
echo "<p>Skopiuj całą poniższą zawartość i wklej do zakładki SQL w phpMyAdmin, aby w pełni zsynchronizować dane.</p>";
echo "<textarea rows='20' style='width:100%; font-family: monospace;'>" . htmlspecialchars($sql_output) . "</textarea>";

$conn->close();
?>