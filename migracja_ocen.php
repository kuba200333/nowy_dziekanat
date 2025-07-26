<?php
// Plik: migracja_zajec.php - Kompleksowa migracja struktury dydaktycznej

// --- KONFIGURACJA ---
$db_nowa_host = "localhost";
$db_nowa_user = "root";
$db_nowa_pass = "";
$db_nowa_name = "dziekanat";

$db_stara_host = "localhost";
$db_stara_user = "root";
$db_stara_pass = "";
$db_stara_name = "studia";
// --------------------

$conn_nowa = new mysqli($db_nowa_host, $db_nowa_user, $db_nowa_pass, $db_nowa_name);
$conn_stara = new mysqli($db_stara_host, $db_stara_user, $db_stara_pass, $db_stara_name);
if ($conn_nowa->connect_error || $conn_stara->connect_error) die("Błąd połączenia z bazą danych.");
$conn_nowa->set_charset("utf8mb4");
$conn_stara->set_charset("utf8mb4");

echo "<h1>Rozpoczęto migrację struktury zajęć...</h1>";

// --- Czyszczenie tabel w nowej bazie ---
$conn_nowa->query("SET FOREIGN_KEY_CHECKS=0");
$conn_nowa->query("TRUNCATE TABLE ZapisyStudentow");
$conn_nowa->query("TRUNCATE TABLE Zajecia");
$conn_nowa->query("TRUNCATE TABLE KonfiguracjaKomponentow");
$conn_nowa->query("TRUNCATE TABLE KonfiguracjaPrzedmiotu");
$conn_nowa->query("TRUNCATE TABLE GrupyZajeciowe");
$conn_nowa->query("TRUNCATE TABLE RokiAkademickie");
$conn_nowa->query("TRUNCATE TABLE Przedmioty");
$conn_nowa->query("SET FOREIGN_KEY_CHECKS=1");
echo "<p>Tabele w nowej bazie zostały wyczyszczone.</p><hr>";

// --- KROK A: Migracja unikalnych przedmiotów, lat i grup ---
echo "<h2>Krok A: Migracja danych podstawowych (Przedmioty, Roki, Grupy)...</h2>";
// Przedmioty
$stare_przedmioty = $conn_stara->query("SELECT DISTINCT nazwa_przedmiotu FROM przedmioty");
$stmt_przedmiot = $conn_nowa->prepare("INSERT INTO Przedmioty (nazwa_przedmiotu) VALUES (?)");
while ($p = $stare_przedmioty->fetch_assoc()) {
    $stmt_przedmiot->bind_param("s", $p['nazwa_przedmiotu']);
    $stmt_przedmiot->execute();
}
echo "<p>Dodano " . $stmt_przedmiot->affected_rows . " unikalnych przedmiotów.</p>";

// Rok akademicki (dodajemy jeden domyślny)
$conn_nowa->query("INSERT INTO RokiAkademickie (rok_akademicki_id, nazwa_roku) VALUES (1, '2024/2025')");
$domyslny_rok_id = 1;
echo "<p>Dodano domyślny rok akademicki 2024/2025.</p>";

// Grupy (na podstawie `rok` i `semestr` ze starej tabeli studentów)
$stare_grupy = $conn_stara->query("SELECT DISTINCT rok, semestr FROM studenci");
$stmt_grupa = $conn_nowa->prepare("INSERT INTO GrupyZajeciowe (nazwa_grupy, rok_akademicki_id, semestr) VALUES (?, ?, ?)");
$mapa_grup = []; // Mapa [stary_rok-stary_semestr => nowe_grupa_id]
while ($g = $stare_grupy->fetch_assoc()) {
    $nazwa_grupy = "Grupa R" . $g['rok'] . "S" . $g['semestr'];
    $stmt_grupa->bind_param("sii", $nazwa_grupy, $domyslny_rok_id, $g['semestr']);
    $stmt_grupa->execute();
    $mapa_grup[$g['rok'] . '-' . $g['semestr']] = $conn_nowa->insert_id;
}
echo "<p>Utworzono " . count($mapa_grup) . " grup zajęciowych.</p>";
echo "<hr>";


// --- KROK B: Migracja Konfiguracji ---
echo "<h2>Krok B: Tworzenie konfiguracji przedmiotów i komponentów...</h2>";
$nowe_przedmioty = $conn_nowa->query("SELECT przedmiot_id, nazwa_przedmiotu FROM Przedmioty")->fetch_all(MYSQLI_ASSOC);
$stmt_konf = $conn_nowa->prepare("INSERT INTO KonfiguracjaPrzedmiotu (przedmiot_id, rok_akademicki_id, punkty_ects) VALUES (?, ?, ?)");
$stmt_komp = $conn_nowa->prepare("INSERT INTO KonfiguracjaKomponentow (konfiguracja_id, forma_zajec, waga_oceny) VALUES (?, ?, ?)");
$mapa_form = ['W' => 'Wykład', 'L' => 'Laboratorium', 'A' => 'Audytoryjne', 'P' => 'Projekt', 'S' => 'Seminarium', 'LEK' => 'Lektorat', 'C' => 'Audytoryjne', 'E' => 'Wykład'];
$ile_konf = 0;

foreach($nowe_przedmioty as $np) {
    // 1. Tworzymy konfigurację (ECTS)
    $punkty_ects = 5; // Domyślna wartość, można dostosować
    $stmt_konf->bind_param("iii", $np['przedmiot_id'], $domyslny_rok_id, $punkty_ects);
    $stmt_konf->execute();
    $konfiguracja_id = $conn_nowa->insert_id;
    $ile_konf++;

    // 2. Tworzymy konfigurację komponentów (wagi)
    $stare_formy_dla_przedmiotu = $conn_stara->query("SELECT DISTINCT p.id_formy, fp.forma FROM przedmioty p JOIN formy_przedmiotow fp ON p.id_formy = fp.id_formy WHERE p.nazwa_przedmiotu = '" . $conn_stara->real_escape_string($np['nazwa_przedmiotu']) . "'");
    while($sf = $stare_formy_dla_przedmiotu->fetch_assoc()) {
        $nowa_forma = $mapa_form[$sf['forma']] ?? ucfirst(strtolower(trim($sf['forma'])));
        if ($nowa_forma) {
            $waga = 1.0; // Domyślna waga
            $stmt_komp->bind_param("isd", $konfiguracja_id, $nowa_forma, $waga);
            $stmt_komp->execute();
        }
    }
}
echo "<p>Utworzono $ile_konf konfiguracji przedmiotów i komponentów.</p>";
echo "<hr>";


// --- KROK C i D: Migracja Zajęć i Zapisów Studentów ---
echo "<h2>Krok C i D: Tworzenie Zajęć i Zapisywanie Studentów...</h2>";
// Pobieramy wszystkie wpisy z `oceny`, bo one definiują kto, co i z kim miał
$stare_powiazania = $conn_stara->query("
    SELECT DISTINCT 
        o.id_studenta, p.nazwa_przedmiotu, fp.forma, n.imie, n.nazwisko, s.rok, s.semestr
    FROM oceny o
    JOIN przedmioty p ON o.id_przedmiotu = p.id_przedmiotu
    JOIN formy_przedmiotow fp ON p.id_formy = fp.id_formy
    JOIN nauczyciele n ON o.id_nauczyciela = n.id_nauczyciela
    JOIN studenci s ON o.id_studenta = s.id_studenta
");

$stmt_zajecia = $conn_nowa->prepare("INSERT INTO Zajecia (konfiguracja_id, prowadzacy_id, grupa_id, forma_zajec) VALUES (?, ?, ?, ?)");
$stmt_zapisy = $conn_nowa->prepare("INSERT INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");
$utworzone_zajecia = []; // Mapa [klucz => zajecia_id]
$ile_zajec = 0;
$ile_zapisow = 0;

while($powiazanie = $stare_powiazania->fetch_assoc()) {
    // Mapowanie danych
    $nowa_forma = $mapa_form[$powiazanie['forma']] ?? ucfirst(strtolower(trim($powiazanie['forma'])));
    
    // Klucz unikalny dla zajęć (przedmiot + forma + prowadzący + grupa)
    $klucz_zajec = $powiazanie['nazwa_przedmiotu'] . '-' . $nowa_forma . '-' . $powiazanie['imie'] . '-' . $powiazanie['nazwisko'] . '-' . $powiazanie['rok'] . '-' . $powiazanie['semestr'];
    
    if (!isset($utworzone_zajecia[$klucz_zajec])) {
        // Jeśli zajęcia o tej kombinacji jeszcze nie istnieją w nowej bazie, tworzymy je
        
        // Znajdź ID w nowej bazie
        $nowy_przedmiot_id_res = $conn_nowa->query("SELECT przedmiot_id FROM Przedmioty WHERE nazwa_przedmiotu = '" . $conn_nowa->real_escape_string($powiazanie['nazwa_przedmiotu']) . "'")->fetch_assoc();
        $nowy_prowadzacy_id_res = $conn_nowa->query("SELECT prowadzacy_id FROM Prowadzacy WHERE imie = '" . $conn_nowa->real_escape_string($powiazanie['imie']) . "' AND nazwisko = '" . $conn_nowa->real_escape_string($powiazanie['nazwisko']) . "'")->fetch_assoc();
        
        if ($nowy_przedmiot_id_res && $nowy_prowadzacy_id_res) {
            $konfiguracja_id_res = $conn_nowa->query("SELECT konfiguracja_id FROM KonfiguracjaPrzedmiotu WHERE przedmiot_id = " . $nowy_przedmiot_id_res['przedmiot_id'])->fetch_assoc();
            
            $nowy_prowadzacy_id = $nowy_prowadzacy_id_res['prowadzacy_id'];
            $konfiguracja_id = $konfiguracja_id_res['konfiguracja_id'];
            $nowa_grupa_id = $mapa_grup[$powiazanie['rok'] . '-' . $powiazanie['semestr']];

            $stmt_zajecia->bind_param("iiis", $konfiguracja_id, $nowy_prowadzacy_id, $nowa_grupa_id, $nowa_forma);
            $stmt_zajecia->execute();
            $utworzone_zajecia[$klucz_zajec] = $conn_nowa->insert_id;
            $ile_zajec++;
        }
    }
    
    // Zapisz studenta na te zajęcia
    $nowe_zajecia_id = $utworzone_zajecia[$klucz_zajec] ?? null;
    if ($nowe_zajecia_id) {
        $stmt_zapisy->bind_param("ii", $powiazanie['id_studenta'], $nowe_zajecia_id);
        $stmt_zapisy->execute();
        $ile_zapisow++;
    }
}
echo "<p>Utworzono $ile_zajec unikalnych zajęć.</p>";
echo "<p>Dokonano $ile_zapisow zapisów studentów na zajęcia.</p>";
echo "<hr>";

echo "<h2>Zakończono migrację struktury. Możesz teraz uruchomić skrypt do migracji ocen.</h2>";

$conn_nowa->close();
$conn_stara->close();
?>