<?php
// Plik: views/stypendia_student_form.php (Wersja interaktywna)

$numer_albumu = $_SESSION['user_id'];
$nabor_id = (int)$_GET['nabor_id'];

// Inicjalizacja zmiennych
$pokaz_wyniki = false;
$warunki_spelnione = false;
$srednia = 0;
$oceny_do_wyswietlenia = [];
$wybrane_semestry_str = '';

// Sprawdź, czy użytkownik wybrał semestry i kliknął przycisk
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['oblicz_srednia'])) {
    $pokaz_wyniki = true;
    $wybrane_semestry_str = $_POST['semestry_rok'];
    $semestry = explode(',', $wybrane_semestry_str); // np. "3-4" -> [3, 4]
    
    // Pobierz wszystkie oceny końcowe i ECTS dla wybranych semestrów
    $oceny_sql = "
        SELECT oc.wartosc_obliczona, kp.punkty_ects, p.nazwa_przedmiotu
        FROM OcenyCalkowiteZPrzedmiotu oc
        JOIN KonfiguracjaPrzedmiotu kp ON oc.konfiguracja_id = kp.konfiguracja_id
        JOIN Przedmioty p ON kp.przedmiot_id = p.przedmiot_id
        JOIN (
            SELECT DISTINCT konfiguracja_id, semestr FROM Zajecia z
            JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        ) as sem_info ON sem_info.konfiguracja_id = oc.konfiguracja_id
        WHERE oc.numer_albumu = ? AND sem_info.semestr IN (?, ?)
    ";
    $stmt = $conn->prepare($oceny_sql);
    $stmt->bind_param("iii", $numer_albumu, $semestry[0], $semestry[1]);
    $stmt->execute();
    $oceny_do_sredniej = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Sprawdź warunki i oblicz średnią
    if (!empty($oceny_do_sredniej)) {
        $suma_ocen_wazonych = 0;
        $suma_ects = 0;
        $warunki_spelnione = true; // Zakładamy, że tak, dopóki nie znajdziemy błędu

        foreach ($oceny_do_sredniej as $ocena) {
            $oceny_do_wyswietlenia[] = $ocena;
            if (is_numeric($ocena['wartosc_obliczona'])) {
                if ($ocena['wartosc_obliczona'] < 3.0) {
                    $warunki_spelnione = false; // Znaleziono ocenę negatywną
                }
                $suma_ocen_wazonych += (float)$ocena['wartosc_obliczona'] * (int)$ocena['punkty_ects'];
                $suma_ects += (int)$ocena['punkty_ects'];
            } elseif (strtolower($ocena['wartosc_obliczona']) != 'zal') {
                $warunki_spelnione = false; // Znaleziono "nzal"
            }
        }
        
        if ($warunki_spelnione && $suma_ects > 0) {
            $srednia = $suma_ocen_wazonych / $suma_ects;
        } elseif (!$warunki_spelnione) {
            $srednia = 0;
        }
    }
}

// Przygotuj listę semestrów do wyboru
$max_sem_res = $conn->query("SELECT MAX(g.semestr) as max_sem FROM ZapisyStudentow zs JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE zs.numer_albumu = $numer_albumu")->fetch_assoc();
$max_sem = $max_sem_res['max_sem'] ?? 0;
$lata_akademickie = [];
for ($i = 1; $i <= $max_sem; $i += 2) {
    $lata_akademickie[] = "$i-" . ($i + 1);
}
?>

<h1>Wniosek o Stypendium Rektora</h1>

<form action="index.php?page=stypendia_student_form&nabor_id=<?= $nabor_id ?>" method="POST">
    <div class="form-group">
        <label for="semestry_rok">Wybierz rok akademicki (semestry), za który chcesz złożyć wniosek:</label>
        <select name="semestry_rok" id="semestry_rok" required>
            <?php foreach ($lata_akademickie as $rok): ?>
                <option value="<?= $rok ?>" <?= ($wybrane_semestry_str == $rok) ? 'selected' : '' ?>>
                    Semestry <?= str_replace('-', ' i ', $rok) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" name="oblicz_srednia">Pokaż oceny i oblicz średnią</button>
</form>

<?php if ($pokaz_wyniki): // Wyświetlaj tylko po kliknięciu przycisku ?>
    <hr style="margin-top: 30px;">
    
    <?php if ($warunki_spelnione && !empty($oceny_do_wyswietlenia)): ?>
        <h3>Twoje oceny z wybranych semestrów:</h3>
        <table>
            <thead><tr><th>Przedmiot</th><th>Ocena Końcowa</th><th>ECTS</th></tr></thead>
            <tbody>
                <?php foreach($oceny_do_wyswietlenia as $ocena): ?>
                    <tr>
                        <td><?= htmlspecialchars($ocena['nazwa_przedmiotu']) ?></td>
                        <td><strong><?= htmlspecialchars($ocena['wartosc_obliczona']) ?></strong></td>
                        <td><?= htmlspecialchars($ocena['punkty_ects']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form action="handler.php" method="POST" style="margin-top: 20px; text-align: center; background: #e8f4fd; padding: 20px; border-radius: 8px;">
            <input type="hidden" name="action" value="zloz_wniosek_stypendialny">
            <input type="hidden" name="nabor_id" value="<?= $nabor_id ?>">
            <input type="hidden" name="semestry" value="<?= $wybrane_semestry_str ?>">
            <input type="hidden" name="obliczona_srednia" value="<?= number_format($srednia, 2) ?>">
            
            <p>Twój wniosek zostanie złożony na podstawie wyników w nauce z semestrów: <strong><?= str_replace(',', ' i ', $wybrane_semestry_str) ?></strong>.</p>
            <p>System obliczył Twoją średnią ważoną z tego okresu. Wynosi ona:</p>
            <h2 style="text-align:center; color: #0056b3;"><?= number_format($srednia, 2) ?></h2>
            <p>Ta wartość zostanie użyta w rankingu. Czy na pewno chcesz złożyć wniosek?</p>
            <button type="submit">Tak, Złóż Wniosek</button>
        </form>
        
    <?php else: ?>
        <p class="status-message error" style="margin-top: 20px;">
            Nie możesz złożyć wniosku dla wybranych semestrów, ponieważ nie masz jeszcze wszystkich pozytywnych i zatwierdzonych ocen końcowych z tego okresu.
        </p>
    <?php endif; ?>
<?php endif; ?>