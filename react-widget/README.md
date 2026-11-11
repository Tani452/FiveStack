# ClubHub - Event Calendar React Widget (Phase 9)

An interactive, responsive monthly event calendar built with **React** and **Vite**, embedded directly into the PHP-driven ClubHub events schedule.

---

## Features

- **Live API Integration**: Automatically queries `api/events.php` for approved campus events.
- **Interactive Month View**: Navigate across months, jump to today, and view event indicators and counts per day.
- **Day Event Inspector**: Clicking on any date immediately lists that day's scheduled activities with timings, organizing club, venue details, and links to full event detail pages.
- **Category Filtering**: Quickly filter scheduled events by Technical, Cultural, Literary, and other student club domains.
- **Deterministic Asset Output**: Bundles compiled production assets into `assets/react/calendar-widget.js` and `assets/react/calendar-widget.css` for integration into `pages/events.php`.

---

## Directory Structure

```text
react-widget/
├── index.html            # Standalone Vite development preview container
├── package.json          # Vite + React 18 configuration and npm scripts
├── vite.config.js        # Output path & deterministic bundle naming config
├── README.md             # Build and execution guide
└── src/
    ├── main.jsx          # Entry point mounting onto #calendar-root
    ├── EventCalendar.jsx # Main interactive calendar and day inspector component
    └── calendar.css      # Component stylesheet styled to match ClubHub design tokens
```

---

## Exact Build Commands

From the project root directory or the `react-widget` folder, execute the following commands:

### 1. Install Dependencies
```bash
cd react-widget
npm install
```

### 2. Build for Production
```bash
npm run build
```
This compiles and bundles the widget into the parent assets folder:
- **JavaScript bundle**: `assets/react/calendar-widget.js`
- **CSS bundle**: `assets/react/calendar-widget.css`

### 3. Local Development (Optional)
To test and develop the widget independently with Hot Module Replacement (HMR):
```bash
npm run dev
```
Open the local URL displayed (typically `http://localhost:5173/`).

---

## PHP Host Integration

In `pages/events.php`, the compiled widget is embedded as follows:

1. Target mount element:
   ```html
   <div id="calendar-root"></div>
   ```

2. CSS stylesheet link (in `<head>` or before the widget):
   ```html
   <link rel="stylesheet" href="../assets/react/calendar-widget.css">
   ```

3. Script tag (with `defer`):
   ```html
   <script src="../assets/react/calendar-widget.js" defer></script>
   ```

The rest of `pages/events.php` remains standard server-rendered PHP.

