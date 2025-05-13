const puppeteer = require('puppeteer');

async function runTests() {
  const browser = await puppeteer.launch({
    headless: false,
    defaultViewport: { width: 1280, height: 720 }
  });

  try {
    const page = await browser.newPage();
    await page.goto('http://localhost:3006/todo');
    
    console.log('Navigating to TODO page...');
    await page.waitForTimeout(2000); // Give page time to fully load

    // Wait for the page to load
    await page.waitForSelector('[data-testid="todo-list"], [data-testid="todo-list-container"]', { timeout: 15000 });
    console.log('Page loaded successfully');
    
    console.log('Testing: subtasks are properly nested under main task');
    
    // Create main task
    await page.waitForSelector('[data-testid="create-first-task-button"]', { timeout: 7000 }); // Use data-testid and slightly increased timeout
    await page.click('[data-testid="create-first-task-button"]');
    await page.waitForSelector('input[placeholder="Enter task title"]');
    await page.type('input[placeholder="Enter task title"]', 'Main Task');
    await page.click('button:has-text("Save")');
    
    // Wait for the task to be created and visible
    await page.waitForSelector('text="Main Task"', { timeout: 5000 });
    
    // Add subtask
    const addSubtaskButton = await page.waitForSelector('button:has-text("Add subtask")', { timeout: 5000 });
    await addSubtaskButton?.click();
    await page.waitForSelector('input[placeholder="Enter task title"]');
    await page.type('input[placeholder="Enter task title"]', 'Subtask 1');
    await page.click('button:has-text("Save")');

    // Verify subtask is properly nested
    const subtaskDepth = await page.evaluate(() => {
      const subtask = document.querySelector('text="Subtask 1"')?.closest('[data-depth]');
      return subtask?.getAttribute('data-depth');
    });
    
    if (subtaskDepth === '1') {
      console.log('✅ Subtask depth is correct');
    } else {
      console.log('❌ Subtask depth is incorrect');
    }

    // Try to add another subtask
    const addSubtaskButton2 = await page.waitForSelector('button:has-text("Add subtask")', { timeout: 5000 });
    await addSubtaskButton2?.click();
    await page.waitForSelector('input[placeholder="Enter task title"]');
    await page.type('input[placeholder="Enter task title"]', 'Subtask 2');
    await page.click('button:has-text("Save")');

    // Verify no empty subtasks were created
    const emptySubtasks = await page.evaluate(() => {
      return document.querySelectorAll('[data-testid="todo-item"]:empty').length;
    });

    if (emptySubtasks === 0) {
      console.log('✅ No empty subtasks found');
    } else {
      console.log('❌ Found empty subtasks');
    }

    // Verify subtasks count badge
    const subtaskCount = await page.evaluate(() => {
      const badge = document.querySelector('text="2 subtasks"');
      return badge ? true : false;
    });

    if (subtaskCount) {
      console.log('✅ Subtask count badge is correct');
    } else {
      console.log('❌ Subtask count badge is incorrect');
    }

    console.log('\nTesting: prevents creation of multiple empty subtasks');

    // Create new main task
    // NOTE: This will likely fail if tasks created earlier are not cleared,
    // as the "Create First Task" button only appears when the list is empty.
    await page.waitForSelector('[data-testid="create-first-task-button"]', { timeout: 5000 });
    await page.click('[data-testid="create-first-task-button"]');
    await page.waitForSelector('input[placeholder="Enter task title"]');
    await page.type('input[placeholder="Enter task title"]', 'Test Task');
    await page.click('button:has-text("Save")');

    // Try rapidly clicking add subtask button multiple times
    const addSubtaskButton3 = await page.waitForSelector('button:has-text("Add subtask")', { timeout: 5000 });
    if (addSubtaskButton3) {
      for (let i = 0; i < 5; i++) {
        await addSubtaskButton3.click();
      }
    }

    // Wait a moment for any potential empty tasks to be created
    await new Promise(resolve => setTimeout(resolve, 1000));

    // Count the number of form dialogs that appeared
    const formCount = await page.evaluate(() => {
      return document.querySelectorAll('form[data-testid="todo-form"]').length;
    });

    if (formCount === 1) {
      console.log('✅ Only one form dialog appeared');
    } else {
      console.log('❌ Multiple form dialogs appeared');
    }

  } catch (error) {
    console.error('Test failed:', error);
  } finally {
    await browser.close();
  }
}

runTests().catch(console.error);