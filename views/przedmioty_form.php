<?php
// Plik: views/przedmioty_form.php
?>
<h1>Dodaj Nowy Przedmiot</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_przedmiot">
    <div class="form-group">
        <label for="nazwa_przedmiotu">Nazwa Przedmiotu</label>
        <input type="text" id="nazwa_przedmiotu" name="nazwa_przedmiotu" required>
    </div>
    <button type="submit">Dodaj Przedmiot</button>
</form>