<?php
// Plik: views/zajecia_lista.php (Wersja z ukrytą tabelą przed filtrowaniem)

// Pobieramy dane do filtrów
$roki = $conn->query("SELECT * FROM RokiAkademickie ORDER BY nazwa_roku DESC")->fetch_all(MYSQLI_ASSOC);

// Pobieramy wybrane wartości filtrów z adresu URL
$wybrany_rok_id = isset($_GET['rok_id']) ? (int)$_GET['rok_id'] : 0;
$wybrany_semestr = isset($_GET['semestr']) ? (int)$_GET['semestr'] : 0;

// ZMIANA: Sprawdzamy, czy formularz został wysłany
$czy_filtrowac = isset($_GET['filtruj']);
$result = null;

// Wykonujemy zapytanie do bazy tylko jeśli kliknięto przycisk "Filtruj"
if ($czy_filtrowac) {
    $sql = "
        SELECT 
            z.zajecia_id, 
            p.nazwa_przedmiotu, 
            z.forma_zajec, 
            g.nazwa_grupy,
            IFNULL(CONCAT(pr.tytul_naukowy, ' ', pr.imie, ' ', pr.nazwisko), 'Brak przypisania') AS prowadzacy, 
            kk.waga_oceny,
            (SELECT COUNT(*) FROM ZapisyStudentow zs WHERE zs.zajecia_id = z.zajecia_id) as uzycie_count
        FROM Zajecia z
        JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
        JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
        LEFT JOIN Prowadzacy pr ON z.prowadzacy_id = pr.prowadzacy_id
        LEFT JOIN KonfiguracjaKomponentow kk ON z.konfiguracja_id = kk.konfiguracja_id AND z.forma_zajec = kk.forma_zajec
    ";

    $warunki_where = [];
    $parametry_bind = [];
    $typy_bind = "";

    if ($wybrany_rok_id > 0) {
        $warunki_where[] = "g.rok_akademicki_id = ?";
        $parametry_bind[] = $wybrany_rok_id;
        $typy_bind .= "i";
    }
    if ($wybrany_semestr > 0) {
        $warunki_where[] = "g.semestr = ?";
        $parametry_bind[] = $wybrany_semestr;
        $typy_bind .= "i";
    }

    if (!empty($warunki_where)) {
        $sql .= " WHERE " . implode(" AND ", $warunki_where);
    }

    $sql .= " ORDER BY p.nazwa_przedmiotu, z.forma_zajec";

    $stmt = $conn->prepare($sql);
    if (!empty($parametry_bind)) {
        $stmt->bind_param($typy_bind, ...$parametry_bind);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<h1>Utworzone Zajęcia</h1>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="page" value="zajecia_lista">
    <div style="display: flex; gap: 20px; align-items: end; margin-bottom: 20px;">
        <div class="form-group">
            <label for="rok_id">Rok akademicki:</label>
            <select name="rok_id" id="rok_id">
                <option value="0">-- Wszystkie --</option>
                <?php foreach ($roki as $rok): ?>
                    <option value="<?= $rok['rok_akademicki_id'] ?>" <?= ($wybrany_rok_id == $rok['rok_akademicki_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rok['nazwa_roku']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="semestr">Semestr:</label>
            <select name="semestr" id="semestr">
                <option value="0">-- Wszystkie --</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= ($wybrany_semestr == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <input type="hidden" name="filtruj" value="1">
        <div class="form-group">
            <button type="submit" class="btn-add">Filtruj</button>
        </div>
    </div>
</form>

<a href="index.php?page=zajecia_form" class="btn-add">Utwórz nowe zajęcia</a>

<?php if ($czy_filtrowac): // ZMIANA: Tabela wyświetla się tylko po filtrowaniu ?>
    <table>
        <thead>
            <tr>
                <th>Przedmiot</th>
                <th>Forma</th>
                <th>Grupa</th>
                <th>Prowadzący</th>
                <th>Waga Oceny</th>
                <th style="width: 180px;">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nazwa_przedmiotu']) ?></td>
                        <td><?= htmlspecialchars($row['forma_zajec']) ?></td>
                        <td><?= htmlspecialchars($row['nazwa_grupy']) ?></td>
                        <td><?= htmlspecialchars($row['prowadzacy']) ?></td>
                        <td><?= htmlspecialchars(number_format($row['waga_oceny'] ?? 0, 2)) ?></td>
                        <td style="display: flex; gap: 10px; align-items: center;">
                            <a href="index.php?page=zajecia_edit_form&zajecia_id=<?= $row['zajecia_id'] ?>" class="btn-add" style="background-color: #ffc107; padding: 5px 10px; margin: 0;">Edytuj</a>
                            
                            <form action="handler.php" method="POST" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz usunąć te zajęcia?');">
                                <input type="hidden" name="action" value="delete_zajecia">
                                <input type="hidden" name="zajecia_id" value="<?= $row['zajecia_id'] ?>">
                                <button type="submit" class="btn-add" style="background-color: #dc3545; padding: 5px 10px; margin: 0;" 
                                    <?php if ($row['uzycie_count'] > 0) echo 'disabled title="Nie można usunąć, studenci są zapisani na te zajęcia"'; ?>>
                                    Usuń
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Brak utworzonych zajęć spełniających podane kryteria.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center; margin-top: 20px;">Wybierz kryteria i kliknij "Filtruj", aby wyświetlić listę zajęć.</p>
<?php endif; ?>