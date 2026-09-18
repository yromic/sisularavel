# 11. UI/UX BEHAVIOR CONTRACT

## 1. Scope, Operational Basis & Core Directives

### 1.1 Authoritative Operational Justification (DECISION UI-001)
In accordance with user approval, the SISURAT user interface must be constructed following a strict **Mobile-First Approach**.

> **Authoritative Operational Basis:** Most letter-entry and document-registration activities by operational staff are performed via mobile smartphones during active administrative duties, field coordination, and front-desk reception.

Mobile-first is not merely a styling option; it is a foundational architecture requirement. Narrow-screen mobile usability governs form structure, touch targets, state management, and navigation hierarchy.

### 1.2 Non-Negotiable Mobile-First Principles
1. **Constraint-Driven Layouts:** Layout design must originate from narrow-screen constraints (down to 360px viewport width). Desktop layouts enhance the workflow with dense information displays, but must never replace the underlying mobile workflow.
2. **Zero Desktop Dependence for Core Operations:** All primary data-entry workflows (incoming letters, outgoing letters, public piagam claims, document uploads, searching, filtering, and basic editing) must be 100% executable on a phone without requiring desktop browser mode.
3. **Touch-First Primary Interaction:** Interactive controls must have an effective touch target size of at least **44 × 44 CSS pixels**. Critical actions must never depend on mouse hover (`:hover`) states.
4. **No Fragmented Business Logic:** Mobile and desktop layouts share identical validation rules, permission checks, API endpoints, and data lifecycle handlers.

### 1.3 Functional & Restrained Visual Design (DECISION UI-002)
The visual presentation must be clean, restrained, highly legible, and institutional:
- **Prohibited Aesthetics:** Excessive color gradients, glassmorphism without functional justification, decorative idle animations, oversized hero containers, excessive nested cards, exaggerated corner radiuses (> 16px), and low-contrast minimalist text.
- **Mandatory Attributes:** Clear visual hierarchy, standard high-contrast text ratios (minimum 4.5:1), fast scanning for operational forms, unambiguous primary/secondary/destructive button affordances, and zero layout shift during asynchronous loading.

### 1.4 Consistent Application Icon Library (DECISION UI-003)
- **Dedicated Web Icon System:** A single, consistent SVG-based icon library (e.g., Lucide Icons or Heroicons) must be used across all components.
- **Prohibited Glyphs:** Windows-specific symbols, platform font ligatures, emoji used as functional icons, and generic text characters (such as `×`, `→`, `▲`, `✓`) used in place of standardized accessible SVG icons.
- **Accessibility:** All icon-only interactive controls (e.g. close buttons, search triggers, action menus) must have explicit `aria-label` attributes and visible text labels wherever visual meaning is not universally unambiguous.

### 1.5 Dual-Tier Experience Contract (DECISION UI-004)
- **Mobile Tier:** Optimized for linear data entry, camera/file attachment selection, instant search, fast status filtering, record summaries, and touch confirmations.
- **Desktop Tier:** Enhances the same data model with dense multi-column tabular grids, side-by-side record comparisons, full administrative matrices, and export reporting views.

---

## 2. Breakpoint Architecture & Viewport Standards

The responsive contract does not lock implementation to vendor-specific framework breakpoints, but enforces verified behavior across five representative viewport widths:

| Viewport Category | Representative Width | Primary Layout Characteristic |
| :--- | :--- | :--- |
| **Narrow Mobile** | **360 px** | Single-column stacked forms, sticky bottom actions, collapsible filter drawer, hybrid summary cards. |
| **Standard Mobile**| **390 px** | Single-column forms, full-width touch buttons, bottom navigation / drawer navigation. |
| **Tablet** | **768 px** | Two-column grid forms for short fields, collapsible sidebar, horizontal scrollable data tables. |
| **Standard Desktop**| **1024 px - 1280 px** | Multi-column layouts, persistent sidebar navigation, dense multi-column data tables with direct row actions. |
| **Wide Desktop** | **>= 1440 px** | Centered content container (max-width 1440px), split-screen detail views, comprehensive analytics matrices. |

---

## 3. Screen-by-Screen Specifications & Mobile Adaptations

### 3.1 Login Screen (`/login`)
- **Mobile Viewport (360px - 390px):**
  - Full-viewport centered card without decorative background illustrations.
  - Form inputs: `username` (`autocapitalize="none"`, `autocorrect="off"`), `password` with a native touch-friendly toggle button (eye icon) to show/hide text.
  - Submit button: Full-width touch target (height: 48px).
  - Rate limiting banner: Displayed prominently at top of screen upon HTTP 429 lockout.
- **Desktop Viewport (1024px+):**
  - Institutional branded card with official district logo and security advisory notice.

### 3.2 Letters Management (`/surat-masuk` & `/surat-keluar`)
- **Mobile Viewport (360px - 390px):**
  - **Header:** Sticky top bar displaying active division code and a prominent `+ Tambah Surat` floating action button (FAB) or top-right touch button.
  - **Search & Filter:** Live search bar with tap-to-open bottom sheet for date range and status filters. Active filters are visible as dismissible filter chips.
  - **Record Representation:** Hybrid **Card Summary View**. Each record renders as a compact card showing:
    1. Nomor Surat (bold, primary text)
    2. Asal Surat / Tujuan
    3. Tanggal Surat & Tanggal Diterima
    4. Attachment Indicator: Dedicated touch button ("Lihat PDF", min 44px) opening preview in viewer.
    5. Action Overflow Menu: Three-dot touch icon (`...`) opening a bottom action sheet (Detail, Edit, Hapus) eliminating any dependency on desktop hover.
  - **Pagination:** Mobile-friendly "Sebelumnya / Selanjutnya" touch controls with current page indicator (`Halaman 1 dari 12`).
- **Desktop Viewport (1024px+):**
  - Full dense tabular grid (Columns: No. Agenda, Nomor Surat, Tanggal Surat, Tanggal Diterima, Asal Surat, Perihal, Lampiran, Aksi).
  - Inline action buttons with accessible SVG icons.

### 3.3 Mobile Letter Entry & Editing Forms (`/surat-masuk/create`, `/surat-keluar/create`)
- **Layout:** Strictly single-column linear form layout on viewports < 768px.
- **Field Hierarchy & Controls:**
  1. *Nomor Surat:* Text input with clear required asterisk (`*`).
  2. *Tanggal Surat:* Input `type="date"` utilizing the device's native date picker.
  3. *Tanggal Diterima / Tanggal Share:* Input `type="date"`.
  4. *Asal Surat:* Searchable typeahead dropdown or clear text input.
  5. *Perihal:* Multiline text area (minimum 3 rows) with auto-expand capability.
  6. *Lampiran (File Upload):*
     - File input supporting camera capture or local file selection (`accept="application/pdf,image/*,.doc,.docx"`).
     - File selection preview displays original filename, file size in KB/MB, and a red "Hapus" button.
     - Mobile upload progress indicator during submission.
- **Form Submission & Feedback:**
  - Sticky bottom action bar on mobile ensuring "Batal" and "Simpan Surat" buttons remain visible regardless of scroll position.
  - Submit button immediately enters loading state on tap: displays animated spinner, disables itself to prevent duplicate submissions, and changes label to *"Menyimpan..."*.
  - Inline error rendering: Validation errors appear directly beneath offending fields with red outline and high-contrast error text.

### 3.4 Piagam Management & Public Claim Screen (`/piagam` & `/piagam/ambil`)
- **Public Claim Viewport (Mobile-First):**
  - Step 1: School identification via 8-digit NPSN input or searchable school name typeahead.
  - Step 2: List of eligible certificates matching school. User taps certificate card to select.
  - Step 3: Recipient details form (`nama_pengambil`, `jabatan`, `unit_kerja`).
  - Step 4: **HTML5 Digital Signature Canvas:**
    - Full-width touch canvas pad (minimum height 180px) calibrated for touch stylus or finger signing.
    - Prominent "Bersihkan Tanda Tangan" button.
    - Blank signature detection blocks submission.
  - Step 5: Submission produces a printable, mobile-friendly digital receipt with verification QR code.

---

## 4. State Handling & Interaction Guidelines

1. **Form State Preservation:** If validation fails (HTTP 422), all user-entered form inputs must be preserved in state so the user never retypes entered text.
2. **Duplicate Submission Shield:** Form submit buttons must disable immediately upon click and reject duplicate concurrent taps.
3. **Empty State:** When no records match search/filter criteria, display a clean, centered state illustration, a descriptive label (*"Tidak ada surat ditemukan"*), and a *"Reset Filter"* button.
4. **Destructive Action Safety:** Deletion requires a modal dialog with explicit confirmation prompt: *"Apakah Anda yakin ingin menghapus surat nomor [X]?"* with distinct, high-contrast *"Batal"* and *"Ya, Hapus"* buttons.
