const vscode = require('vscode');

/**
 * Debounce function to prevent excessive saves during rapid typing
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * @param {vscode.ExtensionContext} context
 */
function activate(context) {
    console.log('Windsurf AutoSave extension is now active');

    // Create status bar item
    const statusBarItem = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Right, 100);
    statusBarItem.command = 'windsurf-autosave.toggle';
    context.subscriptions.push(statusBarItem);

    // Update status bar based on configuration
    function updateStatusBar() {
        const config = vscode.workspace.getConfiguration('windsurfAutosave');
        const enabled = config.get('enabled');
        
        if (enabled) {
            statusBarItem.text = '$(save) AutoSave: ON';
            statusBarItem.tooltip = 'Windsurf AutoSave is enabled. Click to disable.';
            statusBarItem.show();
        } else {
            statusBarItem.text = '$(circle-slash) AutoSave: OFF';
            statusBarItem.tooltip = 'Windsurf AutoSave is disabled. Click to enable.';
            statusBarItem.show();
        }
    }

    // Initialize status bar
    updateStatusBar();

    // Toggle command
    const toggleCommand = vscode.commands.registerCommand('windsurf-autosave.toggle', () => {
        const config = vscode.workspace.getConfiguration('windsurfAutosave');
        const enabled = config.get('enabled');
        
        // Toggle the enabled state
        config.update('enabled', !enabled, vscode.ConfigurationTarget.Global)
            .then(() => {
                updateStatusBar();
                vscode.window.showInformationMessage(`Windsurf AutoSave: ${!enabled ? 'Enabled' : 'Disabled'}`);
            });
    });
    context.subscriptions.push(toggleCommand);

    // Get configuration
    const config = vscode.workspace.getConfiguration('windsurfAutosave');
    const debounceDelay = config.get('debounceDelay');
    
    // Create debounced save function
    const debouncedSave = debounce((document) => {
        if (document.isDirty) {
            document.save()
                .then((success) => {
                    if (success && config.get('showNotifications')) {
                        vscode.window.setStatusBarMessage(`$(save) File saved: ${document.fileName.split('/').pop()}`, 2000);
                    }
                }, (error) => {
                    vscode.window.showErrorMessage(`Error saving file: ${error.message}`);
                });
        }
    }, debounceDelay);
    
    // Listen to document changes
    const changeDocumentSubscription = vscode.workspace.onDidChangeTextDocument(event => {
        const config = vscode.workspace.getConfiguration('windsurfAutosave');
        if (config.get('enabled') && event.contentChanges.length > 0) {
            debouncedSave(event.document);
        }
    });
    context.subscriptions.push(changeDocumentSubscription);
    
    // Listen to configuration changes
    context.subscriptions.push(vscode.workspace.onDidChangeConfiguration(e => {
        if (e.affectsConfiguration('windsurfAutosave')) {
            updateStatusBar();
        }
    }));
}

function deactivate() {
    console.log('Windsurf AutoSave extension is now deactivated');
}

module.exports = {
    activate,
    deactivate
};