<?php
// Plik: handler.php (Wersja Diagnostyczna v3 z Wyłączonym Przekierowaniem)
require_once 'db_config.php';

function redirect_with_message($page, $status, $message = '') {
    header("Location: index.php?page={$page}&status={$status}&message=" . urlencode($message));
    exit();
}

/**
 * WERSJA DIAGNOSTYCZNA - Aby sprawdzić, dlaczego ocena się nie oblicza.
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
            z.forma_zajec, z.waga_oceny,
            (SELECT ok.wartosc_oceny FROM OcenyKoncoweZKomponentu ok WHERE ok.zapis_id = zs.zapis_id ORDER BY ok.termin DESC LIMIT 1) as ostatnia_ocena_str,
            (SELECT ok.czy_zatwierdzona FROM OcenyKoncoweZKomponentu ok WHERE ok.zapis_id = zs.zapis_id ORDER BY ok.termin DESC LIMIT 1) as czy_ostatnia_zatwierdzona
        FROM ZapisyStudentow zs
        JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id
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
    
    // Obliczenia i zapis (bez zmian)
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
                $prowadzacy_id = (int)$_POST['prowadzacy_id'];
                $grupa_id = (int)$_POST['grupa_id'];
                $forma_zajec = $_POST['forma_zajec'];
                $waga_oceny = (float)$_POST['waga_oceny'];
                $stmt = $conn->prepare("INSERT INTO Zajecia (konfiguracja_id, prowadzacy_id, grupa_id, forma_zajec, waga_oceny) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisd", $konfiguracja_id, $prowadzacy_id, $grupa_id, $forma_zajec, $waga_oceny);
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
                        
                        // Zapisujemy tylko jeśli jest jakaś wartość LUB jest to zmiana checkboxa
                        // Musimy mieć pewność, że mamy jakąś ocenę do zapisania dla checkboxa
                        $czy_zatwierdzona = isset($terminy_zatwierdzone[$termin]);
                        if (!empty($wartosc) || $czy_zatwierdzona) {
                            if (empty($wartosc)) { // Jeśli zaznaczono checkbox, a nie ma oceny
                                // Potrzebujemy pobrać istniejącą ocenę, by jej nie wyczyścić
                                $stara_ocena_res = $conn->query("SELECT wartosc_oceny FROM OcenyKoncoweZKomponentu WHERE zapis_id=$zapis_id AND termin=$termin");
                                if($stara_ocena_res->num_rows > 0) {
                                    $wartosc = $stara_ocena_res->fetch_assoc()['wartosc_oceny'];
                                } else {
                                    continue; // Nie ma czego zatwierdzić
                                }
                            }
                            $stmt->bind_param("iisi", $zapis_id, $termin, $wartosc, $czy_zatwierdzona);
                            $stmt->execute();
                        }
                    }
                }
                
                // CZĘŚĆ DIAGNOSTYCZNA URUCHOMIENIA AUTOMATU
                echo "<h2>Rozpoczynam sekcję automatycznego obliczania oceny końcowej...</h2>";
                
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
                // Celowo wyłączamy przekierowanie, aby zobaczyć wynik diagnostyki
                redirect_with_message('oceny_wybor_krok1', 'success', 'Oceny zostały zapisane. System podjął próbę obliczenia ocen końcowych.');
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