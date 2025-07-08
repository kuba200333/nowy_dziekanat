<?php
// Plik: handler.php (Wersja Ostateczna - Produkcyjna)
session_start();
require_once 'db_config.php';

function redirect_with_message($page, $status, $message = '') {
    header("Location: index.php?page={$page}&status={$status}&message=" . urlencode($message));
    exit();
}

/**
 * WERSJA FINALNA - Oblicza ocenę tylko z ZATWIERDZONYCH komponentów i poprawnie obsługuje "zal".
 */
function obliczIZapiszOceneCalkowita(mysqli $conn, int $numer_albumu, int $konfiguracja_id): bool {
    
    echo "<h3>--- Start diagnostyki funkcji dla studenta: $numer_albumu, przedmiot (konf. id): $konfiguracja_id ---</h3>";

    // 1. Pobierz wymagane formy
    $formy_sql = "SELECT DISTINCT forma_zajec FROM Zajecia WHERE konfiguracja_id = ?";
    $stmt_formy = $conn->prepare($formy_sql);
    $stmt_formy->bind_param("i", $konfiguracja_id);
    $stmt_formy->execute();
    $wymagane_formy_res = $stmt_formy->get_result()->fetch_all(MYSQLI_ASSOC);
    $wymagane_formy = array_column($wymagane_formy_res, 'forma_zajec');
    if (empty($wymagane_formy)) { echo "<p style='color:red;'>BŁĄD: Przedmiot nie ma zdefiniowanych form.</p>"; return false; }
    echo "<p>OK: Wymagane formy dla tego przedmiotu: " . implode(', ', $wymagane_formy) . "</p>";

    // 2. Pobierz oceny studenta
    $oceny_studenta_sql = "
        SELECT 
            z.forma_zajec, kk.waga_oceny,
            (SELECT ok.wartosc_oceny FROM OcenyKoncoweZKomponentu ok WHERE ok.zapis_id = zs.zapis_id ORDER BY ok.termin DESC LIMIT 1) as ostatnia_ocena_str,
            (SELECT ok.czy_zatwierdzona FROM OcenyKoncoweZKomponentu ok WHERE ok.zapis_id = zs.zapis_id ORDER BY ok.termin DESC LIMIT 1) as czy_ostatnia_zatwierdzona
        FROM ZapisyStudentow zs
        JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id
        LEFT JOIN KonfiguracjaKomponentow kk ON z.konfiguracja_id = kk.konfiguracja_id AND z.forma_zajec = kk.forma_zajec
        WHERE zs.numer_albumu = ? AND z.konfiguracja_id = ?
    ";
    $stmt_oceny = $conn->prepare($oceny_studenta_sql);
    $stmt_oceny->bind_param("ii", $numer_albumu, $konfiguracja_id);
    $stmt_oceny->execute();
    $oceny_studenta = $stmt_oceny->get_result()->fetch_all(MYSQLI_ASSOC);
    echo "<p>OK: Znaleziono " . count($oceny_studenta) . " komponentów, na które student jest zapisany w ramach tego przedmiotu.</p>";

    $oceny_do_sredniej = [];
    $zaliczone_formy = [];

    // 3. Sprawdź warunki dla każdego komponentu
    foreach ($oceny_studenta as $ocena_komponentu) {
        echo "<hr><strong>Sprawdzam komponent: '" . $ocena_komponentu['forma_zajec'] . "'</strong><br>";

        if ($ocena_komponentu['ostatnia_ocena_str'] === NULL) {
             echo "<p style='color:red;'>WARUNEK NIESPEŁNIONY: Brak jakiejkolwiek oceny z tego komponentu. Przerywam.</p>";
             return false;
        }
        echo "OK: Znaleziono ostatnią ocenę: '" . $ocena_komponentu['ostatnia_ocena_str'] . "'.<br>";

        if (empty($ocena_komponentu['czy_ostatnia_zatwierdzona'])) {
            echo "<p style='color:red;'>WARUNEK NIESPEŁNIONY: Ostatnia ocena z tego komponentu NIE JEST ZATWIERDZONA (checkbox nie jest zaznaczony). Przerywam.</p>";
            return false;
        }
        echo "OK: Ostatnia ocena jest ZATWIERDZONA.<br>";

        $ocena_str = strtoupper(trim($ocena_komponentu['ostatnia_ocena_str']));
        
        if (is_numeric($ocena_str)) {
            $ocena_numeryczna = (float)$ocena_str;
            if ($ocena_numeryczna < 3.0) {
                 echo "<p style='color:red;'>WARUNEK NIESPEŁNIONY: Ocena numeryczna " . $ocena_numeryczna . " jest negatywna. Przerywam.</p>";
                 return false;
            }
            $oceny_do_sredniej[] = ['ocena' => $ocena_numeryczna, 'waga' => $ocena_komponentu['waga_oceny']];
            $zaliczone_formy[] = $ocena_komponentu['forma_zajec'];
            echo "OK: Ocena numeryczna jest pozytywna.<br>";
        } 
        elseif ($ocena_str == 'ZAL') {
            $zaliczone_formy[] = $ocena_komponentu['forma_zajec'];
            echo "OK: Ocena 'ZAL' jest pozytywna.<br>";
        } 
        else {
            echo "<p style='color:red;'>WARUNEK NIESPEŁNIONY: Ocena '" . $ocena_str . "' jest negatywna (np. NZAL) lub nierozpoznana. Przerywam.</p>";
            return false;
        }
    }

    echo "<hr><p>Sprawdzanie kompletu zaliczeń... Student zaliczył " . count(array_unique($zaliczone_formy)) . " unikalnych form na " . count($wymagane_formy) . " wymaganych.</p>";
    if(count(array_unique($zaliczone_formy)) !== count($wymagane_formy)) {
         echo "<p style='color:red;'>WARUNEK NIESPEŁNIONY: Brak kompletu zaliczeń ze wszystkich wymaganych form. Przerywam.</p>";
        return false; 
    }

    echo "<p style='color:green; font-weight:bold;'>SUKCES: Wszystkie warunki spełnione. Przystępuję do obliczeń.</p>";
    
    // Obliczenia i zapis
    if (empty($oceny_do_sredniej)) {
        $ocena_calkowita_do_zapisu = "zal";
    } else {
        $suma_wag = 0; $suma_ocen_wazonych = 0;
        foreach ($oceny_do_sredniej as $oc) {
            $suma_ocen_wazonych += $oc['ocena'] * $oc['waga'];
            $suma_wag += $oc['waga'];
        }
        if ($suma_wag == 0) {
             $ocena_calkowita_do_zapisu = "zal";
        } else {
            $srednia_wazona = $suma_ocen_wazonych / $suma_wag;
            $ocena_calkowita_numeryczna = round($srednia_wazona * 2) / 2;
            $ocena_calkowita_do_zapisu = number_format($ocena_calkowita_numeryczna, 2);
        }
    }
    $zapis_oceny_sql = "INSERT INTO OcenyCalkowiteZPrzedmiotu (numer_albumu, konfiguracja_id, wartosc_obliczona, data_obliczenia) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE wartosc_obliczona = VALUES(wartosc_obliczona), data_obliczenia = NOW()";
    $stmt_zapis_oceny = $conn->prepare($zapis_oceny_sql);
    $stmt_zapis_oceny->bind_param("iis", $numer_albumu, $konfiguracja_id, $ocena_calkowita_do_zapisu);
    $stmt_zapis_oceny->execute();

    if ($stmt_zapis_oceny->affected_rows > 0) {
        echo "<p style='color:blue;'>INFO: Zapisano ocenę w bazie danych.</p>";
    } else {
        echo "<p style='color:orange;'>INFO: Ocena w bazie danych nie została zmieniona (prawdopodobnie była już taka sama).</p>";
    }
    echo "<h3>--- Koniec diagnostyki funkcji ---</h3>";
    return true;
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $stmt = null; 

    try {
        switch ($action) {
            case 'add_rok':
                $stmt = $conn->prepare("INSERT INTO RokiAkademickie (nazwa_roku) VALUES (?)");
                $stmt->bind_param("s", $_POST['nazwa_roku']);
                $stmt->execute();
                redirect_with_message('roki_lista', 'success', 'Dodano nowy rok akademicki.');
                break;

            case 'add_prowadzacy':
                $stmt = $conn->prepare("INSERT INTO Prowadzacy (imie, nazwisko, tytul_naukowy, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $_POST['imie'], $_POST['nazwisko'], $_POST['tytul_naukowy'], $_POST['email']);
                $stmt->execute();
                redirect_with_message('prowadzacy_lista', 'success', 'Dodano nowego prowadzącego.');
                break;

            case 'add_przedmiot':
                $stmt = $conn->prepare("INSERT INTO Przedmioty (nazwa_przedmiotu) VALUES (?)");
                $stmt->bind_param("s", $_POST['nazwa_przedmiotu']);
                $stmt->execute();
                redirect_with_message('przedmioty_lista', 'success', 'Dodano nowy przedmiot.');
                break;
                
            case 'add_sala':
                $stmt = $conn->prepare("INSERT INTO SaleZajeciowe (budynek, numer_sali) VALUES (?, ?)");
                $stmt->bind_param("ss", $_POST['budynek'], $_POST['numer_sali']);
                $stmt->execute();
                redirect_with_message('sale_lista', 'success', 'Dodano nową salę.');
                break;
                
            case 'add_grupa':
                $stmt = $conn->prepare("INSERT INTO GrupyZajeciowe (nazwa_grupy, rok_akademicki_id, semestr) VALUES (?, ?, ?)");
                $stmt->bind_param("sii", $_POST['nazwa_grupy'], $_POST['rok_akademicki_id'], $_POST['semestr']);
                $stmt->execute();
                redirect_with_message('grupy_lista', 'success', 'Dodano nową grupę.');
                break;
                
            case 'add_student':
                $stmt = $conn->prepare("INSERT INTO Studenci (numer_albumu, imie, nazwisko, email, rok_rozpoczecia_studiow, status_studenta) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssis", $_POST['numer_albumu'], $_POST['imie'], $_POST['nazwisko'], $_POST['email'], $_POST['rok_rozpoczecia'], $_POST['status_studenta']);
                $stmt->execute();
                redirect_with_message('studenci_lista', 'success', 'Dodano nowego studenta.');
                break;

            case 'add_konfiguracja':
                $stmt = $conn->prepare("INSERT INTO KonfiguracjaPrzedmiotu (przedmiot_id, rok_akademicki_id, punkty_ects) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $_POST['przedmiot_id'], $_POST['rok_akademicki_id'], $_POST['punkty_ects']);
                $stmt->execute();
                redirect_with_message('konfiguracje_lista', 'success', 'Skonfigurowano przedmiot.');
                break;
                
            case 'add_zajecia':
                $konfiguracja_id = (int)$_POST['konfiguracja_id'];
                $prowadzacy_id = !empty($_POST['prowadzacy_id']) ? (int)$_POST['prowadzacy_id'] : null;
                $grupa_id = (int)$_POST['grupa_id'];
                $forma_zajec = $_POST['forma_zajec'];
                
                $stmt = $conn->prepare("INSERT INTO Zajecia (konfiguracja_id, prowadzacy_id, grupa_id, forma_zajec) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $konfiguracja_id, $prowadzacy_id, $grupa_id, $forma_zajec);
                $stmt->execute();
                $nowe_zajecia_id = $conn->insert_id;
                $studenci_w_grupie_sql = "SELECT DISTINCT zs.numer_albumu FROM ZapisyStudentow zs JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id WHERE z.grupa_id = ? AND z.zajecia_id != ?";
                $stmt_studenci = $conn->prepare($studenci_w_grupie_sql);
                $stmt_studenci->bind_param("ii", $grupa_id, $nowe_zajecia_id);
                $stmt_studenci->execute();
                $studenci_do_zapisu_res = $stmt_studenci->get_result()->fetch_all(MYSQLI_ASSOC);
                $studenci_do_zapisu = array_column($studenci_do_zapisu_res, 'numer_albumu');
                if (!empty($studenci_do_zapisu)) {
                    $stmt_zapis = $conn->prepare("INSERT INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");
                    foreach ($studenci_do_zapisu as $numer_albumu) {
                        $stmt_zapis->bind_param("ii", $numer_albumu, $nowe_zajecia_id);
                        $stmt_zapis->execute();
                    }
                }
                redirect_with_message('zajecia_lista', 'success', 'Utworzono nowe zajęcia i automatycznie zapisano na nie studentów z grupy.');
                break;

            case 'przypisz_prowadzacego':
                $zajecia_id = (int)$_POST['zajecia_id'];
                $prowadzacy_id = (int)$_POST['prowadzacy_id'];

                $stmt = $conn->prepare("UPDATE Zajecia SET prowadzacy_id = ? WHERE zajecia_id = ?");
                $stmt->bind_param("ii", $prowadzacy_id, $zajecia_id);
                $stmt->execute();
                
                redirect_with_message('zajecia_obsada_form', 'success', 'Prowadzący został pomyślnie przypisany do zajęć.');
                break;

            case 'zapisz_obecnosc':
                $termin_id = (int)$_POST['termin_id'];
                $obecnosc_post = $_POST['obecnosc'] ?? [];

                $sql = "INSERT INTO Obecnosc (numer_albumu, termin_id, status_obecnosci) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE status_obecnosci = VALUES(status_obecnosci)";
                $stmt = $conn->prepare($sql);

                foreach ($obecnosc_post as $numer_albumu => $status) {
                    if (!empty($status)) {
                        $stmt->bind_param("iis", $numer_albumu, $termin_id, $status);
                        $stmt->execute();
                    }
                }
                redirect_with_message('obecnosc_plan_nauczyciela', 'success', 'Obecność została zapisana.');
                break;

            case 'zapisz_konfiguracje_komponentu':
                $konfiguracja_id = (int)$_POST['konfiguracja_id'];
                $forma_zajec = $_POST['forma_zajec'];
                $waga_oceny = (float)$_POST['waga_oceny'];

                $sql = "INSERT INTO KonfiguracjaKomponentow (konfiguracja_id, forma_zajec, waga_oceny) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE waga_oceny = VALUES(waga_oceny)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isd", $konfiguracja_id, $forma_zajec, $waga_oceny);
                $stmt->execute();
                redirect_with_message('konfiguracje_komponentow_form', 'success', 'Waga dla komponentu została zapisana.');
                break;    

            case 'zarzadzaj_zapisami_masowymi':
                $zajecia_id = (int)$_POST['zajecia_id'];
                $rok_rozpoczecia = (int)$_POST['rok_rozpoczecia'];
                $studenci_do_zapisu = $_POST['studenci_do_zapisu'] ?? [];
                $aktualnie_zapisani_sql = "SELECT numer_albumu FROM ZapisyStudentow WHERE zajecia_id = ?";
                $stmt_aktualni = $conn->prepare($aktualnie_zapisani_sql);
                $stmt_aktualni->bind_param("i", $zajecia_id);
                $stmt_aktualni->execute();
                $aktualnie_zapisani = array_column($stmt_aktualni->get_result()->fetch_all(MYSQLI_ASSOC), 'numer_albumu');
                $do_dodania = array_diff($studenci_do_zapisu, $aktualnie_zapisani);
                $do_usuniecia = array_diff($aktualnie_zapisani, $studenci_do_zapisu);
                if (!empty($do_dodania)) {
                    $stmt_insert = $conn->prepare("INSERT INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");
                    foreach ($do_dodania as $numer_albumu) {
                        $stmt_insert->bind_param("ii", $numer_albumu, $zajecia_id);
                        $stmt_insert->execute();
                    }
                }
                if (!empty($do_usuniecia)) {
                    $placeholders = implode(',', array_fill(0, count($do_usuniecia), '?'));
                    $stmt_delete = $conn->prepare("DELETE FROM ZapisyStudentow WHERE zajecia_id = ? AND numer_albumu IN ($placeholders)");
                    $types = 'i' . str_repeat('i', count($do_usuniecia));
                    $params = array_merge([$zajecia_id], $do_usuniecia);
                    $stmt_delete->bind_param($types, ...$params);
                    $stmt_delete->execute();
                }
                $redirect_url = "zapisy_masowe_zarzadzaj&zajecia_id={$zajecia_id}&rok_rozpoczecia={$rok_rozpoczecia}";
                redirect_with_message($redirect_url, 'success', 'Zapisy na zajęcia zostały zaktualizowane.');
                break;

            case 'dodaj_ocene_czastkowa':
                $zajecia_id = (int)$_POST['zajecia_id'];
                $zapis_id = (int)$_POST['zapis_id'];
                $wartosc_oceny = $_POST['wartosc_oceny'];
                $opis = $_POST['opis'];
                if (!empty($zapis_id) && !empty($wartosc_oceny)) {
                    $sql = "INSERT INTO OcenyCzastkowe (zapis_id, wartosc_oceny, opis, data_wystawienia) VALUES (?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $zapis_id, $wartosc_oceny, $opis);
                    $stmt->execute();
                }
                $redirect_url = "oceny_czastkowe_zarzadzaj&zajecia_id={$zajecia_id}";
                redirect_with_message($redirect_url, 'success', 'Dodano ocenę cząstkową.');
                break;

            case 'wystaw_ocene_z_komponentu':
                // Zapis ocen z komponentów (ta część jest poprawna)
                $zajecia_id = (int)$_POST['zajecia_id'];
                $oceny_post = $_POST['oceny'] ?? [];
                $zatwierdzone_post = $_POST['zatwierdzone'] ?? [];
                $zablokowane_sql = "SELECT zapis_id, termin FROM OcenyKoncoweZKomponentu WHERE czy_zatwierdzona = TRUE AND zapis_id IN (SELECT zapis_id FROM ZapisyStudentow WHERE zajecia_id = ?)";
                $stmt_check = $conn->prepare($zablokowane_sql);
                $stmt_check->bind_param("i", $zajecia_id);
                $stmt_check->execute();
                $zablokowane_result = $stmt_check->get_result()->fetch_all(MYSQLI_ASSOC);
                $zablokowane = [];
                foreach ($zablokowane_result as $row) {
                    $zablokowane[$row['zapis_id']][$row['termin']] = true;
                }
                $sql = "INSERT INTO OcenyKoncoweZKomponentu (zapis_id, termin, wartosc_oceny, czy_zatwierdzona, data_wystawienia) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE wartosc_oceny = VALUES(wartosc_oceny), czy_zatwierdzona = VALUES(czy_zatwierdzona), data_wystawienia = NOW()";
                $stmt = $conn->prepare($sql);
                $wszystkie_zmienione_zapis_ids = array_unique(array_merge(array_keys($oceny_post), array_keys($zatwierdzone_post)));
                foreach ($wszystkie_zmienione_zapis_ids as $zapis_id) {
                    $terminy_ocen = $oceny_post[$zapis_id] ?? [];
                    $terminy_zatwierdzone = $zatwierdzone_post[$zapis_id] ?? [];
                    $wszystkie_terminy = array_unique(array_merge(array_keys($terminy_ocen), array_keys($terminy_zatwierdzone)));
                    foreach($wszystkie_terminy as $termin) {
                        if (isset($zablokowane[$zapis_id][$termin])) continue;
                        $wartosc = $terminy_ocen[$termin] ?? '';
                        if (!empty($wartosc)) {
                            $czy_zatwierdzona = isset($terminy_zatwierdzone[$termin]);
                            $stmt->bind_param("iisi", $zapis_id, $termin, $wartosc, $czy_zatwierdzona);
                            $stmt->execute();
                        }
                    }
                }
                echo "<h2>Zakończono zapis ocen z komponentów.</h2>";

                // Uruchomienie diagnostyki
                $konf_id_res = $conn->query("SELECT konfiguracja_id FROM Zajecia WHERE zajecia_id = $zajecia_id")->fetch_assoc();
                $konfiguracja_id = $konf_id_res['konfiguracja_id'];
                if ($konfiguracja_id) {
                    if (!empty($wszystkie_zmienione_zapis_ids)) {
                        $placeholders = implode(',', array_fill(0, count($wszystkie_zmienione_zapis_ids), '?'));
                        $numery_albumow_sql = "SELECT DISTINCT numer_albumu FROM ZapisyStudentow WHERE zapis_id IN ($placeholders)";
                        $stmt_albumy = $conn->prepare($numery_albumow_sql);
                        $types = str_repeat('i', count($wszystkie_zmienione_zapis_ids));
                        $stmt_albumy->bind_param($types, ...$wszystkie_zmienione_zapis_ids);
                        $stmt_albumy->execute();
                        $albumy_res = $stmt_albumy->get_result()->fetch_all(MYSQLI_ASSOC);
                        foreach ($albumy_res as $row) {
                            echo "<hr><p><b>Wywołuję funkcję obliczającą dla studenta o numerze albumu: " . $row['numer_albumu'] . "</b></p>";
                            obliczIZapiszOceneCalkowita($conn, $row['numer_albumu'], $konfiguracja_id);
                        }
                    }
                }
                echo "<h2>Zakończono handler. Przekierowanie jest wyłączone na potrzeby diagnostyki.</h2>";
                
                // Celowo wyłączamy przekierowanie
                redirect_with_message('oceny_wprowadz_form&zajecia_id='.$zajecia_id, 'success', 'Oceny zostały zapisane.');
                break;

            // W pliku handler.php

            case 'stworz_grupe_z_wyboru':
                $wybor_id = (int)$_POST['wybor_id'];
                $przedmiot_id = (int)$_POST['przedmiot_id'];
                $prowadzacy_id = (int)$_POST['prowadzacy_id'];
                $studenci_ids = $_POST['studenci_ids'] ?? [];

                if (empty($studenci_ids)) {
                    redirect_with_message('wybory_admin_wyniki&wybor_id=' . $wybor_id, 'error', 'Nie wybrano żadnych studentów do utworzenia grupy.');
                    break;
                }

                // 1. Pobierz dane o wydarzeniu wyboru, aby uzyskać rok i semestr
                $stmt_wybor = $conn->prepare("SELECT w.rok_akademicki_id, w.semestr, p.nazwa_przedmiotu FROM WyboryPrzedmiotow w JOIN Przedmioty p ON p.przedmiot_id = ? WHERE w.wybor_id = ?");
                $stmt_wybor->bind_param("ii", $przedmiot_id, $wybor_id);
                $stmt_wybor->execute();
                $dane_wyboru = $stmt_wybor->get_result()->fetch_assoc();
                $rok_akademicki_id = $dane_wyboru['rok_akademicki_id'];
                $semestr = $dane_wyboru['semestr'];
                $nazwa_przedmiotu = $dane_wyboru['nazwa_przedmiotu'];

                // 2. Znajdź konfigurację przedmiotu dla tego roku akademickiego
                $stmt_konf = $conn->prepare("SELECT konfiguracja_id FROM KonfiguracjaPrzedmiotu WHERE przedmiot_id = ? AND rok_akademicki_id = ?");
                $stmt_konf->bind_param("ii", $przedmiot_id, $rok_akademicki_id);
                $stmt_konf->execute();
                $konfiguracja = $stmt_konf->get_result()->fetch_assoc();
                if (!$konfiguracja) {
                    redirect_with_message('wybory_admin_wyniki&wybor_id=' . $wybor_id, 'error', 'Błąd: Przedmiot musi być najpierw skonfigurowany na dany rok akademicki.');
                    break;
                }
                $konfiguracja_id = $konfiguracja['konfiguracja_id'];

                // 3. Utwórz nową grupę zajęciową
                $nazwa_nowej_grupy = "Grupa - " . $nazwa_przedmiotu . " " . date('Y-m-d H:i');
                $stmt_grupa = $conn->prepare("INSERT INTO GrupyZajeciowe (nazwa_grupy, rok_akademicki_id, semestr) VALUES (?, ?, ?)");
                $stmt_grupa->bind_param("sii", $nazwa_nowej_grupy, $rok_akademicki_id, $semestr);
                $stmt_grupa->execute();
                $nowa_grupa_id = $conn->insert_id;

                // 4. Utwórz nowe "Zajęcia" (komponent) dla tej grupy
                $stmt_zajecia = $conn->prepare("INSERT INTO Zajecia (konfiguracja_id, prowadzacy_id, grupa_id, forma_zajec, waga_oceny) VALUES (?, ?, ?, 'Wybór', 1.0)");
                $stmt_zajecia->bind_param("iii", $konfiguracja_id, $prowadzacy_id, $nowa_grupa_id);
                $stmt_zajecia->execute();
                $nowe_zajecia_id = $conn->insert_id;

                // 5. Zapisz wybranych studentów na nowo utworzone zajęcia
                $stmt_zapisy = $conn->prepare("INSERT INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");
                foreach ($studenci_ids as $student_id) {
                    $stmt_zapisy->bind_param("ii", $student_id, $nowe_zajecia_id);
                    $stmt_zapisy->execute();
                }
                
                redirect_with_message('dashboard', 'success', 'Nowa grupa została utworzona, a studenci zapisani na zajęcia.');
                break;
            case 'modyfikuj_termin':
                $termin_id = (int)$_POST['termin_id'];
                $status = $_POST['status'];
                $nowy_prowadzacy_id = !empty($_POST['prowadzacy_id']) ? (int)$_POST['prowadzacy_id'] : null;
                $nowa_sala_id = !empty($_POST['sala_id']) ? (int)$_POST['sala_id'] : null;
                $notatki = $_POST['notatki'];

                // Budujemy dynamicznie zapytanie UPDATE, aby zmieniać tylko te pola, które zostały podane
                $sql_parts = [];
                $params = [];
                $types = "";

                // Zawsze aktualizujemy status i notatki
                $sql_parts[] = "status = ?";
                $params[] = $status;
                $types .= "s";

                $sql_parts[] = "notatki = ?";
                $params[] = $notatki;
                $types .= "s";

                // Jeśli podano nowego prowadzącego, dodajemy go do zapytania
                if ($nowy_prowadzacy_id) {
                    $sql_parts[] = "prowadzacy_id = ?";
                    $params[] = $nowy_prowadzacy_id;
                    $types .= "i";
                }

                // Jeśli podano nową salę, dodajemy ją do zapytania
                if ($nowa_sala_id) {
                    $sql_parts[] = "sala_id = ?";
                    $params[] = $nowa_sala_id;
                    $types .= "i";
                }

                // Dodajemy ID terminu na końcu listy parametrów
                $params[] = $termin_id;
                $types .= "i";

                $sql = "UPDATE TerminyZajec SET " . implode(', ', $sql_parts) . " WHERE termin_id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
                redirect_with_message('dashboard', 'success', 'Termin zajęć został pomyślnie zaktualizowany.');
                break;

            case 'stworz_wybory':
                // 1. Zapisz główne wydarzenie wyboru
                $stmt = $conn->prepare("INSERT INTO WyboryPrzedmiotow (nazwa_wyboru, rok_akademicki_id, semestr, data_rozpoczecia, data_zakonczenia) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siiss", $_POST['nazwa_wyboru'], $_POST['rok_akademicki_id'], $_POST['semestr'], $_POST['data_rozpoczecia'], $_POST['data_zakonczenia']);
                $stmt->execute();
                $wybor_id = $conn->insert_id;

                // 2. Zapisz przedmioty do wyboru
                $przedmioty_ids = $_POST['przedmioty_ids'];
                $stmt_opcje = $conn->prepare("INSERT INTO OpcjeWyboru (wybor_id, przedmiot_id) VALUES (?, ?)");
                foreach ($przedmioty_ids as $przedmiot_id) {
                    $stmt_opcje->bind_param("ii", $wybor_id, $przedmiot_id);
                    $stmt_opcje->execute();
                }

                // 3. Zapisz grupy docelowe
                $grupy_ids = $_POST['grupy_ids'];
                $stmt_grupy = $conn->prepare("INSERT INTO GrupyDoceloweWyboru (wybor_id, grupa_id) VALUES (?, ?)");
                foreach ($grupy_ids as $grupa_id) {
                    $stmt_grupy->bind_param("ii", $wybor_id, $grupa_id);
                    $stmt_grupy->execute();
                }
                redirect_with_message('dashboard', 'success', 'Nowe wydarzenie wyboru przedmiotów zostało pomyślnie utworzone.');
                break;

            case 'zapisz_wybor_studenta':
                $wybor_id = (int)$_POST['wybor_id'];
                $numer_albumu = (int)$_SESSION['user_id'];
                $wybrana_opcja_id = (int)$_POST['wybrana_opcja_id'];

                // Proste zabezpieczenie przed podwójnym głosowaniem
                $check_sql = "SELECT odpowiedz_id FROM OdpowiedziStudentow WHERE wybor_id = ? AND numer_albumu = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("ii", $wybor_id, $numer_albumu);
                $stmt_check->execute();
                if ($stmt_check->get_result()->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO OdpowiedziStudentow (wybor_id, numer_albumu, wybrana_opcja_id) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $wybor_id, $numer_albumu, $wybrana_opcja_id);
                    $stmt->execute();
                }
                
                redirect_with_message('wybory_student_glosuj&wybor_id=' . $wybor_id, 'success', 'Twój wybór został zapisany!');
                break;
            

            case 'stworz_nabor': // Obsługa formularza tworzenia naboru przez admina
                $stmt = $conn->prepare("INSERT INTO NaborStypendialny (nazwa_naboru, rok_akademicki_id, data_startu, data_konca) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $_POST['nazwa_naboru'], $_POST['rok_akademicki_id'], $_POST['data_startu'], $_POST['data_konca']);
                $stmt->execute();
                redirect_with_message('stypendia_admin_lista', 'success', 'Nowy nabór na stypendia został pomyślnie utworzony.');
                break;

            case 'zloz_wniosek_stypendialny':
                // UWAGA: Przed zapisem, serwer powinien SAMODZIELNIE ponownie obliczyć średnią,
                // aby zapobiec manipulacjom. Poniżej uproszczona logika zapisu.
                $nabor_id = (int)$_POST['nabor_id'];
                $numer_albumu = (int)$_SESSION['user_id'];
                $semestry = $_POST['semestry'];
                $obliczona_srednia = (float)$_POST['obliczona_srednia'];

                $stmt = $conn->prepare("INSERT INTO WnioskiStypendialne (nabor_id, numer_albumu, semestry, obliczona_srednia) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisd", $nabor_id, $numer_albumu, $semestry, $obliczona_srednia);
                $stmt->execute();
                redirect_with_message('dashboard', 'success', 'Twój wniosek o stypendium został pomyślnie złożony!');
                break;

            case 'rozpatrz_wniosek': // Obsługa formularza edycji wniosku przez admina
                $wniosek_id = (int)$_POST['wniosek_id'];
                $nowy_status = $_POST['status_wniosku'];
                $uwagi = $_POST['uwagi_pracownika'];

                $stmt = $conn->prepare("UPDATE WnioskiStypendialne SET status_wniosku = ?, uwagi_pracownika = ? WHERE wniosek_id = ?");
                $stmt->bind_param("ssi", $nowy_status, $uwagi, $wniosek_id);
                $stmt->execute();
                redirect_with_message('stypendia_admin_lista', 'success', 'Wniosek został rozpatrzony.');
                break;

            case 'stworz_nabor':
                // Pobieramy wszystkie dane z formularza
                $nazwa_naboru = $_POST['nazwa_naboru'];
                $typ_stypendium = $_POST['typ_stypendium'];
                $rok_akademicki_id = (int)$_POST['rok_akademicki_id'];
                $data_startu = $_POST['data_startu'];
                $data_konca = $_POST['data_konca'];
                $status_naboru = $_POST['status_naboru'];

                // Przygotowujemy zapytanie SQL
                $stmt = $conn->prepare("INSERT INTO NaborStypendialny (nazwa_naboru, typ_stypendium, rok_akademicki_id, data_startu, data_konca, status_naboru) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisss", $nazwa_naboru, $typ_stypendium, $rok_akademicki_id, $data_startu, $data_konca, $status_naboru);
                $stmt->execute();
                
                redirect_with_message('stypendia_admin_lista', 'success', 'Nowy nabór na stypendia został pomyślnie utworzony.');
                break;

            case 'stworz_ankiete':
                $stmt = $conn->prepare("INSERT INTO AnkietyOkresy (nazwa_ankiety, rok_akademicki_id, semestr, data_startu, data_konca) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siiss", $_POST['nazwa_ankiety'], $_POST['rok_akademicki_id'], $_POST['semestr'], $_POST['data_startu'], $_POST['data_konca']);
                $stmt->execute();
                redirect_with_message('dashboard', 'success', 'Nowy okres ankietyzacji został utworzony.');
                break;

            case 'zapisz_odpowiedz_ankiety':
                $numer_albumu = $_SESSION['user_id'];
                $stmt = $conn->prepare("INSERT INTO AnkietyOdpowiedzi (okres_id, numer_albumu, zajecia_id, ocena_opisowa, ocena_przygotowanie, ocena_sposob_oceniania) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisii", $_POST['okres_id'], $numer_albumu, $_POST['zajecia_id'], $_POST['ocena_opisowa'], $_POST['ocena_przygotowanie'], $_POST['ocena_sposob_oceniania']);
                $stmt->execute();
                redirect_with_message('ankiety_student_lista', 'success', 'Dziękujemy za wypełnienie ankiety!');
                break;

            case 'dopisz_studentow_do_zajec':
                $docelowe_zajecia_id = (int)$_POST['docelowe_zajecia_id'];
                $studenci_ids = $_POST['studenci_ids'] ?? [];

                if (empty($studenci_ids)) {
                    redirect_with_message('wybory_admin_wyniki', 'error', 'Nie wybrano żadnych studentów do dopisania.');
                    break;
                }

                if (empty($docelowe_zajecia_id)) {
                    redirect_with_message('wybory_admin_wyniki', 'error', 'Nie wybrano docelowych zajęć.');
                    break;
                }

                // Przygotuj zapytanie, które doda studenta tylko jeśli nie jest jeszcze zapisany
                // INSERT IGNORE zignoruje błędy unikalności klucza (jeśli student już jest zapisany)
                $stmt_zapis = $conn->prepare("INSERT IGNORE INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");

                $zapisano_licznik = 0;
                foreach ($studenci_ids as $student_id) {
                    $stmt_zapis->bind_param("ii", $student_id, $docelowe_zajecia_id);
                    $stmt_zapis->execute();
                    if ($stmt_zapis->affected_rows > 0) {
                        $zapisano_licznik++;
                    }
                }
                
                redirect_with_message('wybory_admin_wyniki', 'success', "Dopisano $zapisano_licznik nowych studentów do wybranych zajęć.");
                break;
                
            case 'edit_konfiguracja':
                $konfiguracja_id = (int)$_POST['konfiguracja_id'];
                $punkty_ects = (int)$_POST['punkty_ects'];

                $stmt = $conn->prepare("UPDATE KonfiguracjaPrzedmiotu SET punkty_ects = ? WHERE konfiguracja_id = ?");
                $stmt->bind_param("ii", $punkty_ects, $konfiguracja_id);
                $stmt->execute();
                redirect_with_message('konfiguracje_lista', 'success', 'Liczba punktów ECTS została zaktualizowana.');
                break;

            case 'delete_konfiguracja':
                $konfiguracja_id = (int)$_POST['konfiguracja_id'];

                // Zabezpieczenie po stronie serwera
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM Zajecia WHERE konfiguracja_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $konfiguracja_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('konfiguracje_lista', 'error', 'Nie można usunąć. Ta konfiguracja jest już przypisana do zajęć.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM KonfiguracjaPrzedmiotu WHERE konfiguracja_id = ?");
                    $stmt->bind_param("i", $konfiguracja_id);
                    $stmt->execute();
                    redirect_with_message('konfiguracje_lista', 'success', 'Konfiguracja przedmiotu została usunięta.');
                }
                break;


            case 'edit_przedmiot':
                $przedmiot_id = (int)$_POST['przedmiot_id'];
                $nowa_nazwa = $_POST['nazwa_przedmiotu'];

                $stmt = $conn->prepare("UPDATE Przedmioty SET nazwa_przedmiotu = ? WHERE przedmiot_id = ?");
                $stmt->bind_param("si", $nowa_nazwa, $przedmiot_id);
                $stmt->execute();
                redirect_with_message('przedmioty_lista', 'success', 'Nazwa przedmiotu została zaktualizowana.');
                break;

            case 'delete_przedmiot':
                $przedmiot_id = (int)$_POST['przedmiot_id'];

                // Podwójne zabezpieczenie po stronie serwera
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM KonfiguracjaPrzedmiotu WHERE przedmiot_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $przedmiot_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('przedmioty_lista', 'error', 'Nie można usunąć przedmiotu, ponieważ jest on używany w konfiguracjach.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM Przedmioty WHERE przedmiot_id = ?");
                    $stmt->bind_param("i", $przedmiot_id);
                    $stmt->execute();
                    redirect_with_message('przedmioty_lista', 'success', 'Przedmiot został usunięty.');
                }
                break;

            case 'dodaj_szablon_planu':
                $zajecia_id = (int)$_POST['zajecia_id'];
                $sala_id = (int)$_POST['sala_id'];
                $dzien_tygodnia = (int)$_POST['dzien_tygodnia'];
                $typ_cyklu = $_POST['typ_cyklu'];
                $godzina_rozpoczecia = $_POST['godzina_rozpoczecia'];
                $godzina_zakonczenia = $_POST['godzina_zakonczenia'];
                $data_startu_cyklu = $_POST['data_startu_cyklu'];
                $data_konca_cyklu = $_POST['data_konca_cyklu'];

                $stmt = $conn->prepare("INSERT INTO SzablonyPlanu (zajecia_id, sala_id, dzien_tygodnia, typ_cyklu, godzina_rozpoczecia, godzina_zakonczenia, data_startu_cyklu, data_konca_cyklu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisssss", $zajecia_id, $sala_id, $dzien_tygodnia, $typ_cyklu, $godzina_rozpoczecia, $godzina_zakonczenia, $data_startu_cyklu, $data_konca_cyklu);
                $stmt->execute();
                
                $prowadzacy_id = $conn->query("SELECT prowadzacy_id FROM Zajecia WHERE zajecia_id = $zajecia_id")->fetch_assoc()['prowadzacy_id'];

                $stmt_termin = $conn->prepare("INSERT INTO TerminyZajec (zajecia_id, prowadzacy_id, sala_id, data_zajec, godzina_rozpoczecia, godzina_zakonczenia) VALUES (?, ?, ?, ?, ?, ?)");

                $begin = new DateTime($data_startu_cyklu);
                $end = new DateTime($data_konca_cyklu);
                $end = $end->modify('+1 day');
                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($begin, $interval, $end);

                foreach ($dateRange as $date) {
                    if ($date->format('N') == $dzien_tygodnia) {
                        $is_match = false;
                        if ($typ_cyklu == 'tygodniowy') {
                            $is_match = true;
                        } else {
                            $week_number = (int)$date->format("W");
                            if ($typ_cyklu == 'dwutyg_parzysty' && $week_number % 2 == 0) {
                                $is_match = true;
                            } elseif ($typ_cyklu == 'dwutyg_nieparzysty' && $week_number % 2 != 0) {
                                $is_match = true;
                            }
                        }
                        if ($is_match) {
                            $data_zajec_str = $date->format('Y-m-d');
                            $stmt_termin->bind_param("iiisss", $zajecia_id, $prowadzacy_id, $sala_id, $data_zajec_str, $godzina_rozpoczecia, $godzina_zakonczenia);
                            $stmt_termin->execute();
                        }
                    }
                }
                redirect_with_message('dashboard', 'success', 'Szablon planu został zapisany, a terminy wygenerowane!');
                break;


            default:
                redirect_with_message('dashboard', 'error', 'Nieznana akcja.');
                break;
        }
        if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
    } catch (mysqli_sql_exception $e) {
        die('Błąd Bazy Danych: ' . $e->getMessage());
    }
}
$conn->close();
?>









