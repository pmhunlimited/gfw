
CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    tagline VARCHAR(255),
    logo VARCHAR(255),
    admin_email VARCHAR(255),
    whatsapp_number VARCHAR(50),
    selected_model VARCHAR(50) DEFAULT 'gemini-3-flash-preview',
    gemini_api_key VARCHAR(255),
    deepseek_api_key VARCHAR(255),
    smtp_host VARCHAR(255),
    smtp_port VARCHAR(10),
    smtp_user VARCHAR(255),
    smtp_pass VARCHAR(255),
    smtp_sender_email VARCHAR(255),
    smtp_sender_name VARCHAR(255),
    social_twitter_token VARCHAR(255),
    social_facebook_token VARCHAR(255),
    social_instagram_token VARCHAR(255),
    social_enabled BOOLEAN DEFAULT FALSE,
    fb_url VARCHAR(255),
    tw_url VARCHAR(255),
    ig_url VARCHAR(255),
    yt_url VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    reset_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    category VARCHAR(100),
    author VARCHAR(100),
    image VARCHAR(255),
    video_url VARCHAR(255),
    is_top_story BOOLEAN DEFAULT FALSE,
    is_scheduled BOOLEAN DEFAULT FALSE,
    publish_date DATETIME,
    tags TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    author VARCHAR(100) NOT NULL,
    text TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL
);
