<?php
// Plik: views/oceny_czastkowe_zarzadzaj.php

if (!isset($_GET['zajecia_id'])) {
    echo "<h1>Błąd</h1><p>Nie wybrano zajęć. <a href='index.php?page=oceny_czastkowe_wybor'>Wróć do wyboru</a>.</p>";
    return;
}

$zajecia_id = (int)$_GET['zajecia_id'];

// Info do nagłówka
$info_sql = "SELECT p.nazwa_przedmiotu, z.forma_zajec, g.nazwa_grupy FROM Zajecia z JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id WHERE z.zajecia_id = ?";
$stmt_info = $conn->prepare($info_sql);
$stmt_info->bind_param("i", $zajecia_id);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$naglowek = htmlspecialchars($info['nazwa_przedmiotu'] . ' - ' . $info['forma_zajec'] . ' (' . $info['nazwa_grupy'] . ')');

// Pobranie studentów i ich zapisów
$studenci_sql = "SELECT s.numer_albumu, s.imie, s.nazwisko, zs.zapis_id FROM ZapisyStudentow zs JOIN Studenci s ON zs.numer_albumu = s.numer_albumu WHERE zs.zajecia_id = ? ORDER BY s.nazwisko, s.imie";
$stmt_studenci = $conn->prepare($studenci_sql);
$stmt_studenci->bind_param("i", $zajecia_id);
$stmt_studenci->execute();
$studenci = $stmt_studenci->get_result()->fetch_all(MYSQLI_ASSOC);

// Pobranie wszystkich ocen cząstkowych dla tych zajęć
$oceny_sql = "SELECT zapis_id, wartosc_oceny, opis, data_wystawienia FROM OcenyCzastkowe WHERE zapis_id IN (SELECT zapis_id FROM ZapisyStudentow WHERE zajecia_id = ?) ORDER BY data_wystawienia DESC";
$stmt_oceny = $conn->prepare($oceny_sql);
$stmt_oceny->bind_param("i", $zajecia_id);
$stmt_oceny->execute();
$oceny_result = $stmt_oceny->get_result()->fetch_all(MYSQLI_ASSOC);

// Grupujemy oceny po zapis_id (czyli po studencie)
$istniejace_oceny = [];
foreach ($oceny_result as $ocena) {
    $istniejace_oceny[$ocena['zapis_id']][] = $ocena;
}
?>

<h1>Zarządzanie Ocenami Cząstkowymi: <?= $naglowek ?></h1>

<hr>
<h2>Lista studentów i ich oceny</h2>
<table>
    <thead>
        <tr>
            <th>Student</th>
            <th>Oceny bieżące (opis)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($studenci) > 0): ?>
            <?php foreach ($studenci as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['imie'] . ' ' . $student['nazwisko']) ?></td>
                    <td>
                        <?php if (isset($istniejace_oceny[$student['zapis_id']])): ?>
                            <?php foreach ($istniejace_oceny[$student['zapis_id']] as $ocena): ?>
                                <span style="display: inline-block; background: #eee; padding: 2px 8px; border-radius: 4px; margin: 2px;">
                                    <strong><?= htmlspecialchars($ocena['wartosc_oceny']) ?></strong> (<?= htmlspecialchars($ocena['opis']) ?>)
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Brak ocen.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2">Brak studentów na tych zajęciach.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<hr>
<h2>Dodaj nową ocenę cząstkową</h2>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="dodaj_ocene_czastkowa">
    <input type="hidden" name="zajecia_id" value="<?= $zajecia_id ?>">
    
    <div class="form-group">
        <label for="zapis_id">Wybierz studenta</label>
        <select id="zapis_id" name="zapis_id" required>
            <option value="">-- Wybierz z listy --</option>
            <?php foreach ($studenci as $student): ?>
                <option value="<?= $student['zapis_id'] ?>">
                    <?= htmlspecialchars($student['nazwisko'] . ' ' . $student['imie']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="wartosc_oceny">Ocena</label>
        <input type="number" step="0.5" min="2" max="5" id="wartosc_oceny" name="wartosc_oceny" required>
    </div>
    
    <div class="form-group">
        <label for="opis">Opis (np. Kartkówka 1)</label>
        <input type="text" id="opis" name="opis" required>
    </div>
    
    <button type="submit">Dodaj Ocenę</button>
</form>