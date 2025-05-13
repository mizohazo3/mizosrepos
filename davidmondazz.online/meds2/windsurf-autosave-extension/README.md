# Windsurf AutoSave Extension

This extension automatically saves your files after every edit in the Windsurf editor, eliminating the need to manually save files.

## Features

- **Auto-Save**: Automatically saves files after changes are made
- **Debounce**: Prevents excessive saving during rapid typing with configurable delay
- **Status Bar Indicator**: Shows the current state of auto-save (enabled/disabled)
- **Toggle Command**: Easily enable or disable auto-save through the status bar
- **Configurable**: Customize behavior through extension settings

## Installation

1. Download the extension files
2. Place them in your Windsurf extensions folder
3. Restart Windsurf editor
4. The extension will be automatically activated

## Configuration

This extension contributes the following settings:

- `windsurfAutosave.enabled`: Enable or disable automatic saving (default: `true`)
- `windsurfAutosave.debounceDelay`: Delay in milliseconds before saving after an edit (default: `300`)
- `windsurfAutosave.showNotifications`: Show notifications when files are saved (default: `false`)

## Usage

Once installed, the extension will automatically save your files after every edit. You can:

- See the current status in the status bar (`AutoSave: ON` or `AutoSave: OFF`)
- Click the status bar item to toggle auto-save on/off
- Configure settings through the Windsurf settings menu

## Building and Packaging

To build the extension:

1. Install Node.js and npm
2. Run `npm install` to install dependencies
3. Package the extension using VSIX format
4. Install in Windsurf editor

## License

MIT