# Manual Test Plan

Run each test on your own XAMPP installation. **Do not mark a test as passed before you have executed it.**
Fill in *Actual Result* and *Status* (Pass / Fail) while testing, with the date and your name.

- Tester: __________________   Date: __________________   Browser: __________________

Prepare test files: a valid JPG, PNG and WEBP; a `.txt` file; a text file renamed `fake.png`; an image over 5 MB; seven small images.

| ID | Test | Preconditions | Steps | Expected result | Actual result | Status |
|----|------|---------------|-------|-----------------|---------------|--------|
| TC-01 | Visitor registration (new visitor) | MySQL running, DB imported | Open upload.php. Enter name 'Priya Sharma', choose 1 valid JPG, tick consent, submit. | Redirect to My Photos page; thank-you message; a visitor code `VP-XXXXXXXX` is shown; photo listed as Pending; one row in `visitors`, one in `photos`. | _not executed_ | Not run |
| TC-02 | Photo upload - multiple files | TC-01 done | Upload 3 valid photos (JPG, PNG, WEBP) in one submit using drag-and-drop. | Previews appear before submit; 3 photos saved as Pending; 3 files exist in `uploads/` with random names. | _not executed_ | Not run |
| TC-03 | Missing consent | - | Fill name and photo but leave consent unticked, submit. | Upload refused with a consent message; nothing saved. | _not executed_ | Not run |
| TC-04 | Missing name | - | Leave name and code empty, choose a photo, tick consent, submit. | Error asking for the full name; nothing saved. | _not executed_ | Not run |
| TC-05 | Invalid file upload (wrong type) | - | Try uploading `notes.txt`, `test.gif` and a `.php` file renamed to `x.jpg`. | Each file is rejected with a clear message; nothing saved; no file in `uploads/`. | _not executed_ | Not run |
| TC-06 | Fake image (script with image extension) | - | Rename a text file to `fake.png` and upload. | Rejected: content does not match image type. | _not executed_ | Not run |
| TC-07 | Oversized image | Image larger than 5 MB | Upload an image of about 6 MB. | Rejected with 'larger than the 5 MB limit' (client preview marks it red; server also refuses). | _not executed_ | Not run |
| TC-08 | Too many files | 7 valid images | Select 7 images and submit. | Message that at most 6 photos are allowed; nothing saved. | _not executed_ | Not run |
| TC-09 | Returning visitor - retrieve photos | Code from TC-01 | Home page -> enter the code in 'Returning visitor?' or open My Photos. | Name, code and ALL previous photos with their status are shown. | _not executed_ | Not run |
| TC-10 | Returning visitor - add photos | Code from TC-01 | Upload page -> enter code (name empty) -> upload 1 photo. | Photo is added to the SAME visitor (no new visitor row); My Photos now shows old and new photos. | _not executed_ | Not run |
| TC-11 | Invalid visitor code | - | Enter `VP-AAAAAAAA` (not existing) and `abc`. | 'Could not find that visitor code' message; no data leaked. | _not executed_ | Not run |
| TC-12 | Duplicate names | - | Register two visitors both named 'Priya Sharma' without codes. | Two separate visitor rows with different codes. | _not executed_ | Not run |
| TC-13 | Admin login - valid | DB imported | Open admin/login.php, login `admin` / `Admin@123`. | Dashboard opens with 4 statistic cards. | _not executed_ | Not run |
| TC-14 | Admin login - invalid | - | Enter a wrong password; repeat 5 times. | 'Invalid username or password' each time; after 5 failures a 60-second lock-out message. | _not executed_ | Not run |
| TC-15 | Admin pages protected | Logged out | Open admin/dashboard.php, visitors.php, photos.php directly. | Redirected to login page with a warning. | _not executed_ | Not run |
| TC-16 | Photo approval | Pending photos exist | Admin -> Photos -> Pending -> Approve one photo. | Status becomes Approved; pending count decreases, approved count increases. | _not executed_ | Not run |
| TC-17 | Photo rejection | Pending photo exists | Reject a pending photo. | Status becomes Rejected; photo does NOT appear in the slideshow. | _not executed_ | Not run |
| TC-18 | Slideshow shows only approved photos | TC-16, TC-17 done | Open slideshow.php. | Approved photo shown with visitor name; pending and rejected photos never appear. | _not executed_ | Not run |
| TC-19 | Slideshow behaviour | At least 2 approved photos | Watch for 15 s; use Prev, Next, Pause/Play, Full screen; press Left/Right/Space/F. | Each photo about 5 s with fade; last photo wraps to first; controls work; fullscreen toggles. | _not executed_ | Not run |
| TC-20 | Slideshow auto-update | Slideshow open | Approve another photo in admin without reloading the slideshow. | New photo joins the slideshow within about 15 seconds. | _not executed_ | Not run |
| TC-21 | Slideshow empty state | No approved photos | Reject/delete all approved photos and open slideshow. | 'No photos to show yet' message; no errors. | _not executed_ | Not run |
| TC-22 | Multiple simultaneous uploads | Two browsers / devices | At the same moment, upload photos from Browser A (name 'Amit') and Browser B (name 'Neha'). | Two visitors with different codes; each photo belongs to the correct visitor; no missing or mixed files. | _not executed_ | Not run |
| TC-23 | Photo deletion | Photo exists | Admin -> delete a photo (confirm). | Row removed from `photos`; file removed from `uploads/`; photo disappears from slideshow and My Photos. | _not executed_ | Not run |
| TC-24 | Visitor deletion | Visitor with photos | Admin -> Visitors -> Delete visitor (confirm). | Visitor, all their photos (rows) and files are removed. | _not executed_ | Not run |
| TC-25 | XSS escaping | - | Register name `<script>alert(1)</script>`. | Rejected by name validation. (If a name is inserted directly into the DB, it must display as text.) | _not executed_ | Not run |
| TC-26 | CSRF protection | Admin logged in | Submit an approve form after removing the `csrf_token` field (browser dev tools). | 'Security check failed' and no change. | _not executed_ | Not run |
| TC-27 | Script execution blocked in uploads | A file in uploads/ | Open `http://localhost/visitor-photo-slideshow/uploads/` and a stored image URL. | Directory listing is forbidden; image opens normally. | _not executed_ | Not run |
| TC-28 | Logout | Logged in | Click Logout, then press the browser Back button. | Login page with 'logged out' message; protected pages require login again. | _not executed_ | Not run |
| TC-29 | Responsive layout | - | Resize the browser to phone width on home, upload, admin dashboard and slideshow. | No horizontal scrolling; menus collapse; sidebar becomes a top bar; controls remain usable. | _not executed_ | Not run |
| TC-30 | Syntax check (PHP lint) | XAMPP installed | In a terminal run `C:\xampp\php\php.exe -l <file>` for every `.php` file. | 'No syntax errors detected' for every file. | _not executed_ | Not run |

## Defects found

| # | Test ID | Description | Fix | Re-tested |
|---|---------|-------------|-----|-----------|
| | | | | |
