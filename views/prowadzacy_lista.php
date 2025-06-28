<?php
// Plik: views/prowadzacy_lista.php
$result = $conn->query("SELECT prowadzacy_id, imie, nazwisko, tytul_naukowy, email FROM Prowadzacy ORDER BY nazwisko, imie");
?>
<h1>Lista Prowadzących</h1>
<a href="index.php?page=prowadzacy_form" class="btn-add">Dodaj nowego prowadzącego</a>
<table>
    <thead>
        <tr>
            <th>Tytuł, Imię i Nazwisko</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['tytul_naukowy'] . ' ' . $row['imie'] . ' ' . $row['nazwisko']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2">Brak prowadzących w bazie danych.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>