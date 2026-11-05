# Assumptions

| Topic | Assumption / decision | Reason |
|-------|----------------------|--------|
| **Returning visitor identification** | No login for visitors. Each new visitor gets a random **visitor code** (`VP-` + 8 characters). Entering the code identifies them again. | Simple for the assignment, works on shared kiosks, no passwords to manage. |
| **Duplicate names** | Names are not unique. Two people called "Priya Sharma" get two different codes and separate records. A name alone never links to an existing visitor. | Requirement: do not treat the same name as the same person. |
| **Unique visitor codes** | 8 characters from 32 easy-to-read symbols (no I, O, 0, 1) = about 1.1 trillion combinations, generated with `random_int()`. A `UNIQUE` index plus retry guarantees no duplicates. | Hard to guess, easy to type. |
| **Code is a secret** | Anyone who knows a code can view that visitor's photos on "My Photos" and add photos to it. Treated like a library card, not a password. | Acceptable risk for a low-security gallery. Visitors are told to keep it safe. |
| **Name for returning visitors** | When a code is used, the stored name is kept and the typed name is ignored. | Prevents accidental renaming. |
| **Photo display duration** | 5 seconds per photo, 1 second fade, endless loop. Slideshow checks for changes every 15 seconds. | Matches the brief. |
| **Consent and privacy** | Photos are uploaded as `pending`. A consent tick-box is mandatory and the time is stored (`photos.consent_at`). Only `approved` photos are public. Admin can reject or delete any photo. Only the visitor's name is shown with a photo - no email or phone is collected. | Public display needs explicit consent. |
| **Pending photo URLs** | Files in `uploads/` have random 32-character names, so a pending photo is not guessable, but a person who knows the exact URL could open it. | Acceptable for a local/college deployment; noted as a limitation. |
| **File size limit** | 5 MB per photo; maximum 6 photos per submission (keeps a request under XAMPP's 40 MB limit). Visitors can submit again for more. | Matches brief and server defaults. |
| **Supported formats** | JPG, JPEG, PNG, WEBP. Checked by extension, real MIME type (`finfo`) and `getimagesize()`. Script-like names (e.g. `x.php.jpg`) are refused. Images are not re-encoded or resized. | Security; keeps code simple. |
| **Photo approval** | Every new photo starts as `pending`. Admin can approve, reject (kept in database, not shown) or delete (database row and file removed). A rejected photo can still be approved later. | Moderation before public display. |
| **Simultaneous uploads** | Each upload is a separate request with its own database transaction. Visitor codes and file names are random and unique (DB unique indexes), so concurrent uploads cannot overwrite or mix each other's records. If anything fails the transaction is rolled back and the saved files are removed. | InnoDB transactions + random names. |
| **Retention and deletion** | Photos stay until an admin deletes them. Deleting a visitor deletes all their photos (rows via `ON DELETE CASCADE`, files by PHP). There is no automatic expiry. | Simple, predictable. |
| **Admin accounts** | One default admin is created by the SQL script. There is no admin registration page. | Prevents public sign-up to the admin area. |
| **Hosting** | Single local XAMPP server, one admin at a time, small number of visitors. | College project scale. |
| **Internet** | Bootstrap and Bootstrap Icons come from a CDN. | Keeps the project small. |
