<?php
// Plik: views/student_zapisy_form.php (Wersja z poprawnym filtrowaniem semestru)

// Krok 1: Pobieramy dane do filtrów
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;
$wybrany_semestr = isset($_GET['semestr']) ? (int)$_GET['semestr'] : 0;
$studenci_w_semestrze = [];

// Pobieramy studentów tylko jeśli wybrano rok i semestr
if ($wybrany_rok_id > 0 && $wybrany_semestr > 0) {
    $studenci_sql = "
        SELECT s.numer_albumu, s.imie, s.nazwisko FROM Studenci s
        WHERE EXISTS (
            SELECT 1 FROM PrzebiegStudiow ps 
            WHERE ps.numer_albumu = s.numer_albumu 
              AND ps.rok_akademicki_id = ? 
              AND ps.semestr = ?
        )
        ORDER BY s.nazwisko, s.imie
    ";
    $stmt_studenci = $conn->prepare($studenci_sql);
    $stmt_studenci->bind_param("ii", $wybrany_rok_id, $wybrany_semestr);
    $stmt_studenci->execute();
    $studenci_w_semestrze = $stmt_studenci->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<h1>Indywidualne Zarządzanie Zapisami Studenta</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="student_zapisy_form">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Krok 1: Wybierz rok akademicki:</label>
            <select name="rok_id" id="rok_id" onchange="this.form.submit()">
                <option value="0">-- Wybierz rok --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="semestr">Krok 2: Wybierz semestr:</label>
            <select name="semestr" id="semestr" onchange="this.form.submit()">
                <option value="0">-- Wybierz semestr --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= ($wybrany_semestr == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <?php if ($wybrany_rok_id > 0 && $wybrany_semestr > 0): ?>
        <div class="form-group">
            <label for="numer_albumu">Krok 3: Wybierz studenta:</label>
            <select name="numer_albumu" id="numer_albumu" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($studenci_w_semestrze as $student): ?>
                    <option value="<?= $student['numer_albumu'] ?>" <?= (isset($_GET['numer_albumu']) && $_GET['numer_albumu'] == $student['numer_albumu']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn-add">Pokaż zapisy</button>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php
// Wyświetlanie listy zajęć, jeśli student został wybrany
if (isset($_GET['numer_albumu']) && !empty($_GET['numer_albumu'])):
    $numer_albumu = (int)$_GET['numer_albumu'];

    // 1. Znajdź wszystkie grupy, do których należy student w wybranym roku i semestrze
    $grupy_studenta_sql = "
        SELECT DISTINCT z.grupa_id 
        FROM ZapisyStudentow zs
        JOIN Zajecia z ON zs.zajecia_id = z.zajecia_id
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        WHERE zs.numer_albumu = ? AND g.rok_akademicki_id = ? AND g.semestr = ?
    ";
    $stmt_grupy = $conn->prepare($grupy_studenta_sql);
    $stmt_grupy->bind_param("iii", $numer_albumu, $wybrany_rok_id, $wybrany_semestr);
    $stmt_grupy->execute();
    $grupy_studenta_res = $stmt_grupy->get_result()->fetch_all(MYSQLI_ASSOC);
    $grupy_studenta_ids = array_column($grupy_studenta_res, 'grupa_id');

    // 2. Pobierz wszystkie zajęcia z tych grup
    if(!empty($grupy_studenta_ids)) {
        $placeholders = implode(',', array_fill(0, count($grupy_studenta_ids), '?'));
        $wszystkie_zajecia_sql = "
            SELECT 
                z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy,
                (SELECT COUNT(*) FROM ZapisyStudentow zs WHERE zs.zajecia_id = z.zajecia_id AND zs.numer_albumu = ?) as czy_zapisany
            FROM Zajecia z
            JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
            JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
            JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
            WHERE z.grupa_id IN ($placeholders)
            ORDER BY p.nazwa_przedmiotu
        ";
        $stmt_zajecia = $conn->prepare($wszystkie_zajecia_sql);
        $types = 'i' . str_repeat('i', count($grupy_studenta_ids));
        $params = array_merge([$numer_albumu], $grupy_studenta_ids);
        $stmt_zajecia->bind_param($types, ...$params);
        $stmt_zajecia->execute();
        $wszystkie_zajecia = $stmt_zajecia->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $wszystkie_zajecia = [];
    }
?>
    <hr>
    <h3>Zarządzaj zapisami dla: <?= htmlspecialchars($_GET['numer_albumu']) ?></h3>
    <p>Zaznacz zajęcia, na które student ma być zapisany. Odznaczenie spowoduje wypisanie.</p>
    <form action="handler.php" method="POST">
        <input type="hidden" name="action" value="zarzadzaj_zapisami_studenta">
        <input type="hidden" name="numer_albumu" value="<?= $numer_albumu ?>">
        <input type="hidden" name="rok_id" value="<?= $wybrany_rok_id ?>">
        <input type="hidden" name="semestr" value="<?= $wybrany_semestr ?>">
        
        <table>
            <thead><tr><th>Zapisz</th><th>Przedmiot</th><th>Forma</th><th>Grupa</th></tr></thead>
            <tbody>
                <?php if(empty($wszystkie_zajecia)): ?>
                    <tr><td colspan="4">Student nie należy do żadnej grupy w tym semestrze lub grupy te nie mają przypisanych zajęć.</td></tr>
                <?php else: ?>
                    <?php foreach($wszystkie_zajecia as $zajecia): ?>
                    <tr>
                        <td><input type="checkbox" name="zajecia_ids[]" value="<?= $zajecia['zajecia_id'] ?>" <?= $zajecia['czy_zapisany'] ? 'checked' : '' ?>></td>
                        <td><?= htmlspecialchars($zajecia['nazwa_przedmiotu']) ?></td>
                        <td><?= htmlspecialchars($zajecia['forma_zajec']) ?></td>
                        <td><?= htmlspecialchars($zajecia['nazwa_grupy']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <button type="submit">Zapisz zmiany w zapisach</button>
    </form>

<?php endif; ?>