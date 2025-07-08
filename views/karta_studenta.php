<?php
// Plik: views/karta_studenta.php (Kompletna i poprawiona wersja)

// Używamy ID studenta zapisanego w sesji
$numer_albumu = (int)$_SESSION['user_id'];

// Pobierz dane studenta
$student_info_stmt = $conn->prepare("SELECT imie, nazwisko FROM Studenci WHERE numer_albumu = ?");
$student_info_stmt->bind_param("i", $numer_albumu);
$student_info_stmt->execute();
$student_info = $student_info_stmt->get_result()->fetch_assoc();

// Znajdź zakres semestrów dla studenta
$semestry_sql = "SELECT MIN(g.semestr) as min_sem, MAX(g.semestr) as max_sem FROM ZapisyStudentow zs JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE zs.numer_albumu = ?";
$semestry_stmt = $conn->prepare($semestry_sql);
$semestry_stmt->bind_param("i", $numer_albumu);
$semestry_stmt->execute();
$semestry_info = $semestry_stmt->get_result()->fetch_assoc();
$min_semestr = $semestry_info['min_sem'] ?? 1;
$max_semestr = $semestry_info['max_sem'] ?? 1;
$biezacy_semestr = isset($_GET['semestr']) ? (int)$_GET['semestr'] : $max_semestr;

// --- Główna logika pobierania danych ---

// 1. Pobierz wszystkie komponenty (zajęcia)
// ZMIANA: Zapytanie zostało w pełni poprawione, aby pobierało wagę z `KonfiguracjaKomponentow` i używało LEFT JOIN dla prowadzącego
$komponenty_sql = "
    SELECT 
        k.konfiguracja_id,
        p.nazwa_przedmiotu,
        z.forma_zajec,
        z.zajecia_id,
        zs.zapis_id,
        IFNULL(CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko), '') as prowadzacy,
        kk.waga_oceny
    FROM ZapisyStudentow zs
    JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    LEFT JOIN KonfiguracjaKomponentow kk ON k.konfiguracja_id = kk.konfiguracja_id AND z.forma_zajec = kk.forma_zajec
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    LEFT JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
    WHERE zs.numer_albumu = ? AND g.semestr = ?
    ORDER BY p.nazwa_przedmiotu, z.forma_zajec
";
$stmt_komponenty = $conn->prepare($komponenty_sql);
$stmt_komponenty->bind_param("ii", $numer_albumu, $biezacy_semestr);
$stmt_komponenty->execute();
$komponenty_res = $stmt_komponenty->get_result()->fetch_all(MYSQLI_ASSOC);

$przedmioty_w_semestrze = [];
foreach($komponenty_res as $komponent) {
    $przedmioty_w_semestrze[$komponent['konfiguracja_id']]['nazwa'] = $komponent['nazwa_przedmiotu'];
    $przedmioty_w_semestrze[$komponent['konfiguracja_id']]['komponenty'][] = $komponent;
}

// 2. Pobierz zatwierdzone oceny z komponentów
$oceny_komp_sql = "SELECT ocena_komponentu_id, zapis_id, termin, wartosc_oceny FROM OcenyKoncoweZKomponentu WHERE zapis_id IN (SELECT zapis_id FROM ZapisyStudentow WHERE numer_albumu = ?) AND czy_zatwierdzona = TRUE";
$stmt_oceny_k = $conn->prepare($oceny_komp_sql);
$stmt_oceny_k->bind_param("i", $numer_albumu);
$stmt_oceny_k->execute();
$oceny_komp_res = $stmt_oceny_k->get_result()->fetch_all(MYSQLI_ASSOC);
$oceny_z_komponentow = [];
foreach($oceny_komp_res as $ocena) {
    $oceny_z_komponentow[$ocena['zapis_id']][$ocena['termin']] = $ocena;
}

// 3. Pobierz oceny całkowite i ECTS
$oceny_calk_sql = "SELECT oc.konfiguracja_id, oc.wartosc_obliczona, kp.punkty_ects FROM OcenyCalkowiteZPrzedmiotu oc JOIN KonfiguracjaPrzedmiotu kp ON oc.konfiguracja_id = kp.konfiguracja_id WHERE oc.numer_albumu = ?";
$stmt_oceny_c = $conn->prepare($oceny_calk_sql);
$stmt_oceny_c->bind_param("i", $numer_albumu);
$stmt_oceny_c->execute();
$oceny_calk_res = $stmt_oceny_c->get_result()->fetch_all(MYSQLI_ASSOC);
$oceny_calkowite = [];
foreach($oceny_calk_res as $ocena) {
    $oceny_calkowite[$ocena['konfiguracja_id']] = ['ocena' => $ocena['wartosc_obliczona'], 'ects' => $ocena['punkty_ects']];
}

// --- Obliczanie Statystyk ---
// (Cały blok kodu do obliczania statystyk skopiowany z `karta_studenta_pokaz.php`)
$ects_zdobyte_semestr = 0;
$ects_do_zdobycia_total = 0;
$ects_zdobyte_total = 0;
$srednia_semestr_suma_wazona = 0;
$srednia_semestr_suma_ects = 0;
$srednia_rok_suma_wazona = 0;
$srednia_rok_suma_ects = 0;
$srednie_komponenty = [];

$wszystkie_zapisy_sql = "SELECT DISTINCT k.konfiguracja_id, k.punkty_ects FROM ZapisyStudentow zs JOIN Zajecia z ON zs.zajecia_id=z.zajecia_id JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id=k.konfiguracja_id WHERE zs.numer_albumu = ?";
$stmt_wszystkie_zapisy = $conn->prepare($wszystkie_zapisy_sql);
$stmt_wszystkie_zapisy->bind_param("i", $numer_albumu);
$stmt_wszystkie_zapisy->execute();
$wszystkie_zapisy_res = $stmt_wszystkie_zapisy->get_result()->fetch_all(MYSQLI_ASSOC);

foreach($wszystkie_zapisy_res as $zapis) {
    $ects_do_zdobycia_total += $zapis['punkty_ects'];
    if(isset($oceny_calkowite[$zapis['konfiguracja_id']])) {
        $ocena_calk = $oceny_calkowite[$zapis['konfiguracja_id']]['ocena'];
        if((is_numeric($ocena_calk) && $ocena_calk >= 3.0) || strtolower($ocena_calk) == 'zal') {
            $ects_zdobyte_total += $zapis['punkty_ects'];
        }
    }
}

$semestry_w_roku = [];
if ($biezacy_semestr % 2 != 0) { $semestry_w_roku = [$biezacy_semestr, $biezacy_semestr + 1]; } 
else { $semestry_w_roku = [$biezacy_semestr - 1, $biezacy_semestr]; }

$oceny_do_srednich_sql = "SELECT oc.wartosc_obliczona, kp.punkty_ects, g.semestr FROM OcenyCalkowiteZPrzedmiotu oc JOIN KonfiguracjaPrzedmiotu kp ON oc.konfiguracja_id = kp.konfiguracja_id JOIN Zajecia z ON z.konfiguracja_id = oc.konfiguracja_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE oc.numer_albumu = ? GROUP BY oc.ocena_calkowita_id";
$stmt_srednie = $conn->prepare($oceny_do_srednich_sql);
$stmt_srednie->bind_param("i", $numer_albumu);
$stmt_srednie->execute();
$oceny_do_srednich_res = $stmt_srednie->get_result()->fetch_all(MYSQLI_ASSOC);

foreach($oceny_do_srednich_res as $ocena) {
    if(is_numeric($ocena['wartosc_obliczona'])) {
        $ocena_num = (float)$ocena['wartosc_obliczona'];
        $ects = (int)$ocena['punkty_ects'];
        if ($ocena['semestr'] == $biezacy_semestr) {
            $srednia_semestr_suma_wazona += $ocena_num * $ects;
            $srednia_semestr_suma_ects += $ects;
        }
        if (in_array($ocena['semestr'], $semestry_w_roku)) {
            $srednia_rok_suma_wazona += $ocena_num * $ects;
            $srednia_rok_suma_ects += $ects;
        }
    }
    if ($ocena['semestr'] == $biezacy_semestr && ((is_numeric($ocena['wartosc_obliczona']) && $ocena['wartosc_obliczona'] >= 3.0) || strtolower($ocena['wartosc_obliczona']) == 'zal')) {
        $ects_zdobyte_semestr += $ocena['punkty_ects'];
    }
}
$srednia_semestr = ($srednia_semestr_suma_ects > 0) ? round($srednia_semestr_suma_wazona / $srednia_semestr_suma_ects, 2) : 0;
$srednia_rok = ($srednia_rok_suma_ects > 0) ? round($srednia_rok_suma_wazona / $srednia_rok_suma_ects, 2) : 0;

$oceny_komponentow_sql = "SELECT z.forma_zajec, ok.wartosc_oceny FROM OcenyKoncoweZKomponentu ok JOIN ZapisyStudentow zs ON ok.zapis_id = zs.zapis_id JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE zs.numer_albumu = ? AND ok.czy_zatwierdzona = TRUE AND g.semestr = ?";
$stmt_komp_avg = $conn->prepare($oceny_komponentow_sql);
$stmt_komp_avg->bind_param("ii", $numer_albumu, $biezacy_semestr);
$stmt_komp_avg->execute();
$oceny_komp_avg_res = $stmt_komp_avg->get_result()->fetch_all(MYSQLI_ASSOC);
$sumy_ocen_komp = []; $liczniki_ocen_komp = [];
foreach($oceny_komp_avg_res as $ocena) {
    if(is_numeric($ocena['wartosc_oceny'])) {
        $forma = $ocena['forma_zajec'];
        if(!isset($sumy_ocen_komp[$forma])) { $sumy_ocen_komp[$forma] = 0; $liczniki_ocen_komp[$forma] = 0; }
        $sumy_ocen_komp[$forma] += (float)$ocena['wartosc_oceny'];
        $liczniki_ocen_komp[$forma]++;
    }
}
$formy = ['Laboratorium', 'Audytoryjne', 'Wykład', 'Lektorat', 'Projekt', 'Seminarium'];
foreach($formy as $forma) {
    if(isset($sumy_ocen_komp[$forma])) {
        $srednie_komponenty[$forma] = number_format(round($sumy_ocen_komp[$forma] / $liczniki_ocen_komp[$forma], 2), 2);
    } else { $srednie_komponenty[$forma] = "Brak danych"; }
}
?>

<h1>Twoje Oceny - <?= htmlspecialchars($student_info['imie'] . ' ' . $student_info['nazwisko']) ?></h1>

<div style="display: flex; justify-content: space-between; align-items: center; background: #333; color: white; padding: 10px; border-radius: 5px;">
    <a href="?page=karta_studenta&semestr=<?= $biezacy_semestr - 1 ?>" class="btn-add" <?= ($biezacy_semestr <= $min_semestr) ? 'style="visibility: hidden;"' : '' ?>>
        &laquo; Poprzedni semestr
    </a>
    <h2 style="margin: 0; color: white;">Semestr: <?= $biezacy_semestr ?></h2>
    <a href="?page=karta_studenta&semestr=<?= $biezacy_semestr + 1 ?>" class="btn-add" <?= ($biezacy_semestr >= $max_semestr) ? 'style="visibility: hidden;"' : '' ?>>
        Następny semestr &raquo;
    </a>
</div>

<table style="margin-top: 20px;">
    <thead>
        <tr>
            <th>Nazwa przedmiotu</th>
            <th>Typ</th>
            <th>ID Oceny</th>
            <th>Nauczyciel</th>
            <th>Waga</th>
            <th>I termin</th>
            <th>I poprawka</th>
            <th>II poprawka</th>
            <th>ECTS</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($przedmioty_w_semestrze)): ?>
            <tr><td colspan="9">Brak przedmiotów i ocen w tym semestrze.</td></tr>
        <?php else: ?>
            <?php foreach($przedmioty_w_semestrze as $konfiguracja_id => $przedmiot): ?>
                <?php foreach($przedmiot['komponenty'] as $komponent): ?>
                    <tr>
                        <td><?= htmlspecialchars($przedmiot['nazwa']) ?></td>
                        <td><?= htmlspecialchars($komponent['forma_zajec']) ?></td>
                        <td><?= $oceny_z_komponentow[$komponent['zapis_id']][1]['ocena_komponentu_id'] ?? '' ?></td>
                        <td><?= htmlspecialchars($komponent['prowadzacy']) ?></td>
                        <td><?= htmlspecialchars(number_format($komponent['waga_oceny'], 2)) ?></td>
                        <td><?= $oceny_z_komponentow[$komponent['zapis_id']][1]['wartosc_oceny'] ?? '' ?></td>
                        <td><?= $oceny_z_komponentow[$komponent['zapis_id']][2]['wartosc_oceny'] ?? '' ?></td>
                        <td><?= $oceny_z_komponentow[$komponent['zapis_id']][3]['wartosc_oceny'] ?? '' ?></td>
                        <td></td> 
                    </tr>
                <?php endforeach; ?>
                <tr style="background-color: #fffb8f; font-weight: bold;">
                    <td><?= htmlspecialchars($przedmiot['nazwa']) ?></td>
                    <td>ocena końcowa</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td colspan="3" style="text-align: center; font-size: 1.2em;">
                        <?= $oceny_calkowite[$konfiguracja_id]['ocena'] ?? 'Brak' ?>
                    </td>
                    <td><?= $oceny_calkowite[$konfiguracja_id]['ects'] ?? '0' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div style="margin-top: 40px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
    <h2 style="margin-top: 0;">Statystyki</h2>
    <table style="width: 100%;">
        <tbody>
            <tr style="background-color: #e9ecef;">
                <td style="font-weight: bold;">Zdobyte punkty ECTS w tym semestrze:</td>
                <td style="font-weight: bold; font-size: 1.2em;"><?= $ects_zdobyte_semestr ?></td>
            </tr>
            <tr>
                <td>Zdobyte punkty ECTS od początku studiów:</td>
                <td><?= $ects_zdobyte_total ?></td>
            </tr>
             <tr>
                <td>Do zdobycia punktów ECTS od początku studiów:</td>
                <td><?= $ects_do_zdobycia_total ?></td>
            </tr>
            <tr style="background-color: #e9ecef;">
                <td style="font-weight: bold;">Średnia ważona ECTS w tym semestrze:</td>
                <td style="font-weight: bold; font-size: 1.2em;"><?= number_format($srednia_semestr, 2) ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Średnia ważona ECTS w tym roku akademickim:</td>
                <td style="font-weight: bold;"><?= number_format($srednia_rok, 2) ?></td>
            </tr>
            <tr><td colspan="2"><hr></td></tr>
             <tr>
                <td>Średnia arytmetyczna z laboratoriów:</td>
                <td><?= $srednie_komponenty['Laboratorium'] ?></td>
            </tr>
             <tr>
                <td>Średnia arytmetyczna z audytoriów:</td>
                <td><?= $srednie_komponenty['Audytoryjne'] ?></td>
            </tr>
             <tr>
                <td>Średnia arytmetyczna z wykładów:</td>
                <td><?= $srednie_komponenty['Wykład'] ?></td>
            </tr>
             <tr>
                <td>Średnia arytmetyczna z lektoratów:</td>
                <td><?= $srednie_komponenty['Lektorat'] ?></td>
            </tr>
        </tbody>
    </table>
</div>