<?php
// Plik: views/wybory_admin_wyniki.php (Wersja z dopisywaniem do istniejących zajęć)

// Pobieramy listę wszystkich "wydarzeń wyboru" do filtra
$wszystkie_wybory = $conn->query("SELECT * FROM WyboryPrzedmiotow ORDER BY data_zakonczenia DESC")->fetch_all(MYSQLI_ASSOC);
$wybrany_wybor_id = isset($_GET['wybor_id']) ? (int)$_GET['wybor_id'] : 0;
$wyniki = [];

if ($wybrany_wybor_id > 0) {
    // Pobieramy wyniki dla wybranego wydarzenia
    $wyniki_sql = "
        SELECT 
            o.opcja_id, o.przedmiot_id, p.nazwa_przedmiotu,
            s.numer_albumu, s.imie, s.nazwisko
        FROM OdpowiedziStudentow os
        JOIN OpcjeWyboru o ON os.wybrana_opcja_id = o.opcja_id
        JOIN Przedmioty p ON o.przedmiot_id = p.przedmiot_id
        JOIN Studenci s ON os.numer_albumu = s.numer_albumu
        WHERE os.wybor_id = ?
        ORDER BY p.nazwa_przedmiotu, s.nazwisko
    ";
    $stmt = $conn->prepare($wyniki_sql);
    $stmt->bind_param("i", $wybrany_wybor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Grupujemy wyniki po wybranym przedmiocie
    foreach ($result as $row) {
        $wyniki[$row['opcja_id']]['details'] = [
            'przedmiot_id' => $row['przedmiot_id'],
            'nazwa_przedmiotu' => $row['nazwa_przedmiotu']
        ];
        $wyniki[$row['opcja_id']]['studenci'][] = $row;
    }
}

// Pobieramy listę wszystkich utworzonych zajęć do listy rozwijanej
$zajecia_lista_sql = "
    SELECT z.zajecia_id, p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy 
    FROM Zajecia z
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    ORDER BY p.nazwa_przedmiotu, g.nazwa_grupy
";
$zajecia_lista = $conn->query($zajecia_lista_sql)->fetch_all(MYSQLI_ASSOC);
?>

<h1>Podgląd Wyników Wyboru Przedmiotów</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="wybory_admin_wyniki">
    <div class="form-group">
        <label for="wybor_id">Wybierz wydarzenie do analizy:</label>
        <select name="wybor_id" id="wybor_id" onchange="this.form.submit()">
            <option value="0">-- Wybierz z listy --</option>
            <?php foreach ($wszystkie_wybory as $wybor): ?>
                <option value="<?= $wybor['wybor_id'] ?>" <?= ($wybrany_wybor_id == $wybor['wybor_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($wybor['nazwa_wyboru']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($wybrany_wybor_id > 0 && empty($wyniki)): ?>
    <p>Brak odpowiedzi studentów dla tego wydarzenia.</p>
<?php endif; ?>

<?php foreach ($wyniki as $opcja_id => $dane): ?>
    <div style="margin-top: 30px; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
        <h2>Przedmiot: <?= htmlspecialchars($dane['details']['nazwa_przedmiotu']) ?></h2>
        <p><strong>Liczba chętnych studentów:</strong> <?= count($dane['studenci']) ?></p>

        <form action="handler.php" method="POST">
            <input type="hidden" name="action" value="dopisz_studentow_do_zajec">
            
            <h4>Lista studentów:</h4>
            <p>Odznacz studentów, których nie chcesz dodawać do wybranej poniżej grupy zajęciowej.</p>
            <table>
                <thead><tr><th>Zaznacz</th><th>Numer albumu</th><th>Imię i nazwisko</th></tr></thead>
                <tbody>
                    <?php foreach ($dane['studenci'] as $student): ?>
                        <tr>
                            <td><input type="checkbox" name="studenci_ids[]" value="<?= $student['numer_albumu'] ?>" checked></td>
                            <td><?= $student['numer_albumu'] ?></td>
                            <td><?= htmlspecialchars($student['imie'] . ' ' . $student['nazwisko']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="form-group" style="margin-top: 20px;">
                <label for="docelowe_zajecia_id_<?= $opcja_id ?>">Wybierz docelowe zajęcia (grupę), do której dopisać studentów:</label>
                <select name="docelowe_zajecia_id" id="docelowe_zajecia_id_<?= $opcja_id ?>" required>
                    <option value="">-- Wybierz istniejące zajęcia --</option>
                    <?php foreach ($zajecia_lista as $zajecia): ?>
                        <option value="<?= $zajecia['zajecia_id'] ?>">
                            <?= htmlspecialchars($zajecia['nazwa_przedmiotu'] . ' - ' . $zajecia['forma_zajec'] . ' (' . $zajecia['nazwa_grupy'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn-add">Dopisz zaznaczonych studentów do zajęć</button>
        </form>
    </div>
<?php endforeach; ?>