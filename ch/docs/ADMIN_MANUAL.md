# Cosmos House staff guide

Administration: **https://cosmoshouse.online/admin.php**. The first administrator chooses their own username and password using the private one-time setup link supplied to the owner. The link expires after 48 hours and is disabled once an administrator has been created.

## First setup

1. Create your administrator account. Use a unique password of at least 16 characters.
2. Open **House settings**. Enter the actual guest capacity, included guests, minimum/maximum nights, contact details, check-in/out times and policies. No guest capacity or production price is assumed.
3. Open **Prices & discounts**. Save the reviewed nightly base price in **THB**, then add seasonal rates, long-stay discounts and fees as needed. Use the price calculator to review the result.
4. Open **Staff accounts** and give each operator an individual account. Staff manage bookings, calendar, prices, content and photos. Administrators additionally manage house settings, accounts, SMTP, launch approvals and backups.
5. Open **Website content** to edit the English landing page and protected English site pages. Save a draft, open its preview and publish when ready.
6. Configure **Email** with your provider's SMTP hostname, username, password, sender address and staff notification address. STARTTLS and direct TLS are supported. Until real email is explicitly enabled, notifications are simulated. The SMTP password is never shown in forms; leave the password field blank to keep the saved value.

The temporary public landing page remains available while staff prepare the full site. Public guest bookings are closed. A separate release is required to open them.

## Daily bookings

- **Overview** shows enquiries needing review, reservations, upcoming check-ins/check-outs and failed emails.
- **Bookings** lets you search by reference, guest or email and filter by status and stay dates. Open a reference for contact details, notes, payment status, price snapshot, history and email events. CSV exports include all bookings and are available to signed-in staff.
- **Create booking** registers stays received by phone or email. Choose enquiry, reservation or confirmed. Enter an agreed total and its reason, or leave the total blank to calculate it from the current price rules. A confirmed stay or active reservation blocks dates immediately; an enquiry does not.
- A reservation expires after the selected number of hours, up to seven days. The maintenance worker runs every minute. Dates are released even if a worker has not yet changed an expired reservation's status.
- Use the booking detail to confirm, decline or cancel. The server checks conflicts again before accepting a reservation or confirmation. It never forces a double booking.
- You can edit guest contact details, add internal notes, change stay dates or an agreed total, and record payment manually. No payment is collected by the website. Rescheduling recalculates the current price unless a new agreed total and reason are supplied.
- Closed bookings can be anonymised. This permanently removes personal details and marks associated email events as simulated.

## Calendar and prices

**Calendar** provides month and week views, links to booking details and date blocks for maintenance or owner stays. The end date of a block is the first available day. One guest may check in on another guest's check-out date.

All amounts in forms use **THB**; enter decimals with a dot, for example `2800.50`. Percentage discounts use ordinary percentages, for example `10` for 10%. The highest priority rate wins when several date rules match. Price changes apply to new quotes immediately; existing bookings keep their agreed price snapshots. Seasonal rates, discount conditions and fees can be edited or paused. Combining discounts is off by default.

## Content and photos

**Website content** has separate editors for the landing page and each English full site page. Draft changes remain private. Publishing updates the page immediately. You can edit search metadata, select the landing page's eight photos and captions, choose the main/overlapping photo, and set the main photo's corner radius. English is the only available language on the landing page and English preview navigation.

**Photos** accepts JPEG, PNG and WebP images up to 8 MB and 24 megapixels. Uploaded images are converted to WebP. Unpublished uploads are visible only to signed-in staff. Existing house photos can also be selected. Full gallery settings let you publish a caption, category, order and visibility for each photo. Uploads are added to the full gallery only when you explicitly publish their gallery settings.

## Email, accounts and recovery

**Email** displays pending, sent, failed and simulated events. Queue a test email after configuring and approving real delivery. The worker processes the queue every minute. Failed delivery does not undo a saved booking; retry failed events after correcting the cause. Simulated messages and internal `TEST-` bookings are never sent later. Real delivery through your mail provider must be tested after you enter its credentials.

**Staff accounts** lets administrators create, deactivate and change roles or reset other accounts' passwords. Changed roles, deactivation and password changes revoke older sessions. At least one active administrator is required. **My account** changes your own password.

**Backups** lets administrators create, verify and download a backup of the private database and uploaded images. Backups run nightly at 03:15 Bangkok time, with a small scheduling delay. Retention is configurable between 7 and 365 days (default 30). Downloads contain guest information and account password hashes; store them securely. Server recovery is documented in `BACKEND_DEPLOY.md` and closes public booking and real email until reviewed again.

**Activity** records who changed bookings, rules, content, accounts and settings. **Launch checklist** records the owner's review; it cannot bypass the server launch gate or silently open public bookings.
