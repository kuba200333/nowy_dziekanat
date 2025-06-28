<?php
// Plik: views/oceny_wybor_krok1.php (Wersja z filtrowaniem dla nauczycieli)

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

// Podstawowa część zapytania
$zajecia_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy,
           g.semestr, r.nazwa_roku,
           CONCAT(pr.imie, ' ', pr.nazwisko) AS prowadzacy
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    JOIN RokiAkademickie r ON g.rok_akademicki_id = r.rok_akademicki_id
    JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
";

// Jeśli użytkownik NIE JEST adminem, dodaj warunek filtrujący
if (!$is_admin) {
    $zajecia_sql .= " WHERE z.prowadzacy_id = ?";
}

$zajecia_sql .= " ORDER BY p.nazwa_przedmiotu, r.nazwa_roku, g.semestr, g.nazwa_grupy, z.forma_zajec";

$stmt = $conn->prepare($zajecia_sql);
if (!$is_admin) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$zajecia_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<h1>Wystawianie Ocen Końcowych z Komponentu (Krok 1/2)</h1>
<p>
    <?php if($is_admin): ?>
        Jesteś administratorem. Widzisz wszystkie zajęcia w systemie.
    <?php else: ?>
        Widzisz tylko zajęcia, które prowadzisz.
    <?php endif; ?>
</p>

<form action="index.php" method="GET">
    <input type="hidden" name="page" value="oceny_wprowadz_form">
    
    <div class="form-group">
        <label for="zajecia_id">Wybierz zajęcia</label>
        <select id="zajecia_id" name="zajecia_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($zajecia_lista as $zajecia): ?>
                <option value="<?= $zajecia['zajecia_id'] ?>">
                    <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (Rok: ' . $zajecia['nazwa_roku'] . ' Semestr: ' . $zajecia['semestr'] . ' ' . $zajecia['nazwa_grupy'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Wybierz studentów do oceny</button>
</form>