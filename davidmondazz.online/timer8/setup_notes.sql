-- Create users table if it doesn't exist (this should already exist in your system)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create items table if it doesn't exist (this should already exist in your system)
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notes table
CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY user_item_unique (user_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX idx_notes_user_id ON notes(user_id);
CREATE INDEX idx_notes_item_id ON notes(item_id);
CREATE INDEX idx_notes_created_at ON notes(created_at);

-- Insert some sample data (optional, uncomment if needed)
/*
INSERT INTO users (username, password, email) VALUES
('testuser', '$2y$10$abcdefghijklmnopqrstuv', 'test@example.com');

INSERT INTO items (name, description, price, image_url) VALUES
('Test Item 1', 'Description 1', 10.99, 'https://example.com/image1.jpg'),
('Test Item 2', 'Description 2', 20.99, 'https://example.com/image2.jpg');

INSERT INTO notes (user_id, item_id) VALUES
(1, 1),
(1, 2);
*/

-- Create or replace view for easy note retrieval
CREATE OR REPLACE VIEW view_notes AS
SELECT 
    n.id,
    n.user_id,
    n.item_id,
    i.name AS item_name,
    i.description AS item_description,
    i.price AS item_price,
    i.image_url,
    n.created_at
FROM notes n
JOIN items i ON n.item_id = i.id;

-- Grant necessary permissions (adjust according to your needs)
GRANT SELECT, INSERT, UPDATE, DELETE ON notes TO 'mcgkxyz_masterpop'@'localhost';
GRANT SELECT ON view_notes TO 'mcgkxyz_masterpop'@'localhost'; 