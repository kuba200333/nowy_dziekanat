<h1>Dodaj Nowy Wydział</h1>
<form action="handler.php" method="POST">
    <input type="hidden" name="action" value="add_wydzial">
    <div class="form-group"><label for="nazwa_wydzialu">Pełna nazwa wydziału:</label><input type="text" name="nazwa_wydzialu" id="nazwa_wydzialu" required></div>
    <div class="form-group"><label for="skrot_wydzialu">Skrót (np. WI):</label><input type="text" name="skrot_wydzialu" id="skrot_wydzialu"></div>
    <button type="submit">Dodaj Wydział</button>
</form>