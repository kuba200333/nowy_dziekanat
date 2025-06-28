<?php
// Plik: views/oceny_czastkowe_wybor.php (Wersja z filtrowaniem dla nauczycieli)

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];

// Podstawowa część zapytania SQL
$zajecia_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy,
           CONCAT(pr.imie, ' ', pr.nazwisko) AS prowadzacy
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
";

// Jeśli zalogowany użytkownik NIE JEST adminem, dodajemy warunek filtrujący
if (!$is_admin) {
    $zajecia_sql .= " WHERE z.prowadzacy_id = ?";
}

$zajecia_sql .= " ORDER BY p.nazwa_przedmiotu, g.nazwa_grupy, z.forma_zajec";

$stmt = $conn->prepare($zajecia_sql);

// Jeśli użytkownik NIE JEST adminem, bindowanie parametru jest potrzebne
if (!$is_admin) {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$zajecia_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<h1>Wystawianie Ocen Cząstkowych (Bieżących)</h1>
<p>
    <?php if($is_admin): ?>
        Jesteś administratorem. Widzisz wszystkie zajęcia w systemie.
    <?php else: ?>
        Widzisz tylko zajęcia, które prowadzisz.
    <?php endif; ?>
</p>
<p>Wybierz zajęcia, aby dodać oceny za np. kartkówki, aktywność, zadania domowe.</p>

<form action="index.php" method="GET">
    <input type="hidden" name="page" value="oceny_czastkowe_zarzadzaj">
    
    <div class="form-group">
        <label for="zajecia_id">Wybierz zajęcia (Przedmiot - Forma - Grupa)</label>
        <select id="zajecia_id" name="zajecia_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($zajecia_lista as $zajecia): ?>
                <option value="<?= $zajecia['zajecia_id'] ?>">
                    <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (' . $zajecia['nazwa_grupy'] . ')' . ' - Prow. ' . $zajecia['prowadzacy']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit">Zarządzaj ocenami</button>
</form>