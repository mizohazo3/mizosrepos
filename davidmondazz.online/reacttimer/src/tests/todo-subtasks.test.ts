import puppeteer, { Browser, Page } from 'puppeteer';

// Increase Jest timeout
jest.setTimeout(30000);

describe('Todo Subtasks', () => {
  let browser: Browser;
  let page: Page;

  beforeAll(async () => {
    browser = await puppeteer.launch({
      headless: false,
      defaultViewport: { width: 1280, height: 720 }
    });
    page = await browser.newPage();
  });

  beforeEach(async () => {
    await page.goto('http://localhost:3006/todo');
    // Wait for the page to load
    await page.waitForSelector('[data-testid="todo-list"]', { timeout: 10000 });
  });

  afterAll(async () => {
    if (browser) {
      await browser.close();
    }
  });

  test('subtasks are properly nested under main task', async () => {
    // Create main task
    await page.waitForSelector('button:has-text("Create First Task")', { timeout: 5000 });
    await page.click('button:has-text("Create First Task")');
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
    
    expect(subtaskDepth).toBe('1');

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

    expect(emptySubtasks).toBe(0);

    // Verify subtasks count badge
    const subtaskCount = await page.evaluate(() => {
      const badge = document.querySelector('text="2 subtasks"');
      return badge ? true : false;
    });

    expect(subtaskCount).toBe(true);
  });

  test('prevents creation of multiple empty subtasks', async () => {
    // Create main task
    await page.waitForSelector('button:has-text("Create First Task")', { timeout: 5000 });
    await page.click('button:has-text("Create First Task")');
    await page.waitForSelector('input[placeholder="Enter task title"]');
    await page.type('input[placeholder="Enter task title"]', 'Test Task');
    await page.click('button:has-text("Save")');

    // Try rapidly clicking add subtask button multiple times
    const addSubtaskButton = await page.waitForSelector('button:has-text("Add subtask")', { timeout: 5000 });
    if (addSubtaskButton) {
      for (let i = 0; i < 5; i++) {
        await addSubtaskButton.click();
      }
    }

    // Wait a moment for any potential empty tasks to be created
    await new Promise(resolve => setTimeout(resolve, 1000));

    // Count the number of form dialogs that appeared
    const formCount = await page.evaluate(() => {
      return document.querySelectorAll('form[data-testid="todo-form"]').length;
    });

    // Should only have one form open despite multiple clicks
    expect(formCount).toBe(1);
  });
});