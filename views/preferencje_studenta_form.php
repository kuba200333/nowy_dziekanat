<?php
// Plik: views/preferencje_studenta_form.php

// Krok 1: Wybór studenta
$studenci = $conn->query("SELECT numer_albumu, imie, nazwisko FROM Studenci ORDER BY nazwisko, imie")->fetch_all(MYSQLI_ASSOC);
$wybrany_student_id = isset($_GET['numer_albumu']) ? (int)$_GET['numer_albumu'] : 0;
?>

<h1>Zarządzanie Preferencjami Studentów</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="preferencje_studenta_form">
    <div class="form-group">
        <label for="numer_albumu">Krok 1: Wybierz studenta, którego preferencje chcesz edytować:</label>
        <select name="numer_albumu" id="numer_albumu" onchange="this.form.submit()" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($studenci as $student): ?>
                <option value="<?= $student['numer_albumu'] ?>" <?= ($wybrany_student_id == $student['numer_albumu']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie'] . ' (' . $student['numer_albumu'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php
// Krok 2: Wyświetlanie formularza i listy preferencji, jeśli student został wybrany
if ($wybrany_student_id > 0):
    // Pobierz istniejące preferencje
    $preferencje_sql = "
        SELECT s.imie, s.nazwisko, s.numer_albumu, ps.typ_preferencji 
        FROM PreferencjeStudentow ps
        JOIN Studenci s ON ps.student_docelowy_id = s.numer_albumu
        WHERE ps.student_glowny_id = ?
    ";
    $stmt = $conn->prepare($preferencje_sql);
    $stmt->bind_param("i", $wybrany_student_id);
    $stmt->execute();
    $istniejace_preferencje = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
    <hr>
    <h3>Krok 2: Ustaw preferencje dla wybranego studenta</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h4>Aktualne preferencje:</h4>
            <ul>
                <?php foreach($istniejace_preferencje as $pref): ?>
                    <li>
                        <?= htmlspecialchars($pref['imie'] . ' ' . $pref['nazwisko']) ?> - 
                        <strong style="color: <?= $pref['typ_preferencji'] == 'razem' ? 'green' : 'red' ?>;">
                            <?= $pref['typ_preferencji'] ?>
                        </strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h4>Dodaj nową preferencję:</h4>
            <form action="handler.php" method="POST">
                <input type="hidden" name="action" value="dodaj_preferencje_studenta">
                <input type="hidden" name="student_glowny_id" value="<?= $wybrany_student_id ?>">
                <div class="form-group">
                    <label for="student_docelowy_id">Wybierz studenta:</label>
                    <select name="student_docelowy_id" required>
                        <?php foreach ($studenci as $student): if ($student['numer_albumu'] == $wybrany_student_id) continue; ?>
                            <option value="<?= $student['numer_albumu'] ?>"><?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="typ_preferencji">Typ preferencji:</label>
                    <select name="typ_preferencji" required>
                        <option value="razem">Chce być razem w grupie</option>
                        <option value="osobno">NIE chce być razem w grupie</option>
                    </select>
                </div>
                <button type="submit">Dodaj Preferencję</button>
            </form>
        </div>
    </div>
<?php endif; ?>