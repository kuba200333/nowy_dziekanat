<?php
// Plik: views/roki_form.php
?>
<h1>Dodaj Nowy Rok Akademicki</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_rok">
    <div class="form-group">
        <label for="nazwa_roku">Nazwa Roku (format RRRR/RRRR)</label>
        <input type="text" id="nazwa_roku" name="nazwa_roku" required placeholder="np. 2024/2025">
    </div>
    <button type="submit">Dodaj Rok</button>
</form>