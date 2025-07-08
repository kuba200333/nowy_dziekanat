<?php
// Plik: views/zajecia_obsada_form.php
$zajecia_bez_obsady_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    WHERE z.prowadzacy_id IS NULL
    ORDER BY p.nazwa_przedmiotu
";
$zajecia_bez_obsady = $conn->query($zajecia_bez_obsady_sql)->fetch_all(MYSQLI_ASSOC);
$prowadzacy_lista = $conn->query("SELECT prowadzacy_id, imie, nazwisko, tytul_naukowy FROM Prowadzacy ORDER BY nazwisko")->fetch_all(MYSQLI_ASSOC);
?>
<h1>Zarządzanie Obsadą Zajęć</h1>
<p>Poniżej znajduje się lista zajęć, które nie mają jeszcze przypisanego prowadzącego. Wybierz zajęcia i przypisz do nich odpowiednią osobę.</p>

<?php if(empty($zajecia_bez_obsady)): ?>
    <p class="status-message success">Wszystkie zajęcia mają przypisanych prowadzących.</p>
<?php else: ?>
    <form action="handler.php" method="POST">
        <input type="hidden" name="action" value="przypisz_prowadzacego">
        <div class="form-group">
            <label for="zajecia_id">Wybierz zajęcia do obsadzenia:</label>
            <select name="zajecia_id" id="zajecia_id" required>
                <option value="">-- Wybierz z listy --</option>
                <?php foreach ($zajecia_bez_obsady as $zajecia): ?>
                    <option value="<?= $zajecia['zajecia_id'] ?>">
                        <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (' . $zajecia['nazwa_grupy'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="prowadzacy_id">Wybierz prowadzącego:</label>
            <select name="prowadzacy_id" id="prowadzacy_id" required>
                <option value="">-- Wybierz prowadzącego --</option>
                <?php foreach ($prowadzacy_lista as $prowadzacy): ?>
                    <option value="<?= $prowadzacy['prowadzacy_id'] ?>"><?= htmlspecialchars($prowadzacy['tytul_naukowy'] . ' ' . $prowadzacy['imie'] . ' ' . $prowadzacy['nazwisko']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Przypisz Prowadzącego</button>
    </form>
<?php endif; ?>