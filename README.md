# Bookvilla
Online book collection website in PHP (Academic project)

here I have used `XAMPP v.3.3.0`

You also need to configure email for sending emails to users for operations such create account, verify email, forgot password. To send email from localhost I used this article https://www.codingnepalweb.com/configure-xampp-to-send-mail-from-localhost/ , and if you're uploading larger files then change configuration for upload file max size in `php.ini` file

Functions in this project:
  - Login
  - Signup
  - Forgot password
  - Books Home Index
  - Search Books
  - Save and download Books
  - Update profile
  - Contact to admin
  - Admin Functions :
    - Dashboard for new users, verified users, uploaded books, count for users and books
    - Delete books
    - Modify users data
    - Show submitted contact forms

Create database and tables before running the website, schema for this present in `bookvilla/table_schema.sql` file
