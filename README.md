# ziad-api-plugin
WordPress API Plugin 
# Ziad API Plugin

A WordPress plugin that retrieves data from an external API and displays it in a customizable table format.

## Features

- Custom Gutenberg block for displaying API data
- Toggle column visibility controls
- Admin dashboard page with refresh button
- Smart caching (1 hour) to minimize API calls
- AJAX endpoint for data access
- WP CLI command for data refresh

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Usage

### Add Block to Page
- Edit any page
- Add "API Data Table" block
- Configure column visibility in sidebar settings

### Admin Page
- Navigate to **Ziad API** in WordPress admin
- View data and click "Refresh Data" button

### CLI Command
```bash
wp ziad-api refresh
```

## File Structure
```
ziad-api-plugin/
├── src/
│   ├── Plugin.php
│   ├── APIClient.php
│   ├── AJAXHandler.php
│   ├── AdminPage.php
│   ├── CLI/
│   │   └── RefreshDataCommand.php
│   └── Blocks/
│       ├── BlockRegistrar.php
│       └── assets/
│           ├── block.js
│           ├── frontend.js
│           └── block.css
├── assets/
│   ├── admin.js
│   └── admin.css
├── composer.json
├── ziad-api-plugin.php
└── README.md
```

## Technologies

- PHP OOP with PSR-4 autoloading
- WordPress Gutenberg (Block Editor)
- AJAX & Transients API
- Composer
