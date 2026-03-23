# Bellas Kitchen Theme

A barebones WordPress theme designed as a baseline for the Bella's Kitchen website.

## Theme Structure

```
BellasKitchenTheme/
├── style.css          # Theme stylesheet and metadata
├── functions.php      # Theme functions and setup
├── index.php          # Main template file (fallback for all pages)
├── header.php         # Header template
├── footer.php         # Footer template
├── single.php         # Single post template
├── page.php           # Page template
├── archive.php        # Archive template
├── front-page.php     # Front/home page template
├── js/
│   └── main.js        # Theme JavaScript
└── README.md          # This file
```

## Features

- Clean, semantic HTML5 markup
- Basic CSS styling with flexbox layout
- Theme setup with:
  - Title tag support
  - Featured images
  - Navigation menus
  - Widget support
- Responsive meta viewport tag
- Proper WordPress hooks and filters

## Getting Started

1. **Activate the theme** in the WordPress admin:
   - Navigate to Appearance > Themes
   - Find "Bellas Kitchen Theme"
   - Click "Activate"

2. **Customize the theme**:
   - Edit `style.css` to add custom styles
   - Modify templates (header.php, footer.php, etc.) for layout changes
   - Add hooks and functions in `functions.php`

3. **Set up navigation**:
   - Create a menu in Appearance > Menus
   - Assign it to the Primary Menu location

## File Descriptions

- **style.css**: Contains theme metadata and basic CSS styling
- **functions.php**: Handles theme setup, enqueues scripts/styles, registers menus and widgets
- **header.php**: HTML document structure and header markup
- **footer.php**: Footer markup and closing HTML tags
- **index.php**: Default template for posts/pages (fallback)
- **single.php**: Template for single post display
- **page.php**: Template for single page display
- **archive.php**: Template for archives (categories, tags, date-based)
- **front-page.php**: Template for the front/home page with latest posts
- **js/main.js**: Barebones JavaScript file for theme functionality

## Extending the Theme

You can extend this theme by:
- Adding more template files (404.php, search.php, etc.)
- Creating additional CSS files and enqueuing them
- Adding custom post types and taxonomies
- Creating custom page templates

## Requirements

- WordPress 5.0+
- PHP 7.2+
