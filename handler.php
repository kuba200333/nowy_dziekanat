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
                $nazwa_przedmiotu = $_POST['nazwa_przedmiotu'];
                $wydzial_id = (int)$_POST['wydzial_id'];

                $stmt = $conn->prepare("INSERT INTO Przedmioty (nazwa_przedmiotu, wydzial_id) VALUES (?, ?)");
                $stmt->bind_param("si", $nazwa_przedmiotu, $wydzial_id);
                $stmt->execute();
                redirect_with_message('przedmioty_lista', 'success', 'Dodano nowy przedmiot i przypisano go do wydziału.');
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

                // Upewnij się, że nazwa tabeli to "KonfiguracjaKomponentow" (z 's' na końcu)
                $sql = "INSERT INTO KonfiguracjaKomponentow (konfiguracja_id, forma_zajec, waga_oceny) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE waga_oceny = VALUES(waga_oceny)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isd", $konfiguracja_id, $forma_zajec, $waga_oceny);
                $stmt->execute();
                redirect_with_message('konfiguracje_lista', 'success', 'Waga dla komponentu została zapisana.');
                break;
                
            case 'zarzadzaj_zapisami_masowymi':
                $zajecia_id = (int)$_POST['zajecia_id'];
                $rok_rozpoczecia = (int)$_POST['rok_rozpoczecia'];
                $studenci_z_formularza = $_POST['studenci_do_zapisu'] ?? [];
                $stmt_rocznik = $conn->prepare("SELECT numer_albumu FROM Studenci WHERE rok_rozpoczecia_studiow = ?");
                $stmt_rocznik->bind_param("i", $rok_rozpoczecia);
                $stmt_rocznik->execute();
                $wszyscy_z_rocznika = array_column($stmt_rocznik->get_result()->fetch_all(MYSQLI_ASSOC), 'numer_albumu');
                $stmt_aktualni = $conn->prepare("SELECT numer_albumu FROM ZapisyStudentow WHERE zajecia_id = ?");
                $stmt_aktualni->bind_param("i", $zajecia_id);
                $stmt_aktualni->execute();
                $aktualnie_zapisani = array_column($stmt_aktualni->get_result()->fetch_all(MYSQLI_ASSOC), 'numer_albumu');
                $do_dodania = array_diff($studenci_z_formularza, $aktualnie_zapisani);
                $odznaczeni_z_rocznika = array_diff($wszyscy_z_rocznika, $studenci_z_formularza);
                $do_usuniecia = array_intersect($odznaczeni_z_rocznika, $aktualnie_zapisani);
                if (!empty($do_dodania)) {
                    $stmt_insert = $conn->prepare("INSERT IGNORE INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");
                    foreach ($do_dodania as $numer_albumu) {
                        $stmt_insert->bind_param("ii", $numer_albumu, $zajecia_id);
                        $stmt_insert->execute();
                    }
                }
                if (!empty($do_usuniecia)) {
                    // POPRAWKA: Zamiast skomplikowanego bindowania, tworzymy bezpieczny string z ID
                    $ids_to_delete_str = implode(',', array_map('intval', $do_usuniecia));
                    $conn->query("DELETE FROM ZapisyStudentow WHERE zajecia_id = $zajecia_id AND numer_albumu IN ($ids_to_delete_str)");
                }
                $redirect_url = "zapisy_masowe_zarzadzaj&zajecia_id={$zajecia_id}&rok_rozpoczecia={$rok_rozpoczecia}";
                redirect_with_message($redirect_url, 'success', 'Zapisy na zajęcia zostały zaktualizowane.');
                break;
                
            case 'zarzadzaj_zapisami_studenta':
                $numer_albumu = (int)$_POST['numer_albumu'];
                $rok_id = (int)$_POST['rok_id'];
                $semestr = (int)$_POST['semestr'];
                $zajecia_zaznaczone = $_POST['zajecia_ids'] ?? [];

                $grupy_studenta_sql = "SELECT DISTINCT z.grupa_id FROM ZapisyStudentow zs JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE zs.numer_albumu = ? AND g.rok_akademicki_id = ? AND g.semestr = ?";
                $stmt_grupy = $conn->prepare($grupy_studenta_sql);
                $stmt_grupy->bind_param("iii", $numer_albumu, $rok_id, $semestr);
                $stmt_grupy->execute();
                $grupy_ids = array_column($stmt_grupy->get_result()->fetch_all(MYSQLI_ASSOC), 'grupa_id');
                
                $wszystkie_istotne_zajecia_ids = [];
                if (!empty($grupy_ids)) {
                    $placeholders = implode(',', array_map('intval', $grupy_ids));
                    $zajecia_sql = "SELECT zajecia_id FROM Zajecia WHERE grupa_id IN ($placeholders)";
                    $zajecia_res = $conn->query($zajecia_sql);
                    $wszystkie_istotne_zajecia_ids = array_column($zajecia_res->fetch_all(MYSQLI_ASSOC), 'zajecia_id');
                }

                if (!empty($zajecia_zaznaczone)) {
                    $stmt_zapisz = $conn->prepare("INSERT IGNORE INTO ZapisyStudentow (numer_albumu, zajecia_id) VALUES (?, ?)");
                    foreach ($zajecia_zaznaczone as $zajecia_id_raw) {
                        // POPRAWKA: Tworzymy nową zmienną przed bindowaniem
                        $zajecia_id = (int)$zajecia_id_raw;
                        $stmt_zapisz->bind_param("ii", $numer_albumu, $zajecia_id);
                        $stmt_zapisz->execute();
                    }
                }

                $do_usuniecia = array_diff($wszystkie_istotne_zajecia_ids, $zajecia_zaznaczone);
                if (!empty($do_usuniecia)) {
                    $ids_to_delete_str = implode(',', array_map('intval', $do_usuniecia));
                    $conn->query("DELETE FROM ZapisyStudentow WHERE numer_albumu = $numer_albumu AND zajecia_id IN ($ids_to_delete_str)");
                }

                $redirect_url = "student_zapisy_form&rok_id={$rok_id}&semestr={$semestr}&numer_albumu={$numer_albumu}";
                redirect_with_message($redirect_url, 'success', 'Zapisy studenta zostały zaktualizowane.');
                break;


            // W pliku handler.php wewnątrz switch($action)

            case 'dodaj_preferencje_studenta':
                $student_glowny_id = (int)$_POST['student_glowny_id'];
                $student_docelowy_id = (int)$_POST['student_docelowy_id'];
                $typ_preferencji = $_POST['typ_preferencji'];

                $stmt = $conn->prepare("
                    INSERT INTO PreferencjeStudentow (student_glowny_id, student_docelowy_id, typ_preferencji) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE typ_preferencji = VALUES(typ_preferencji)
                ");
                $stmt->bind_param("iis", $student_glowny_id, $student_docelowy_id, $typ_preferencji);
                $stmt->execute();
                
                redirect_with_message('preferencje_studenta_form&numer_albumu=' . $student_glowny_id, 'success', 'Preferencja została zapisana.');
                break;

            // W pliku handler.php wewnątrz switch($action)

            case 'generuj_grupy_robocze':
                $wybor_id = (int)$_POST['wybor_id'];
                $max_wielkosc = (int)$_POST['max_wielkosc_grupy'];

                // 1. Wyczyść poprzednie wyniki dla tego naboru
                $conn->query("DELETE FROM GrupyRobocze WHERE wybor_id = $wybor_id");

                // 2. Pobierz studentów i ich wybrane przedmioty
                $sql_odpowiedzi = "
                    SELECT os.numer_albumu, p.nazwa_przedmiotu
                    FROM OdpowiedziStudentow os
                    JOIN OpcjeWyboru ow ON os.wybrana_opcja_id = ow.opcja_id
                    JOIN Przedmioty p ON ow.przedmiot_id = p.przedmiot_id
                    WHERE os.wybor_id = ?
                ";
                $stmt_odp = $conn->prepare($sql_odpowiedzi);
                $stmt_odp->bind_param("i", $wybor_id);
                $stmt_odp->execute();
                $odpowiedzi = $stmt_odp->get_result()->fetch_all(MYSQLI_ASSOC);

                // 3. Pobierz WSZYSTKIE preferencje z bazy
                $preferencje_res = $conn->query("SELECT * FROM PreferencjeStudentow")->fetch_all(MYSQLI_ASSOC);
                $preferencje = ['razem' => [], 'osobno' => []];
                foreach($preferencje_res as $pref) {
                    $preferencje[$pref['typ_preferencji']][$pref['student_glowny_id']][] = $pref['student_docelowy_id'];
                    // Dodajemy też odwrotną relację dla bezpieczeństwa
                    $preferencje[$pref['typ_preferencji']][$pref['student_docelowy_id']][] = $pref['student_glowny_id'];
                }

                // 4. Pogrupuj studentów wg identycznego zestawu przedmiotów
                $bloki_przedmiotowe = [];
                foreach($odpowiedzi as $odp) {
                    $bloki_przedmiotowe[$odp['nazwa_przedmiotu']][] = $odp['numer_albumu'];
                }

                // --- NOWY, ZAAWANSOWANY ALGORYTM GRUPOWANIA ---
                $stmt_grupa = $conn->prepare("INSERT INTO GrupyRobocze (nazwa_grupy_roboczej, wybor_id, opis_opcji_wyboru) VALUES (?, ?, ?)");
                $stmt_czlonek = $conn->prepare("INSERT INTO CzlonkowieGrupRoboczych (grupa_robocza_id, numer_albumu) VALUES (?, ?)");

                foreach($bloki_przedmiotowe as $opis_przedmiotow => $studenci_w_bloku) {
                    $nieprzypisani = $studenci_w_bloku;
                    $wygenerowane_grupy_w_bloku = [];
                    $nr_grupy = 1;

                    while(!empty($nieprzypisani)) {
                        $nowa_grupa = [];
                        $lider_grupy = array_shift($nieprzypisani); // Bierzemy pierwszego z listy
                        $nowa_grupa[] = $lider_grupy;

                        // Spróbuj dobrać do niego studentów z preferencją "razem"
                        $partnerzy = $preferencje['razem'][$lider_grupy] ?? [];
                        foreach($partnerzy as $partner_id) {
                            if (count($nowa_grupa) < $max_wielkosc && in_array($partner_id, $nieprzypisani)) {
                                $nowa_grupa[] = $partner_id;
                                // Usuń partnera z listy nieprzypisanych
                                $nieprzypisani = array_diff($nieprzypisani, [$partner_id]);
                            }
                        }

                        // Dopełnij grupę pozostałymi studentami, sprawdzając konflikty
                        foreach($nieprzypisani as $kandydat_id) {
                            if (count($nowa_grupa) >= $max_wielkosc) break;

                            $ma_konflikt = false;
                            // Sprawdź, czy kandydat ma konflikt z kimś już w grupie
                            $konflikty_kandydata = $preferencje['osobno'][$kandydat_id] ?? [];
                            if (!empty(array_intersect($konflikty_kandydata, $nowa_grupa))) {
                                $ma_konflikt = true;
                            }
                            
                            if (!$ma_konflikt) {
                                $nowa_grupa[] = $kandydat_id;
                            }
                        }
                        
                        // Usuń wszystkich przypisanych studentów z głównej puli
                        $nieprzypisani = array_diff($nieprzypisani, $nowa_grupa);
                        
                        // Zapisz grupę do tablicy
                        $wygenerowane_grupy_w_bloku[] = $nowa_grupa;
                    }

                    // Zapisz wygenerowane grupy do bazy danych
                    foreach($wygenerowane_grupy_w_bloku as $grupa_studentow) {
                        $nazwa_grupy = htmlspecialchars($opis_przedmiotow) . " - Grupa Robocza #" . $nr_grupy;
                        $stmt_grupa->bind_param("sis", $nazwa_grupy, $wybor_id, $opis_przedmiotow);
                        $stmt_grupa->execute();
                        $grupa_id = $conn->insert_id;

                        foreach($grupa_studentow as $numer_albumu) {
                            $stmt_czlonek->bind_param("ii", $grupa_id, $numer_albumu);
                            $stmt_czlonek->execute();
                        }
                        $nr_grupy++;
                    }
                }

                redirect_with_message("generator_grup&wybor_id={$wybor_id}", 'success', 'Grupy robocze zostały wygenerowane z uwzględnieniem preferencji.');
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

            // W pliku handler.php wewnątrz switch($action)

            case 'modyfikuj_termin':
                $termin_id = (int)$_POST['termin_id'];
                $prowadzacy_id = (int)$_POST['prowadzacy_id'];
                $sala_id = (int)$_POST['sala_id'];
                $status = $_POST['status'];
                $notatki = $_POST['notatki'];
                $data_zajec = $_POST['data_zajec'];
                $godzina_rozpoczecia = $_POST['godzina_rozpoczecia'];
                $godzina_zakonczenia = $_POST['godzina_zakonczenia'];

                $stmt = $conn->prepare("
                    UPDATE TerminyZajec 
                    SET prowadzacy_id = ?, sala_id = ?, status = ?, notatki = ?, data_zajec = ?, godzina_rozpoczecia = ?, godzina_zakonczenia = ?
                    WHERE termin_id = ?
                ");
                $stmt->bind_param("iisssssi", $prowadzacy_id, $sala_id, $status, $notatki, $data_zajec, $godzina_rozpoczecia, $godzina_zakonczenia, $termin_id);
                $stmt->execute();
                
                // ZMIANA: Nowa logika przekierowania
                if (isset($_POST['return_to']) && !empty($_POST['return_to'])) {
                    $return_url = urldecode($_POST['return_to']);
                    // Usuwamy stary status i message, jeśli istnieją, aby się nie dublowały
                    $return_url = preg_replace('/(&?status=.*)(&?message=.*)/', '', $return_url);
                    $return_url = preg_replace('/&?status=[^&]*/', '', $return_url);
                    $return_url = preg_replace('/&?message=[^&]*/', '', $return_url);
                    
                    $separator = strpos($return_url, '?') === false ? '?' : '&';
                    header("Location: " . $return_url . $separator . "status=success&message=" . urlencode('Termin zajęć został zaktualizowany.'));
                    exit();
                } else {
                    // Domyślne przekierowanie, jeśli adres powrotny nie został przekazany
                    redirect_with_message('plan_modyfikuj_termin', 'success', 'Termin zajęć został zaktualizowany.');
                }
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
            
            case 'edit_grupa':
                $grupa_id = (int)$_POST['grupa_id'];
                $nazwa_grupy = $_POST['nazwa_grupy'];
                $semestr = (int)$_POST['semestr'];
                $rok_akademicki_id = (int)$_POST['rok_akademicki_id'];

                $stmt = $conn->prepare("UPDATE GrupyZajeciowe SET nazwa_grupy = ?, semestr = ?, rok_akademicki_id = ? WHERE grupa_id = ?");
                $stmt->bind_param("siii", $nazwa_grupy, $semestr, $rok_akademicki_id, $grupa_id);
                $stmt->execute();
                redirect_with_message('grupy_lista', 'success', 'Dane grupy zostały zaktualizowane.');
                break;

            case 'edit_zajecia':
                $zajecia_id = (int)$_POST['zajecia_id'];
                $grupa_id = (int)$_POST['grupa_id'];
                $prowadzacy_id = !empty($_POST['prowadzacy_id']) ? (int)$_POST['prowadzacy_id'] : null;

                $stmt = $conn->prepare("UPDATE Zajecia SET grupa_id = ?, prowadzacy_id = ? WHERE zajecia_id = ?");
                $stmt->bind_param("iii", $grupa_id, $prowadzacy_id, $zajecia_id);
                $stmt->execute();
                redirect_with_message('zajecia_lista', 'success', 'Dane zajęć zostały zaktualizowane.');
                break;

            case 'delete_zajecia':
                $zajecia_id = (int)$_POST['zajecia_id'];

                // Zabezpieczenie po stronie serwera - sprawdź, czy jacyś studenci są zapisani
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM ZapisyStudentow WHERE zajecia_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $zajecia_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('zajecia_lista', 'error', 'Nie można usunąć zajęć, ponieważ są do nich zapisani studenci.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM Zajecia WHERE zajecia_id = ?");
                    $stmt->bind_param("i", $zajecia_id);
                    $stmt->execute();
                    redirect_with_message('zajecia_lista', 'success', 'Zajęcia zostały usunięte.');
                }
                break;

            case 'dodaj_wpis_przebiegu':
                $numer_albumu = (int)$_POST['numer_albumu'];
                $semestr = (int)$_POST['semestr'];
                $rok_akademicki_id = (int)$_POST['rok_akademicki_id'];
                $status_semestru = $_POST['status_semestru'];
                $stmt = $conn->prepare("INSERT INTO PrzebiegStudiow (numer_albumu, semestr, rok_akademicki_id, status_semestru) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status_semestru=VALUES(status_semestru)");
                $stmt->bind_param("iiis", $numer_albumu, $semestr, $rok_akademicki_id, $status_semestru);
                $stmt->execute();
                redirect_with_message('student_profil&numer_albumu='.$numer_albumu.'&tab=przebieg', 'success', 'Dodano nowy wpis w przebiegu studiów.');
                break;

            case 'edit_student':
                $numer_albumu = (int)$_POST['numer_albumu'];
                // Pobieramy wszystkie nowe dane z formularza
                $imie = $_POST['imie'];
                $nazwisko = $_POST['nazwisko'];
                $pesel = $_POST['pesel'];
                $data_urodzenia = $_POST['data_urodzenia'];
                $adres_zamieszkania = $_POST['adres_zamieszkania'];
                $telefon = $_POST['telefon'];
                $email = $_POST['email'];
                $rok_rozpoczecia_studiow = (int)$_POST['rok_rozpoczecia_studiow'];
                $status_studenta = $_POST['status_studenta'];

                $stmt = $conn->prepare("UPDATE Studenci SET imie=?, nazwisko=?, pesel=?, data_urodzenia=?, adres_zamieszkania=?, telefon=?, email=?, rok_rozpoczecia_studiow=?, status_studenta=? WHERE numer_albumu=?");
                $stmt->bind_param("sssssssisi", $imie, $nazwisko, $pesel, $data_urodzenia, $adres_zamieszkania, $telefon, $email, $rok_rozpoczecia_studiow, $status_studenta, $numer_albumu);
                $stmt->execute();
                redirect_with_message('student_profil&numer_albumu='.$numer_albumu.'&tab=dane', 'success', 'Dane studenta zostały zaktualizowane.');
                break;

            case 'edit_rok':
                $rok_id = (int)$_POST['rok_id'];
                $nazwa_roku = $_POST['nazwa_roku'];
                $stmt = $conn->prepare("UPDATE RokiAkademickie SET nazwa_roku = ? WHERE rok_akademicki_id = ?");
                $stmt->bind_param("si", $nazwa_roku, $rok_id);
                $stmt->execute();
                redirect_with_message('roki_lista', 'success', 'Nazwa roku akademickiego została zaktualizowana.');
                break;

            case 'delete_rok':
                $rok_id = (int)$_POST['rok_id'];
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM KonfiguracjaPrzedmiotu WHERE rok_akademicki_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $rok_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();
                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('roki_lista', 'error', 'Nie można usunąć roku, jest używany w konfiguracjach przedmiotów.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM RokiAkademickie WHERE rok_akademicki_id = ?");
                    $stmt->bind_param("i", $rok_id);
                    $stmt->execute();
                    redirect_with_message('roki_lista', 'success', 'Rok akademicki został usunięty.');
                }
                break;

            case 'edit_sala':
                $sala_id = (int)$_POST['sala_id'];
                $budynek = $_POST['budynek'];
                $numer_sali = $_POST['numer_sali'];

                $stmt = $conn->prepare("UPDATE SaleZajeciowe SET budynek = ?, numer_sali = ? WHERE sala_id = ?");
                $stmt->bind_param("ssi", $budynek, $numer_sali, $sala_id);
                $stmt->execute();
                redirect_with_message('sale_lista', 'success', 'Dane sali zostały zaktualizowane.');
                break;

            case 'delete_sala':
                $sala_id = (int)$_POST['sala_id'];
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM TerminyZajec WHERE sala_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $sala_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('sale_lista', 'error', 'Nie można usunąć sali, jest używana w planie zajęć.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM SaleZajeciowe WHERE sala_id = ?");
                    $stmt->bind_param("i", $sala_id);
                    $stmt->execute();
                    redirect_with_message('sale_lista', 'success', 'Sala została usunięta.');
                }
                break;
                        
            case 'delete_student':
                $numer_albumu = (int)$_POST['numer_albumu'];

                // Zabezpieczenie po stronie serwera
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM ZapisyStudentow WHERE numer_albumu = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $numer_albumu);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('studenci_lista', 'error', 'Nie można usunąć studenta, który jest zapisany na zajęcia. Zmień jego status na "skreślony".');
                } else {
                    $stmt = $conn->prepare("DELETE FROM Studenci WHERE numer_albumu = ?");
                    $stmt->bind_param("i", $numer_albumu);
                    $stmt->execute();
                    redirect_with_message('studenci_lista', 'success', 'Student został usunięty z bazy danych.');
                }
                break;

            case 'delete_grupa':
                $grupa_id = (int)$_POST['grupa_id'];

                // Zabezpieczenie po stronie serwera
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM Zajecia WHERE grupa_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $grupa_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('grupy_lista', 'error', 'Nie można usunąć grupy, ponieważ jest przypisana do zajęć.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM GrupyZajeciowe WHERE grupa_id = ?");
                    $stmt->bind_param("i", $grupa_id);
                    $stmt->execute();
                    redirect_with_message('grupy_lista', 'success', 'Grupa została usunięta.');
                }
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
                $wydzial_id = (int)$_POST['wydzial_id'];

                $stmt = $conn->prepare("UPDATE Przedmioty SET nazwa_przedmiotu = ?, wydzial_id = ? WHERE przedmiot_id = ?");
                $stmt->bind_param("sii", $nowa_nazwa, $wydzial_id, $przedmiot_id);
                $stmt->execute();
                redirect_with_message('przedmioty_lista', 'success', 'Dane przedmiotu zostały zaktualizowane.');
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

            case 'edit_prowadzacy':
                $prowadzacy_id = (int)$_POST['prowadzacy_id'];
                $imie = $_POST['imie'];
                $nazwisko = $_POST['nazwisko'];
                $tytul_naukowy = $_POST['tytul_naukowy'];
                $email = $_POST['email'];
                $is_admin = (int)$_POST['is_admin'];

                $stmt = $conn->prepare("UPDATE Prowadzacy SET imie = ?, nazwisko = ?, tytul_naukowy = ?, email = ?, is_admin = ? WHERE prowadzacy_id = ?");
                $stmt->bind_param("ssssii", $imie, $nazwisko, $tytul_naukowy, $email, $is_admin, $prowadzacy_id);
                $stmt->execute();
                redirect_with_message('prowadzacy_lista', 'success', 'Dane prowadzącego zostały zaktualizowane.');
                break;

            case 'add_wydzial':
                $stmt = $conn->prepare("INSERT INTO Wydzialy (nazwa_wydzialu, skrot_wydzialu) VALUES (?, ?)");
                $stmt->bind_param("ss", $_POST['nazwa_wydzialu'], $_POST['skrot_wydzialu']);
                $stmt->execute();
                redirect_with_message('wydzialy_lista', 'success', 'Dodano nowy wydział.');
                break;
            case 'edit_wydzial':
                $stmt = $conn->prepare("UPDATE Wydzialy SET nazwa_wydzialu = ?, skrot_wydzialu = ? WHERE wydzial_id = ?");
                $stmt->bind_param("ssi", $_POST['nazwa_wydzialu'], $_POST['skrot_wydzialu'], $_POST['wydzial_id']);
                $stmt->execute();
                redirect_with_message('wydzialy_lista', 'success', 'Dane wydziału zaktualizowano.');
                break;
            case 'delete_wydzial':
                $wydzial_id = (int)$_POST['wydzial_id'];
                $check = $conn->query("SELECT COUNT(*) as cnt FROM Przedmioty WHERE wydzial_id = $wydzial_id")->fetch_assoc()['cnt'];
                if ($check > 0) {
                    redirect_with_message('wydzialy_lista', 'error', 'Nie można usunąć wydziału, ma przypisane przedmioty.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM Wydzialy WHERE wydzial_id = ?");
                    $stmt->bind_param("i", $wydzial_id);
                    $stmt->execute();
                    redirect_with_message('wydzialy_lista', 'success', 'Wydział został usunięty.');
                }
                break;

            // Logika dla Przebiegu Studiów
            case 'rejestruj_na_kolejny_semestr':
                $numer_albumu = (int)$_POST['numer_albumu'];
                $nastepny_semestr = (int)$_POST['aktualny_semestr'] + 1;
                $biezacy_rok = $conn->query("SELECT rok_akademicki_id FROM RokiAkademickie ORDER BY nazwa_roku DESC LIMIT 1")->fetch_assoc()['rok_akademicki_id'];
                $stmt = $conn->prepare("INSERT INTO PrzebiegStudiow (numer_albumu, semestr, rok_akademicki_id, status_semestru) VALUES (?, ?, ?, 'zarejestrowany') ON DUPLICATE KEY UPDATE status_semestru = 'zarejestrowany'");
                $stmt->bind_param("iii", $numer_albumu, $nastepny_semestr, $biezacy_rok);
                $stmt->execute();
                redirect_with_message('student_profil&numer_albumu='.$numer_albumu.'&tab=przebieg', 'success', 'Student został zarejestrowany na kolejny semestr.');
                break;

            case 'delete_prowadzacy':
                $prowadzacy_id = (int)$_POST['prowadzacy_id'];

                // Zabezpieczenie po stronie serwera
                $check_sql = "SELECT COUNT(*) as uzycie_count FROM Zajecia WHERE prowadzacy_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("i", $prowadzacy_id);
                $stmt_check->execute();
                $uzycie = $stmt_check->get_result()->fetch_assoc();

                if ($uzycie['uzycie_count'] > 0) {
                    redirect_with_message('prowadzacy_lista', 'error', 'Nie można usunąć prowadzącego, ponieważ jest przypisany do zajęć.');
                } else {
                    $stmt = $conn->prepare("DELETE FROM Prowadzacy WHERE prowadzacy_id = ?");
                    $stmt->bind_param("i", $prowadzacy_id);
                    $stmt->execute();
                    redirect_with_message('prowadzacy_lista', 'success', 'Prowadzący został usunięty.');
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









