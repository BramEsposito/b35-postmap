# Post Map

Displays a GitHub-style contribution heatmap of published WordPress posts.

## Usage

Add the shortcode to any post, page, or widget:

```
[postmap]
```

### Parameters

| Parameter | Default | Description |
|---|---|---|
| `weeks` | `52` | Number of weeks to display |
| `post_types` | `post` | Comma-separated list of post types to include |

**Examples:**

```
[postmap weeks="26"]
[postmap post_types="post,photo"]
[postmap weeks="104" post_types="post,page"]
```

## How it works

- The grid always ends on the most recent past Saturday so every column is a full week (Sun–Sat).
- Each cell is colored by post activity relative to the busiest day in the displayed range — 5 levels from none to most active.
- Month labels appear below the grid. A month label is omitted if fewer than half of that month's days fall within the displayed range.
- On smaller screens, earlier weeks are hidden to keep the grid legible: last 26 weeks below 768px, last 16 weeks below 480px.
- Results are cached for 1 hour per unique combination of date range and post types.

## Theming

Cell colors are driven by CSS custom properties, so they can be overridden per-theme:

```css
.b35-postmap {
    --b35-postmap-color-0: #ebedf0; /* no activity */
    --b35-postmap-color-1: #9be9a8;
    --b35-postmap-color-2: #40c463;
    --b35-postmap-color-3: #30a14e;
    --b35-postmap-color-4: #216e39; /* most active */
}
```

Dark mode colors are included by default via `prefers-color-scheme: dark`.
