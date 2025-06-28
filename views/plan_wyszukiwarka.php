<?php
// Plik: views/plan_wyszukiwarka.php (Wersja 7-dniowa)

// --- Logika PHP ---
// Cała logika PHP na górze pliku do pobierania danych pozostaje bez zmian.
$filtry = [
    'student' => $_GET['student'] ?? '',
    'prowadzacy' => $_GET['prowadzacy'] ?? '',
    'przedmiot' => $_GET['przedmiot'] ?? '',
    'sala' => $_GET['sala'] ?? '',
    'grupa' => $_GET['grupa'] ?? ''
];
$tydzien_offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$terminy_do_wyswietlenia = [];
$czy_filtrowac = !empty(array_filter($filtry));

function generateColor($seed) {
    $hash = crc32($seed); $r = ($hash & 0xFF0000) >> 16; $g = ($hash & 0x00FF00) >> 8; $b = $hash & 0x0000FF;
    return "rgba(" . ($r | 128) . ", " . ($g | 128) . ", " . ($b | 128) . ", 0.9)";
}

$dzisiaj = new DateTime();
if ($tydzien_offset != 0) {
    $modyfikator = ($tydzien_offset > 0 ? '+' : '-') . abs($tydzien_offset) . ' weeks';
    $dzisiaj->modify($modyfikator);
}
$start_tygodnia = (clone $dzisiaj)->modify('monday this week');
$koniec_tygodnia = (clone $dzisiaj)->modify('sunday this week');
$start_tygodnia_str = $start_tygodnia->format('Y-m-d');
$koniec_tygodnia_str = $koniec_tygodnia->format('Y-m-d');

if ($czy_filtrowac) {
    $warunki_where = [];
    $parametry_bind = [];
    $typy_bind = "";
    $sql = "
        SELECT 
            t.data_zajec, t.godzina_rozpoczecia, t.godzina_zakonczenia, t.status, t.notatki,
            p.nazwa_przedmiotu, z.forma_zajec, s.budynek, s.numer_sali,
            CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko) as prowadzacy,
            g.nazwa_grupy
        FROM TerminyZajec t
        JOIN Zajecia z ON t.zajecia_id = z.zajecia_id
        JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        JOIN SaleZajeciowe s ON t.sala_id = s.sala_id
        JOIN Prowadzacy pr ON t.prowadzacy_id = pr.prowadzacy_id
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        LEFT JOIN ZapisyStudentow zs ON z.zajecia_id = zs.zajecia_id
        LEFT JOIN Studenci st ON zs.numer_albumu = st.numer_albumu
    ";
    if (!empty($filtry['student'])) { $warunki_where[] = "st.numer_albumu = ?"; $parametry_bind[] = $filtry['student']; $typy_bind .= "i"; }
    if (!empty($filtry['prowadzacy'])) { $warunki_where[] = "CONCAT(pr.imie, ' ', pr.nazwisko) LIKE ?"; $parametry_bind[] = "%" . $filtry['prowadzacy'] . "%"; $typy_bind .= "s"; }
    if (!empty($filtry['przedmiot'])) { $warunki_where[] = "p.nazwa_przedmiotu LIKE ?"; $parametry_bind[] = "%" . $filtry['przedmiot'] . "%"; $typy_bind .= "s"; }
    if (!empty($filtry['sala'])) { $warunki_where[] = "CONCAT(s.budynek, ' ', s.numer_sali) LIKE ?"; $parametry_bind[] = "%" . $filtry['sala'] . "%"; $typy_bind .= "s"; }
    if (!empty($filtry['grupa'])) { $warunki_where[] = "g.nazwa_grupy LIKE ?"; $parametry_bind[] = "%" . $filtry['grupa'] . "%"; $typy_bind .= "s"; }
    $warunki_where[] = "t.data_zajec BETWEEN ? AND ?";
    $parametry_bind[] = $start_tygodnia_str;
    $parametry_bind[] = $koniec_tygodnia_str;
    $typy_bind .= "ss";
    $sql .= " WHERE " . implode(" AND ", $warunki_where);
    $sql .= " GROUP BY t.termin_id ORDER BY t.data_zajec, t.godzina_rozpoczecia";
    $stmt = $conn->prepare($sql);
    if (!empty($parametry_bind)) { $stmt->bind_param($typy_bind, ...$parametry_bind); }
    $stmt->execute();
    $terminy_do_wyswietlenia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<style>
    /* ZMIANA 1: Siatka ma teraz 7 kolumn na dni tygodnia */
    .schedule-container { display: grid; grid-template-columns: 60px repeat(7, 1fr); grid-template-rows: auto; border: 1px solid #ccc; background-color: white; }
    .schedule-header { padding: 10px; text-align: center; font-weight: bold; border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; background-color: #f8f9fa; }
    .schedule-day-grid { display: grid; grid-template-rows: repeat(48, 20px); position: relative; border-right: 1px solid #ccc; }
    .schedule-times { border-right: 1px solid #ccc; }
    .schedule-times div { height: 80px; border-bottom: 1px dashed #eee; display: flex; align-items: center; justify-content: center; font-size: 12px; box-sizing: border-box;}
    .schedule-event { position: absolute; width: 90%; left: 2.5%; border-radius: 5px; padding: 5px; font-size: 11px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: white; border: 1px solid rgba(0,0,0,0.2); }
    .event-title { font-weight: bold; }
    .event-details { font-size: 10px; }
    .event-cancelled { text-decoration: line-through; }
</style>

<h1>Uniwersalna Wyszukiwarka Planu Zajęć</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="plan_wyszukiwarka">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; margin-bottom: 20px;">
        <div class="form-group"><label>Numer albumu studenta:</label><input type="text" name="student" value="<?= htmlspecialchars($filtry['student']) ?>"></div>
        <div class="form-group"><label>Prowadzący:</label><input type="text" name="prowadzacy" value="<?= htmlspecialchars($filtry['prowadzacy']) ?>"></div>
        <div class="form-group"><label>Przedmiot:</label><input type="text" name="przedmiot" value="<?= htmlspecialchars($filtry['przedmiot']) ?>"></div>
        <div class="form-group"><label>Sala:</label><input type="text" name="sala" value="<?= htmlspecialchars($filtry['sala']) ?>"></div>
        <div class="form-group"><label>Nazwa grupy:</label><input type="text" name="grupa" value="<?= htmlspecialchars($filtry['grupa']) ?>"></div>
        <div class="form-group"><button type="submit" class="btn-add" style="width: 100%;">Filtruj</button></div>
    </div>
</form>

<?php if ($czy_filtrowac): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
        <a href="?page=plan_wyszukiwarka&offset=<?= $tydzien_offset - 1 ?>&<?= http_build_query($filtry) ?>" class="btn-add">&laquo; Poprzedni tydzień</a>
        <strong>Tydzień: <?= $start_tygodnia->format('d.m.Y') ?> - <?= $koniec_tygodnia->format('d.m.Y') ?></strong>
        <a href="?page=plan_wyszukiwarka&offset=<?= $tydzien_offset + 1 ?>&<?= http_build_query($filtry) ?>" class="btn-add">Następny tydzień &raquo;</a>
    </div>

    <div class="schedule-container">
        <div class="schedule-header">Godz.</div>
        <div class="schedule-header">Poniedziałek</div>
        <div class="schedule-header">Wtorek</div>
        <div class="schedule-header">Środa</div>
        <div class="schedule-header">Czwartek</div>
        <div class="schedule-header">Piątek</div>
        <div class="schedule-header">Sobota</div>
        <div class="schedule-header">Niedziela</div>

        <div class="schedule-times">
            <?php for ($h = 8; $h < 20; $h++): ?>
                <div><?= sprintf('%02d:00', $h) ?></div>
            <?php endfor; ?>
        </div>

        <?php for ($day = 1; $day <= 7; $day++): ?>
            <div class="schedule-day-grid">
                <?php foreach ($terminy_do_wyswietlenia as $termin): ?>
                    <?php
                        $data_terminu = new DateTime($termin['data_zajec']);
                        if ($data_terminu->format('N') != $day) continue;
                        
                        $start = new DateTime($termin['godzina_rozpoczecia']);
                        $end = new DateTime($termin['godzina_zakonczenia']);
                        $duration_minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
                        $start_minutes_from_8am = ($start->format('H') - 8) * 60 + (int)$start->format('i');
                        $top_position = ($start_minutes_from_8am / 15) * 20;
                        $height = ($duration_minutes / 15) * 20;
                        
                        $kolor_tla = '#85929E'; 
                        $extra_class = '';
                        $mapa_kolorow = [
                            'Wykład' => '#247C84', 'Lektorat' => '#C44F00', 'Laboratorium' => '#1A8238',
                            'Audytoryjne' => '#007BB0', 'Projekt' => '#8E44AD', 'Seminarium' => '#D35400'
                        ];
                        if (array_key_exists($termin['forma_zajec'], $mapa_kolorow)) {
                            $kolor_tla = $mapa_kolorow[$termin['forma_zajec']];
                        }
                        if (stripos($termin['nazwa_przedmiotu'], 'rektorskie') !== false) {
                            $kolor_tla = '#3788D8';
                        }
                        if ($termin['status'] == 'odwolane') {
                            $kolor_tla = '#494949';
                            $extra_class = 'event-cancelled';
                        }
                        if ($termin['status'] == 'zastepstwo') {
                            $kolor_tla = '#006FDD';
                        }
                    ?>
                    <div class="schedule-event <?= $extra_class ?>" style="top: <?= $top_position ?>px; height: <?= $height ?>px; background-color: <?= $kolor_tla ?>;">
                        <div class="event-title"><?= htmlspecialchars($termin['nazwa_przedmiotu']) ?></div>
                        <div class="event-details">
                            <?= htmlspecialchars($termin['forma_zajec']) ?> (<?= htmlspecialchars($termin['nazwa_grupy']) ?>)<br>
                            <?= htmlspecialchars($termin['prowadzacy']) ?><br>
                            <strong><?= htmlspecialchars($termin['budynek'] . ' ' . $termin['numer_sali']) ?></strong><br>
                            <?php if(!empty($termin['notatki'])): ?>
                                <em><?= htmlspecialchars($termin['notatki']) ?></em>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <p style="text-align:center; margin-top: 20px;">Wprowadź kryteria w polach powyżej i kliknij "Filtruj", aby wyświetlić plan zajęć.</p>
<?php endif; ?>