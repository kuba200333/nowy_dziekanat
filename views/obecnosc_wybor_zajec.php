<?php
// Plik: views/obecnosc_wybor_zajec.php

$prowadzacy_id = $_SESSION['user_id'];

// Zapytanie, które pobiera listę nadchodzących zajęć dla zalogowanego prowadzącego
$terminy_sql = "
    SELECT 
        t.termin_id,
        t.data_zajec,
        t.godzina_rozpoczecia,
        p.nazwa_przedmiotu,
        z.forma_zajec,
        g.nazwa_grupy
    FROM TerminyZajec t
    JOIN Zajecia z ON t.zajecia_id = z.zajecia_id
    JOIN KonfiguracjaPrzedmiotu k ON z.konfiguracja_id = k.konfiguracja_id
    JOIN Przedmioty p ON k.przedmiot_id = p.przedmiot_id
    JOIN GrupyZajeciowe g ON z.grupa_id = g.grupa_id
    WHERE t.prowadzacy_id = ? 
    ORDER BY t.data_zajec ASC, t.godzina_rozpoczecia ASC
";
$stmt = $conn->prepare($terminy_sql);
$stmt->bind_param("i", $prowadzacy_id);
$stmt->execute();
$terminy_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Sprawdzanie Obecności - Wybierz Zajęcia</h1>
<p>Poniżej znajduje się lista Twoich nadchodzących zajęć. Wybierz termin, dla którego chcesz sprawdzić lub zaktualizować listę obecności.</p>

<table>
    <thead>
        <tr>
            <th>Data i Godzina</th>
            <th>Przedmiot</th>
            <th>Forma</th>
            <th>Grupa</th>
            <th>Akcja</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($terminy_lista)): ?>
            <tr><td colspan="5">Brak zaplanowanych zajęć w przyszłości.</td></tr>
        <?php else: ?>
            <?php foreach ($terminy_lista as $termin): ?>
                <tr>
                    <td><?= htmlspecialchars($termin['data_zajec'] . ' ' . substr($termin['godzina_rozpoczecia'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($termin['nazwa_przedmiotu']) ?></td>
                    <td><?= htmlspecialchars($termin['forma_zajec']) ?></td>
                    <td><?= htmlspecialchars($termin['nazwa_grupy']) ?></td>
                    <td>
                        <a href="index.php?page=obecnosc_form&termin_id=<?= $termin['termin_id'] ?>" class="btn-add">Sprawdź Obecność</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>