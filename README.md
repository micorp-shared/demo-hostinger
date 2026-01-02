# Micode Portfolio

## Esthétique

Minimalisme suisse, codes du luxe. Retenue et élégance avant tout.

Touches synthwave subtiles : scanlines, orbes flottantes, particules, accents cyan/violet.

Typographie : Funnel Display (titres), IBM Plex Mono (corps).

Palette sombre (#080808), contrastes doux, espaces généreux.

Animations discrètes au scroll. Moins c'est plus.

## Base de données

```sql
CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(2000) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Initialisation : `php init-db.php`
