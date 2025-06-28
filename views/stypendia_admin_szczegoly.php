<?php
// Plik: views/stypendia_admin_szczegoly.php

if (!isset($_GET['wniosek_id'])) {
    echo "<h1>Błąd</h1><p>Nie podano ID wniosku.</p>";
    return;
}
$wniosek_id = (int)$_GET['wniosek_id'];

// Pobierz wszystkie dane o wniosku, studencie i naborze
$sql_wniosek = "
    SELECT w.*, s.imie, s.nazwisko, n.nazwa_naboru
    FROM WnioskiStypendialne w
    JOIN Studenci s ON w.numer_albumu = s.numer_albumu
    JOIN NaborStypendialny n ON w.nabor_id = n.nabor_id
    WHERE w.wniosek_id = ?
";
$stmt = $conn->prepare($sql_wniosek);
$stmt->bind_param("i", $wniosek_id);
$stmt->execute();
$wniosek = $stmt->get_result()->fetch_assoc();

if (!$wniosek) {
     echo "<h1>Błąd</h1><p>Nie znaleziono wniosku o podanym ID.</p>";
    return;
}

// Pobierz oceny, które były podstawą obliczenia średniej
$semestry = explode(',', $wniosek['semestry']);
$oceny_sql = "
    SELECT p.nazwa_przedmiotu, oc.wartosc_obliczona, kp.punkty_ects
    FROM OcenyCalkowiteZPrzedmiotu oc
    JOIN KonfiguracjaPrzedmiotu kp ON oc.konfiguracja_id = kp.konfiguracja_id
    JOIN Przedmioty p ON kp.przedmiot_id = p.przedmiot_id
    JOIN (
        SELECT DISTINCT konfiguracja_id, semestr FROM Zajecia z
        JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    ) as sem_info ON sem_info.konfiguracja_id = oc.konfiguracja_id
    WHERE oc.numer_albumu = ? AND sem_info.semestr IN (?, ?)
";
$stmt_oceny = $conn->prepare($oceny_sql);
$stmt_oceny->bind_param("iii", $wniosek['numer_albumu'], $semestry[0], $semestry[1]);
$stmt_oceny->execute();
$oceny_do_wyswietlenia = $stmt_oceny->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Szczegóły Wniosku Stypendialnego</h1>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div>
        <h3>Dane Wniosku</h3>
        <p><strong>Student:</strong> <?= htmlspecialchars($wniosek['imie'] . ' ' . $wniosek['nazwisko']) ?> (<?= $wniosek['numer_albumu'] ?>)</p>
        <p><strong>Nabór:</strong> <?= htmlspecialchars($wniosek['nazwa_naboru']) ?></p>
        <p><strong>Data złożenia:</strong> <?= $wniosek['data_zlozenia'] ?></p>
        <p><strong>Średnia (punkty):</strong> <strong><?= htmlspecialchars(number_format($wniosek['obliczona_srednia'], 3)) ?></strong></p>
        <p><strong>Aktualny status:</strong> <?= htmlspecialchars($wniosek['status_wniosku']) ?></p>
        <?php if(!empty($wniosek['uwagi_pracownika'])): ?>
            <p><strong>Uwagi:</strong> <?= htmlspecialchars($wniosek['uwagi_pracownika']) ?></p>
        <?php endif; ?>

        <hr>
        <h3>Formularz Decyzji</h3>
        <form action="handler.php" method="POST">
            <input type="hidden" name="action" value="rozpatrz_wniosek">
            <input type="hidden" name="wniosek_id" value="<?= $wniosek_id ?>">

            <div class="form-group">
                <label for="status_wniosku">Zmień status na:</label>
                <select name="status_wniosku" id="status_wniosku">
                    <option value="zaakceptowany" <?= ($wniosek['status_wniosku'] == 'zaakceptowany') ? 'selected' : '' ?>>Zaakceptowany</option>
                    <option value="odrzucony" <?= ($wniosek['status_wniosku'] == 'odrzucony') ? 'selected' : '' ?>>Odrzucony</option>
                    <option value="do_poprawy" <?= ($wniosek['status_wniosku'] == 'do_poprawy') ? 'selected' : '' ?>>Do poprawy</option>
                </select>
            </div>
            <div class="form-group">
                <label for="uwagi_pracownika">Uwagi dla studenta (opcjonalne):</label>
                <textarea name="uwagi_pracownika" id="uwagi_pracownika" rows="3"><?= htmlspecialchars($wniosek['uwagi_pracownika']) ?></textarea>
            </div>
            <button type="submit">Zapisz Decyzję</button>
        </form>
    </div>
    <div>
        <h3>Oceny brane pod uwagę przy obliczaniu średniej</h3>
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
    </div>
</div>
<br>
<a href="index.php?page=stypendia_admin_wnioski&nabor_id=<?= $wniosek['nabor_id'] ?>">Wróć do listy wniosków</a>