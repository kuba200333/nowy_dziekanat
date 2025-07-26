<?php
// Plik: views/student_profil_oceny.php
// Ten plik jest dołączany w `student_profil.php`, więc ma dostęp do zmiennych $conn i $numer_albumu.

// Znajdź zakres semestrów dla studenta
$semestry_sql = "SELECT MIN(g.semestr) as min_sem, MAX(g.semestr) as max_sem FROM ZapisyStudentow zs JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE zs.numer_albumu = ?";
$semestry_stmt = $conn->prepare($semestry_sql);
$semestry_stmt->bind_param("i", $numer_albumu);
$semestry_stmt->execute();
$semestry_info = $semestry_stmt->get_result()->fetch_assoc();
$min_semestr = $semestry_info['min_sem'] ?? 1;
$max_semestr = $semestry_info['max_sem'] ?? 1;

// Ustaw bieżący semestr na podstawie parametru GET, domyślnie najwcześniejszy
$biezacy_semestr = isset($_GET['semestr']) ? (int)$_GET['semestr'] : $min_semestr;

// --- Pobieranie danych do tabeli ocen (logika skopiowana i dostosowana z karta_studenta_pokaz.php) ---

// 1. Pobierz komponenty, na które student był zapisany w danym semestrze
$komponenty_sql = "
    SELECT 
        k.konfiguracja_id, p.nazwa_przedmiotu, z.forma_zajec, z.zajecia_id, zs.zapis_id,
        IFNULL(CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko), 'Brak przypisania') as prowadzacy,
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
// ... (Wklej tutaj cały blok kodu do obliczania statystyk z pliku `karta_studenta_pokaz.php`, jeśli go potrzebujesz) ...

?>

<h3>Oceny</h3>
<div style="display: flex; justify-content: space-between; align-items: center; background: #333; color: white; padding: 10px; border-radius: 5px;">
    <a href="?page=student_profil&numer_albumu=<?= $numer_albumu ?>&tab=oceny&semestr=<?= $biezacy_semestr - 1 ?>" class="btn-add" <?= ($biezacy_semestr <= $min_semestr) ? 'style="visibility: hidden;"' : '' ?>>
        &laquo; Poprzedni semestr
    </a>
    <h2 style="margin: 0; color: white;">Semestr: <?= $biezacy_semestr ?></h2>
    <a href="?page=student_profil&numer_albumu=<?= $numer_albumu ?>&tab=oceny&semestr=<?= $biezacy_semestr + 1 ?>" class="btn-add" <?= ($biezacy_semestr >= $max_semestr) ? 'style="visibility: hidden;"' : '' ?>>
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
                        <td><?= htmlspecialchars(number_format($komponent['waga_oceny'] ?? 0, 2)) ?></td>
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