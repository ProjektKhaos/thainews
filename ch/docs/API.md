# Boknings-API v1

Alla vägar är relativa till Cosmos House-katalogen. Previewsession krävs även för API. Publik bokning är av som standard; när den är av ger `POST` 503 utan skrivning.

| Metod och väg | Indata | Utdata |
|---|---|---|
| `GET booking_api.php?action=status` | – | `bookingOpen` |
| `GET booking_api.php?action=availability&from=YYYY-MM-DD&to=YYYY-MM-DD` | högst 366 dagar | blockerade nattdatum och vistelsegränser, ingen PII |
| `POST booking_api.php?action=quote` | JSON `checkIn`, `checkOut`, `guests`, valfri `code` | `quoteToken`, UTC-expiry, natt-/rabatt-/avgiftsrader, `totalSatang` |
| `POST booking_api.php?action=inquire` | JSON `quoteToken`, `idempotencyKey` (32–64 hex), `name`, `email`, `language`, `privacy=true`, valfri `phone`, `message` | 201 med opak `reference` och `status=inquiry`; upprepat anrop ger samma referens |

POST kräver `Content-Type: application/json`, previewcookie och `X-CSRF-Token` från runtime-renderat formulär. `Origin` måste matcha värden om rubriken skickas. Svar är JSON. Förväntade fel: 403 CSRF/ursprung, 409 konflikt eller ändrat pris, 410 utgången offert, 413 för stor body, 422 validering, 429 för många försök, 503 stängt eller otillgänglig DB. API:t är internt för samma webbplats, inte ett tredjeparts-API.

Adminåtgärder använder HTML-formulär på `admin.php`, POST, adminsession och CSRF. De är inte publika JSON-rutter. `api.php` är ett permanent stängt kompatibilitetssvar för det äldre JSON-flödet.
