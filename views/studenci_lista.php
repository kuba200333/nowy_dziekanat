<?php
// Plik: views/studenci_lista.php
$result = $conn->query("SELECT numer_albumu, imie, nazwisko, email, rok_rozpoczecia_studiow, status_studenta FROM Studenci ORDER BY nazwisko, imie");
?>
<h1>Lista Studentów</h1>
<a href="index.php?page=studenci_form" class="btn-add">Dodaj nowego studenta</a>
<table>
    <thead>
        <tr>
            <th>Nr Albumu</th>
            <th>Imię i Nazwisko</th>
            <th>Email</th>
            <th>Rok Rozpoczęcia</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['numer_albumu']) ?></td>
                    <td><?= htmlspecialchars($row['imie'] . ' ' . $row['nazwisko']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['rok_rozpoczecia_studiow']) ?></td>
                    <td><?= htmlspecialchars($row['status_studenta']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Brak studentów w bazie danych.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>