<?php
// Plik: views/student_profil_przebieg.php (Wersja poprawiona)

// Ten plik jest dołączany w student_profil.php, więc ma dostęp do zmiennych $conn i $numer_albumu.

// POPRAWIONE ZAPYTANIE: Używamy LEFT JOIN, aby pokazać wpisy nawet jeśli rok akademicki nie jest jeszcze przypisany.
$przebieg_sql = "
    SELECT ps.*, r.nazwa_roku 
    FROM PrzebiegStudiow ps 
    LEFT JOIN RokiAkademickie r ON ps.rok_akademicki_id = r.rok_akademicki_id
    WHERE ps.numer_albumu = ? ORDER BY ps.semestr ASC
";
$stmt_przebieg = $conn->prepare($przebieg_sql);
$stmt_przebieg->bind_param("i", $numer_albumu);
$stmt_przebieg->execute();
$przebieg = $stmt_przebieg->get_result()->fetch_all(MYSQLI_ASSOC);

$ostatni_wpis = end($przebieg);
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h3>Przebieg Studiów</h3>
<table>
    <thead>
        <tr>
            <th>Semestr</th>
            <th>Rok Akademicki</th>
            <th>Status</th>
            <th>Akcje</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($przebieg)): ?>
            <tr><td colspan="4">Brak wpisów w przebiegu studiów. Dodaj pierwszy wpis poniżej.</td></tr>
        <?php else: ?>
            <?php foreach($przebieg as $wpis): ?>
            <tr>
                <td><?= $wpis['semestr'] ?></td>
                <td><?= htmlspecialchars($wpis['nazwa_roku'] ?? 'Brak') ?></td>
                <td><strong><?= htmlspecialchars($wpis['status_semestru']) ?></strong></td>
                <td>
                    <?php if ($wpis === $ostatni_wpis && $wpis['status_semestru'] != 'zarejestrowany'): ?>
                        <form action="handler.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="rejestruj_na_kolejny_semestr">
                            <input type="hidden" name="numer_albumu" value="<?= $numer_albumu ?>">
                            <input type="hidden" name="aktualny_semestr" value="<?= $wpis['semestr'] ?>">
                            <button type="submit" class="btn-add">Zarejestruj na semestr <?= $wpis['semestr'] + 1 ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<hr>
<h3>Dodaj nowy wpis do przebiegu studiów (ręcznie)</h3>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="dodaj_wpis_przebiegu">
    <input type="hidden" name="numer_albumu" value="<?= $numer_albumu ?>">
    <div class="form-group">
        <label for="semestr">Numer semestru:</label>
        <input type="number" name="semestr" id="semestr" required>
    </div>
    <div class="form-group">
        <label for="rok_akademicki_id">Rok akademicki:</label>
        <select name="rok_akademicki_id" required>
            <?php foreach ($roki as $rok): ?>
                <option value="<?= $rok['rok_akademicki_id'] ?>"><?= htmlspecialchars($rok['nazwa_roku']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="status_semestru">Status semestru:</label>
        <select name="status_semestru" required>
            <option value="zarejestrowany">Zarejestrowany</option>
            <option value="zaliczony">Zaliczony</option>
            <option value="niezaliczony">Niezaliczony</option>
            <option value="urlop">Urlop</option>
        </select>
    </div>
    <button type="submit">Dodaj Wpis</button>
</form>