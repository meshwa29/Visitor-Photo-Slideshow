# Architecture

## 1. Technology selection

| Choice | Why |
|--------|-----|
| **Core PHP 8.1+** | Required by the assignment; runs directly on XAMPP; no framework to learn or install. |
| **MySQL + PDO** | Relational data (visitors -> photos) with foreign keys; PDO prepared statements stop SQL injection. |
| **Bootstrap 5 + custom CSS** | Fast responsive layout; `style.css` adds the project's own design system. |
| **Plain JavaScript (ES5)** | No build step; runs on older smart-TV browsers. |
| **Session + bcrypt (`password_hash`)** | Standard, safe PHP admin authentication. |
| **File storage in `uploads/`, path in DB** | Simple and fast; database stays small. |

## 2. System architecture

```mermaid
flowchart LR
    subgraph Browser
        V[Visitor pages<br/>index / upload / my_photos]
        S[Slideshow page<br/>slideshow.php + slideshow.js]
        A[Admin pages<br/>login / dashboard / visitors / photos]
    end
    subgraph Apache_PHP[Apache + PHP 8.1 - XAMPP]
        F[includes/functions.php<br/>validation, CSRF, sessions]
        D[slideshow_data.php<br/>JSON of approved photos]
        H[admin/approve_photo.php<br/>admin/delete_visitor.php]
    end
    DB[(MySQL<br/>visitor_photo_slideshow)]
    FS[(uploads/ folder)]

    V --> F
    A --> F
    A --> H
    S -- polls every 15 s --> D
    F --> DB
    H --> DB
    D --> DB
    F --> FS
    H --> FS
    S -- loads images --> FS
```

## 3. Database design

Tables: `visitors`, `photos`, `admins`. Foreign key `photos.visitor_id -> visitors.id` with `ON DELETE CASCADE`.
Indexes: unique `visitors.visitor_code`, unique `photos.photo_name`, `(status, uploaded_at)` for the slideshow query, `photos.visitor_id`, unique `admins.username`.
`photos.consent_at` (an addition to the required columns) records when consent was given.

```mermaid
erDiagram
    VISITORS ||--o{ PHOTOS : uploads
    VISITORS {
        int id PK
        varchar visitor_code UK
        varchar visitor_name
        datetime created_at
    }
    PHOTOS {
        int id PK
        int visitor_id FK
        varchar photo_name UK
        varchar photo_path
        enum status "pending|approved|rejected"
        datetime consent_at
        datetime uploaded_at
    }
    ADMINS {
        int id PK
        varchar username UK
        varchar password_hash
        datetime created_at
    }
```

## 4. Visitor upload workflow

```mermaid
sequenceDiagram
    actor Visitor
    participant Page as upload.php
    participant DB as MySQL
    participant Disk as uploads/
    Visitor->>Page: Name (or visitor code), photos, consent
    Page->>Page: Check CSRF, name/code, consent, each file<br/>(size, extension, finfo MIME, getimagesize)
    alt any check fails
        Page-->>Visitor: Show all error messages, nothing saved
    else all checks pass
        Page->>DB: BEGIN
        Page->>DB: Create visitor + code (new) or load visitor (code)
        loop each photo
            Page->>Disk: move_uploaded_file (random name)
            Page->>DB: INSERT photo (status = pending)
        end
        Page->>DB: COMMIT
        Page-->>Visitor: Redirect to my_photos.php (shows visitor code)
    end
```

## 5. Approval and slideshow workflow

```mermaid
flowchart TD
    U[Photo uploaded] --> P[status = pending]
    P --> AD{Admin decision}
    AD -- Approve --> AP[status = approved]
    AD -- Reject --> RJ[status = rejected]
    AD -- Delete --> DL[Row and file removed]
    AP --> FEED[slideshow_data.php returns approved photos]
    FEED --> JS[slideshow.js shows photo 5 s, fades, loops]
    JS -- every 15 s --> FEED
```

```mermaid
sequenceDiagram
    participant TV as slideshow.php (browser)
    participant API as slideshow_data.php
    participant DB as MySQL
    loop every 15 seconds
        TV->>API: GET (no cache)
        API->>DB: SELECT approved photos
        DB-->>API: rows
        API-->>TV: JSON list
        TV->>TV: If the list changed, update it and keep the current photo
    end
    loop every 5 seconds
        TV->>TV: Fade to next photo (wraps to first)
    end
```

## 6. Security layers
CSRF tokens on all POST forms - PDO prepared statements - output escaping with `e()` - bcrypt password hashes - session ID regenerated at login - login lock-out after 5 failures - extension + MIME + image checks - random file names - `uploads/.htaccess` disables script execution - `config/`, `includes/`, `sql/`, `tests/` blocked from browsers - admin pages call `require_admin()` - redirects restricted to a whitelist.
