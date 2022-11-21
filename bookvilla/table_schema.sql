CREATE DATABASE bookvilla;

CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    u_name VARCHAR(50) NOT NULL,
    u_email TEXT NOT NULL UNIQUE,
    u_password TEXT NOT NULL,
    u_role_type VARCHAR(50) NOT NULL DEFAULT 'normal_user',
    uuid TEXT NOT NULL,
    confirm_token TEXT NOT NULL,
    email_confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expire_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    signin_id TIMESTAMP NULL
);

CREATE TABLE user_password_reset (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid TEXT NOT NULL,
    reset_token TEXT NOT NULL,
    expire_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL
);

CREATE TABLE books(
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    author_name VARCHAR(50) NOT NULL,
    book_filename VARCHAR(100) NOT NULL,
    book_filesize FLOAT NOT NULL,
    book_thumbnail VARCHAR(100) NOT NULL,
    book_language VARCHAR(100) NOT NULL,
    book_category VARCHAR(100) NOT NULL,
    book_description TEXT NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    uploaded_by BIGINT NOT NULL,
    download_count INTEGER NOT NULL DEFAULT 0,
    
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE saved_books(
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    book_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)

CREATE TABLE contact_forms(
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    u_name VARCHAR(50) NOT NULL,
    u_email TEXT NOT NULL,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    form_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    form_read TIMESTAMP NULL
)